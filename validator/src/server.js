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

/*
 * Lisans dogrulamasi.
 *
 * NEDEN PAYLASILAN ANAHTAR YETMIYOR
 *
 * Onceki tasarimda her Pro musterisi AYNI dizgeyi aliyordu. Sonuclari: bir
 * sizinti herkesi bedava yapar, tek bir musterinin erisimi iptal edilemez,
 * aboneligi biten kullanmaya devam eder, ve dizgeyi degistirmek butun
 * musterileri ayni anda kirar. Ustelik musteri IKI anahtar aliyordu -- once
 * Freemius lisansi, sonra bu -- ve ilk ekran hangisini istedigini
 * soylemiyordu; urunun sahibi bile 9 Eylul 2026'da yanlis kutuya yapistirdi.
 *
 * Artik eklenti kendi Freemius lisans anahtarini gonderiyor ve servis onu
 * Freemius'a soruyor. Ayri bir anahtar yok: iptal, iade ve abonelik bitisi
 * kendiliginden isliyor, girilecek hicbir sey kalmiyor.
 *
 * LICENSE_SECRET yine kabul ediliyor, iki sebeple: izleme is akisi onu
 * kullaniyor, ve kendi kopyasini calistiran kurulumun Freemius'a ihtiyaci
 * olmamali.
 */
const PRODUCT_ID = process.env.FREEMIUS_PRODUCT_ID ?? '38206';
const FREEMIUS_API = process.env.FREEMIUS_API ?? 'https://api.freemius.com';

/** Gecerli cevap ne kadar sure guvenilir sayilir. */
const LICENSE_TTL_MS = 60 * 60 * 1000;

/*
 * Freemius'a ulasilamazsa son bilinen GECERLI cevap bu sure boyunca
 * kullanilmaya devam eder. Odemis bir musterinin faturasini, bizim
 * bagimliligimiz cevap vermedi diye durdurmak kabul edilemez.
 */
const LICENSE_GRACE_MS = 24 * 60 * 60 * 1000;

/*
 * Olumsuz cevap da kisa sure tutulur; aksi halde rastgele anahtarla yapilan
 * her istek Freemius'a bir cagriya donusurdu.
 */
const LICENSE_FAIL_TTL_MS = 5 * 60 * 1000;

/** onbellek anahtari -> { ok, reason, checkedAt } */
const licenseCache = new Map();

/** Freemius'a giden istegin butcesi. */
const LICENSE_TIMEOUT_MS = 8000;

/** İstek gövdesi üst sınırı; doğrulama CPU yakar, açık uçlu bırakılmaz. */
const MAX_BYTES = 2 * 1024 * 1024;

/*
 * Halka acik deneme ucu (/v1/try).
 *
 * NEDEN VAR
 *
 * Urunun farki bir ozellik degil, dogruluk -- ve dogruluk ancak
 * gosterilebilirse satar. Olculdu: ucretsiz bir rakibin XRechnung ciktisi
 * Almanya'nin resmi denetleyicisinden 13 iddiadan dusuyor, bizimki
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
 * seti gecerken XRechnung'dan 6 iddiadan dusuyordu -- bkz. ADR 0010. Yani
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
 * Belirteci paylaşılan sırla sabit zamanlı karşılaştırır.
 *
 * @param {string} token Bearer belirteci.
 * @returns {boolean}
 */
function isSharedSecret(token) {
  if (SECRET === '' || token === '') {
    return false;
  }

  const a = Buffer.from(token);
  const b = Buffer.from(SECRET);

  // timingSafeEqual esit uzunluk ister; uzunluk farki da bir sizintidir,
  // bu yuzden once uzunluk elenir.
  if (a.length !== b.length) {
    return false;
  }

  return timingSafeEqual(a, b);
}

/**
 * Freemius lisansını sorar ve cevabı önbelleğe alır.
 *
 * Uc: GET /v1/products/{urun}/installs/{kurulum}/license.json
 *       ?uid={site}&license_key={anahtar}
 *
 * Kimlik bilgisi istemez; ucun kendisi zaten yalnizca dogru uclu bilen
 * cagiriciya cevap verir. Yani servise bir Freemius sirri konmasi gerekmez --
 * konsaydi sizmasi paylasilan anahtardan daha kotu olurdu.
 *
 * @param {string} key     Lisans anahtari.
 * @param {string} install Freemius kurulum kimligi.
 * @param {string} uid     Sitenin anonim kimligi.
 * @returns {Promise<{ok: boolean, reason: string}>}
 */
async function checkLicense(key, install, uid) {
  const cacheKey = `${install}|${uid}|${key}`;
  const cached = licenseCache.get(cacheKey);
  const now = Date.now();

  if (cached) {
    const ttl = cached.ok ? LICENSE_TTL_MS : LICENSE_FAIL_TTL_MS;

    if (now - cached.checkedAt < ttl) {
      return { ok: cached.ok, reason: cached.reason };
    }
  }

  const url = `${FREEMIUS_API}/v1/products/${encodeURIComponent(PRODUCT_ID)}`
    + `/installs/${encodeURIComponent(install)}/license.json`
    + `?uid=${encodeURIComponent(uid)}&license_key=${encodeURIComponent(key)}`;

  let payload;

  try {
    const answer = await fetch(url, {
      headers: { accept: 'application/json' },
      signal: AbortSignal.timeout(LICENSE_TIMEOUT_MS),
    });

    payload = await answer.json();

    if (!answer.ok) {
      // Freemius hatayi govdede anlatir; 404 "boyle bir lisans yok" demektir.
      const verdict = { ok: false, reason: payload?.error?.message ?? `http_${answer.status}` };

      licenseCache.set(cacheKey, { ...verdict, checkedAt: now });

      return verdict;
    }
  } catch (error) {
    /*
     * Freemius'a ulasilamadi. Daha once GECERLI dedigi bir lisans varsa
     * odemesiz sure boyunca onu surdururuz; yoksa reddederiz ve bunu kisa
     * sure onbellege almayiz, cunku sebep musteri degil bizim tarafimiz.
     */
    if (cached?.ok && now - cached.checkedAt < LICENSE_GRACE_MS) {
      return { ok: true, reason: 'grace' };
    }

    return { ok: false, reason: 'licence_service_unreachable' };
  }

  const cancelled = true === payload?.is_cancelled;
  const expiry = payload?.expiration ? Date.parse(`${payload.expiration}Z`.replace(' ', 'T')) : null;
  const expired = null !== expiry && Number.isFinite(expiry) && expiry < now;

  const verdict = cancelled
    ? { ok: false, reason: 'licence_cancelled' }
    : expired
      ? { ok: false, reason: 'licence_expired' }
      : { ok: true, reason: 'licence_valid' };

  licenseCache.set(cacheKey, { ...verdict, checkedAt: now });

  return verdict;
}

/**
 * İsteği yetkilendirir.
 *
 * İki yol var ve sırası önemli: paylaşılan sır önce denenir çünkü yerelde
 * çözülür ve ağ istemez. Eşleşmezse belirteç bir Freemius lisans anahtarı
 * sayılır.
 *
 * @param {import('node:http').IncomingMessage} request İstek.
 * @returns {Promise<{ok: boolean, reason: string}>}
 */
async function authorise(request) {
  const header = request.headers.authorization ?? '';
  const token = header.startsWith('Bearer ') ? header.slice(7).trim() : '';

  if (token === '') {
    return { ok: false, reason: 'missing_token' };
  }

  if (isSharedSecret(token)) {
    return { ok: true, reason: 'shared_secret' };
  }

  const install = String(request.headers['x-deklera-install'] ?? '').trim();
  const uid = String(request.headers['x-deklera-uid'] ?? '').trim();

  if (!/^[0-9]{1,20}$/.test(install) || !/^[a-zA-Z0-9]{8,64}$/.test(uid)) {
    return { ok: false, reason: 'missing_install' };
  }

  return checkLicense(token, install, uid);
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

  const auth = await authorise(request);

  if (!auth.ok) {
    /*
     * Sebep govdeye yaziliyor: "unauthorised" tek basina yonetici ekraninda
     * hicbir sey anlatmiyordu. "licence_expired" ile "missing_install"
     * arasindaki fark, musterinin ne yapacagini belirler.
     */
    json(response, 401, { error: 'unauthorised', reason: auth.reason });

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

export { server, takeQuota, authorise };
