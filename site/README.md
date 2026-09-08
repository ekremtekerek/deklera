# Açılış sayfası

Bağımlılıksız statik HTML. Dört dil, ortak bir stil dosyası:

```
site/
  index.html            en   açılış
  de|fr|pl/index.html        açılış
  check/index.html      en   kontrol aracı
  de|fr|pl/check/index.html  kontrol aracı
  style.css             sekizi de bunu kullanır
  check.js              kontrol aracının mantığı, sekizi için ortak
  lang.js               dil şeridi
```

Yazı tipleri Google Fonts'tan gelir, başka hiçbir dış kaynak yoktur.
Kontrol aracı doğrulama servisine `POST /v1/try` atar; başka çağrı yoktur.

**Stil neden ayrı dosyada:** eskiden tek dosyaydı ve CSS `<style>` içindeydi.
Dört dile çıkınca bu, her renk değişikliğinde dört dosyayı düzenlemek demeye
başladı — yani üçünün unutulması demek. Ortak dosya tek doğruluk kaynağıdır.

## Nerede barındırılır

Herhangi bir statik barındırmada çalışır. Şu an GitHub Pages'te:
https://ekremtekerek.github.io/deklera/

**Dal seçerek yayınlanmıyor, iş akışıyla yayınlanıyor** — Pages'in dal
kaynağı klasör olarak yalnızca `/` veya `/docs` kabul ediyor, `site/` kabul
etmiyor. Dağıtımı `.github/workflows/pages.yml` yapıyor ve depo ayarlarında
Pages → Source **"GitHub Actions"** olmak zorunda. `site/` altında bir şey
değişince kendiliğinden çalışır.

Kendi alan adınıza koyacaksanız `site/` dizinini olduğu gibi kopyalamak
yeterlidir.

## Neyi anlatıyor

Konumlandırma kasıtlıdır ve rakip araştırmasına dayanır (bkz.
`docs/adr/0005-iletim-yapilacak-mi.md` ve `readme.txt`): sayfa "biz de
e-fatura yapıyoruz" demiyor — o yarışta genişlikte kaybediyoruz — **"faturanız
reddedilecek mi, kesmeden önce söyleyelim"** diyor.

Sayfa bir SaaS şablonu gibi değil, **ürünün kendi ekranı gibi** kurulmuştur:
başlıkta soru, altında ön uçuş raporunun verdict satırı, sonra eklentinin
render ettiği biçimde üç gerçek bulgu kartı. Ziyaretçi ürünü anlatan bir metin
değil, ürünün çıktısını görür.

"Ne yapmıyoruz" bölümü tam bir bölüm olarak durur ve kısaltılmamalıdır. En
güçlü rakip faturayı ağa gönderiyor; biz göndermiyoruz. Alıcının bunu satın
aldıktan sonra öğrenmesi hem iade hem kötü değerlendirme demektir.

**"Resmi denetleyicilerle ölçüldü" bölümü** ürünün en güçlü farkıdır ve
sayıları uydurma değildir: her biri `docs/adr/0010`, `docs/adr/0011` ve
`docs/RELEASE.md` → "Ulusal denetleyici sınavı"nda kayıtlı bir ölçümdür.
**Ölçüm tekrarlanmadan bu bölüm değiştirilmez.** Sonundaki "biçimsel olarak
kabul edilebilir, rakamlarınızın doğruluğunu söylemez" cümlesi de kalmalıdır;
onsuz iddia olduğundan geniş okunur.

## Diller

Sayfalar çeviri değil, aynı argümanın o ülkenin satıcısına yazılmış hâlidir:
ülke listesinin ve ölçüm kartlarının sırası her dilde o ülke başta olacak
şekilde değişir. Alman bir satıcı önce XRechnung'u görmelidir.

Sayfa dilleri eklentinin çevirileriyle aynı üçlüdür (`de_DE`, `fr_FR`,
`pl_PL`). **Yeni bir dil eklerken ikisi birlikte eklenmelidir** — o dilde
reklam verip yönetici ekranını İngilizce bırakmak, sayfanın verdiği sözü
bozar.

Her sayfada `hreflang` bağlantıları ve altbilgide dil seçici vardır; yeni dil
eklenirse **sekiz dosyanın da** bağlantı listesi güncellenmelidir.

### Dil şeridi hedefi nasıl bulur

Her sayfanın `<html>` etiketi iki şey bildirir: `data-root` (site köküne
göreli yol) ve `data-page` (kökten sonraki konum, açılış için boş, kontrol
aracı için `check/`). `lang.js` hedefi bunlardan kurar.

Yol, dilin sondan kesilmesiyle bulunamaz: `/de/` için işe yarardı ama
`/de/check/` için yanlış sonuç verirdi. **Yeni sayfa eklerken bu iki
öznitelik unutulmamalıdır**; yoksa şerit hiç görünmez.

## Güncellenmesi gerekenler

- WordPress.org onaylandığında: eklenti dizini bağlantısı eklenir, "awaiting
  review" cümlesi dört dilden de kaldırılır.
- Fiyat veya plan değişirse tablolar elle güncellenir (dört dosya).
- Ölçüm sayıları değişirse dört açılış **ve** dört kontrol sayfası birlikte
  güncellenir; sayı iki yerde geçiyor.
