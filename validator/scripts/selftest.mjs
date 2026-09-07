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
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

process.env.NODE_ENV = 'test';

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
// 12 iddiadan dusuyordu (bkz. ADR 0010). Taban set tek basina Alman musteriye
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

if (failures > 0) {
  console.error(`\n${failures} kontrol basarisiz.`);
  process.exit(1);
}

console.log('\nTum kontroller gecti.');
