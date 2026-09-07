# 0009 — Eklenti "Konform"dan "Deklera"ya yeniden adlandırıldı

Tarih: 7 Eylül 2026
Durum: **Kabul edildi**

## Bağlam

`konform-0.1.0.zip` WordPress.org'a 1 Eylül 2026'da gönderildi. 5 Eylül'de
inceleme, gönderimi askıya alarak geri döndü. Gerekçelerden biri kod değil,
**ad**:

> “Konform” is a distinctive name, but it is also a registered trademark owned
> by another entity. It cannot be used as the plugin's display name or slug.

Tespit e-postada ✨ işaretiyle geliyor, yani bir yapay zekâ aracının çıktısı ve
insan tarafından doğrulanmamış olabilir. Bu bir seçenek doğuruyordu: itiraz mı,
yeniden adlandırma mı?

### Neden itiraz edilmedi

İtiraz edilebilirdi. Marka hukuku sınıf temellidir; "KONFORM" adının kayıtlı
olduğu sınıflar (konformal kaplama kimyasalları) bir WooCommerce faturalama
eklentisiyle karışma ihtimali taşımaz. Muhtemelen haklı çıkılırdı.

Ama itirazın bedeli hakkın değerinden büyük:

| | İtiraz | Yeniden adlandırma |
|---|---|---|
| Süre | Her tur 1–3 hafta, tur sayısı belirsiz | Bir gün |
| Sonuç | Belirsiz; inceleme ekibi sınıf analizi yapmıyor | Kesin |
| Kaybedilirse | Yine yeniden adlandırma, bir ay sonra | — |
| Maliyet | Kurulu kullanıcı yok, kaybedilecek marka değeri yok | Aynı |

Belirleyici olan son satır: eklentinin **hiç kullanıcısı yok**. Ad değiştirmenin
maliyeti bir daha asla bu kadar düşük olmayacak. İtirazın maliyeti ise sadece
zaman değil — e-posta açıkça "ele alınmayan konular gönderinin reddine yol
açar" diyor ve reddedilen gönderiler **bir daha incelenmiyor**.

## Karar

Ad `Deklera` olarak değiştirildi ve değişiklik **kod tabanının tamamına**
uygulandı: görünen ad, slug, metin alanı, ad alanı, seçenek ve kanca önekleri,
veritabanı tabloları, Strauss öneki, dosya ve dizin adları. 116 dosya.

Yüzeysel bir değişiklik (yalnızca görünen ad ve slug) incelemeyi geçmeye
yeterdi. Tercih edilmedi: içeride `konform_` önekleri kalsaydı marka bir daha
asla kodla örtüşmezdi ve her yeni geliştirici "burası neden Konform?" diye
sormak zorunda kalırdı. Kullanıcı yokken temiz kesmek, sonradan hiç mümkün
olmayacak bir lüks.

## Adın nasıl seçildiği

Aday adlar üç ölçütle elendi: WordPress.org'da eşleşme, web'de aynı adı taşıyan
ürün/şirket, ve mevcut markalarla karışma. Eleme kayda değer, çünkü ilk bakışta
"boş" görünen adların çoğu doluydu:

| Aday | Sonuç |
|---|---|
| Fiscera | ❌ fiscera.com — faal bir iş yazılımı |
| Ledgera | ❌ ledgera.finance ve ledgera (ledger-as-a-service) — iki ayrı ürün |
| Norvera | ❌ norvera.com — mobil servis şirketi |
| Validera | ❌ validera.co + Validera Limited (UK) |
| Cisoft | ❌ dünyada çok sayıda şirket, **cisoft.com.tr dahil** — kendi alan adımız .net.tr, karışma kaçınılmaz |
| Faktera | ⚠️ tam eşleşme yok ama Fakturia / Faktura / Faktum / Fakturo hepsi faturalama yazılımı — incelemenin "benzerlik" ölçütünden geçmesi şüpheli |
| Emitera | ✅ temiz; en yakını Emitrr (farklı yazım, farklı pazar) |
| **Deklera** | ✅ **temiz**; hiçbir eşleşme çıkmadı |

`Deklera`, "deklarasyon" kökünden. Uydurma, ayırt edici, telaffuz edilebilir ve
ne yaptığına dair bir ipucu taşıyor — incelemenin istediği üç şey.

## Görünen ad kısa bırakıldı

`Plugin Name: Deklera`, "Deklera E-Invoicing for WooCommerce" değil. Gerekçe
0.1.0'daki kararla aynı ve hâlâ geçerli: WordPress.org slug'ı bu başlıktan
türetir, slug onaydan sonra **değiştirilemez** ve metin alanı slug ile birebir
aynı olmak zorundadır. Uzun bir başlık `deklera-e-invoicing-for-woocommerce`
slug'ı doğurur; metin alanımız `deklera` olduğu için translate.wordpress.org'dan
gelen çeviriler hiçbir zaman yüklenmez.

Cevap e-postasında slug açıkça `deklera` olarak isteniyor. İstek kabul edilse
bile başlığı uzatmanın kazancı (SEO) riski (kalıcı yanlış slug) karşılamıyor;
başlık onaydan sonra slug'a dokunmadan uzatılabilir.

## Sonuçlar

**Veri:** Seçenek adları ve tablolar değiştiği için 0.2.0'ı kurmuş bir sitede
ayarlar ve belge arşivi görünmez olur. **Lisans da öyle:** Freemius kaydını
eklenti slug'una göre saklıyor (`fs_accounts['sites']['konform']`), slug
değişince SDK `deklera` altına bakıyor ve bulamıyor — Pro lisansı yeniden
etkinleştirilmek zorunda. Geliştirme sitesinde birebir gözlendi. Geçiş kodu yazılmadı — yazılsaydı
`konform_` dizileri kod tabanında kalacaktı, ki bütün amaç onlardan
kurtulmaktı. Bilinen kurulum sayısı sıfır.

**Depo dışında kalanlar** (bu ADR'nin kapsamadığı, elle yapılacak işler):

- Freemius panosunda ürün ADI ve SLUG'u. 7 Eylül'de ikisi de denendi,
  sonuç farklı çıktı:

  **Ad değişti.** İlk üç deneme sessizce geri döndü ve "kilitli" sanıldı;
  değilmiş. Sebep arayüzdeydi: Update düğmesine referansla tıklamak istek
  üretmiyordu (ağ izleyicisinde tek istek yok). Gerçek fare tıklamasıyla
  kaydedildi. Ürün artık her yerde **Deklera – EU E-Invoicing for
  WooCommerce**; checkout sayfasında sekme başlığı ve sepet satırı dahil
  eski addan iz yok.

  **Slug değişmedi ve gerçekten kilitli.** Slug alanına `deklera` yazılınca
  Update düğmesi **devre dışı** kalıyor — uygulama kaydı baştan reddediyor.
  Alanın kendi ipucu sebebi söylüyor: "If your item is listed on the
  WordPress.org repository, use the exact same slug." Yani sıra ters: önce
  WordPress.org `deklera`'yı ayırmalı. Bugün bir şey kırmıyor — Freemius
  ürünü `id` + `public_key` ile tanıyor ve premium slug zaten
  `deklera-premium`.
- GitHub deposunun adı. 7 Eylül'de `gh repo rename` ile `deklera` yapıldı;
  readme'deki TERMS.md ve PRIVACY.md bağlantıları doğrulandı (HTTP 200).

**Yapılanlar (7 Eylül):** 0.3.0 paketi WordPress.org'a "Upload updated plugin
for review" ile yüklendi ve gönderim artık **Deklera** adıyla görünüyor; atanan
slug hâlâ `konform`, cevapta açıkça `deklera` isteniyor. Freemius'ta 0.3.0
premium dağıtıldı ve **Released** yapıldı. WordPress.org'un kendi Plugin
Check'i pakette 175 hata gösteriyor; hepsi tek bir sebepten —
`TextDomainMismatch`, "Expected 'konform' but got 'deklera'" — ve slug
ayrılınca sıfırlanacak. İnceleme e-postası bu uyarıyı zaten öngörmüştü.
Paketteki çeviri çağrısı sayısı 176; hata sayısıyla örtüşüyor.

**Değişmeyen:** doğrulama servisinin adresi (`konform-validator.onrender.com`).
Dış bir servis, adı Render üzerinde ayrıca değiştirilmeli ve değiştirildiğinde
eski adresi kullanan kurulumlar kırılır. Eklenti paketinde geçmiyor, yalnızca
CI ve belgelerde.

## Alınan ders

Ad kontrolü gönderimden **önce** yapılmalıydı; e-postanın tarif ettiği
kontrolün (arama motorunda adı ve parçalarını aratmak) tamamı beş dakika
sürüyor. Bir haftadan fazla bekledikten sonra kod dışı bir sebeple geri
dönmek, yazılan hiçbir satırın önlemeyeceği türden bir kayıp.

`docs/RELEASE.md` sürüm öncesi listesine bu adım eklendi.
