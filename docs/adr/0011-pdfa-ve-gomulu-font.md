# 0011 — Yerleşik şablon gömülü TrueType'a geçiyor

Tarih: 2026-09-07
Durum: kabul edildi
İlgili: [0002](0002-pdf-uretimi.md) — bu ADR onun "Latin-1 sınırı" bölümünü geçersiz kılar

## Bağlam

Almanya'yı resmi denetleyiciden geçirdikten sonra ([ADR 0010](0010-almanya-ulusal-kurallari.md))
sıra Fransa'daydı. Fransa'nın Factur-X'i XML değil, **hibrit** bir belgedir:
XML, PDF/A-3 uyumlu bir PDF'in içine gömülür. Yani orada denetlenen şey
yalnızca veri değil, dosyanın kendisi.

veraPDF (PDF/A'nın referans denetleyicisi) ile ölçtük:

```
FAIL /data/ornek-facturx.pdf 3b
passedChecks="1040" failedChecks="2"
```

1040 denetimden 1038'i geçiyordu. Düşen ikisi aynı maddeydi — ISO 19005-3,
madde 6.2.11.4.1:

> The font programs for all fonts used for rendering within a conforming file
> shall be embedded within that file

Bağlam satırı suçluyu doğrudan gösteriyordu: `font[0](Helvetica-Bold)` ve
`font[0](Helvetica)`.

Sebep FPDF'in tasarımı: Helvetica onun **çekirdek** fontlarından biridir ve
çekirdek fontlar tanım gereği gömülmez — PDF okuyucusunun kendi kopyasına
güvenilir. Bu sıradan bir PDF'te doğru davranıştır. PDF/A'da yasaktır, çünkü
PDF/A'nın tek amacı belgenin otuz yıl sonra da aynı görünmesidir.

Sonuç: ürettiğimiz Factur-X, biçimsel olarak Factur-X değildi. Kendi
testlerimiz bunu göremezdi; XML kusursuzdu, kap kusurluydu.

## İkinci sorun: aynı yerden çıktı

`setasign/fpdf` yalnızca Latin-1 (CP1252) yazabilir. ADR 0002 bunu bilinçli
bir sınır olarak kabul etmişti ve doğru olanı yapıyordu: karakteri sessizce
kırpmak yerine üretmeyi reddediyordu.

Ama o karar, ürünün Fransa ve Almanya'ya baktığı bir noktada alınmıştı.
Bugün KSeF'i destekliyoruz. Lehçe bir firma adının — Wróblewski Łódź —
faturaya basılamaması, "kabul edilmiş sınır" değil, Polonya pazarının
tamamının kapalı olması demekti. Aynısı Çekçe, Macarca, Romence, Yunanca ve
Baltık dilleri için de geçerli.

## Karar

Yerleşik şablon `setasign/tfpdf` ile çizilir; yazı tipi gömülü TrueType
(DejaVuSans, normal ve kalın).

Tek değişiklik ikisini birden kapatıyor:

- Font dosyanın **içindedir** → PDF/A-3 sağlanır.
- Metin **UTF-8** yazılır → Latin-1 kapısı ve `assert_representable()` kalkar.

tFPDF yalnızca kullanılan glifleri gömer, bütün fontu değil: örnek fatura
32 KB, hibrit hâli 42 KB.

Ölçüm, gerçek eklenti çıktısıyla tekrarlandı:

```
PASS /data/ornek-facturx.pdf 3b
```

Aynı belgedeki metin PDF'ten geri okundu; Wróblewski, Łódź, żółć, Αθήνα ve
София'nın hepsi yerinde.

## Bunun bedeli ve nasıl ödendiği

**Paket boyutu.** tFPDF 9,5 MB yazı tipi taşır (DejaVu ailesinin tamamı).
Şablon ikisini kullanıyor; `bin/font-cache.php` gerisini derleme sırasında
atıyor — 9,5 MB → 1,7 MB.

**Hız.** tFPDF bir TTF'i ilk gördüğünde 750 KB'ı ayrıştırıp yanına
`.mtx.php` / `.cw.dat` önbelleği bırakır. Dizin yazılabilir değilse bunu
**her faturada** yeniden yapar. Ölçüldü: fatura başına 1858 ms.

Önbelleği hazır paketlemek ilk bakışta çözüm gibi duruyor ama bir tuzağı var:
tFPDF önbelleğin içine TTF'in **mutlak yolunu** yazar. Derleme makinesinin
yolu müşterinin sunucusunda yoktur; font sessizce gömülemez ve PDF/A yine
bozulur — üstelik bu sefer hiçbir hata vermeden.

Bu yüzden `bin/font-cache.php` önbelleği üretip `$ttffile` satırını `__DIR__`
göreli hâle getiriyor. Çalışma anında ne ayrıştırma ne dosya yazma kalıyor:

| | süre |
| --- | --- |
| önbelleksiz | 1858 ms |
| paketlenmiş önbellekle | 204 ms |

Betik `bin/deps.sh` sonunda çağrılır, çünkü Strauss ağacı her yenilendiğinde
`vendor-prefixed/` sıfırdan kurulur ve önbellek silinir. Geliştirme ortamı da
böylece paketle aynı davranır.

## Bunun sınırı

Bu ölçüm **yerleşik şablon** içindir. Mağaza bir PDF fatura eklentisi
kullanıyorsa (`WcpdfSource`) kaynak PDF bizim denetimimizde değildir ve o
eklentinin çıktısı PDF/A uyumlu olmayabilir. Bunu belgeliyoruz; sessizce
düzelttiğimizi iddia etmiyoruz.
