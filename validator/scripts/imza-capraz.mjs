/**
 * İmzayı PHP'nin kendi uygulamasına karşı çapraz doğrular.
 *
 * NEDEN AYRI BİR BETİK
 *
 * İmza yanlışsa Freemius 403 döner ve sebep gövdede yazmaz; hata "lisans
 * geçersiz" gibi görünür ve müşteride aranır. Algoritmayı elle çevirirken iki
 * ayrıntı kolayca kaçıyor: PHP'nin hash_hmac'i onaltılık DİZGE döndürür
 * (base64 ona uygulanır, ham bayta değil), ve imzalanan yol sorgusuz kısımdır.
 *
 * Bu yüzden aynı girdiyle PHP'de ve Node'da imza üretilip karşılaştırılır.
 * PHP, composer konteynerinde çalışır:
 *
 *   docker compose run --rm -T composer node ... yok; bkz. RELEASE.md
 *
 * Çalıştır: node scripts/imza-capraz.mjs "<php-ciktisi>"
 * PHP çıktısı `scripts/imza-php.php` ile üretilir.
 */

import { createHmac } from 'node:crypto';

const [, , beklenen, tarih] = process.argv;

const SECRET = 'sk_sinav_gizli_anahtar';
const PUBLIC = 'pk_sinav_acik_anahtar';
const ID = '38206';
const PATH = '/v1/plugins/38206/installs/123456/license.json';

const toSign = ['GET', '', '', tarih, PATH].join('\n');
const hex = createHmac('sha256', SECRET).update(toSign).digest('hex');

const signature = Buffer.from(hex)
  .toString('base64')
  .replace(/\+/g, '-')
  .replace(/\//g, '_')
  .replace(/=/g, '');

const uretilen = `FS ${ID}:${PUBLIC}:${signature}`;

console.log('node : ' + uretilen);
console.log('php  : ' + beklenen);

if (uretilen === beklenen) {
  console.log('\nEsit.');
} else {
  console.error('\nFARKLI — imza uygulamasi yanlis.');
  process.exit(1);
}
