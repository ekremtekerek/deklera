# 0010 — Almanya'nın ulusal kurallarına (XRechnung 3.0.2) uymak

Tarih: 2026-09-07
Durum: kabul edildi

## Bağlam

Ürünü satışa açmadan önce şu soruyu cevaplamamız gerekti: ürettiğimiz fatura
gerçekten ilgili ülkenin kurumunca kabul edilir mi? O güne kadarki bütün
güvencemiz kendi testlerimizdi — yani kendi yorumumuzu kendimize doğrulatıyorduk.

EN 16931 bir tabandır. Ülkeler üstüne CIUS adı verilen daraltmalar koyar:
standardın isteğe bağlı bıraktığı bir alanı zorunlu kılabilirler. Almanya bunu
XRechnung profiliyle yapar ve resmi denetleyicisini KoSIT yayımlar.

Bu yüzden ölçtük. KoSIT'in resmi yapılandırması
(`xrechnung-3.0.2-validator-configuration-2026-08-31`) indirildi, içindeki
`XRechnung-CII-validation.xsl` Schematron'u alındı ve eklentinin ürettiği
gerçek XRechnung çıktısı bu kural setinden geçirildi.

## Ölçüm

İlk çalıştırma **12 başarısız iddia / 6 ayrı kural** verdi:

| Kural | Ne istiyor |
| --- | --- |
| BR-DE-5 | Satıcı iletişim kişisi (BT-41) zorunlu |
| BR-DE-6 | Satıcı iletişim telefonu (BT-42) zorunlu |
| BR-DE-1 | Ödeme talimatları (BG-16) zorunlu |
| BR-DE-27 | Telefon numarası boş bırakılamaz |
| PEPPOL-EN16931-R020 | Satıcı elektronik adresi (BT-34) zorunlu |
| PEPPOL-EN16931-R010 | Alıcı elektronik adresi (BT-49) zorunlu |

Hiçbiri EN 16931'de zorunlu değil. Yani kendi testlerimiz haklıydı ve fatura
yine de Almanya'da reddedilirdi. Bir CIUS'u okuyarak tahmin etmek ile resmi
denetleyiciden geçirmek arasındaki fark tam olarak bu.

## Karar

Altı bulgunun tamamı kapatıldı:

- `Party` iki isteğe bağlı alan kazandı: `contact` ve `phone`. Sonda ve
  varsayılanlı duruyorlar; mevcut çağrılar bozulmadı.
- `ZugferdBuilder`, satıcı ve alıcı için e-posta adresini `EM` şemasıyla
  elektronik adres olarak da yazıyor (BT-34, BT-49).
- Ödeme aracı kodu (BT-81) eklendi. Seçilen kod **68** — "çevrimiçi ödeme
  servisi". 58/59 (SEPA) ve 48 (kart) BR-DE-19/20/21'i tetikler ve IBAN,
  mandate kimliği ya da kart son hanesi ister; bir web mağazasının ödeme
  ağ geçidinden geçen siparişinde bu veri yoktur. 68 doğru olanı söyler ve
  ek veri istemez. `deklera/payment_means` süzgeciyle değiştirilebilir.
- İletişim kişisi boşsa mağaza adına düşülür — doğru ve yanıltıcı olmayan
  bir cevap. Telefonun böyle bir yedeği **yok**: uydurmak faturaya yanlış
  bilgi yazmak olurdu.

Telefon kod tarafından doldurulamadığı için ön uçuşa yeni bir mağaza kuralı
eklendi: `NationalProfile`. Alman bir mağazada telefon boşsa engelleyici
bulgu üretir. Sebebi zamanlama: eksiklik dosya üretilirken değil, fatura
kuruma sunulduğunda patlar — orada müşteri çoktan geç kalmıştır.

## Sonuç

Aynı belge, aynı kural setinden yeniden geçirildi:

```
=== kalan bulgular ===
HIC BULGU YOK
```

Almanya'nın resmi XRechnung 3.0.2 kuralları sıfır bulguyla geçiyor.

## Bunun sınırı

Bu, belgenin **biçimsel** olarak kabul edilebilir olduğunu gösterir. Verinin
doğruluğunu (KDV numarası gerçekten o firmaya mı ait, tutar doğru mu)
göstermez; onu mağaza sahibi sağlar. Ayrıca bu ölçüm Almanya içindir —
Fransa (Factur-X / PDF/A-3) ve Polonya (KSeF FA(3)) ayrı ayrı ölçülür.
