<?php
/**
 * Lehce ceviri eslemesi.
 *
 * TERMINOLOJI NOTLARI (degistirmeden once okuyun)
 *
 * - "Credit note" -> "Faktura korygujaca". Polonya'da belge turu 381'in
 *   yasal adi budur.
 * - "Reverse charge" -> "Odwrotne obciazenie". Fatura uzerinde gorunmesi
 *   gereken ibare budur.
 * - "VAT number" -> "numer VAT". Polonyali satici icin bu numara NIP'tir,
 *   ama alan AB genelinde kullanildigi icin genel terim birakildi.
 * - "Intra-community supply of goods" -> "wewnatrzwspolnotowa dostawa
 *   towarow" (WDT); hizmet karsiligi "wewnatrzwspolnotowe swiadczenie
 *   uslug".
 * - Lehcede UC cogul bicim vardir; __cogul__ dizileri uc elemanlidir.
 *
 * @package Deklera
 */

return array(
	// Eklenti kimligi.
	'Deklera' => 'Deklera',
	'https://github.com/ekremtekerek/deklera' => 'https://github.com/ekremtekerek/deklera',
	'Hasan Ekrem Tekerek' => 'Hasan Ekrem Tekerek',
	"Turns WooCommerce orders into legally valid e-invoices for the seller's country and delivers them through the seller's own e-invoicing provider." => 'Zamienia zamówienia WooCommerce w prawnie ważne e-faktury dla kraju sprzedawcy i przekazuje je przez własnego dostawcę e-fakturowania sprzedawcy.',

	// Altyapi hatalari.
	'Deklera requires PHP %1$s or later. This server is running PHP %2$s.' => 'Deklera wymaga PHP %1$s lub nowszego. Na tym serwerze działa PHP %2$s.',
	'Deklera dependencies are missing. Run "composer install" in the plugin directory.' => 'Brakuje zależności Deklery. Uruchom „composer install” w katalogu wtyczki.',

	// Siparis ekrani.
	'E-invoice' => 'E-faktura',
	'The official rule set rejected this document:' => 'Urzędowy zestaw reguł odrzucił ten dokument:',
	'No document generated yet.' => 'Nie wygenerowano jeszcze żadnego dokumentu.',
	'This order cannot be invoiced yet:' => 'Do tego zamówienia nie można jeszcze wystawić faktury:',
	'Version %d' => 'Wersja %d',
	'File missing or modified' => 'Brak pliku lub plik został zmieniony',
	'KSeF number:' => 'Numer KSeF:',
	'Not registered with KSeF yet — this file is not a legal invoice.' => 'Jeszcze niezarejestrowana w KSeF — ten plik nie jest fakturą w rozumieniu prawa.',
	'Generate new version' => 'Wygeneruj nową wersję',
	'Generate document' => 'Wygeneruj dokument',
	'History' => 'Historia',

	// Yetki ve hata.
	'You are not allowed to download this document.' => 'Nie masz uprawnień do pobrania tego dokumentu.',
	'Document not found.' => 'Nie znaleziono dokumentu.',
	'The archived file is missing or has been modified since it was created. It cannot be served.' => 'Zarchiwizowany plik nie istnieje lub został zmieniony po utworzeniu. Nie może zostać udostępniony.',
	'You are not allowed to generate documents.' => 'Nie masz uprawnień do generowania dokumentów.',
	'You are not allowed to change these settings.' => 'Nie masz uprawnień do zmiany tych ustawień.',
	'You are not allowed to run this scan.' => 'Nie masz uprawnień do uruchomienia tego sprawdzenia.',

	// On ucus ekrani.
	'Deklera e-invoicing' => 'E-fakturowanie Deklera',
	'Deklera — e-invoicing pre-flight check' => 'Deklera — kontrola wstępna e-faktur',
	'Settings saved. The check was run again.' => 'Ustawienia zapisane. Kontrola została uruchomiona ponownie.',
	'There are no completed orders to check yet. Come back once you have taken your first order.' => 'Nie ma jeszcze zrealizowanych zamówień do sprawdzenia. Wróć, gdy przyjmiesz pierwsze zamówienie.',
	'Checked' => 'Sprawdzone',
	'Would be rejected' => 'Zostałyby odrzucone',
	'Needs review' => 'Do przejrzenia',
	'Ready to invoice' => 'Gotowe do fakturowania',
	'Run the check again' => 'Uruchom kontrolę ponownie',
	'Fix these once, for the whole store' => 'Napraw raz, dla całego sklepu',
	'Order data that needs attention' => 'Dane zamówień wymagające uwagi',
	'How to fix:' => 'Jak naprawić:',
	'Rule:' => 'Reguła:',
	'Affected orders:' => 'Zamówienia, których to dotyczy:',
	'For information' => 'Informacyjnie',

	// Ayarlar.
	'Settings' => 'Ustawienia',
	'Your VAT number' => 'Twój numer VAT',
	'WooCommerce has no field for this, so Deklera stores it. Include the country prefix.' => 'WooCommerce nie ma na to pola, więc Deklera przechowuje go osobno. Podaj przedrostek kraju.',
	'Contact name on the invoice' => 'Osoba kontaktowa na fakturze',
	'Leave empty to use the store name. Germany requires this field to be present.' => 'Zostaw puste, aby użyć nazwy sklepu. Niemcy wymagają, aby to pole było wypełnione.',
	'Contact telephone number' => 'Numer telefonu kontaktowego',
	'Required for German invoices (XRechnung). There is no sensible default, so it cannot be filled in for you.' => 'Wymagany dla faktur niemieckich (XRechnung). Nie istnieje sensowna wartość domyślna, więc pole nie może zostać wypełnione za Ciebie.',
	'Save' => 'Zapisz',

	// KSeF.
	'KSeF (Poland)' => 'KSeF (Polska)',
	'An FA(3) invoice does not legally exist until KSeF has accepted it and assigned a number. Deklera sends each invoice automatically and records the number.' => 'Faktura FA(3) nie istnieje w świetle prawa, dopóki KSeF jej nie przyjmie i nie nada jej numeru. Deklera wysyła każdą fakturę automatycznie i zapisuje numer.',
	'KSeF token' => 'Token KSeF',
	'Saved — leave blank to keep it' => 'Zapisany — zostaw puste, aby go zachować',
	'Not set' => 'Nie ustawiono',
	'Generate the token in your KSeF account. It is stored on this site and never shown again.' => 'Wygeneruj token na swoim koncie KSeF. Jest przechowywany na tej stronie i nigdy nie zostanie ponownie wyświetlony.',
	'Environment' => 'Środowisko',
	'Test — invoices have no legal effect' => 'Testowe — faktury nie wywołują skutków prawnych',
	'Production — invoices are real' => 'Produkcyjne — faktury są rzeczywiste',
	'Start with Test. Invoices sent to Production are legally issued and cannot be withdrawn.' => 'Zacznij od środowiska testowego. Faktury wysłane na produkcję są wystawione w świetle prawa i nie można ich wycofać.',

	// Pro dogrulama.
	'Official validation (Pro)' => 'Walidacja urzędowa (Pro)',
	'Validation against the official EN 16931 rule set requires the Pro plan. It cannot run inside WordPress because the rule set needs XSLT 2.0, which PHP does not support.' => 'Walidacja wobec oficjalnego zestawu reguł EN 16931 wymaga planu Pro. Nie może działać wewnątrz WordPressa, ponieważ zestaw reguł wymaga XSLT 2.0, którego PHP nie obsługuje.',
	'One step left: paste your validation key below. It is in the email you received when you bought Pro. The service address is already filled in.' => 'Został jeden krok: wklej poniżej swój klucz walidacji. Znajdziesz go w wiadomości e-mail otrzymanej przy zakupie Pro. Adres usługi jest już uzupełniony.',
	'Read the setup guide' => 'Przeczytaj przewodnik konfiguracji',
	'Validation service address' => 'Adres usługi walidacji',
	'Already set to the service run by the plugin author. Change it only if you run your own copy of it.' => 'Ustawiony na usługę prowadzoną przez autora wtyczki. Zmień go tylko wtedy, gdy prowadzisz własną instancję.',
	'Validation key' => 'Klucz walidacji',
	'From your Pro purchase email. This is not the licence key that activated the plugin.' => 'Z wiadomości e-mail o zakupie Pro. To nie jest klucz licencyjny, którym aktywowano wtyczkę.',
	'Saved. Leave empty to keep it.' => 'Zapisany. Zostaw puste, aby go zachować.',

	// KDV kategorileri.
	'Standard rate' => 'Stawka podstawowa',
	'Zero rated goods' => 'Towary opodatkowane stawką zerową',
	'Exempt from VAT' => 'Zwolnione z VAT',
	'VAT reverse charge' => 'Odwrotne obciążenie VAT',
	'VAT exempt intra-community supply' => 'Wewnątrzwspólnotowa dostawa towarów zwolniona z VAT',
	'Export, outside the scope of VAT' => 'Eksport, poza zakresem VAT',
	'Services outside the scope of VAT' => 'Usługi poza zakresem VAT',
	'Canary Islands general indirect tax' => 'Ogólny podatek pośredni Wysp Kanaryjskich (IGIC)',
	'Ceuta and Melilla tax' => 'Podatek Ceuty i Melilli (IPSI)',

	// Belge turleri.
	'Commercial invoice' => 'Faktura handlowa',
	'Credit note' => 'Faktura korygująca',
	'Corrected invoice' => 'Faktura skorygowana',
	'Self-billed invoice' => 'Faktura wystawiona przez nabywcę (samofakturowanie)',
	'Refund' => 'Zwrot',

	// Fatura uzerindeki yasal ibareler.
	'Reverse charge: VAT is due from the recipient.' => 'Odwrotne obciążenie: VAT rozlicza nabywca.',
	'Intra-Community supply, exempt under Article 138 of Council Directive 2006/112/EC.' => 'Wewnątrzwspólnotowa dostawa towarów, zwolniona na podstawie art. 138 dyrektywy Rady 2006/112/WE.',
	'Export outside the European Union, exempt from VAT.' => 'Eksport poza Unię Europejską, zwolniony z VAT.',
	'Exempt from VAT.' => 'Zwolnione z VAT.',
	'Not subject to VAT.' => 'Niepodlegające opodatkowaniu VAT.',

	// Vergi kararlarinin gerekcesi.
	'The store is based outside the EU, so the supply is outside the scope of EU VAT.' => 'Sklep ma siedzibę poza UE, więc dostawa pozostaje poza zakresem unijnego VAT.',
	'Domestic sale with a VAT rate applied.' => 'Sprzedaż krajowa z zastosowaną stawką VAT.',
	'Cross-border sale to an EU consumer, taxed at the destination rate under OSS.' => 'Sprzedaż transgraniczna do konsumenta z UE, opodatkowana stawką kraju przeznaczenia w ramach OSS.',
	'The buyer is outside the EU, so the supply is treated as an export.' => 'Nabywca znajduje się poza UE, więc dostawę traktuje się jako eksport.',
	'Assumed to be a service because every item is virtual or downloadable.' => 'Uznano za usługę, ponieważ wszystkie pozycje są wirtualne lub do pobrania.',
	'Assumed to be goods because the order contains shippable items.' => 'Uznano za towary, ponieważ zamówienie zawiera pozycje podlegające wysyłce.',
	'Cross-border EU sale with no VAT and no valid buyer VAT number.' => 'Transgraniczna sprzedaż w UE bez VAT i bez ważnego numeru VAT nabywcy.',
	'Domestic sale with no VAT applied.' => 'Sprzedaż krajowa bez zastosowanego VAT.',

	// PDF sablonu — bunlari ALICI okur.
	'Deklera built-in template' => 'Wbudowany szablon Deklery',
	'Invoice' => 'Faktura',
	'Number' => 'Numer',
	'Date' => 'Data',
	'VAT number' => 'Numer VAT',
	'Bill to' => 'Nabywca',
	'Description' => 'Nazwa',
	'Qty' => 'Ilość',
	'Unit price' => 'Cena jedn.',
	'VAT' => 'VAT',
	'Net' => 'Netto',
	'Net total' => 'Razem netto',
	'Total' => 'Razem brutto',

	// Alici kimligi kurallari.
	'Customer identity' => 'Dane nabywcy',
	'The order has no customer name.' => 'Zamówienie nie zawiera nazwy nabywcy.',
	'The buyer name is mandatory on every invoice.' => 'Nazwa nabywcy jest obowiązkowa na każdej fakturze.',
	'Open the order and fill in the billing name or company.' => 'Otwórz zamówienie i uzupełnij nazwisko lub nazwę firmy do faktury.',
	'The order has no billing country.' => 'Zamówienie nie zawiera kraju do faktury.',
	'The buyer country determines the VAT treatment. Without it the tax category cannot be decided.' => 'Kraj nabywcy decyduje o sposobie opodatkowania VAT. Bez niego nie można ustalić kategorii podatkowej.',
	'Open the order and set the billing country.' => 'Otwórz zamówienie i ustaw kraj do faktury.',
	'This is a cross-border EU business sale with no VAT, but the customer VAT number is missing.' => 'To transgraniczna sprzedaż firmowa w UE bez VAT, ale brakuje numeru VAT nabywcy.',
	'When VAT is not charged on an intra-community supply, the buyer VAT identifier is mandatory. Without it the exemption cannot be justified and the invoice is rejected.' => 'Gdy przy wewnątrzwspólnotowej dostawie nie nalicza się VAT, numer VAT nabywcy jest obowiązkowy. Bez niego nie da się uzasadnić zwolnienia, a faktura zostaje odrzucona.',
	'Open the order and add the VAT number to the billing details, then start collecting it at checkout.' => 'Otwórz zamówienie, dodaj numer VAT do danych do faktury, a następnie zacznij zbierać go przy składaniu zamówienia.',
	'The customer VAT number "%s" is not in a valid EU format.' => 'Numer VAT nabywcy „%s” nie ma prawidłowego formatu unijnego.',
	'An EU VAT number starts with the two-letter country code, for example DE123456789.' => 'Unijny numer VAT zaczyna się od dwuliterowego kodu kraju, na przykład DE123456789.',
	'Open the order and correct the VAT number in the billing details.' => 'Otwórz zamówienie i popraw numer VAT w danych do faktury.',
	'The VAT number is registered in %1$s but the billing country is %2$s.' => 'Numer VAT jest zarejestrowany w %1$s, a kraj do faktury to %2$s.',
	'A mismatch is legitimate for branch offices, but it is also a common sign of a mistyped VAT number.' => 'Rozbieżność jest uzasadniona w przypadku oddziałów, ale bywa też częstym objawem błędnie wpisanego numeru VAT.',
	'Open the order and confirm both values with the customer.' => 'Otwórz zamówienie i potwierdź obie wartości z nabywcą.',

	// Fatura temelleri.
	'Invoice essentials' => 'Podstawowe elementy faktury',
	'The order has no invoice number.' => 'Zamówienie nie ma numeru faktury.',
	'Every e-invoice must carry a unique invoice number. Without it the document cannot be issued.' => 'Każda e-faktura musi mieć niepowtarzalny numer. Bez niego dokumentu nie można wystawić.',
	'Check your order numbering plugin, or set a number with the deklera/invoice_number filter.' => 'Sprawdź wtyczkę numerującą zamówienia albo ustaw numer filtrem deklera/invoice_number.',
	'The order currency "%s" is not a valid ISO 4217 code.' => 'Waluta zamówienia „%s” nie jest prawidłowym kodem ISO 4217.',
	'The invoice currency must be a three-letter ISO 4217 code such as EUR or PLN.' => 'Waluta faktury musi być trzyliterowym kodem ISO 4217, na przykład EUR lub PLN.',
	'Fix the store currency under WooCommerce > Settings > General.' => 'Popraw walutę sklepu w WooCommerce > Ustawienia > Ogólne.',
	'The order has no billable lines.' => 'Zamówienie nie zawiera pozycji do zafakturowania.',
	'An invoice must contain at least one line. Orders with only zero-value items cannot be invoiced.' => 'Faktura musi zawierać co najmniej jedną pozycję. Zamówień złożonych wyłącznie z pozycji o wartości zero nie można zafakturować.',
	'Open the order and check that it still contains its products.' => 'Otwórz zamówienie i sprawdź, czy nadal zawiera swoje produkty.',

	// Ulusal profil.
	'National profile requirements' => 'Wymagania profilu krajowego',
	'Your store has no billing phone number.' => 'Twój sklep nie ma numeru telefonu do faktury.',
	"Germany's XRechnung profile makes the seller contact phone mandatory (BR-DE-6). Without it the official validator rejects the invoice, even though the EU standard itself allows it to be empty." => 'Niemiecki profil XRechnung czyni telefon kontaktowy sprzedawcy obowiązkowym (BR-DE-6). Bez niego urzędowy walidator odrzuca fakturę, mimo że sama norma unijna dopuszcza puste pole.',
	'Add it in the Settings box at the bottom of this page.' => 'Dodaj go w polu Ustawienia na dole tej strony.',

	// Magaza kimligi.
	'Store identity' => 'Dane sklepu',
	'Your store has no business name.' => 'Twój sklep nie ma nazwy firmy.',
	'The seller name appears on every invoice and is mandatory.' => 'Nazwa sprzedawcy pojawia się na każdej fakturze i jest obowiązkowa.',
	'Set the site title under Settings > General.' => 'Ustaw tytuł witryny w Ustawienia > Ogólne.',
	'Your store has no country set.' => 'Twój sklep nie ma ustawionego kraju.',
	'The seller country decides which national e-invoicing rules apply to you. Nothing can be generated without it.' => 'Kraj sprzedawcy decyduje, które krajowe przepisy o e-fakturowaniu Cię obowiązują. Bez niego nic nie zostanie wygenerowane.',
	'Set it under WooCommerce > Settings > General > Store address.' => 'Ustaw go w WooCommerce > Ustawienia > Ogólne > Adres sklepu.',
	'Your store postal address is incomplete.' => 'Adres pocztowy Twojego sklepu jest niekompletny.',
	'The seller street address and city are mandatory on every EU e-invoice.' => 'Ulica i miejscowość sprzedawcy są obowiązkowe na każdej unijnej e-fakturze.',
	'Complete the address under WooCommerce > Settings > General > Store address.' => 'Uzupełnij adres w WooCommerce > Ustawienia > Ogólne > Adres sklepu.',
	'Your store has no VAT number configured.' => 'Twój sklep nie ma skonfigurowanego numeru VAT.',
	'A seller VAT identifier is required on EU e-invoices. WooCommerce has no field for it, so Deklera stores it separately.' => 'Numer VAT sprzedawcy jest wymagany na unijnych e-fakturach. WooCommerce nie ma na to pola, więc Deklera przechowuje go osobno.',
	'Your store VAT number "%s" is not in a valid EU format.' => 'Numer VAT Twojego sklepu „%s” nie ma prawidłowego formatu unijnego.',
	'An EU VAT number starts with the two-letter country code, for example FR12345678901.' => 'Unijny numer VAT zaczyna się od dwuliterowego kodu kraju, na przykład FR12345678901.',
	'Correct it in the Settings box at the bottom of this page.' => 'Popraw go w polu Ustawienia na dole tej strony.',

	// KDV tutarliligi.
	'VAT category and rate' => 'Kategoria i stawka VAT',
	'VAT category %1$s is used together with a rate of %2$s%%.' => 'Kategoria VAT %1$s została użyta ze stawką %2$s%%.',
	'Categories that carry no VAT must have a rate of exactly zero. A non-zero rate makes the invoice internally inconsistent.' => 'Kategorie bez VAT muszą mieć stawkę dokładnie zerową. Stawka różna od zera czyni fakturę wewnętrznie sprzeczną.',
	'Review the tax rates under WooCommerce > Settings > Tax for this customer country.' => 'Przejrzyj stawki podatku dla tego kraju nabywcy w WooCommerce > Ustawienia > Podatek.',
	'A standard-rate VAT line carries a rate of zero.' => 'Pozycja ze stawką podstawową ma stawkę zerową.',
	'The standard category requires a rate above zero. A zero rate here usually means no tax rule matched the customer address.' => 'Kategoria podstawowa wymaga stawki większej od zera. Stawka zerowa zwykle oznacza, że żadna reguła podatkowa nie pasowała do adresu nabywcy.',
	'Check that a tax rate exists for this country under WooCommerce > Settings > Tax.' => 'Sprawdź, czy dla tego kraju istnieje stawka w WooCommerce > Ustawienia > Podatek.',
	'No VAT was charged on a sale to a private customer in %s.' => 'Przy sprzedaży osobie prywatnej w %s nie naliczono VAT.',
	'Under the One Stop Shop rules a sale to an EU consumer is taxed at the rate of their country. Charging no VAT means either the tax rate is missing or the exemption cannot be justified.' => 'Zgodnie z zasadami One Stop Shop sprzedaż konsumentowi z UE opodatkowana jest stawką jego kraju. Brak naliczonego VAT oznacza, że albo brakuje stawki, albo zwolnienia nie da się uzasadnić.',
	'Add a tax rate for this country under WooCommerce > Settings > Tax, or confirm you are below the OSS threshold.' => 'Dodaj stawkę dla tego kraju w WooCommerce > Ustawienia > Podatek albo potwierdź, że jesteś poniżej progu OSS.',
	'This order was treated as an intra-community service.' => 'To zamówienie potraktowano jako wewnątrzwspólnotowe świadczenie usług.',
	'This order was treated as an intra-community supply of goods.' => 'To zamówienie potraktowano jako wewnątrzwspólnotową dostawę towarów.',
	'Goods use category K and services use category AE. Deklera decides this from whether the items are shippable or virtual, which is a reasonable guess but not always right.' => 'Towary mają kategorię K, a usługi kategorię AE. Deklera rozstrzyga to na podstawie tego, czy pozycje podlegają wysyłce, czy są wirtualne — to rozsądne założenie, ale nie zawsze trafne.',
	'If the classification is wrong, override it with the deklera/tax_category filter.' => 'Jeśli klasyfikacja jest błędna, nadpisz ją filtrem deklera/tax_category.',

	// Toplamlar.
	'Invoice totals' => 'Sumy faktury',
	'The invoice lines add up to %1$s but the order total is %2$s, a difference of %3$s.' => 'Pozycje faktury sumują się do %1$s, a suma zamówienia wynosi %2$s — różnica %3$s.',
	'Validators reject an invoice whose totals do not reconcile, even by one cent. This usually comes from an order-level discount, a coupon or a refund that is not represented on any line.' => 'Walidatory odrzucają fakturę, której sumy się nie zgadzają, choćby o grosz. Zwykle wynika to z rabatu na poziomie zamówienia, kuponu albo zwrotu, który nie ma odzwierciedlenia w żadnej pozycji.',
	'Open the order and compare the line totals with the order total.' => 'Otwórz zamówienie i porównaj sumy pozycji z sumą zamówienia.',
	'The VAT breakdown totals %1$s but WooCommerce recorded %2$s.' => 'Zestawienie VAT sumuje się do %1$s, a WooCommerce zapisało %2$s.',
	'The sum of the VAT breakdown must equal the VAT charged. A gap means a rate was applied that Deklera could not reconstruct from the order.' => 'Suma zestawienia VAT musi być równa naliczonemu VAT. Rozbieżność oznacza, że zastosowano stawkę, której Deklera nie zdołała odtworzyć z zamówienia.',
	'Review the tax lines on the order and the rounding setting under WooCommerce > Settings > Tax.' => 'Przejrzyj pozycje podatkowe zamówienia oraz ustawienie zaokrąglania w WooCommerce > Ustawienia > Podatek.',

	// Denetim izi.
	'Document generated' => 'Dokument wygenerowany',
	'Generation failed' => 'Generowanie nie powiodło się',
	'Document downloaded' => 'Dokument pobrany',
	'Queued for generation' => 'W kolejce do wygenerowania',
	'Rejected by official validation' => 'Odrzucony przez walidację urzędową',
	'Attached to customer email' => 'Załączony do wiadomości do klienta',
	'Removed after the retention period' => 'Usunięty po okresie przechowywania',
	'Sent to KSeF' => 'Wysłany do KSeF',
	'Registered by KSeF' => 'Zarejestrowany przez KSeF',
	'Rejected by KSeF' => 'Odrzucony przez KSeF',

	// Belge dili kurali.
	'Invoice language' => 'Język faktury',
	'This invoice would be issued in your store language, not %s.' => 'Ta faktura zostałaby wystawiona w języku Twojego sklepu, a nie w %s.',
	'Deklera writes the invoice in the buyer\'s language and ships German, French and Polish. WordPress can only switch to a language it has installed, so without the language pack the document falls back to your store language.' => 'Deklera sporządza fakturę w języku nabywcy i zawiera niemiecki, francuski oraz polski. WordPress może przełączyć się tylko na zainstalowany język; bez pakietu językowego dokument wraca do języka Twojego sklepu.',
	'Install it under Settings > General > Site Language, or add it under Dashboard > Updates. The site needs to reach WordPress.org once to download it.' => 'Zainstaluj go w Ustawienia > Ogólne > Język witryny albo dodaj w Kokpit > Aktualizacje. Witryna musi raz połączyć się z WordPress.org, aby go pobrać.',

	'__cogul__' => array(
		'All %s recent order would be accepted.' => array(
			'%s ostatnie zamówienie zostałoby przyjęte.',
			'Wszystkie %s ostatnie zamówienia zostałyby przyjęte.',
			'Wszystkie %s ostatnich zamówień zostałoby przyjętych.',
		),
		'%1$s of your last %2$s orders would be rejected.' => array(
			'%1$s z Twoich ostatnich %2$s zamówień zostałoby odrzucone.',
			'%1$s z Twoich ostatnich %2$s zamówień zostałyby odrzucone.',
			'%1$s z Twoich ostatnich %2$s zamówień zostałoby odrzuconych.',
		),
		'%s order' => array(
			'%s zamówienie',
			'%s zamówienia',
			'%s zamówień',
		),
		'and %s more' => array(
			'i %s więcej',
			'i %s więcej',
			'i %s więcej',
		),
	),
);
