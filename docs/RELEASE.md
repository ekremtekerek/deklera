# Sürüm çıkarma

Bir sürümü elle toplamak hata üretir; sıra aşağıdaki gibidir ve her adımın bir
doğrulaması vardır.

---

## 1. Sürüm numarası

Üç yerde aynı olmalı:

| Yer | Alan |
|---|---|
| `plugin/deklera/deklera.php` | `Version:` başlığı |
| `plugin/deklera/deklera.php` | `const VERSION` |
| `plugin/deklera/readme.txt` | `Stable tag:` |

`readme.txt` içindeki `== Changelog ==` bölümüne de bu sürüm girilir.
`build.sh` sürümü `Version:` başlığından okur; diğer ikisi tutmazsa hiçbir şey
patlamaz ama WordPress ve Freemius farklı sürüm görür.

---

## 2. Paketi üret

**İki ayrı paket üretilir ve karıştırılmamalıdır.**

```sh
bash bin/build.sh             # ücretsiz  -> WordPress.org
bash bin/build.sh --premium   # ücretli   -> Freemius
```

Üretilenler: `build/deklera-<sürüm>.zip` ve `build/deklera-<sürüm>-premium.zip`,
her ikisinin kökünde `deklera/` dizini ile.

### Neden iki paket

Tek fark Freemius SDK'sının `is_premium` bayrağıdır.

**Bu bayrak bir özellik kapısı DEĞİLDİR.** Önce öyle sanıldı, ölçümle
düzeltildi. SDK'nın tanımı şu:

```php
function can_use_premium_code() {
    return $this->is_trial() || $this->has_features_enabled_license();
}
```

`is_premium`'a hiç bakmıyor. Yerel kurulumda ölçüldü: `is_premium => false`
olan yapıda, lisans varken `can_use_premium_code()` **true** dönüyor ve
`Licensing::plan()` **pro** veriyor.

Bayrağın işi, çalışan yapının hangisi olduğunu işaretlemektir. SDK güncelleme
isteğinde `is_premium() || _can_download_premium()` diye bakar ve müşterinin
indirdiği ürün premium pakettir. Freemius'a yüklenen zip bu yüzden premium
olmalıdır; aksi hâlde sürümler ücretsiz yapı olarak dağıtılır.

**Asıl risk başkaydı ve gerçekti:** satış bir kez açıldığında Deployment
tamamen boştu ("No deployments yet"). Ödeme yapan kişinin indirebileceği
hiçbir paket yoktu. Satışı kapatmayı gerektiren buydu.

Bayrak yalnızca hazırlık dizinindeki kopyada açılır; depodaki kaynak ücretsiz
sürümdür ve öyle kalır. `sed` tutmazsa betik hata verip durur — sessizce
ücretsiz paket üretip premium diye yüklemez.

`build.sh` artık `build/` dizininin tamamını değil yalnızca hazırlık dizinini
siler; aksi hâlde ikinci varyant üretilirken birincinin zip'i uçardı.

Betik sırayla: kaynağı kopyalar (testler ve geliştirme dosyaları hariç),
bağımlılıkları kurar, Strauss ile `Deklera\Vendor\` altına önekler,
`post-strauss.php` ile meta verileri ve varlıkları düzeltir, geliştirme
paketlerini siler, arşivler.

Strauss dört bin dosya işler; adım dakikalar sürer, takılmış değildir.

---

## 3. Paketi denetle

Bunlar CI'nin göremediği, yalnızca üretilmiş pakette görülebilen şeylerdir.

### Önce: paket eksiksiz mi

**Bu denetim atlanamaz.** Windows'ta bind mount, PHP'nin dizin yineleyicisinde
büyük dizinleri eksik döndürüyor; Strauss sınıf dosyalarını sessizce atlayabilir
— hata vermeden, `exit 0` dönerek. `Deklera\Vendor\*` sınıflarının psr-4
karşılığı olmadığı, yalnızca classmap'ten çözüldükleri için eksik bir dosya
doğrudan çalışma anında ölümcül hatadır.
Ayrıntı: [ADR 0007](adr/0007-bind-mount-dosya-kaybi.md).

Denetimin kendisi bind mount üzerinde çalıştırılırsa aynı tuzağa düşer (ilk
denemede sınıfların 909'unu saydı, gerçek sayı 959'du). Bu yüzden paket önce
konteyner içi diske kopyalanır:

```sh
rm -rf build/verify && mkdir -p build/verify
( cd build/verify && unzip -q ../deklera-*.zip )

docker compose run --rm -T composer sh -c "
  rm -rf /tmp/pkg && mkdir -p /tmp/pkg
  cp -r /repo/build/verify/deklera /tmp/pkg/deklera
  php /repo/bin/verify-classmap.php /tmp/pkg/deklera
"
# "EKSIK: 0" beklenir. Aksi halde surum cikarilmaz.
```

```sh
# Bagimlilik izolasyonu: oneksiz sinif sizmamali
unzip -l build/deklera-*.zip | grep -E 'vendor/(horstoeko|jms|smalot|setasign)' 
# ciktisi bos olmali

# Composer'in kendisi veya test artefakti girmemis olmali
unzip -l build/deklera-*.zip | grep -E 'vendor/composer/composer|phpunit'
# ciktisi bos olmali

# Freemius SDK oneklenmemis olmali - onek lisanslamayi bozar
unzip -l build/deklera-*.zip | grep 'vendor/freemius'
# dolu olmali
```

Yukarıdakiler dosyaların varlığına bakar. Asıl soru ise paketin **kendi**
otomatik yükleyicisinin ne çözdüğüdür — dosya doğru yerde durup yükleyici onu
görmüyor olabilir. CI bunu kaynak ağacında sınar; dağıtılan pakette ayrıca
sınanmalıdır:

```sh
MSYS_NO_PATHCONV=1 docker compose run --rm -T -w /repo/build/deklera composer php -r '
require "vendor/autoload.php";
$bare = "horstoeko" . chr(92) . "zugferd" . chr(92) . "ZugferdDocumentBuilder";
$prefixed = "Deklera" . chr(92) . "Vendor" . chr(92) . $bare;
if ( ! class_exists( $prefixed ) ) { echo "HATA: onekli sinif yok\n"; exit(1); }
if ( class_exists( $bare ) ) { echo "HATA: oneksiz sinif sizmis\n"; exit(1); }
echo "izolasyon tamam\n";
'
```

Ters eğik çizgi `chr(92)` ile kuruluyor: kabuk ve PHP arasında geçen bir
dizgede kaçış karakteri sessizce yenir ve sınıf adı yanlış çözülür.

### Plugin Check

WordPress.org'un asıl kapısı budur. Yerel WordPress'te:

```sh
docker compose run --rm wpcli wp plugin install plugin-check --activate
docker compose run --rm wpcli wp plugin check deklera \
  --format=csv --fields=file,line,type,code --exclude-directories=tests \
  | grep ',ERROR,'
```

Çıktı boş olmalı. Kalması kabul edilen uyarılar:

- `load_plugin_textdomainFound` — çağrı kodda duruyor ama **yalnızca premium
  yapıda çalışıyor**; ücretsiz sürümde `is_premium()` kapısından geçemiyor.
  Plugin Check statik bakar, koşulu göremez. 0.1.0 incelemesi bu çağrıya itiraz
  etti; verilen cevap ve gerekçe `docs/wporg-cevap-0.3.0.md` içinde
  (bkz. `docs/I18N.md` bölüm 4).
- `PrefixAllGlobals.InvalidPrefixPassed` (`freemius.php`) — SDK köprü
  fonksiyonu; adı Freemius tarafından belirlenir.
- `DirectDB.UnescapedDBParameter` — tablo adları `$wpdb->prefix` ile kurulur,
  kullanıcı girdisi değildir; değerler `prepare()` ile bağlanır.

`Tested up to:` değeri güncel WordPress sürümünün gerisinde kalırsa Plugin
Check bunu **hata** sayar. Güncel sürüm:
`curl -s https://api.wordpress.org/core/version-check/1.7/`

**Plugin Check geliştirme ağacına bakar, pakete değil.** Bu fark bir hataya yol
açabiliyor: kökteki `phpunit.xml.dist` için `application_detected` hatası verir,
oysa o dosya `build.sh` tarafından paketten dışlanır. Karar vermeden önce
pakete bakın:

```sh
unzip -l build/deklera-*.zip | grep -E 'deklera/phpunit|deklera/tests/'
# ciktisi bos olmali
```

Önekli paketlerin içindeki `phpunit.xml.dist` dosyaları (üç tane) pakette
kalır ve sorun değildir; 0.1.0 bunlarla birlikte WordPress.org taramasını
geçti.

---

## 4. Freemius'a yükle

Dashboard → Deklera → Deployment → Add New Version → **`-premium` zip'ini**
yükle. Ücretsiz paketi buraya yüklemeyin; lisans alan müşteride Pro açılmaz.

Yükledikten sonra sürümün **Release Status'ünü `Released` yapın**. Freemius
uyarıyor: *"The paid version will not be available for download or update for
your customers until you change the release status to Released."*

**"Release plans to users" iki şart sağlanmadan açılmaz:**

1. Deployment'ta yayınlanmış bir **premium** sürüm bulunmalı
2. Doğrulama servisi ayakta olmalı

Pro planın satılan **tek** özelliği resmî doğrulamadır (bkz.
[ADR 0004](adr/0004-ucretsiz-pro-ayrimi.md)); servis çalışmıyorsa satın alan
kişi ödediği şeyin tamamını alamaz.

**Üzerine yazma tuzağı:** var olan bir sürümün üstüne yükleme yapıldığında
Freemius Release Status'ü **`Unreleased`'e geri döndürüyor**. Fark edilmezse
satış açık kalır ama paket teslim edilmez. Her üzerine yazmadan sonra durumu
yeniden `Released` yapın.

Servis 1 Eylül 2026'da yayına alındı: `konform-validator.onrender.com`.

---

## 5. WordPress.org'a gönder

Yalnızca ücretsiz sürüm gönderilir; Pro Freemius üzerinden dağıtılır.

### İlk gönderimden önce: ad kontrolü

**Bu adım atlanırsa gönderim koddan bağımsız bir sebeple geri döner.** 0.1.0'da
tam olarak bu oldu: paket temizdi, ad değildi (bkz. `docs/adr/0009`).

İnceleme adı üç ölçütle bakıyor; üçü de gönderimden önce beş dakikada
denetlenebilir:

```sh
# 1. WordPress.org'da benzer ad var mi
curl -s "https://api.wordpress.org/plugins/info/1.2/?action=query_plugins\
&request[search]=<ad>&request[per_page]=5" | grep -o '"slug":"[^"]*"'

# 2. Web'de ayni adi tasiyan urun/sirket var mi  -> arama motorunda "<ad>"
# 3. Adin icinde baskasinin markasi geciyor mu   -> WooCommerce, WordPress,
#    WP, Woo ... yalnizca "... for WooCommerce" kalibiyla, basta asla
```

Boş sonuç yeterli değil: adın **alan adı** da bakılmalı. Elenen adayların çoğu
WordPress.org'da boştu ama `.com` adresinde faal bir ürün vardı.

### Gönderim

1. https://wordpress.org/plugins/developers/add/
2. Aynı zip yüklenir.
3. İnceleme sırası birkaç gün ile birkaç hafta arasındadır.

### Slug — geri dönüşü yok, önce bunu okuyun

WordPress.org slug'ı **ana eklenti dosyasındaki `Plugin Name` başlığından**
türetir ve **onaydan sonra değiştirilemez**. Metin alanı da slug ile birebir
aynı olmak zorundadır.

Bu yüzden gönderimde ad kasıtlı olarak kısadır: `Plugin Name: Deklera`.
Uzun adla gönderilseydi slug `deklera-eu-e-invoicing-for-woocommerce` olurdu,
metin alanımız `deklera` olduğu için translate.wordpress.org'dan gelen
çeviriler hiçbir zaman yüklenmezdi — `docs/I18N.md`'nin tamamı boşa giderdi.

Slug gönderimden sonra **bir kez** düzeltilebilir; sonrası için ekiple
yazışmak gerekir.

Onay geldikten sonra görünen ad slug'a dokunmadan uzatılabilir. İki dosyada
birden değiştirin, yoksa eklenti ekranıyla dizin farklı ad gösterir:

- `plugin/deklera/deklera.php` → `Plugin Name:`
- `plugin/deklera/readme.txt` → `=== ... ===` başlığı

Önerilen uzun ad: `Deklera – EU E-Invoicing for WooCommerce`.

`Contributors` alanı da gerçek bir WordPress.org kullanıcı adı olmalıdır
(`ekremtekerek`); uydurma bir değer yazar bağlantısını boşa düşürür.

İnceleme ekibinin sorduğu iki şey bu eklentide zaten karşılanmış durumda:
`readme.txt` içindeki **External services** bölümü doğrulama servisini,
gönderilen veriyi ve varsayılan olarak kapalı olduğunu açıkça anlatır;
gizlilik ve kullanım şartları depoda kaynağa karşı denetlenebilir hâldedir.

Kabul edildikten sonra `Tested up to` her WordPress sürümünde güncellenmeli;
aksi halde eklenti dizinde "güncel değil" uyarısıyla gösterilir.

---

## Gönderim kaydı

**0.1.0 — 1 Eylül 2026'da gönderildi, 5 Eylül'de askıya alındı.**

Gönderim **`Konform`** adıyla yapıldı; aşağıdaki kayıtta geçen eski ad budur ve
tarihsel olduğu için değiştirilmedi. Yazışmanın konu satırı hâlâ eski adı
taşıyor:

- Atanan slug: **`konform`** (metin alanıyla eşleşiyordu, hedef buydu)
- Otomatik tarama: **Pass**
- Tek uyarı: `missing_composer_json_file` — paket `vendor/` taşıyıp
  `composer.json` taşımıyordu. Gönderilen sürüm için düzeltilmedi (sayfa
  "uyarıyı dolanmayın, inceleyen elle doğrulayacak" diyor ve yeniden gönderim
  önerilmiyor); `build.sh` bundan sonraki paketlerde `composer.json`'ı
  bırakıyor.
- İnceleme yazışması: ekremtekerek@gmail.com, konu
  *"[WordPress Plugin Directory] Review in Progress: Konform"* —
  **cevaplar bu konuya, aynı iş parçacığına yazılır; yeni e-posta açılmaz.**

**İnceleme sonucu (5 Eylül 2026): askıya alındı, üç madde.**

| Madde | Durum |
|---|---|
| Ad marka çatışması — "Konform" başka bir kuruluşun tescilli markası | Ad `Deklera` oldu, bkz. `docs/adr/0009-yeniden-adlandirma.md` |
| `setasign/fpdf` 1.8.2 güncel değil | 1.9.0'a yükseltildi |
| `load_plugin_textdomain()` .org eklentilerinde gereksiz | Yalnızca Pro yapısında çağrılıyor |

Cevapta **slug açıkça istenmelidir**: yeni slug `deklera`. Görünen adı
değiştirmek tek başına yetmez, e-posta bunu ayrıca söylüyor.

**0.3.0 — yeniden gönderim.** Gönderim ekranındaki "Upload updated plugin for
review" ile yüklenir. **Yeni bir gönderim AÇILMAZ.**

---

## Temiz kurulum sınavı

**Bunu atlamayın.** Geliştirme kurulumu `plugin/deklera` dizinini doğrudan
bağlar; müşterinin indirdiği paket başka bir şeydir. Bu adım eklenmeden önce
üretilen paket hiç kurulmamıştı ve ilk denemede gerçek bir hata çıktı:
kaldırma temizliği hiç çalışmıyordu.

Ortam hazır: `docker-compose.clean.yml`. Eklenti dizinini BAĞLAMAZ, yalnızca
`build/` dizinini salt okunur bağlar; paket `wp plugin install` ile kurulur.

```sh
C="docker compose -f docker-compose.clean.yml -p deklera-clean"
$C down -v && $C up -d
$C run --rm -T --user root wpcli wp core install --url=http://localhost:8090 \
  --title=Sinav --admin_user=admin --admin_password=admin \
  --admin_email=test@example.test --skip-email --allow-root --path=/var/www/html
$C run --rm -T --user root wpcli wp plugin install woocommerce --activate \
  --allow-root --path=/var/www/html
$C run --rm -T --user root wpcli wp plugin install /build/deklera-*.zip \
  --activate --allow-root --path=/var/www/html
```

Antivirüsün TLS'i kestiği makinede WooCommerce indirilemez; `wpcli`
konteynerinde önce `bin/trust-local-ca.sh` çalıştırılmalıdır (`/build` altındaki
`local-ca.pem` oradan görünür).

Mağaza adresi boş bırakılırsa üretim **doğru şekilde** engellenir
("Your store postal address is incomplete"); sınav için
`woocommerce_store_address`, `_city`, `_postcode` ve `woocommerce_default_country`
ayarlanmalıdır.

Sonra:

1. `wp plugin install <zip> --activate` — hatasız kurulmalı
2. Ürün + sipariş oluşturup `Generator::generate()` çalıştırın — belge
   üretilmeli, arşiv dosyası diskte olmalı, `is_intact()` doğrulanmalı
3. `wp option get uninstall_plugins` — eklenti burada görünmeli. Boş `[]`
   dönüyorsa kaldırma temizliği hiç çalışmayacak demektir.
4. `deklera_delete_data_on_uninstall` seçeneğini açıp
   `wp plugin uninstall deklera --deactivate` çalıştırın, sonra ölçün:

| Ne | Beklenen |
|---|---|
| Ayar seçenekleri | silinmiş |
| `deklera_archive_key` | **duruyor** |
| Arşiv dizini ve dosyaları | **duruyor** |
| `deklera_documents`, `deklera_audit` | **duruyor** |

Ayrımın anlamı: kullanıcı **ayarlarını** silmek istedi, **faturalarını**
değil. Arşiv dosyaları kaldığına göre bütünlüklerini doğrulayan anahtar da
kalmalıdır.

---

## Pro müşterisine doğrulama anahtarı nasıl ulaşır

**Bu adım yapılmadan Pro satılamaz.** Eklenti ekranı "anahtar satın alma
e-postanızda" diyor; o e-posta anahtarı taşımıyorsa müşteri ilk dakikada
tıkanır ve destek yazar.

> **Kapı önce lisans anahtarını istiyor.** Premium paket temiz kurulumda
> eklentinin bütün ekranını Freemius'un "Welcome to Deklera! To get started,
> please enter your license key" kapısıyla değiştiriyor; doğrulama anahtarı
> alanı o kapının arkasında kalıyor. Yani müşteri **iki anahtar** alıyor ve
> ilk gördüğü ekran hangisini istediğini söylemiyor. E-posta metninde sıranın
> açıkça yazılması gerekir: önce lisans anahtarı, eklenti açıldıktan sonra
> doğrulama anahtarı. 9 Eylül 2026'da ürünün sahibi bu tuzağa düştü — Render'daki
> doğrulama anahtarını lisans kutusuna yapıştırdı ve Freemius doğru şekilde reddetti.

Freemius → **Emails → Customization → Specific Email Customization**:

| Alan | Değer |
|---|---|
| Email to customize | **New subscription email** (planlar yıllık abonelik; "Lifetime" değil) |
| Custom section title | Two keys: use them in this order |
| Custom section content | Aşağıdaki **Metin** bölümündeki taslak |

Metnin içinde anahtarın yazılacağı yer açıkça işaretli:

```
Validation key:  [[REPLACE-THIS-WITH-THE-VALIDATION-KEY]]
```

Anahtar, doğrulama servisinin `LICENSE_SECRET` ortam değişkenidir (Render →
konform-validator → Environment). Yer tutucuyu gerçek
değerle **değiştirmeden kaydetmeyin** — kaydedilirse her müşteriye o dizge
gider ve boş bırakmaktan kötü olur.

Metin, lisans anahtarıyla doğrulama anahtarının **ayrı şeyler** olduğunu ayrıca
söylüyor; ikisini karıştırmak en sık yapılan kurulum hatası.

### Metin

Freemius'un "Custom section content" alanına düz metin olarak girilir; alan
HTML kabul ediyorsa satır sonları `<br>` ile korunmalıdır. Anahtarın yeri
işaretli — **yer tutucu değiştirilmeden kaydedilmemelidir.**

```text
Thank you for buying Deklera Pro.

There are two keys, and they do different things. Use them in this order.


1. LICENCE KEY - activates the plugin

Your licence key is in this email. Install Deklera, then open

    WooCommerce -> Deklera

The first screen asks for a licence key. Paste it there and click
"Activate License". The plugin opens once that is done.


2. VALIDATION KEY - turns on official validation

This is a different string. It is not the licence key, and the
activation screen above will refuse it.

    Validation key:  [[REPLACE-THIS-WITH-THE-VALIDATION-KEY]]

Paste it in

    WooCommerce -> Deklera -> Official validation (Pro) -> Validation key

then click Save. The service address next to it is already filled in;
leave it as it is unless you run your own copy of the validator.


WHAT CHANGES AFTERWARDS

Every invoice is checked against the official EN 16931 rule set before
it is issued - and in German stores, against the XRechnung rules as
well. A document that would be rejected is not issued at all, so nothing
invalid leaves your shop - and the order screen names the rule that stopped
it, so you know what to change.

The first check after a quiet period can take up to a minute while the
service wakes up. After that a check takes about a second.

Please keep the validation key to yourself. It comes with your
subscription.

Setup guide:
https://github.com/ekremtekerek/deklera/blob/main/docs/GUIDE.md

If anything is unclear, reply to this email.
```

Metnin biçimi kasıtlı: numaralandırma iki anahtarı sıraya koyar, ikinci
başlığın hemen altındaki cümle ("It is not the licence key, and the activation
screen above will refuse it") tam olarak 9 Eylül'de yaşanan hatayı önler.
Menü yolları eklentideki etiketlerle birebir aynıdır; değiştirilirlerse bu
metin de değişmelidir.

**Son cümle bir sürüm şartı taşıyor.** Metin, kuralın sipariş ekranında
göründüğünü söylüyor. 0.3.7'de görünmüyordu: `render_audit()` yalnızca tarihi
ve olay etiketini basıyordu ("Invalid"), kuralın adını değil — detay
veritabanında duruyor ama hiçbir yerde gösterilmiyordu. 9 Eylül 2026'da
düzeltildi; ön uçuş temizken belge yoksa sebep kutunun başında da gösteriliyor.
**E-posta metni bu düzeltmeyi taşıyan sürüm yayımlanmadan kaydedilmemelidir.**

### Yerine geçen tasarım — 9 Eylül 2026

Yukarıdaki iki anahtarlı akış **kaldırılıyor.** Eklenti artık zaten taşıdığı
Freemius lisans anahtarını `Authorization: Bearer` olarak gönderiyor; servis
onu Freemius'a soruyor ve cevabı bir saat önbellekte tutuyor. Girilecek ikinci
bir anahtar yok, iptal ve abonelik bitişi kendiliğinden işliyor.

| | Eski | Yeni |
|---|---|---|
| Müşterinin gireceği anahtar | 2 | **1** (aktivasyonda zaten giriyor) |
| İptal / iade | elle, pratikte imkânsız | kendiliğinden |
| Abonelik bitişi | çalışmaya devam eder | kesilir |
| Sızıntı | herkes etkilenir | tek lisans |

Servis `LICENSE_SECRET`'i kabul etmeye devam ediyor: izleme iş akışı onu
kullanıyor ve kendi kopyasını çalıştıran kurulumun Freemius'a bağlı olmaması
gerekir. Eklentideki anahtar alanı da duruyor ve doluysa o kazanır.

Karar mantığı sahte uca karşı ölçüldü (`validator/scripts/selftest.mjs`):
geçerli, süresiz, iptal edilmiş, süresi dolmuş, bilinmeyen anahtar, kurulum
kimliği eksik, önbellek, ve Freemius erişilemezken ödemesiz süre — on bir
kontrol.

> **Uç kimlik doğrulaması istiyor — ölçüldü, 9 Eylül 2026.**
> `/v1/products/38206/installs/1/license.json` çağrısı, parametreleri doğru
> olsun ya da olmasın, çıplak bir nginx **403** döndürüyor; aynı anda
> `/v1/ping.json` **200** veriyor, yani engel bizim tarafımızda değil.
> `/v1/plugins/...` biçimi de aynı sonucu veriyor. Dolayısıyla "kimlik
> bilgisi gerekmiyor" varsayımı **yanlış**: bu uç yalnızca imzalı isteğe
> cevap veriyor.
>
> Freemius API'si Bearer değil **HMAC** kullanıyor
> (`Authorization: FS {kimlik}:{acik_anahtar}:base64(hmac-sha256(...))`,
> bkz. SDK `FreemiusWordPress.php`). Yani servisin ya ürün/geliştirici
> kapsamında kendi kimlik bilgisini taşıması ya da başka bir yol seçilmesi
> gerekiyor.
>
> **Seçilen yol: ürün kapsamlı HMAC.** Servis kendi Freemius gizli anahtarını
> taşıyor ve lisansı imzalı olarak soruyor. Öteki seçenek — Freemius
> webhook'larıyla yerel bir liste tutmak — Render ücretsiz katmanında
> **kalıcı disk olmadığı** için elendi; yeniden başlatma listeyi silerdi.

#### İmza

Algoritma SDK'nın kendi uygulamasından alındı:

```
imzalanacak = METOT \n content-md5 \n content-type \n tarih \n yol
Authorization: FS {urun}:{acik_anahtar}:base64url(hmac_sha256_hex)
```

İki ayrıntı sessizce imzayı bozar ve ikisi de kolayca kaçıyor:

- PHP'nin `hash_hmac`'i varsayılan olarak **onaltılık dizge** döndürür;
  base64 ham bayta değil o dizgeye uygulanır.
- İmzalanan yol **sorgusuz** kısımdır; `?uid=…` imzaya girmez.

Bu yüzden Node uygulaması PHP'ninkine karşı çapraz doğrulandı: aynı girdiyle
üretilen iki imza birebir aynı çıktı (`validator/scripts/imza-capraz.mjs` ve
`imza-php.php`). Yanlış imzanın bedeli, Freemius'un sebepsiz 403'ü ve hatanın
müşteride aranmasıdır.

#### Kurulum

`FREEMIUS_SECRET_KEY` — Freemius panosu → ürün → **Keys** → `sk_…` —
Render'da servisin ortamına konur. Depoya girmez. Boşsa lisans sorgusu
yapılmaz ve sebep `licence_check_not_configured` olarak bildirilir; sessizce
"geçersiz lisans" denmez.

#### Canlı ölçümün durumu

9 Eylül 2026'da üretimde ölçüldü: `FREEMIUS_SECRET_KEY` konduktan sonra
Freemius **imzayı kabul etti** ve uydurma bir kurulum için kendi cümlesiyle
cevap verdi:

```
HTTP 401  {"error":"unauthorised",
           "reason":"Plugin [38206] not authorized to access Install [123456]."}
```

Yani imza, kapsam ve uç doğru. Ölçülmemiş olan tek şey **başarı yolunun**
canlı gövdesi; onun için elde etkin bir lisans gerekiyor
(`build/sinav-lisans.php`).

Alan adları tahmin değil: SDK'nın kendi lisans varlığından alındı
(`class-fs-plugin-license.php`) — `is_features_enabled = !is_cancelled`,
`is_lifetime = expiration null`, `is_expired = !lifetime && expiration <
şimdi`. Servis birebir bunu uyguluyor.

> **Bu ölçüm artık sürüm engeli değil.** Sebebi tasarımda: tanımadığımız bir
> gövde geldiğinde servis **kilitlemiyor**, doğrulamayı sürdürüp durumu
> `licence_shape_unknown` olarak bildiriyor. İptal ve süre bitimi anlaşılan
> cevaplardır ve reddedilir; yumuşayan tek hâl "hiç anlamadık"tır. Böylece
> Freemius'un bir gün biçim değiştirmesi, ödemiş müşterilerin faturasını
> durdurmaz. Yine de ilk gerçek Pro satışından önce canlı ölçüm yapılmalıdır.

### Eski tasarımın bilinen zayıflığı

Servis **tek paylaşılan anahtarla** çalışıyordu: her Pro müşterisi aynı dizgeyi
alıyordu. Sonuçları:

- Anahtar bir kez sızarsa herkes servisi bedava kullanır.
- Tek bir müşterinin erişimi iptal edilemez; anahtar değişirse **hepsi** kırılır.
- Abonelik biten müşteri, anahtarı sakladığı sürece kullanmaya devam eder.

Müşteri sayısı bir avuçken kabul edilebilir, ölçeklenince değil. Doğrusu,
doğrulayıcının Bearer olarak **Freemius lisans anahtarını** alıp geçerliliğini
Freemius'a sorması olurdu: ayrı bir anahtar, ayrı bir e-posta adımı ve bu
bölümün tamamı ortadan kalkar. Yapılmadı, çünkü servis kodu ve dağıtımı
değişiyor; ilk satışları geciktirmemek için sonraya bırakıldı.

---

## Lisans zinciri sınavı

**7 Eylül 2026'da geçildi.** Temiz WordPress, paketten kurulan premium sürüm:

```
is_registered EVET · is_paying EVET · can_use_premium_code TRUE
lisans 2036691 · plan pro · has_hosted_validation ACIK
```

Bu sınavın asıl sorusu şuydu: **kodda slug `deklera`, Freemius panosunda
`konform` iken lisans açılır mı?** Açılıyor. Ürün `id` + `public_key` ile
tanınıyor; slug yalnızca yerel depolama anahtarı.

### Test lisansı oluştururken

Freemius → Licenses → Create License'ta **"User (optional)" alanını boş
bırakmayın.** Kullanıcısız lisansta etkinleştirme, sitenin yönetici
e-postasıyla yeni hesap açmaya çalışır ve onay maili bekler:

> Thanks! You should receive a confirmation email… complete the opt-in.

Test sitesinin e-postası sahteyse mail hiç gelmez, lisans hiç bağlanmaz ve
`is_registered` `hayır` kalır. Alana mevcut Freemius hesabının e-postası
yazılınca sorun ortadan kalkar.

Bu **gerçek müşteride yaşanmaz**: satın alanın hesabı Freemius tarafından
zaten açılır ve lisans ona bağlı gelir. Yani bu tuzak sınavın kendisine
aittir, ürüne değil.

---

## Pro: gerçek doğrulama sınavı

**Bunu da atlamayın.** Pro tek bir şey satıyor: belgenin **resmi** EN 16931
kural setine karşı doğrulanması. O halka çalışmıyorsa satılan şey yok demektir,
ve testler bunu yakalamaz — servis ayrı bir makinede.

Sınav `docker-compose.clean.yml` ortamında, **premium paketle** yapılır:

```sh
C="docker compose -f docker-compose.clean.yml -p deklera-clean"
$C run --rm -T --user root wpcli wp plugin install /build/deklera-*-premium.zip \
  --activate --allow-root --path=/var/www/html
```

Doğrulama servisinin adresi ve anahtarı geliştirme sitesinden taşınır; anahtar
**hiçbir yere yazılmaz**, kabuk değişkeninde kalır:

```sh
K=$(docker compose run --rm -T wpcli option get deklera_validator_key)
E=$(docker compose run --rm -T wpcli option get deklera_validator_endpoint)
$C run --rm -T --user root -e "VK=$K" -e "VE=$E" wpcli sh -c '
  wp option update deklera_validator_endpoint "$VE" --allow-root --path=/var/www/html
  wp option update deklera_validator_key      "$VK" --allow-root --path=/var/www/html'
```

Plan, eklentinin kendi `deklera/plan` filtresiyle Pro'ya zorlanır — bu lisansın
yerine geçmez, yalnızca lisans açıldığında müşterinin aldığı özelliği ölçer.

**İki yönlü ölçülmeli.** Yalnızca geçerli bir belge göndermek hiçbir şey
kanıtlamaz; servis her şeye "geçerli" diyor olabilir.

| Gönderilen | Beklenen |
|---|---|
| Eklentinin ürettiği gerçek fatura | `valid`, 0 hata, kural seti sürümü dolu |
| Aynı faturanın `GrandTotalAmount` alanı bozulmuş hâli | `invalid`, **BR-CO-15** ve **BR-CO-16** |

7 Eylül 2026'da 0.3.0 premium paketiyle ölçülen sonuç: kural seti **1.3.16**,
geçerli belge 0 bulgu (servis tarafı 2,5 sn), bozuk belge iki ölümcül bulguyla
reddedildi. Sonda betigi: `bin/pro-dogrula.php` (iki yonu de kendisi olcer ve sonunda
"calisiyor" ya da "surum cikarilmaz" der).

**Render ücretsiz katmanı uyuyor.** İlk istek 12 saniye sürebilir; bu bir hata
değil, soğuk başlangıç. Eklenti zaman aşımını buna göre veriyor, ama müşteriye
ilk doğrulamanın yavaş olabileceğini söylemek gerekir.

### 9 Eylül 2026 — 0.3.7, müşterinin yolundan

7 Eylül ölçümü anahtarı doğrudan veritabanına yazıyordu ve **taban** kural
setini bozuyordu (BR-CO-15/16). İki boşluk kalmıştı: anahtarın yönetici
ekranından girilmesi, ve **ulusal** kuralların gerçekten dönmesi. İkisi de
9 Eylül'de kapatıldı.

Temiz kurulum, WooCommerce 11.1.0, paketten kurulan 0.3.7 premium, Alman
mağaza (profil `xrechnung`), tamamlanmış tek sipariş:

| Ölçüm | Sonuç |
|---|---|
| Anahtar girilmeden üretim | belge üretildi, günlük: "Validation is not enabled." |
| Kasıtlı yanlış anahtarla servis | **HTTP 401** — TLS ve kapı çalışıyor |
| Doğru anahtar, yönetici ekranından | HTTP 200, sürüm 2, "Valid against EN 16931 1.3.16." |
| Ödeme aracı 58, IBAN yok | üretim **engellendi**: `[BR-DE-23-a]` |

Son satır sınavın kendisidir. BR-DE-23-a Almanya'nın kuralıdır ve ön uçuşta
karşılığı **yoktur**; belgeyi düşüren şey yalnızca servisin çalıştırdığı resmi
XRechnung kural seti olabilir. Ayrıca cevap Almanca geldi, yani KoSIT
yapılandırmasının kendi metni.

Telefonu silerek denemek işe yaramaz: ön uçuş kuralı (`NationalProfile`)
üretimi zaten kendisi engeller ve ölçüm servisten geldiğini kanıtlamaz. Bir
kez böyle ölçüldü ve sonuç yanıltıcıydı.

**TLS uyarısı.** Bu makinede WordPress'in HTTP API'si kendi sertifika paketini
kullanıyor ve antivirüs araya girdiği için sınav ortamında kök sertifikanın
`wp-includes/certificates/ca-bundle.crt` sonuna eklenmesi gerekir. Eklenmezse
istek "cURL error 60" ile düşer ve suç anahtarda sanılır.

---

## Polonya: gerçek gönderim sınavı

**Bu da atlanamaz** ve sebebi somut: 0.2.0 hazırlanırken 85 birim test
geçiyordu, paket doğrulanmıştı ve sekiz vergi senaryosu canlı KSeF'e karşı
çalışmıştı — eklenti yine de WordPress'te **hiçbir fatura gönderemiyordu.**

Sebep, iki doğrulamanın da aynı yolu atlamasıydı: birim testler zaman
damgasını tam sayı olarak sahteliyordu, canlı denemeler ise XAdES yolunu
kullanıyordu. Kullanıcının gerçekten izlediği yol (KSeF jetonu) hiç
koşulmamıştı.

Yerel WordPress'te, `api-test` ortamına karşı:

1. Mağazayı geçici olarak Polonya'ya alın; KDV numarasını test NIP'iyle
   eşleştirin.
2. Geçerli bir KSeF jetonu girin ve ortamı **test** bırakın. Jeton üretmek
   iki adımdır — `bin/ksef-live-test.php` XAdES ile bir **erişim** jetonu
   alır, `bin/ksef-token-al.php` onunla API'den kalıcı bir **KSeF jetonu**
   ister. İkincisi olmadan kullanıcının yolu değil, dev betiğinin yolu
   koşulmuş olur — 0.2.0'da tam olarak bu kaçtı.
3. Polonyalı alıcılı bir sipariş oluşturup `Generator::generate()` çalıştırın.
4. Kuyruğu koşturun:
   `wp action-scheduler run --hooks=deklera_submit_to_ksef`
5. Denetim izini okuyun. Beklenen sıra:

```
generated        KSeF FA(3), ...
queued           Queued for KSeF submission.
ksef_sent        KSeF reference ...
ksef_registered  KSeF number ...
```

`ksef_registered` yoksa sürüm çıkarılmaz. Numara gelmemesi tek başına hata
değildir (KSeF gecikebilir) ama `failed` satırı varsa sebebi çözülmelidir.

Yerel makinede antivirüs TLS kesiyorsa WordPress konteyneri de `cURL error 60`
alır ve denetim izine `failed` düşer — bu üründe değil, ortamda bir sorundur.
Konteyneri kök olarak açıp `bin/trust-local-ca.sh` çalıştırın.

Sınav bitince **mağaza ülkesini ve KDV numarasını geri alın, jetonu silin.**
Jeton bir kimlik bilgisidir; geliştirme kurulumunda unutulmuş bir jeton
bırakmayın.

Windows'ta konteynere yol geçirirken `MSYS_NO_PATHCONV=1` gerekir; yoksa
Git Bash `/pkg/...` yolunu Windows yoluna çevirir ve WP-CLI "Invalid plugin
slug" der.

---

## Ulusal denetleyici sınavı

EN 16931 bir tabandır; ülkeler üstüne kendi daraltmalarını koyar. Kendi
testlerimiz yeşilken belgenin bir ülkede reddedilmesi mümkündür — Almanya'da
tam olarak bu oldu, bkz. [ADR 0010](adr/0010-almanya-ulusal-kurallari.md).

Bu bölüm ölçümü nasıl tekrarlayacağınızı anlatır. **Kural setleri ve
denetleyiciler sürüm alır**; yılda bir kez yeniden çalıştırın.

### Örnek belgeleri üret

Örnekleri geliştirme sitesinden üretin, temiz kurulumdan değil: temiz kurulum
**paketlenmiş** eklentiyi çalıştırır, dolayısıyla henüz paketlenmemiş bir
düzeltmeyi göremezsiniz. Bir gün bu şekilde kaybedildi.

```sh
docker compose run --rm -T wpcli eval-file - < build/ornek-uret.php
```

### Sürekli izleme

Yukarıdakiler elle çalıştırılan ölçümlerdir. Bunlardan biri — ulusal profilin
canlı serviste gerçekten farklı davranması — `.github/workflows/validator-health.yml`
içinde yarım saatte bir otomatik koşar.

O adım `VALIDATOR_KEY` deposu sırrını ister ve **sır tanımlı değilse sessizce
atlanır**. Sırrı eklemek için:

```sh
gh secret set VALIDATOR_KEY --repo ekremtekerek/deklera
```

Değer, doğrulama servisinin `LICENSE_SECRET` ortam değişkenidir (Render →
konform-validator → Environment). Sır eklenmeden izleme yalnızca servisin
ayakta olduğunu görür; ulusal kuralların çalıştığını görmez.

### Almanya — XRechnung 3.0.2 (KoSIT)

Resmi yapılandırma KoSIT'in `validator-configuration-xrechnung` deposundan
indirilir; içindeki Schematron doğrudan çalıştırılabilir:

```sh
docker run --rm -v "$(pwd):/w" -w /w/validator node:22-alpine \
  node node_modules/xslt3/xslt3.js \
  -s:/w/build/ornek-xrechnung.xml \
  -xsl:/w/build/xrechnung/resources/xrechnung/3.0.2/xsl/XRechnung-CII-validation.xsl \
  -o:/w/build/xr-rapor.xml

grep -c failed-assert build/xr-rapor.xml
```

Beklenen: `0`. Sıfırdan büyükse rapordaki `id="..."` değerleri hangi BR-DE
kuralının düştüğünü söyler.

### Fransa — Factur-X

Fransa'da denetlenen şey yalnızca veri değil, **dosyanın kendisi**: Factur-X,
XML'i PDF/A-3 uyumlu bir PDF'e gömer. Üç şeyin ayrı ayrı ölçülmesi gerekir.

**1. PDF/A-3 uygunluğu** — veraPDF, PDF/A'nın referans denetleyicisidir:

```sh
docker run --rm -v "$(pwd)/build:/data" verapdf/cli \
  --format text /data/ornek-facturx.pdf
```

Beklenen: `PASS /data/ornek-facturx.pdf 3b`. `FAIL` alırsanız `--format mrr`
ile ayrıntı raporu üretip `<description>` ve `<context>` satırlarına bakın;
`context` kusurlu nesneyi doğrudan gösterir.

**2. XML'in EN 16931 uyumu** — CII söz dizimi kural seti:

```sh
docker run --rm -v "$(pwd):/w" -w /w/validator node:22-alpine \
  node node_modules/xslt3/xslt3.js \
  -s:/w/build/ornek-facturx-fr.xml \
  -xsl:/w/build/xrechnung/resources/cii/16b/xsl/EN16931-CII-validation.xsl \
  -o:/w/build/fr-rapor.xml
```

**3. Factur-X'e özgü iliştirme** — PDF içindeki dört işaret doğru olmalı:

```sh
tr -c '[:print:]\n' '\n' < build/ornek-facturx.pdf \
  | grep -E 'factur-x\.xml|/AFRelationship|fx:(DocumentType|Version|ConformanceLevel)'
```

Beklenen: `/AFRelationship /Data`, gömülü dosya adı `factur-x.xml`,
`fx:DocumentType INVOICE`, `fx:Version 1.0`, `fx:ConformanceLevel EN 16931`.

### Polonya — KSeF

Polonya'da belge, KSeF numara verene kadar hukuken **var olmaz**. O yüzden
buradaki sınav şema doğrulaması değil, gerçek gönderimdir — bkz. yukarıdaki
"Polonya: gerçek gönderim sınavı".
