# Deklera doğrulama servisi

EN 16931 Schematron kural setini çalıştıran küçük bir HTTP servisi.

**Neden ayrı bir servis?** Resmî kural seti XSLT 2.0'a derlenir. PHP'nin
`ext-xsl` uzantısı libxslt'yi sarmalar ve XSLT 1.0'da kalır, dolayısıyla resmî
doğrulama eklentinin içinde çalıştırılamaz. Ayrıntı:
[`docs/adr/0003-dogrulama-calisma-ortami.md`](../docs/adr/0003-dogrulama-calisma-ortami.md).

Bu kısıt aynı zamanda ürünün lisans korumasıdır: nulled bir kopya doğrulama
yapamaz.

---

## Uçlar

| Yol | Yöntem | Kimlik | Yanıt |
|---|---|---|---|
| `/health` | GET | yok | `{ok, rules_version, xrechnung_version, syntax}` |
| `/v1/validate` | POST | `Authorization: Bearer <LICENSE_SECRET>` | `{valid, errors[], warnings[], profile, rules_version}` |

Gövde: `{"xml": "<rsm:CrossIndustryInvoice …>", "profile": "en16931"}`,
en fazla 2 MB.

### Halka açık deneme ucu

`POST /v1/try` — **kimlik doğrulaması yok.** Açılış sitesindeki
[kontrol sayfası](https://ekremtekerek.github.io/deklera/check/) bunu çağırır.

Gövde `/v1/validate` ile aynıdır (`xml`, `profile`); yanıta bir de `remaining`
eklenir. Farkları:

| | `/v1/validate` | `/v1/try` |
|---|---|---|
| Kimlik | Bearer anahtar | yok |
| Gövde sınırı | 2 MB | 512 KB |
| Kota | yok | IP başına saatte 10 (`TRY_PER_HOUR`) |
| CORS | — | açık (sayfa başka kaynaktan çağırıyor) |

**Neden var:** ürünün farkı bir özellik değil, doğruluk — ve doğruluk ancak
gösterilebilirse satar. Ölçüldü: ücretsiz bir rakibin XRechnung çıktısı
Almanya'nın resmi denetleyicisinden 26 iddiadan düşüyor, bizimki sıfırdan
geçiyor (bkz. `docs/adr/0005` eki). Ziyaretçi bunu kendi belgesiyle
görebilmeli.

**Neden sınırlı:** kimlik doğrulaması olmayan bir uç, bedava bir API'ye
dönüşüp Pro'nun sattığı şeyi boşa çıkarabilir. Kota, küçük gövde sınırı ve
tek belge — toplu kullanım için elverişsiz. Pro'nun sattığı şey zaten bu
değil: orada doğrulama HER faturada, WordPress'in içinde, kesilmeden önce
çalışır.

Kota süreç belleğindedir. Tek örnek çalıştığımız için yeterli; ölçekleme
gerekirse paylaşılan bir depoya taşınmalı.

### Profiller

`profile` iki değer alır:

| Değer | Ne çalışır |
|---|---|
| `en16931` (varsayılan) | Yalnızca AB taban kural seti |
| `xrechnung` | Taban **ve** Almanya'nın XRechnung 3.0.2 CIUS'u |

Ulusal profil istendiğinde taban set de çalışır. Ulusal set yalnızca
daraltmaları taşır; tek başına çalıştırmak, tabanın yakaladığı hataları
görmeden "geçti" demek olurdu.

Bunun neden gerektiği ölçülmüştür: eklentinin çıktısı taban seti geçerken
Almanya'nın resmi denetleyicisinden on iki iddiadan düşüyordu
(bkz. `docs/adr/0010`). Yani taban set tek başına bir Alman müşteriye
"bu fatura kabul edilir" diyemez.

Bilinmeyen bir profil sessizce tabana düşer — istemci servisten yeni
olabilir; daha az kural çalıştırmak hiç doğrulamamaktan iyidir. Yanıttaki
`profile` alanı hangisinin koşulduğunu söyler.

`LICENSE_SECRET` tanımlı değilse servis **her isteği 401 ile reddeder**.
Yanlışlıkla kimliksiz açılan bir servis çalışır durumda görünmez.

---

## Dağıtım

### Render (şu an kullanılan)

Panelden **New → Web Service**, GitHub deposu `ekremtekerek/deklera`:

| Alan | Değer |
|---|---|
| Name | `deklera-validator` |
| Language | Docker |
| Branch | `main` |
| Region | Frankfurt (EU Central) |
| Root Directory | `validator` |
| Instance Type | Free |
| Health Check Path | `/health` |
| `LICENSE_SECRET` | değer alanındaki **Generate** ile üretilir |
| `RULES_VERSION` | `1.3.16` |

Kök dizin `validator` olduğu için `plugin/` altındaki değişiklikler yeniden
dağıtım tetiklemez. `Dockerfile` doğrudan kullanılır; `compose.yml` ve
`Caddyfile` bu yolda devrede değildir.

İlk yapı kural setini indirip SEF'e derlediği için birkaç dakika sürer —
ölçülen: 1 dk 1 sn.

### Kendi sunucunuzda

Gereken: Docker'ı olan herhangi bir sunucu ve alan adının A kaydının o sunucuya
bakması. TLS'i Caddy kendisi alır ve yeniler.

```sh
git clone https://github.com/ekremtekerek/deklera.git
cd deklera/validator
cp .env.example .env
# .env içinde DOMAIN'i yazın ve anahtarı üretin:
#   openssl rand -hex 32
docker compose up -d --build
```

İlk yapı kural setini indirip SEF'e derlediği için birkaç dakika sürer. Sonrası
saniyeler içindedir; imaj kendi kendine yeter, çalışma anında dışarı çıkmaz.

Doğrulama:

```sh
curl -s https://<alan-adiniz>/health
# {"ok":true,"rules_version":"1.3.16","xrechnung_version":"2026-08-31","syntax":"CII"}
```

### Sağlayıcı seçimi

**Şu an yayında:** Render, Frankfurt bölgesi, ücretsiz katman
(`deklera-validator`, `https://konform-validator.onrender.com`). Kaynak GitHub
deposundan, kök dizin `validator/`, `main` dalına her gönderimde yeniden
dağıtılıyor.

Tek şart veri yerleşiminin AB'de olması: fatura XML'i işleniyor. Gizlilik
politikası bunu açıkça taahhüt etmiyor, ama AB e-fatura uyumluluğu satan bir
üründe verinin AB dışına çıkması satışta size sorulur.

Servis durum tutmaz. Yük artarsa aynı imajdan ikinci bir kopya çalıştırmak
yeterlidir; paylaşılan bir veritabanı yoktur.

#### Ücretsiz katmanın iki bedeli — ve nasıl karşılandığı

Bunlar ölçüldü, tahmin değil.

**Uykuya dalma.** Ücretsiz servis 15 dakika atıl kalınca duruyor; ilk isteğin
uyanması 50 saniyeyi buluyor. Eklenti bunu tek bir zaman aşımıyla karşılasaydı
seyrek gelen her istek boşa giderdi. Bu yüzden süre bağlama göre ayrıldı:
etkileşimli istekte 15 saniye (bir yönetici ekran başında bekliyor, ekran
kilitlenmemeli), kuyruktaki işte 90 saniye (orada kimse beklemiyor). Ayrımı
`Scheduler::is_running_in_background()` kuruyor.

Pratik sonucu: **otomatik üretim** (sipariş tamamlanınca, Pro) soğuk servisi
bekler ve doğrular. Sipariş ekranındaki **elle üretim** soğuk servise denk
gelirse "doğrulama yapılamadı" der, belge yine üretilir; ikinci deneme
ısınmış servise gider ve doğrular.

**Geçiş anında 404.** Servis dururken kenar sunucu isteği bekletmek yerine
birkaç dakika 404 döndürüyor. Ölçüm: çalışan bir servis önce 200, sonra
~2 dakika 404, sonra yine 200. Eklenti kesin bir HTTP durumuyla dönen geçici
hatalarda (404, 5xx) 3 saniye sonra bir kez daha deniyor. Zaman aşımında
denemiyor — orada bütçenin tamamı zaten harcanmıştır.

**Hız.** 0,1 CPU'da doğrulama 4,2 saniye sürüyor (güçlü bir makinede
0,1–0,3 sn). Isınmış serviste 15 saniyelik bütçeye sığıyor, ama pay dar.

Bu üçü, ücretsiz katmanı çalışır kılıyor; ortadan kaldırmıyor. Sürekli açık
bir makine ($7/ay Render Starter ya da ~4 €/ay Hetzner) üçünü de sıfırlar.

#### Kalıcı ücretsiz alternatif: Oracle Cloud Always Free

Uykuya dalmayan tek kalıcı ücretsiz seçenek. Frankfurt (`eu-frankfurt-1`)
bölgesi, Ampere A1 (ARM) makine, sürekli açık. Haziran 2026'dan beri ücretsiz
sınır 2 OCPU / 12 GB; bu servis için gerekenin kat kat üstünde. Yukarıdaki üç
bedeli de ortadan kaldırır, ama kendi riskini getirir (aşağıda).

Bu yola geçilirse `compose.yml` ve `Caddyfile` kullanılır; Render'da ikisi de
devrede değildir, TLS'i ve yönlendirmeyi Render kendi yapar.

İmaj ARM'de sorunsuz çalışır: `node:22-slim` çok mimarili ve hem `saxon-js`
hem `xslt3` saf JavaScript'tir, derlenen bir ikili yoktur.

Üç tuzağı var, üçü de kuruluşta:

- **Kapasite.** A1 makineleri "out of capacity" hatası verebilir. Frankfurt
  genelde birkaç dakikada verir; alamazsanız biraz sonra tekrar deneyin.
- **Güvenlik duvarı iki katmanlı.** VCN security list'te 80/443'ü açmak
  yetmez; Oracle Linux imajı portları makinenin kendi güvenlik duvarında da
  kapatır. İkisi de açılmazsa Caddy sertifika alamaz ve sebebi görünmez.
- **Atıl makineler geri alınabilir.** Oracle, 7 gün boyunca CPU, ağ ve bellek
  kullanımının 20'nin altında kaldığı Always Free makinelerini geri alma
  hakkını saklı tutuyor. Günde birkaç fatura doğrulayan bir servis bu tanıma
  **girer**. Bedeli olan risk budur: para ödenen bir özelliğin altındaki servis
  sessizce durabilir.

Makinenin kendi güvenlik duvarını açmak (Oracle Linux):

```sh
sudo firewall-cmd --permanent --add-service=http --add-service=https
sudo firewall-cmd --reload
# Bazi Oracle imajlarinda firewalld yerine dogrudan iptables kuralli gelir:
sudo iptables -I INPUT -p tcp -m multiport --dports 80,443 -j ACCEPT
sudo netfilter-persistent save 2>/dev/null || sudo service iptables save
```

Geri alma riski, `/health` izlemesini bu yolda daha da gerekli kılar: makine
durdurulursa aynı gün haberiniz olur. İzleme zaten kurulu, aşağıdaki
**İzleme** bölümüne bakın. Oracle'da uykuya dalma olmadığı için aralık
serbestçe sıkılaştırılabilir.

Riski tamamen kaldırmak isterseniz Hetzner CX22 (Almanya, ~4 €/ay) aynı
`compose.yml` ile çalışır ve ne kapasite ne geri alma sorunu vardır.

---

## Eklenti tarafındaki ayar

**WooCommerce → Deklera** sayfasının altındaki "Official validation (Pro)" bölümü:

- **Doğrulama ucu**: `https://<alan-adiniz>/v1/validate`
- **Anahtar**: `.env` içindeki `LICENSE_SECRET` değeri

Servis erişilemezse eklenti belgeyi yine de üretir ve doğrulamanın
çalışmadığını kaydeder. Ağ arızası fatura kesmeyi durdurmamalıdır.

---

## Kural seti güncellemesi

Avrupa Komisyonu yeni sürüm yayımladığında:

```sh
# .env içinde RULES_VERSION'ı değiştirin
docker compose up -d --build
```

Almanya'nın ulusal profili ayrı bir takvimde yürür ve KoSIT yayımlar:

```sh
# .env içinde XRECHNUNG_VERSION'ı değiştirin (örn. 2026-08-31)
docker compose up -d --build
```

İkisi bağımsızdır; birini yükseltmek diğerini etkilemez.

Dün geçerli olan bir belge bugün bulgu üretebilir; bu kuralların değişmesidir,
bir kusur değil. Kullanım şartları bunu açıkça söyler.

---

## Yerelde çalıştırma

```sh
npm ci
npm run build:rules      # kural setini indirir ve derler
npm test                 # resmî örnek belgeye karşı öz-test
LICENSE_SECRET=dev npm start
```

`npm test` HTTP katmanını atlar ve `validate()` fonksiyonunu doğrudan çağırır;
lisans anahtarı devreye girmez.

---

## İzleme

`/health` ucu GitHub Actions'tan yarım saatte bir kontrol ediliyor
(`.github/workflows/validator-health.yml`). Başarısız koşum, depo sahibine
GitHub tarafından e-posta ile bildirilir; ayrı bir izleme servisine hesap
açmaya gerek yok.

Kontrol iki şeye bakar: servis cevap veriyor mu (`ok:true`) ve kural setini
gerçekten yüklemiş mi (`rules_version` bildiriliyor mu). İkincisi önemli —
kural seti okunamazsa servis 200 dönüp doğrulama yapamayabilir.

**Aralığı 15 dakikanın altına çekmeyin.** Render'ın ücretsiz katmanı 15 dakika
atıl kalınca uykuya dalar; daha sık ping atmak izleme olmaktan çıkıp servisi
yapay olarak ayakta tutmaya döner. Uykuya dalma kabul edilmiş bir bedeldir,
dolanılacak bir engel değil.

Not: GitHub, 60 gün hiç etkinlik olmayan depolarda zamanlanmış iş akışlarını
devre dışı bırakır.
