# WordPress.org inceleme cevabı — 0.3.10

**Nasıl gönderilir:** 13 Eylül 2026 tarihli inceleme e-postasına, **aynı iş
parçacığında** yanıt olarak. Paket önce
https://wordpress.org/plugins/developers/add/ sayfasından yüklenir.

İnceleme kimliği: `R deklera/ekremtekerek/5Sep26/T3 13Sep26/4.2.1 (P0TDX363450HGN)`

---

## Ne istediler

Aynı madde, üçüncü tur: **izin verilmeyen dosya türleri.** İki örnek:

- `vendor-prefixed/horstoeko/zugferd/src/assets/facturx_extension_schema.xmp`
- `vendor-prefixed/symfony/yaml/Resources/bin/yaml-lint`

## Ne öğrendik

İlk dosya, 0.3.9 cevabında "çalışma anında okunuyor, bu yüzden tuttum" diye
açıklanmıştı. Yine örnek olarak geldi. Yani ekip ya açıklamayı kabul etmedi
ya da dosya türüne bakarak karar veriyor.

Dersi şu: **gerekli bir dosya türünü savunmak yerine, izin verilen bir türe
çevirmenin yolunu aramak turu kısaltıyor.** Tartışma her seferinde üç günlük
bir tur daha demek.

## Ne yapıldı

- `yaml-lint` silindi. symfony/yaml'in komut satırı aracı; kütüphanenin
  kendisi (serializer üst verisini ayrıştıran YAML okuyucusu) gerekli, bu
  betik değil. Pakette LICENSE dışında başka uzantısız dosya yok.
- XMP şeması **`.xml` olarak yeniden adlandırıldı.** Dosyanın içi zaten XML;
  kütüphane onu `simplexml_load_file()` ile uzantıya bakmadan okuyor ve adını
  değiştirmek için `ZugferdSettings::setXmpMetaDataFilename()` sunuyor.
  `ZugferdBuilder::use_xml_named_xmp_schema()` kütüphaneye yeni adı söylüyor.
  Davranış aynı.
- Yapıma denetim eklendi: pakette `.xmp`, `.sch`, `.xslt` ya da LICENSE
  dışında uzantısız dosya kalırsa yapım durur.

Kalan olağandışı türler (`.yml`, `.xlf`, `.xsd`, `.icc`, `.ttf`, `.dat`)
bu turda örnek olarak gösterilmedi. Önceki cevapta açıklandılar; dokunulmadı.

---

## Gönderilecek metin

```
Hello,

Thank you for the follow-up.

Both files are dealt with in 0.3.10:

- symfony/yaml/Resources/bin/yaml-lint is a command-line tool that came
  with the library. The plugin never runs it, so it is removed. There are no
  other extensionless files left apart from the LICENSE files.

- facturx_extension_schema.xmp is now shipped as facturx_extension_schema.xml.
  The file is XML, the library reads it with simplexml_load_file(), and it
  provides a setting for the file name, so the plugin now points it at the
  .xml copy. French Factur-X invoices are produced exactly as before.

I added a build check so that .xmp, .sch, .xslt and extensionless files
cannot reappear in the package.

Version 0.3.10 is uploaded. I tested it on a clean WordPress install with
WP_DEBUG on: German, French and Polish documents are still produced
correctly, including the embedded Factur-X metadata, and uninstall behaves
as before.

Best regards,
Hasan Ekrem Tekerek
```
