# WordPress.org inceleme cevabı — 0.3.9

**Nasıl gönderilir:** 10 Eylül 2026 tarihli inceleme e-postasına, **aynı iş
parçacığında** yanıt olarak. Yeni e-posta açılmaz.

Paket önce https://wordpress.org/plugins/developers/add/ sayfasından yüklenir,
sonra bu cevap yazılır.

İnceleme kimliği: `R deklera/ekremtekerek/5Sep26/T2 10Sep26/4.2.1 (P0TDX363450HGN)`

---

## Ne istediler

Tek madde: **izin verilmeyen dosya türleri.** Örnek olarak on iki dosya
gösterildi — `horstoeko/zugferd` kütüphanesiyle gelen `.sch` (Schematron) ve
`.xslt` dosyaları.

E-postadaki şu cümle belirleyici oldu: *"the Plugins Team may not share all
cases of the same issue"*. Yani gösterilen on ikiyi silmek yetmez; aynı sınıfa
giren her şey temizlenmeli. Pakette sayıldığında 481 `.yml`, 57 `.xlf`, 20
`.map`, 6 `.gitignore` ve benzeri gelişim artefaktı olduğu görüldü.

## Yanlış giden bir şey — ve nasıl yakalandı

İlk düzeltmede `.yml` dosyaları da silindi; sürekli tümleştirme ayarı
sanılmışlardı. Değillerdi: `horstoeko/zugferd`in `src/yaml/` dizinindeki 270
dosya JMS serializer'ın üst verisi ve kütüphane onları `addMetadataDir()` ile
kaydediyor.

Sonuç sessiz bir bozulmaydı. Belge yine üretildi — 8333 bayt, hatasız — ama
içi yanlıştı; doğrusu 6319 bayt. Bozuk olan DAHA BÜYÜKTÜ, çünkü eleman adları
yanlış açılıyordu. Boyuta ya da dosya sayısına bakarak anlamak mümkün değildi.
Sınıf haritası, izolasyon ve Plugin Check denetimlerinin hepsi yeşildi.

Yakalayan şey `bin/uc-ulke.php` oldu: Fransa yolunda PDF üreticisi bozuk
XML'de tarih alanını arayınca çöktü.

İki koruma eklendi: gerekli varlıklar (XMP, ICC, FA3.xsd) yoksa yapım durur,
ve serializer üst verisi 200 dosyanın altına düşerse yapım durur.

## Ne yapıldı

`bin/build.sh` içine budama adımı eklendi. Silinenlerin hiçbiri çalışma anında
okunmuyor: zugferd'in `schema/` dizini yalnızca `ZugferdProfiles` içindeki bir
dizide **isimle** geçiyor, onu okuyan sınıflar (`ZugferdDocumentValidator`,
`ZugferdKositValidator`) bu eklentide kullanılmıyor.

**İki dosya türü bilerek bırakıldı** ve cevapta açıklanıyor; ikisi de çalışma
anında gerekli:

- `facturx_extension_schema.xmp` — Factur-X'in PDF/A-3 içine gömülmesini
  şart koştuğu XMP uzantı şeması. `ZugferdDocumentPdfBuilder` okuyor.
- `intermedia/ksef-fa3/schema/FA3.xsd` — Polonya FA(3) şeması; belge
  arşivlenmeden önce buna karşı doğrulanıyor (`Fa3Builder::SCHEMA`).

Budama adımı bu ikisinin varlığını ayrıca **kontrol ediyor** ve yoksa yapımı
durduruyor. Sessizce silinmiş bir şema Fransa ya da Polonya çıktısını çalışma
anında bozar ve bunu ancak müşteri görür.

---

## Gönderilecek metin

```
Hello,

Thank you for the review.

The .sch and .xslt files came from a bundled library (horstoeko/zugferd)
and nothing in the plugin reads them. They are gone, along with the other
artefacts I could confirm are never read: source maps, an example PDF,
library FAQ pages in .htm, and tooling dotfiles. I took the note about the
examples not being exhaustive seriously and went through the package by
file type rather than by your list.

Some uncommon file types remain in the vendored libraries. Each one is
read at runtime, so I have kept it rather than guess:

- vendor-prefixed/horstoeko/zugferd/src/yaml/*.yml (270 files)
  Serializer metadata. The library registers these directories with
  addMetadataDir() and cannot serialise an invoice without them. I did
  remove them at first; the plugin then produced an invoice that looked
  fine and was structurally wrong, with no error anywhere. I put them back
  and added a build check so it cannot happen again. The remaining .yml
  files under src/validation are the matching constraint definitions.

- vendor-prefixed/horstoeko/zugferd/src/assets/facturx_extension_schema.xmp
  The XMP extension schema that the Factur-X standard requires to be
  embedded in the PDF/A-3 document.

- vendor-prefixed/horstoeko/zugferd/src/assets/*.icc
  The sRGB colour profile that PDF/A requires as an output intent.

- vendor-prefixed/intermedia/ksef-fa3/schema/*.xsd
  The official Polish FA(3) schema and the three schemas it imports. Each
  generated document is validated against it before archiving.

- vendor-prefixed/setasign/tfpdf/font/unifont/*.ttf and *.dat
  The font that is embedded into the PDF, and its character-width cache.
  PDF/A does not allow non-embedded fonts.

If you would rather any of these were handled another way, tell me and I
will change them.

Version 0.3.9 with these changes is uploaded. I tested it on a clean
WordPress install with WP_DEBUG on: documents are still produced for all
three countries and the uninstall behaviour is unchanged.

Best regards,
Hasan Ekrem Tekerek
```
