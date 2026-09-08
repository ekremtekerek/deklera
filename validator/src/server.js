/**
 * Deklera — barındırılan EN 16931 doğrulama servisi.
 *
 * Neden ayrı bir servis: EN 16931 ve KoSIT kural setleri XSLT 2.0'a derlenir.
 * PHP'nin ext-xsl uzantısı libxslt'yi sarmalar ve XSLT 1.0'da kalır, dolayısıyla
 * resmi doğrulama eklentinin İÇİNDE çalıştırılamaz. Bu kısıt aynı zamanda
 * ürünün lisans korumasıdır: null'lanmış bir kopya doğrulama yapamaz, yani işe
 * yaramaz.
 *
 * Neden Cloudflare Worker değil: bkz. docs/adr/0003-dogrulama-calisma-ortami.md
 *
 * Bağımlılığı olmayan düz node:http kullanılır; servis tek uçlu ve tek işlidir,
 * bir çatı katmanı eklemek yalnızca saldırı yüzeyi ve bakım yükü olurdu.
 */

import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { timingSafeEqual } from 'node:crypto';
import SaxonJS from 'saxon-js';

const here = dirname(fileURLToPath(import.meta.url));

const PORT = Number(process.env.PORT ?? 8790);
const SECRET = process.env.LICENSE_SECRET ?? '';
const RULES_VERSION = process.env.RULES_VERSION ?? '1.3.16';
const XRECHNUNG_VERSION = process.env.XRECHNUNG_VERSION ?? '2026-08-31';

/** İstek gövdesi üst sınırı; doğrulama CPU yakar, açık uçlu bırakılmaz. */
const MAX_BYTES = 2 * 1024 * 1024;

/*
 * Halka acik deneme ucu (/v1/try).
 *
 * NEDEN VAR
 *
 * Urunun farki bir ozellik degil, dogruluk -- ve dogruluk ancak
 * gosterilebilirse satar. Olculdu: ucretsiz bir rakibin XRechnung ciktisi
 * Almanya'nin resmi denetleyicisinden 26 iddiadan dusuyor, bizimki
 * sifirdan geciyor (bkz. docs/adr/0005 eki). Ziyaretci bunu kendi
 * belgesiyle gorebilmeli.
 *
 * NEDEN SINIRLI
 *
 * Kimlik dogrulamasi yok, yani bu uc bedava bir API'ye donusebilir ve
 * Pro'nun sattigi seyi bosa cikarir. Uc onlem var: IP basina saatlik
 * kota, daha kucuk govde siniri, ve tek belge -- toplu kullanim icin
 * elverissiz. Pro'nun satigi sey zaten bu degil: orada dogrulama HER
 * faturada, WordPress'in icinde, kesilmeden once calisir.
 */
const TRY_MAX_BYTES = 512 * 1024;
const TRY_PER_HOUR = Number(process.env.TRY_PER_HOUR ?? 10);
const TRY_WINDOW_MS = 60 * 60 * 1000;

/** IP -> { adet, sifirlama }. Surec belleginde; tek ornek calisiyoruz. */
const tryQuota = new Map();

/**
 * Kotayi tuketir ve kalan hakki dondurur.
 *
 * @param {string} ip Istemci adresi.
 * @returns {{allowed: boolean, remaining: number, resetSeconds: number}}
 */
function takeQuota(ip) {
  const now = Date.now();

  // Suresi dolmus kayitlari at; harita sinirsiz buyumemeli.
  for (const [key, value] of tryQuota) {
    if (value.reset <= now) {
      tryQuota.delete(key);
    }
  }

  const entry = tryQuota.get(ip) ?? { count: 0, reset: now + TRY_WINDOW_MS };

  if (entry.count >= TRY_PER_HOUR) {
    return {
      allowed: false,
      remaining: 0,
      resetSeconds: Math.ceil((entry.reset - now) / 1000),
    };
  }

  entry.count += 1;
  tryQuota.set(ip, entry);

  return {
    allowed: true,
    remaining: TRY_PER_HOUR - entry.count,
    resetSeconds: Math.ceil((entry.reset - now) / 1000),
  };
}

/**
 * Istemci adresini bulur.
 *
 * Render bir vekil arkasindadir; uzak adres her istekte ayni cikar.
 * X-Forwarded-For'un ILK degeri gercek istemcidir.
 *
 * @param {import('node:http').IncomingMessage} request Istek.
 * @returns {string}
 */
function clientIp(request) {
  const forwarded = String(request.headers['x-forwarded-for'] ?? '');

  return forwarded.split(',')[0].trim() || request.socket.remoteAddress || 'bilinmiyor';
}

/**
 * Derlenmiş kural seti. Süreç başına bir kez okunur; her istekte 5 MB JSON
 * ayrıştırmak saniyeler alırdı.
 */
const ruleset = JSON.parse(
  readFileSync(join(here, '..', 'rules', 'en16931-cii.sef.json'), 'utf8'),
);

/*
 * Almanya'nin ulusal profili (XRechnung 3.0.2 CIUS).
 *
 * EN 16931 bir tabandir; ulkeler ustune daraltma koyar ve tabanda ISTEGE
 * BAGLI olan alanlari ZORUNLU kilabilir. Olculdu: eklentinin ciktisi taban
 * seti gecerken XRechnung'dan 12 iddiadan dusuyordu -- bkz. ADR 0010. Yani
 * taban seti tek basina bir Alman musteriye "bu fatura kabul edilir"
 * diyemez.
 *
 * Ikisi birlikte 6,6 MB; ikisi de surec basina bir kez okunur.
 */
const xrechnungRuleset = JSON.parse(
  readFileSync(join(here, '..', 'rules', 'xrechnung-cii.sef.json'), 'utf8'),
);

/**
 * SVRL çıktısını yapılandırılmış bulgulara çevirir.
 *
 * Ham SVRL 10–35 KB XML'dir; istemciye onu göndermek hem bant genişliği hem de
 * eklentide ikinci bir XML ayrıştırıcı demek olurdu.
 *
 * @param {string} svrl SVRL belgesi.
 * @returns {{errors: object[], warnings: object[]}}
 */
export function parseSvrl(svrl) {
  const errors = [];
  const warnings = [];
  const pattern = /<svrl:failed-assert([^>]*)>[\s\S]*?<svrl:text>([\s\S]*?)<\/svrl:text>/g;

  for (const match of svrl.matchAll(pattern)) {
    const attributes = match[1];
    const text = match[2].trim().replace(/\s+/g, ' ');

    const flag = /flag="([^"]*)"/.exec(attributes)?.[1] ?? 'fatal';
    const location = /location="([^"]*)"/.exec(attributes)?.[1] ?? '';

    // Kural kimligi metnin basinda koseli parantez icinde gelir: [BR-IC-11]-...
    const rule = /^\[([A-Z0-9-]+)\]/.exec(text)?.[1] ?? '';

    const finding = {
      rule,
      flag,
      message: text.replace(/^\[[A-Z0-9-]+\]-?/, '').trim(),
      location: location.replace(/\*:/g, ''),
    };

    (flag === 'warning' ? warnings : errors).push(finding);
  }

  return { errors, warnings };
}

/**
 * Tek bir kural setini calistirir.
 *
 * @param {object} rules Derlenmis kural seti.
 * @param {string} xml   Fatura XML'i.
 * @returns {{errors: object[], warnings: object[]} | {fatal: string}}
 */
function run(rules, xml) {
  try {
    return parseSvrl(
      SaxonJS.transform(
        { stylesheetInternal: rules, sourceText: xml, destination: 'serialized' },
        'sync',
      ).principalResult,
    );
  } catch (error) {
    // Bicimsiz XML burada patlar; bu da gecerli bir dogrulama sonucudur.
    return { fatal: String(error?.message ?? error) };
  }
}

/**
 * XML'i resmi kural setine göre doğrular.
 *
 * Ulusal profil istendiginde TABAN SET DE calisir. Sebebi, ulusal setin
 * yalnizca daraltmalari tasimasi: tek basina calistirmak, tabanin yakaladigi
 * hatalari gormeden "gecti" demek olurdu.
 *
 * @param {string} xml     Fatura XML'i.
 * @param {string} profile 'en16931' (varsayilan) veya 'xrechnung'.
 * @returns {{valid: boolean, errors: object[], warnings: object[], duration_ms: number, profile: string}}
 */
export function validate(xml, profile = 'en16931') {
  const started = Date.now();
  const national = profile === 'xrechnung';
  const sonuc = run(ruleset, xml);

  if (sonuc.fatal !== undefined) {
    return {
      valid: false,
      errors: [{ rule: '', flag: 'fatal', message: sonuc.fatal, location: '' }],
      warnings: [],
      duration_ms: Date.now() - started,
      profile,
    };
  }

  let { errors, warnings } = sonuc;

  if (national) {
    const ulusal = run(xrechnungRuleset, xml);

    if (ulusal.fatal === undefined) {
      // Ayni kural iki sette de gecebilir; kimlik ve konuma gore tekillestirilir.
      const gorulen = new Set([...errors, ...warnings].map((f) => `${f.rule}|${f.location}`));

      for (const bulgu of [...ulusal.errors, ...ulusal.warnings]) {
        if (gorulen.has(`${bulgu.rule}|${bulgu.location}`)) {
          continue;
        }

        (bulgu.flag === 'warning' ? warnings : errors).push(bulgu);
      }
    }
  }

  return {
    valid: errors.length === 0,
    errors,
    warnings,
    duration_ms: Date.now() - started,
    profile,
  };
}

/**
 * Yetkilendirme başlığını sabit zamanlı karşılaştırır.
 *
 * @param {string} header Authorization başlığı.
 * @returns {boolean}
 */
function isAuthorised(header) {
  if (SECRET === '') {
    return false;
  }

  const token = header?.startsWith('Bearer ') ? header.slice(7) : '';
  const a = Buffer.from(token);
  const b = Buffer.from(SECRET);

  // timingSafeEqual esit uzunluk ister; uzunluk farki da bir sizintidir,
  // bu yuzden once sabit uzunluga getirilir.
  if (a.length !== b.length) {
    return false;
  }

  return timingSafeEqual(a, b);
}

/**
 * JSON yanıtı yazar.
 *
 * @param {import('node:http').ServerResponse} response Yanıt.
 * @param {number} status Durum kodu.
 * @param {object} body   Gövde.
 * @returns {void}
 */
function json(response, status, body) {
  const payload = JSON.stringify(body);

  response.writeHead(status, {
    'content-type': 'application/json; charset=utf-8',
    'cache-control': 'no-store',
    'content-length': Buffer.byteLength(payload),
    // Deneme sayfasi baska bir kaynaktan (GitHub Pages) cagiriyor.
    'access-control-allow-origin': '*',
  });

  response.end(payload);
}

/**
 * İstek gövdesini sınır denetimiyle okur.
 *
 * @param {import('node:http').IncomingMessage} request İstek.
 * @returns {Promise<string>}
 */
function readBody(request, limit = MAX_BYTES) {
  return new Promise((resolve, reject) => {
    let size = 0;
    const chunks = [];

    request.on('data', (chunk) => {
      size += chunk.length;

      if (size > limit) {
        reject(Object.assign(new Error('payload_too_large'), { status: 413 }));
        request.destroy();

        return;
      }

      chunks.push(chunk);
    });

    request.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    request.on('error', reject);
  });
}

/**
 * Halka acik deneme ucu.
 *
 * @param {import('node:http').IncomingMessage} request Istek.
 * @param {import('node:http').ServerResponse} response Yanit.
 * @returns {Promise<void>}
 */
async function handleTry(request, response) {
  if (request.method !== 'POST') {
    json(response, 405, { error: 'method_not_allowed' });

    return;
  }

  const quota = takeQuota(clientIp(request));

  if (!quota.allowed) {
    json(response, 429, {
      error: 'rate_limited',
      limit: TRY_PER_HOUR,
      retry_after_seconds: quota.resetSeconds,
    });

    return;
  }

  let payload;

  try {
    payload = JSON.parse(await readBody(request, TRY_MAX_BYTES));
  } catch (error) {
    json(response, error?.status ?? 400, { error: error?.message ?? 'invalid_json' });

    return;
  }

  const xml = typeof payload?.xml === 'string' ? payload.xml : '';

  if (xml === '') {
    json(response, 400, { error: 'missing_xml' });

    return;
  }

  const profile = payload?.profile === 'xrechnung' ? 'xrechnung' : 'en16931';

  json(response, 200, {
    ...validate(xml, profile),
    rules_version: RULES_VERSION,
    xrechnung_version: XRECHNUNG_VERSION,
    remaining: quota.remaining,
  });
}

const server = createServer(async (request, response) => {
  const url = new URL(request.url ?? '/', 'http://localhost');

  if (url.pathname === '/health') {
    json(response, 200, {
      ok: true,
      rules_version: RULES_VERSION,
      xrechnung_version: XRECHNUNG_VERSION,
      syntax: 'CII',
    });

    return;
  }

  // Tarayici on kontrolu.
  if (request.method === 'OPTIONS') {
    response.writeHead(204, {
      'access-control-allow-origin': '*',
      'access-control-allow-methods': 'POST, OPTIONS',
      'access-control-allow-headers': 'content-type',
      'access-control-max-age': '86400',
    });
    response.end();

    return;
  }

  if (url.pathname === '/v1/try') {
    await handleTry(request, response);

    return;
  }

  if (url.pathname !== '/v1/validate') {
    json(response, 404, { error: 'not_found' });

    return;
  }

  if (request.method !== 'POST') {
    json(response, 405, { error: 'method_not_allowed' });

    return;
  }

  if (!isAuthorised(request.headers.authorization ?? '')) {
    json(response, 401, { error: 'unauthorised' });

    return;
  }

  let payload;

  try {
    payload = JSON.parse(await readBody(request));
  } catch (error) {
    json(response, error?.status ?? 400, { error: error?.message ?? 'invalid_json' });

    return;
  }

  const xml = typeof payload?.xml === 'string' ? payload.xml : '';

  if (xml === '') {
    json(response, 400, { error: 'missing_xml' });

    return;
  }

  /*
   * Bilinmeyen bir profil sessizce tabana duser. Istemci bizden yeni olabilir;
   * bu durumda daha az kural calistirmak, hic dogrulamamaktan iyidir -- ve
   * cevaptaki profile alani hangisinin kosuldugunu soyler.
   */
  const profile = payload?.profile === 'xrechnung' ? 'xrechnung' : 'en16931';

  json(response, 200, {
    ...validate(xml, profile),
    rules_version: RULES_VERSION,
    xrechnung_version: XRECHNUNG_VERSION,
  });
});

if (process.env.NODE_ENV !== 'test') {
  server.listen(PORT, () => {
    process.stdout.write(`deklera-validator listening on ${PORT}, rules ${RULES_VERSION}\n`);
  });
}

export { server, takeQuota };
