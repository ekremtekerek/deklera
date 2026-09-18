# Tanıtım metinleri

Bu metinler **sizin ağzınızdan** yazıldı. Gönderen siz olmalısınız; kendi
hesabınızdan, kendi sözünüzle. Aşağıdakiler taslak — kendinize göre
kısaltın, uzatın, üslubu değiştirin.

## Üç kural

**1. Geliştirici olduğunuzu her yerde açıkça söyleyin.** Metinlerin hepsinde
var. Gizlemek, tek bir yorumda ortaya çıktığında tüm güveni bitirir — ve
uyumluluk ürününde satılan şey güven.

**2. Her topluluğun kendi tanıtım kurallarını önce okuyun.** Bazıları kendi
ürününüzü paylaşmayı tamamen yasaklıyor, bazıları haftanın belirli bir gününe
sınırlıyor. Kural ihlali, ürünü orada kalıcı olarak yakar.

**3. Ücretli planı öne çıkarmayın.** Bu metinlerin işi ücretsiz aracı ve
demoyu göstermek. Fiyattan söz eden tek cümle bile paylaşımı reklama çevirir.

## Bağlantılar

- Sayfa: `https://ekremtekerek.github.io/deklera/` (`/de/`, `/fr/`, `/pl/`)
- **Ölçüm yazısı: `https://ekremtekerek.github.io/deklera/measured/`** — gönderilerin kancası
- **Dizin sayfası: `https://wordpress.org/plugins/deklera/`** — kurmak isteyen buraya
- **Kontrol aracı: `https://ekremtekerek.github.io/deklera/check/`** (`/de/check/`, `/fr/check/`, `/pl/check/`)
- Demo: `https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ekremtekerek/deklera/main/demo/blueprint.json`
- Kaynak: `https://github.com/ekremtekerek/deklera`

**Kontrol aracı öne çıkarılmalıdır.** Aşağıdaki metinlerin çoğu ondan önce
yazıldı ve demoyu gösteriyor. Demo ürünü anlatır; kontrol aracı ise okuyucunun
KENDİ belgesini sınamasına izin verir — iddia doğrulanabilir olduğu an reklam
olmaktan çıkar. Yeni paylaşımlarda bağlantı bu olmalı.

---

## 1. Reddit — r/woocommerce, r/Wordpress (İngilizce)

> **I built a free tool that tells you which of your WooCommerce orders would be rejected as e-invoices**
>
> I'm the developer, so treat this as a self-promotion post — but the tool is
> free and there is a browser demo, so you can judge it without installing
> anything.
>
> The background: EU e-invoicing is landing on a lot of shops. France from
> September 2026, Poland's KSeF is already live, Germany accepts XRechnung
> today. Most of the discussion is about producing the XML, which is honestly
> the easy part.
>
> The hard part is that WooCommerce order data is usually not clean enough for
> the standard, and you find that out weeks later when an invoice bounces and
> you have to work out which of two hundred business rules you broke.
>
> So the plugin starts there instead. It scans your orders and tells you, in
> plain language, which ones would be rejected and why — with the exact rule
> reference and where to fix it. Things like a cross-border EU business sale
> with no VAT and no customer VAT number, or invoice lines that add up to
> €120.00 against an order total of €112.50.
>
> The whole measurement is written up here — the six German rules my own
> output failed, and why:
>
> https://ekremtekerek.github.io/deklera/measured/
>
> You can run the same check on your own invoice, no sign-up:
>
> https://ekremtekerek.github.io/deklera/check/
>
> The plugin is free in the directory: https://wordpress.org/plugins/deklera/
>
> There is also a browser demo, nothing to install, loading a store with orders that are
> deliberately not ready:
>
> https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ekremtekerek/deklera/main/demo/blueprint.json
>
> It also generates Factur-X for France and XRechnung for Germany, but the
> pre-flight report is the part I actually care about.
>
> What it does not do: outside Poland it does not transmit invoices to a
> network — that still goes through your own provider. Poland is the
> exception: there an invoice does not legally exist until KSeF has accepted
> it, so for Polish stores the plugin submits to KSeF and records the number.
>
> Happy to hear where the checks are wrong — that is the useful feedback for
> me right now.

---

## 2. Kısa biçim — Facebook grupları, LinkedIn, forum yanıtları (İngilizce)

> I built a free WooCommerce plugin that answers one question: which of your
> orders would be rejected if you had to issue them as EU e-invoices?
>
> It reads your orders and reports each problem in plain language with the
> exact EN 16931 rule — missing customer VAT number on an intra-community
> sale, totals that do not reconcile, that kind of thing.
>
> Measurement and free checker: https://ekremtekerek.github.io/deklera/measured/
> Plugin: https://wordpress.org/plugins/deklera/
>
> I'm the developer. It's free; there is a paid tier for official validation,
> but the report is the part worth looking at.

---

## 3. Fransa — *facture électronique* (Fransızca)

Hedef: WooCommerce/WordPress Fransız grupları, e-ticaret forumları.

> **Un outil gratuit pour savoir quelles commandes seraient rejetées en
> facture électronique**
>
> Je suis le développeur, donc c'est bien une publication promotionnelle —
> mais l'outil est gratuit et il y a une démo dans le navigateur, sans rien
> installer.
>
> La facturation électronique arrive en septembre 2026 pour la réception, et
> l'essentiel des discussions porte sur la génération du XML. Ce n'est
> pourtant pas le plus difficile.
>
> Le vrai problème : les données de commande WooCommerce sont rarement assez
> propres pour la norme EN 16931, et on ne s'en aperçoit que des semaines plus
> tard, quand une facture est rejetée.
>
> L'extension commence donc par là. Elle analyse vos commandes et vous dit, en
> langage clair, lesquelles seraient rejetées et pourquoi, avec la règle exacte
> et l'endroit où corriger. Par exemple : une vente intracommunautaire sans TVA
> et sans numéro de TVA client, ou des lignes qui totalisent 120,00 € alors que
> la commande en affiche 112,50 €.
>
> Démo dans le navigateur, rien à installer : [lien]
>
> Elle génère aussi du Factur-X (PDF/A-3 avec le XML intégré). En revanche elle
> **ne transmet pas** les factures en France : cela passe toujours par votre
> PDP. (La Pologne fait exception, le KSeF l'imposant.)
>
> Je suis preneur de retours, surtout si un contrôle vous semble faux.

---

## 4. Almanya — *E-Rechnung* (Almanca)

Hedef: WooCommerce/WordPress Alman grupları, e-ticaret forumları.

> **Kostenloses Werkzeug: welche Bestellungen würden als E-Rechnung abgelehnt?**
>
> Ich bin der Entwickler, das hier ist also Eigenwerbung — aber das Werkzeug
> ist kostenlos und es gibt eine Demo im Browser, ganz ohne Installation.
>
> Bei der E-Rechnung dreht sich fast alles um das Erzeugen der XML. Das ist
> aber der einfache Teil.
>
> Schwierig ist, dass WooCommerce-Bestelldaten für EN 16931 meist nicht sauber
> genug sind — und man merkt es erst Wochen später, wenn eine Rechnung
> abgelehnt wird.
>
> Das Plugin fängt deshalb dort an. Es prüft Ihre Bestellungen und sagt
> verständlich, welche abgelehnt würden und warum, mit der genauen Regel und
> der Stelle zum Korrigieren. Zum Beispiel: eine innergemeinschaftliche
> Lieferung ohne USt-IdNr. des Kunden, oder Positionen, die auf 120,00 €
> kommen, während die Bestellung 112,50 € ausweist.
>
> Die ganze Messung — welche sechs deutschen Regeln meine eigene Ausgabe
> gerissen hat und warum:
>
> https://ekremtekerek.github.io/deklera/measured/
>
> Dieselbe Prüfung können Sie an Ihrer eigenen Rechnung laufen lassen, ohne
> Anmeldung:
>
> https://ekremtekerek.github.io/deklera/check/
>
> Das Plugin ist kostenlos im Verzeichnis: https://wordpress.org/plugins/deklera/
>
> Es erzeugt auch XRechnung 3.0. In Deutschland **versendet** es nichts — das
> läuft weiter über Ihren eigenen Dienstleister. (Polen ist die Ausnahme: dort
> verlangt KSeF die Übermittlung.)
>
> Über Rückmeldungen freue ich mich, besonders wenn eine Prüfung falsch liegt.

---

## 4a. Almanya — Facebook grupları (Almanca)

Facebook forum değil ve 4. bölümdeki metin oraya uzun gelir: ilk iki satırdan
sonra "Mehr anzeigen" ile kesiliyor ve kimse açmıyor. Kanca ilk satırda
olmalı.

İkinci fark kitlede: o gruplarda çoğunlukla **mağaza sahibi** var, geliştirici
değil. Ellerinde yapıştıracak bir fatura XML'i yok, yani kontrol aracı onlara
hitap etmiyor. Onlara hitap eden şey ön uçuş raporu — "kendi siparişlerine bak"
— ve oraya giden yol dizin.

**Gövdede tek bağlantı.** Facebook çok bağlantılı gönderilerin erişimini
kısıyor; ölçüm yazısı ve kontrol aracı ilk yoruma konur.

> Welche Ihrer WooCommerce-Bestellungen würden als E-Rechnung abgelehnt werden?
>
> Ich bin der Entwickler des Plugins, das hier ist also in eigener Sache — es
> ist kostenlos und liegt im WordPress-Verzeichnis.
>
> Beim Thema E-Rechnung dreht sich fast alles um das Erzeugen der XML. Das ist
> der einfache Teil. Schwierig ist, dass WooCommerce-Bestelldaten selten sauber
> genug dafür sind — und man merkt es erst Wochen später, wenn eine Rechnung
> zurückkommt.
>
> Das Plugin fängt deshalb dort an. Es prüft Ihre Bestellungen und sagt in
> verständlicher Sprache, welche abgelehnt würden und warum: mit der genauen
> Regel und der Stelle zum Korrigieren. Zum Beispiel eine innergemeinschaftliche
> Lieferung ohne USt-IdNr. des Kunden. Oder Positionen, die auf 120,00 € kommen,
> während die Bestellung 112,50 € ausweist.
>
> XRechnung 3.0 erzeugt es ebenfalls. **Versenden** tut es in Deutschland
> nichts — das läuft weiter über Ihren eigenen Dienstleister.
>
> Plugins → Installieren → "Deklera"
> https://wordpress.org/plugins/deklera/
>
> Über Rückmeldungen freue ich mich, besonders wenn eine Prüfung falsch liegt.

**İlk yoruma** (gönderdikten hemen sonra, kendi gönderinin altına):

> Falls es jemanden technisch interessiert: Ich habe meine eigene Ausgabe durch
> den offiziellen KoSIT-Validator geschickt, bevor ich veröffentlicht habe. Der
> erste Durchlauf ist an sechs deutschen Regeln gescheitert — alle davon Felder,
> die die EU-Norm offenlässt und Deutschland zur Pflicht macht.
>
> Die ganze Messung: https://ekremtekerek.github.io/deklera/measured/
>
> Und hier können Sie eine eigene Rechnung gegen dasselbe Regelwerk prüfen,
> ohne Anmeldung: https://ekremtekerek.github.io/deklera/check/

### Gönderdikten sonra

İlk saat önemli. Soru gelirse hızlı cevap ver; cevapsız kalan gönderi ölür ve
grupta bir daha paylaşmak zorlaşır. "Ablehnung" ya da "Rechnung kam zurück"
diyen biri çıkarsa, ona ürünü değil **kuralı** anlat — hangi BR kuralına
takıldığını sorabilirsin. Bu, satış konuşmasından daha çok güven kazandırıyor.

---

## 4b. Polonya — *KSeF* (Lehçe)

Hedef: Polonya WooCommerce/WordPress grupları, e-ticaret forumları.

Polonya'nın farkı iletimdir ve metin bunu açıkça söyler: FA(3) KSeF'e gidip
numara alır. Bu, öteki iki ülkede yapmadığımız şeydir ve saklanacak değil
söylenecek bir farktır.

> **Darmowe narzędzie: które zamówienia zostałyby odrzucone jako e-faktura?**
>
> Jestem autorem wtyczki, więc to autopromocja — ale narzędzie jest darmowe,
> a demo działa w przeglądarce, bez instalacji.
>
> Przy KSeF prawie wszystko kręci się wokół wygenerowania XML. To akurat
> łatwa część.
>
> Trudne jest to, że dane zamówień WooCommerce rzadko są na tyle czyste, żeby
> przejść — a dowiadujesz się o tym dopiero wtedy, gdy faktura wraca
> odrzucona.
>
> Wtyczka zaczyna więc od tego: sprawdza zamówienia i mówi zrozumiałym
> językiem, które zostałyby odrzucone i dlaczego, z dokładnym oznaczeniem
> reguły i miejscem do poprawy. Na przykład wewnątrzwspólnotowa dostawa bez
> numeru VAT nabywcy, albo pozycje sumujące się do 120,00 € przy zamówieniu
> na 112,50 €.
>
> Polska jest wyjątkiem po stronie wysyłki: FA(3) trafia do KSeF i wraca z
> numerem — bo bez numeru faktura nie istnieje w świetle prawa. W Niemczech i
> we Francji wtyczka niczego nie wysyła; to zostaje po stronie Twojego
> dostawcy.
>
> Cały pomiar — które sześć niemieckich reguł odrzuciło mój własny wynik
> i dlaczego:
>
> https://ekremtekerek.github.io/deklera/measured/
>
> To samo sprawdzenie możesz uruchomić na własnej fakturze, bez
> rejestracji:
>
> https://ekremtekerek.github.io/deklera/check/
>
> Wtyczka jest za darmo w katalogu: https://wordpress.org/plugins/deklera/
>
> Chętnie przyjmę uwagi, zwłaszcza jeśli któreś sprawdzenie jest błędne.

---

## 4c. Ölçüm yazısı (İngilizce) — asıl çekim malzemesi

Bu bir tanıtım gönderisi değil, **bir ölçümün anlatısı**. İşe yaramasının
sebebi okuyucunun aynı ölçümü kendi belgesiyle yapabilmesi: iddia
doğrulanabilir olunca reklam olmaktan çıkıyor.

**Rakip ismi başlıkta geçmez.** Almanya'da karşılaştırmalı reklam yasaldır
(UWG §6) ama nesnel olmak zorundadır; dahası satıcının rakibe saldırması
topluluklarda affedilmez. Yöntemi yayınlarız, ismi okuyucu kendi ölçümüyle
bulur.

Yeri: site üzerinde bir yazı sayfası, ya da geliştirici topluluğunda bir
gönderi. İkisinde de sonundaki bağlantı kontrol aracına gider.

> **I ran my own WooCommerce e-invoices through Germany's official validator.
> The first run failed six rules.**
>
> I build an e-invoicing plugin, so read this with that in mind. The
> measurement is reproducible and the tool at the end is free.
>
> Most WooCommerce invoice plugins produce the XML, and several produce it
> for nothing. I assumed mine was fine: the unit tests were green, the
> library is well regarded, the output validated against EN 16931.
>
> Then I downloaded KoSIT's official validator configuration — the same
> Schematron a German authority runs — and pointed it at my own output.
>
> Six rules failed. Every one of them a field the European standard leaves
> optional and Germany makes mandatory: the seller's contact name and
> telephone number, the electronic address of both parties, the payment
> means. My invoice was valid EN 16931 and would have been refused in
> Germany.
>
> That gap is the whole problem. The rule set that decides acceptance
> compiles to XSLT 2.0, and PHP's XSL extension only speaks XSLT 1.0 — so a
> WordPress plugin cannot run it in-process. It is genuinely easy to ship
> something that looks right and is refused.
>
> I fixed the six, and then put the validator behind a page so anyone can do
> the same measurement without installing anything:
>
> https://ekremtekerek.github.io/deklera/measured/
>
> (Yazı artık sitede duruyor; gönderilerde doğrudan bu adres verilir.
> Sonundaki bağlantılar kontrol aracına ve dizine gidiyor.)
>
> Paste an invoice — from my plugin or any other — and see what the official
> rule set says. No sign-up, nothing stored.
>
> If you already have an e-invoicing plugin, the useful question to put to
> its author is simply: *can I see your validator report?* That is a fair
> question to ask any vendor, mine included; mine is in the repository.

---

## 4d. Arama reklamı metinleri

Hacim düşük, niyet çok yüksek: bu sorguları yazan kişi çözüm arıyor. Bütçe
Almanya ve Polonya'ya ayrılır — **Fransa'ya değil**, çünkü orada yasal
yükümlülük iletimdir ve biz iletmiyoruz (bkz. ADR 0005).

Google sınırları: başlık **30**, açıklama **90** karakter. Aşağıdakiler
sayılarak yazıldı.

### Almanya

Anahtar kelimeler: `XRechnung WooCommerce`, `E-Rechnung WooCommerce`,
`ZUGFeRD WooCommerce`, `WooCommerce E-Rechnung Plugin`

Başlıklar:

- `XRechnung für WooCommerce`
- `Wird Ihre Rechnung abgelehnt?`
- `E-Rechnung vorher prüfen`
- `Kostenlos, ohne Anmeldung`
- `Am amtlichen Regelwerk`

Açıklamalar:

- `Prüfen Sie Ihre E-Rechnung am offiziellen Regelwerk. Kostenlos, ohne Anmeldung.`
- `Sagt vor dem Versand, welche Bestellungen abgelehnt würden — und warum.`

Açılış sayfası: `/de/check/` — reklamdan gelen kişi doğrudan aracı bulmalı,
ürün anlatısını değil.

### Polonya

Anahtar kelimeler: `KSeF WooCommerce`, `KSeF WordPress`,
`faktura ustrukturyzowana WooCommerce`

Başlıklar:

- `KSeF dla WooCommerce`
- `Czy faktura zostanie odrzucona`
- `Sprawdź e-fakturę za darmo`
- `FA(3) prosto z WooCommerce`
- `Bez rejestracji`

Açıklamalar:

- `Sprawdź e-fakturę urzędowym zestawem reguł. Za darmo, bez rejestracji.`
- `Mówi przed wysyłką, które zamówienia zostałyby odrzucone i dlaczego.`

Açılış sayfası: `/pl/check/`

### Sıra

**Önce WordPress.org onayı, sonra reklam.** Reklamdan gelen kişi eklentiyi
dizinde bulamazsa tıklama boşa gider; dizin içi arama zaten kendi başına bir
kanaldır.

---

## 5. Tek cümlelik biçim — X, Mastodon

> Which of your WooCommerce orders would be rejected as an EU e-invoice? Free
> plugin, and a free checker for your own invoice: https://ekremtekerek.github.io/deklera/check/

---

## Yapılmaması gerekenler

- **WordPress.org destek forumlarında kendi eklentinizi tanıtmayın.** Orası
  yardım içindir; tanıtım hem kural ihlali hem de eklenti dizinindeki
  itibarınızı riske atar.
- **Aynı metni birden çok yere kopyalamayın.** Her topluluğun tonu farklı ve
  kopyala-yapıştır anlaşılıyor.
- **Rakipleri kötülemeyin.** Özellikle POP gibi bizden fazlasını yapan
  (fatura ileten) ürünleri. Farkı anlatın, kusur aramayın.
- **Yorumlara cevap vermeyi göze alamayacağınız gün paylaşmayın.** Cevapsız
  bırakılmış bir tanıtım paylaşımı, hiç paylaşmamaktan kötüdür.

## Not

Fransızca, Almanca ve Lehçe metinler benim yazdığım taslaklardır.
Yayınlamadan önce ana dili o dil olan birinin gözden geçirmesi iyi olur —
özellikle Almanca metindeki resmî ton, o topluluklarda hassas bir konudur.

Fransızca metin ve Fransa reklamı ayrı bir uyarı taşır: Eylül 2026'dan beri
orada yasal yükümlülük faturayı **iletmektir** ve biz iletmiyoruz. Metin bunu
zaten söylüyor; reklam bütçesi ise oraya ayrılmamalı (bkz. ADR 0005).
