/**
 * Dogrulama servisinin oz-testi.
 *
 * CI'da her degisiklikten sonra calisir. Amac servisi degil, KURAL SETINI ve
 * Saxon-JS entegrasyonunu dogrulamak: resmi ornek temiz gecmeli, bozuk girdi
 * hata olarak raporlanmali.
 *
 * Calistir: npm test
 */

import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

process.env.NODE_ENV = 'test';

/*
 * Freemius'un yerine gecen sahte uc.
 *
 * Lisans yetkilendirmesi gercek Freemius'a karsi ancak elde etkin bir lisans
 * varken sinanabilir; oysa yanlis giden her sey odemis bir musteriyi kilitler.
 * Bu yuzden KARAR mantigi burada, kontrollu cevaplarla olculur: iptal, sure
 * bitimi, bilinmeyen anahtar, onbellek ve Freemius erisilemezken odemesiz
 * sure. Gercek uca karsi olcum ayrica yapilir (docs/RELEASE.md).
 *
 * Sunucu, server.js iceri alinmadan ONCE ayaga kalkmali: FREEMIUS_API modul
 * yuklenirken okunuyor.
 */
const licenceHits = { count: 0, lastAuth: '', lastPath: '' };

const freemiusStub = createServer((request, response) => {
  licenceHits.count += 1;
  licenceHits.lastAuth = request.headers.authorization ?? '';
  licenceHits.lastPath = request.url.split('?')[0];

  const url = new URL(request.url, 'http://stub');
  const key = url.searchParams.get('license_key') ?? '';

  const bodies = {
    'gecerli': { id: 1, is_cancelled: false, expiration: '2099-01-01 00:00:00' },
    'suresiz': { id: 2, is_cancelled: false, expiration: null },
    'iptal': { id: 3, is_cancelled: true, expiration: '2099-01-01 00:00:00' },
    'suresi-dolmus': { id: 4, is_cancelled: false, expiration: '2020-01-01 00:00:00' },
    'bilinmeyen-bicim': { id: 5, durum: 'freemius bir gun bicimi degistirirse' },
  };

  if (!(key in bodies)) {
    response.writeHead(404, { 'content-type': 'application/json' });
    response.end(JSON.stringify({ error: { message: 'License not found' } }));

    return;
  }

  response.writeHead(200, { 'content-type': 'application/json' });
  response.end(JSON.stringify(bodies[key]));
});

await new Promise((resolve) => freemiusStub.listen(0, '127.0.0.1', resolve));

process.env.FREEMIUS_API = `http://127.0.0.1:${freemiusStub.address().port}`;
process.env.LICENSE_SECRET = 'paylasilan-sinav-sirri';
process.env.FREEMIUS_SECRET_KEY = 'sk_sinav_gizli_anahtar';

const here = dirname(fileURLToPath(import.meta.url));
const { validate } = await import('../src/server.js');

let failures = 0;

/**
 * Bir beklentiyi dogrular.
 *
 * @param {string}  name      Test adi.
 * @param {boolean} condition Kosul.
 * @param {string}  detail    Ayrinti.
 */
function check(name, condition, detail = '') {
  if (condition) {
    console.log(`  ok    ${name}`);
  } else {
    console.error(`  FAIL  ${name}${detail ? ' — ' + detail : ''}`);
    failures += 1;
  }
}

console.log('EN 16931 dogrulama oz-testi');

const example = readFileSync(join(here, '..', 'rules', 'example-cii.xml'), 'utf8');
const valid = validate(example);

check('resmi ornek gecerli', valid.valid, `${valid.errors.length} hata`);
check('resmi ornek hatasiz', valid.errors.length === 0, JSON.stringify(valid.errors.slice(0, 2)));
check('sure olculuyor', typeof valid.duration_ms === 'number');

const broken = validate('<not-xml');
check('bozuk XML gecersiz', broken.valid === false);
check('bozuk XML hata veriyor', broken.errors.length > 0);

// Zorunlu alani cikarinca kural tetiklenmeli.
const stripped = example.replace(/<ram:SellerTradeParty>[\s\S]*?<\/ram:SellerTradeParty>/, '');
const missing = validate(stripped);
check('eksik satici yakalaniyor', missing.valid === false, `${missing.errors.length} hata`);

// --- Almanya: ulusal profil (XRechnung 3.0.2) ---
//
// Bu bolumun sebebi somut: eklentinin ciktisi TABAN seti gecerken XRechnung'dan
// 6 iddiadan dusuyordu (bkz. ADR 0010). Taban set tek basina Alman musteriye
// "bu fatura kabul edilir" diyemez.
console.log('\n' + 'XRechnung 3.0.2 ulusal profil');
const alman = readFileSync(join(here, '..', 'fixtures', 'xrechnung-de.xml'), 'utf8');
const ulusal = validate(alman, 'xrechnung');

check('alman ornek ulusal profilden geciyor', ulusal.valid, JSON.stringify(ulusal.errors.slice(0, 3)));
check('profil cevapta bildiriliyor', ulusal.profile === 'xrechnung');

// Telefonu (BT-42) cikar: EN 16931'de istege bagli, XRechnung'da zorunlu.
// Iki setin AYNI belgeye farkli cevap vermesi, ulusal setin gercekten
// calistiginin kaniti. Ayni olsalardi bu testi eklemenin anlami olmazdi.
const telefonsuz = alman.replace(
  /<ram:TelephoneUniversalCommunication>[\s\S]*?<\/ram:TelephoneUniversalCommunication>/,
  '',
);

check(
  'telefonsuz belge taban setten yine geciyor',
  validate(telefonsuz, 'en16931').valid,
);

const dusen = validate(telefonsuz, 'xrechnung');

check('telefonsuz belge ulusal profilden dusuyor', dusen.valid === false);
check(
  'dusme sebebi BR-DE kurali',
  dusen.errors.some((e) => e.rule.startsWith('BR-DE')),
  JSON.stringify(dusen.errors.map((e) => e.rule)),
);

// Bilinmeyen profil sessizce tabana dusmeli; istemci bizden yeni olabilir.
check('bilinmeyen profil tabana dusuyor', validate(example, 'mars').valid);

// --- Halka acik deneme ucunun kotasi ---
//
// Kota mantiginda bir hata iki yonde de pahalidir: ya herkesi kilitler ya
// hic korumaz. Ikisi de sessizce olur.

console.log('\n' + 'Deneme ucu kotasi');

const { takeQuota } = await import('../src/server.js');
const ip = 'sinav-' + Math.random();
const izinler = [];

for (let i = 0; i < 12; i += 1) {
  izinler.push(takeQuota(ip).allowed);
}

const gecen = izinler.filter(Boolean).length;

check('varsayilan kota 10', gecen === 10, gecen + ' istek gecti');
check('kota asilinca reddediyor', izinler[10] === false && izinler[11] === false);
check('baska IP etkilenmiyor', takeQuota('sinav-baska-' + Math.random()).allowed);

// --- Lisansla yetkilendirme ---
//
// Yanlis giden her sey odemis bir musteriyi kilitler ya da odemeyeni iceri
// alir; ikisi de sessizce olur.

console.log('\n' + 'Lisans yetkilendirmesi');

const { authorise } = await import('../src/server.js');

/**
 * Sahte istek uretir.
 *
 * @param {string} token   Bearer belirteci.
 * @param {object} extra   Ek basliklar.
 * @returns {object}
 */
function istek(token, extra = {}) {
  return {
    headers: {
      authorization: `Bearer ${token}`,
      'x-deklera-install': '123456',
      'x-deklera-uid': 'a'.repeat(32),
      ...extra,
    },
  };
}

check('paylasilan sir kabul ediliyor', (await authorise(istek('paylasilan-sinav-sirri'))).ok);
check('bos belirtec reddediliyor', !(await authorise({ headers: {} })).ok);
check('gecerli lisans kabul ediliyor', (await authorise(istek('gecerli'))).ok);

/*
 * Imzasiz istek Freemius'tan ciplak 403 alir ve sebep yazmaz; hata "lisans
 * gecersiz" gibi gorunur. Bu yuzden imzanin GERCEKTEN gonderildigi ve dogru
 * yola gidildigi ayrica olculur.
 */
check(
  'istek urun kapsaminda imzalaniyor',
  licenceHits.lastAuth.startsWith('FS 38206:pk_'),
  licenceHits.lastAuth.slice(0, 30),
);
check(
  'dogru uca gidiliyor',
  licenceHits.lastPath === '/v1/plugins/38206/installs/123456/license.json',
  licenceHits.lastPath,
);
check('suresiz lisans kabul ediliyor', (await authorise(istek('suresiz'))).ok);

const iptal = await authorise(istek('iptal'));
check('iptal edilmis lisans reddediliyor', !iptal.ok && iptal.reason === 'licence_cancelled', iptal.reason);

const dolmus = await authorise(istek('suresi-dolmus'));
check('suresi dolmus lisans reddediliyor', !dolmus.ok && dolmus.reason === 'licence_expired', dolmus.reason);

/*
 * Freemius bir gun cevap bicimini degistirirse KILITLEMEYIZ. O gun gelirse
 * secenek ikidir: her odemis musteriyi durdurmak, ya da dogrulamayi
 * surdurup durumu bildirmek. Ilki, bizim tarafimizdaki bir degisiklik
 * yuzunden musterinin faturasini kesmesini engellemek olurdu.
 */
const bicimsiz = await authorise(istek('bilinmeyen-bicim'));
check('tanimadigimiz govde kilitlemiyor', bicimsiz.ok && bicimsiz.reason === 'licence_shape_unknown', bicimsiz.reason);

const bilinmeyen = await authorise(istek('boyle-bir-anahtar-yok'));
check('bilinmeyen anahtar reddediliyor', !bilinmeyen.ok, bilinmeyen.reason);

const eksik = await authorise({ headers: { authorization: 'Bearer gecerli' } });
check('kurulum kimligi olmadan reddediliyor', !eksik.ok && eksik.reason === 'missing_install', eksik.reason);

// Onbellek: ayni ucluyu ikinci kez sormak Freemius'a gitmemeli.
const oncesi = licenceHits.count;
await authorise(istek('gecerli'));
check('gecerli cevap onbellekleniyor', licenceHits.count === oncesi);

// Freemius erisilemezken, daha once GECERLI denen lisans odemesiz sure boyunca
// calismaya devam etmeli. Odemis musteriyi bizim bagimliligimiz durduramaz.
await new Promise((resolve) => freemiusStub.close(resolve));

const kapaliyken = await authorise(istek('gecerli'));
check('servis kapaliyken onbellekten geciyor', kapaliyken.ok, kapaliyken.reason);

const kapaliykenYeni = await authorise(istek('hic-sorulmamis'));
check('servis kapaliyken bilinmeyen giremiyor', !kapaliykenYeni.ok, kapaliykenYeni.reason);

if (failures > 0) {
  console.error(`\n${failures} kontrol basarisiz.`);
  process.exit(1);
}

console.log('\nTum kontroller gecti.');
