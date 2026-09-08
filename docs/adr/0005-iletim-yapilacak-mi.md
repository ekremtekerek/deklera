# 0005 — Fatura iletimi yapılacak mı

Tarih: 2 Eylül 2026
Durum: **Karar bekliyor** — ürün sahibinin kararı

## Bağlam

Deklera belgeyi üretiyor ve doğruluyor, ama **ağa göndermiyor**. `TERMS.md` ve
`readme.txt` bunu açıkça söylüyor.

Pazar araştırması bu boşluğu somutlaştırdı. WordPress.org'da AB e-faturasına
yönelen bir düzine eklenti var; en güçlüsü **POP** (90 kurulum, 15 oyla 98/100,
8 ülke, ZUGFeRD/Factur-X/Peppol/KSeF/SdI) ve **faturayı ağa gönderiyor**.
Kredi tabanlı bir SaaS modeli kullanıyor.

Önemli olan şu: Fransa'da Eylül 2026'dan sonra bir mağazanın **yasal
yükümlülüğü faturayı iletmektir.** Belge üretmek o yükümlülüğün yalnızca bir
parçası. Bugünkü hâlimizle müşteri işini bitiremiyor; ayrıca bir sağlayıcı
bulmak zorunda.

## Seçenekler

### A. Üretir ve doğrularız, iletmeyiz

Bugünkü konum. Dar ama savunulabilir: ön uçuş kontrolü ve resmî EN 16931
doğrulaması rakiplerin sayfalarında geçmiyor, ve doğrulama teknik olarak
korunaklı (kural seti XSLT 2.0, PHP'de çalışmıyor).

- **Artısı:** bugün hazır. Bakım yükü sabit. Hiçbir sağlayıcıyla sözleşme,
  hiçbir para akışı sorumluluğu yok.
- **Eksisi:** müşteri işini bitiremiyor. POP gibi ürünlerin yanında "yarım"
  görünür. Fiyatlandırmada tavan düşük.
- **Konumlandırma:** POP'un rakibi değil, tamamlayıcısı. "Neyi göndereceğinizi
  önce doğrulayın."

### B. Peppol erişim noktası entegrasyonu ekleriz

- **Artısı:** işin tamamı kapanır. Fiyat tavanı yükselir. Fransa 2026 ve
  Polonya KSeF gibi zorunluluklarda tek eklenti yeter.
- **Eksisi:** bu bir hafta sonu işi değil. Erişim noktası sağlayıcısıyla
  sözleşme, kimlik doğrulama, teslim garantisi, hata kuyruğu, yeniden gönderim,
  ve **başkasının parasal yükümlülüğünü taşıyan bir sorumluluk**. Bir belge
  iletilmezse bunun bedeli müşterinin cezası olur.
- Tek kişilik ekiple bu sorumluluğu almak, 0 kurulumlu bir üründe erken.

### C. Önce A, kanıt gelirse B

Ücretsiz sürüm yayılsın, gerçek kullanıcılardan "gönderemiyorum" şikâyeti
gelsin, sonra B'ye geçilsin.

## Öneri

**C.** Gerekçe:

1. B'nin maliyeti yüksek ve geri dönüşü belirsiz. Bugün **sıfır kullanıcı**
   var; kimsenin istemediği bir özelliği inşa etme riski gerçek.
2. A zaten satılabilir ve tek başına savunulabilir bir değer taşıyor:
   "faturanız reddedilecek mi." Bu, POP'un da çözmediği problem.
3. İletim eklendiğinde ürünün doğası değişir — yazılım satmaktan altyapı
   işletmeye geçilir. O adım, talebi ölçmeden atılmamalı.

Kısacası: bu boşluk bugün bir eksiklik değil, **bilinçli bir sınır**. Öyle
kaldığı sürece `readme.txt` ve `TERMS.md`'de olduğu gibi açıkça söylenmeli;
alıcının yanlış beklentiyle gelmesi hem iade hem kötü değerlendirme demektir.

## Karar verildiğinde

Bu dosya güncellenecek. B seçilirse ilk adım sağlayıcı seçimi olur ve o da
ayrı bir ADR ister — Peppol erişim noktaları arasında fiyat, AB veri yerleşimi
ve SLA farkları belirleyicidir.

---

## Ek: 8 Eylül 2026 — konumun ölçülmesi

Bu ADR "A savunulabilir mi" sorusunu gerekçeyle cevaplamıştı. Bugün ölçtük.

### Piyasa

| Ürün | Ne veriyor | Fiyat |
|---|---|---|
| WP Overnight (100k+ kurulum) | UBL, Peppol BIS, Factur-X, ZUGFeRD üretimi | ücretsiz |
| WP Overnight — Peppol **gönderimi** | 500 belge/yıl | 60 €/yıl |
| E-Invoicing For WooCommerce | Factur-X, UBL, ZUGFeRD, XRechnung | ücretsiz |
| Polonya: WP Desk KSeF | KSeF gönderimi | ücretsiz |
| Polonya: ByteWave | KSeF gönderimi | ~69 zł/yıl |

Sonuç açık: **format üretmek para etmiyor, gönderim de bizim
düşündüğümüzden ucuz.** Bu ADR'nin "A'nın fiyat tavanı düşük" öngörüsü
doğruydu; fiyatlar buna göre indirildi (Pro 149 € → 49 €).

### Asıl bulgu: ücretsiz olan, geçerli değil

Aynı Alman siparişi, aynı satıcı verisi, aynı resmi denetleyici
(KoSIT XRechnung 3.0.2):

| | Başarısız iddia | Kural |
|---|---|---|
| E-Invoicing For WooCommerce 1.x (8 Eylül 2026) | **26** | 10 |
| Deklera 0.3.5 | **0** | — |

Düşen kurallar Almanya'nın zorunlu kıldıkları: BR-DE-1 (ödeme talimatları),
BR-DE-7 (satıcı e-postası), BR-DE-15 (alıcı referansı), BR-DE-21, BR-DE-27,
BR-DE-28, ve PEPPOL-EN16931-R001 / R008 (boş elemanlar, dört kez) / R010 /
R020.

Adil olmak için rakibin okuduğu bütün ayarlar dolduruldu; eksik bırakılan
`wooei_id_vat` sonradan eklendiğinde 28 → 26'ya indi, sıfıra inmedi.

**Bu, ürünün varlık sebebinin kanıtıdır.** Bizim ilk çıktımız da 12
iddiadan düşüyordu (ADR 0010); fark, ölçmüş olmamız. Rakip bunu düzeltmek
isterse resmi kural setini çalıştırması gerekir — yani tam olarak Pro'nun
sattığı şeyi.

### Konumlandırmaya etkisi

Fark bir özellik listesi değil, **doğruluk**. Ve gösterilebilir:

> Ürettiğimiz belgeyi resmi denetleyiciden geçiriyoruz ve raporu
> yayınlıyoruz. Aldığınız her eklentiden aynısını isteyin.

**Rakip adı verilmez.** Kanıt bizde; adlandırmak hukuken riskli, gereksiz ve
bir sonraki sürümlerinde geçersiz kalabilir. Kategoriye meydan okumak yeter.

### Bu ölçüm nasıl tekrarlanır

Rakip eklenti temiz yığına kurulur, aynı sipariş ve satıcı verisiyle
XRechnung ürettirilir, `docs/RELEASE.md` → "Almanya" bölümündeki komutla
aynı Schematron'dan geçirilir. Sürüm ve tarih not edilmeli: bu bir anlık
ölçümdür, kalıcı bir gerçek değil.
