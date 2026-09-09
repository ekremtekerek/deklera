<?php
/**
 * Almanca ceviri eslemesi.
 *
 * TERMINOLOJI NOTLARI (degistirmeden once okuyun)
 *
 * - "Credit note" -> "Rechnungskorrektur". "Gutschrift" DEGIL: Almancada
 *   Gutschrift hem duzeltme belgesini hem de alicinin kestigi faturayi
 *   (Selbstabrechnung) anlatir. UStG 14 Abs. 2 anlaminda Gutschrift
 *   ikincisidir; belge turu 381 icin yanlis olur.
 * - "VAT number" -> "USt-IdNr." (Umsatzsteuer-Identifikationsnummer).
 *   "Steuernummer" farkli bir numaradir ve AB faturasinda kullanilmaz.
 * - "Reverse charge" -> "Steuerschuldnerschaft des Leistungsempfangers".
 *   Fatura uzerinde gorunmesi gereken yasal ibare budur.
 * - Belge dizgeleri (Rechnung, Menge, Netto...) ALICININ gozune gider;
 *   yonetici dizgelerinden farkli olarak kisa ve resmi tutulur.
 *
 * @package Deklera
 */

return array(
	// Eklenti kimligi.
	'Deklera' => 'Deklera',
	'https://github.com/ekremtekerek/deklera' => 'https://github.com/ekremtekerek/deklera',
	'Hasan Ekrem Tekerek' => 'Hasan Ekrem Tekerek',
	"Turns WooCommerce orders into legally valid e-invoices for the seller's country and delivers them through the seller's own e-invoicing provider." => 'Wandelt WooCommerce-Bestellungen in rechtsgültige E-Rechnungen für das Land des Verkäufers um und übermittelt sie über dessen eigenen E-Rechnungs-Dienst.',

	// Altyapi hatalari.
	'Deklera requires PHP %1$s or later. This server is running PHP %2$s.' => 'Deklera benötigt PHP %1$s oder neuer. Auf diesem Server läuft PHP %2$s.',
	'Deklera dependencies are missing. Run "composer install" in the plugin directory.' => 'Die Abhängigkeiten von Deklera fehlen. Führen Sie „composer install“ im Plugin-Verzeichnis aus.',

	// Siparis ekrani.
	'E-invoice' => 'E-Rechnung',
	'The official rule set rejected this document:' => 'Das offizielle Regelwerk hat dieses Dokument abgelehnt:',
	'No document generated yet.' => 'Noch kein Dokument erzeugt.',
	'This order cannot be invoiced yet:' => 'Für diese Bestellung kann noch keine Rechnung erstellt werden:',
	'Version %d' => 'Version %d',
	'File missing or modified' => 'Datei fehlt oder wurde verändert',
	'KSeF number:' => 'KSeF-Nummer:',
	'Not registered with KSeF yet — this file is not a legal invoice.' => 'Noch nicht bei KSeF registriert – diese Datei ist keine rechtsgültige Rechnung.',
	'Generate new version' => 'Neue Version erzeugen',
	'Generate document' => 'Dokument erzeugen',
	'History' => 'Verlauf',

	// Yetki ve hata.
	'You are not allowed to download this document.' => 'Sie sind nicht berechtigt, dieses Dokument herunterzuladen.',
	'Document not found.' => 'Dokument nicht gefunden.',
	'The archived file is missing or has been modified since it was created. It cannot be served.' => 'Die archivierte Datei fehlt oder wurde seit ihrer Erstellung verändert. Sie kann nicht ausgeliefert werden.',
	'You are not allowed to generate documents.' => 'Sie sind nicht berechtigt, Dokumente zu erzeugen.',
	'You are not allowed to change these settings.' => 'Sie sind nicht berechtigt, diese Einstellungen zu ändern.',
	'You are not allowed to run this scan.' => 'Sie sind nicht berechtigt, diese Prüfung auszuführen.',

	// On ucus ekrani.
	'Deklera e-invoicing' => 'Deklera E-Rechnung',
	'Deklera — e-invoicing pre-flight check' => 'Deklera – Vorabprüfung der E-Rechnungen',
	'Settings saved. The check was run again.' => 'Einstellungen gespeichert. Die Prüfung wurde erneut ausgeführt.',
	'There are no completed orders to check yet. Come back once you have taken your first order.' => 'Es gibt noch keine abgeschlossenen Bestellungen zum Prüfen. Kommen Sie wieder, sobald Sie Ihre erste Bestellung erhalten haben.',
	'Checked' => 'Geprüft',
	'Would be rejected' => 'Würde abgelehnt',
	'Needs review' => 'Zu prüfen',
	'Ready to invoice' => 'Bereit zur Rechnungsstellung',
	'Run the check again' => 'Prüfung erneut ausführen',
	'Fix these once, for the whole store' => 'Einmal beheben – gilt für den ganzen Shop',
	'Order data that needs attention' => 'Bestelldaten, die Aufmerksamkeit brauchen',
	'How to fix:' => 'So beheben Sie es:',
	'Rule:' => 'Regel:',
	'Affected orders:' => 'Betroffene Bestellungen:',
	'For information' => 'Zur Information',

	// Ayarlar.
	'Settings' => 'Einstellungen',
	'Your VAT number' => 'Ihre USt-IdNr.',
	'WooCommerce has no field for this, so Deklera stores it. Include the country prefix.' => 'WooCommerce hat dafür kein Feld, deshalb speichert Deklera sie. Geben Sie das Länderkürzel mit an.',
	'Contact name on the invoice' => 'Ansprechpartner auf der Rechnung',
	'Leave empty to use the store name. Germany requires this field to be present.' => 'Leer lassen, um den Shop-Namen zu verwenden. In Deutschland muss dieses Feld vorhanden sein.',
	'Contact telephone number' => 'Telefonnummer des Ansprechpartners',
	'Required for German invoices (XRechnung). There is no sensible default, so it cannot be filled in for you.' => 'Für deutsche Rechnungen (XRechnung) vorgeschrieben. Es gibt keinen sinnvollen Standardwert, deshalb kann das Feld nicht für Sie ausgefüllt werden.',
	'Save' => 'Speichern',

	// KSeF.
	'KSeF (Poland)' => 'KSeF (Polen)',
	'An FA(3) invoice does not legally exist until KSeF has accepted it and assigned a number. Deklera sends each invoice automatically and records the number.' => 'Eine FA(3)-Rechnung existiert rechtlich erst, wenn KSeF sie angenommen und eine Nummer vergeben hat. Deklera sendet jede Rechnung automatisch und hält die Nummer fest.',
	'KSeF token' => 'KSeF-Token',
	'Saved — leave blank to keep it' => 'Gespeichert – leer lassen, um ihn zu behalten',
	'Not set' => 'Nicht gesetzt',
	'Generate the token in your KSeF account. It is stored on this site and never shown again.' => 'Erzeugen Sie den Token in Ihrem KSeF-Konto. Er wird auf dieser Website gespeichert und nie wieder angezeigt.',
	'Environment' => 'Umgebung',
	'Test — invoices have no legal effect' => 'Test – Rechnungen haben keine Rechtswirkung',
	'Production — invoices are real' => 'Produktiv – Rechnungen sind echt',
	'Start with Test. Invoices sent to Production are legally issued and cannot be withdrawn.' => 'Beginnen Sie mit Test. In der Produktivumgebung übermittelte Rechnungen gelten als ausgestellt und können nicht zurückgezogen werden.',

	// Pro dogrulama.
	'Official validation (Pro)' => 'Offizielle Validierung (Pro)',
	'Validation against the official EN 16931 rule set requires the Pro plan. It cannot run inside WordPress because the rule set needs XSLT 2.0, which PHP does not support.' => 'Die Prüfung gegen das offizielle EN-16931-Regelwerk erfordert den Pro-Tarif. Sie kann nicht in WordPress laufen, weil das Regelwerk XSLT 2.0 benötigt, das PHP nicht unterstützt.',
	'Read the setup guide' => 'Einrichtungsanleitung lesen',
	'Validation service address' => 'Adresse des Validierungsdienstes',
	'Already set to the service run by the plugin author. Change it only if you run your own copy of it.' => 'Voreingestellt auf den vom Plugin-Autor betriebenen Dienst. Ändern Sie den Wert nur, wenn Sie eine eigene Instanz betreiben.',
	'Validation key' => 'Validierungsschlüssel',
	'Saved. Leave empty to keep it.' => 'Gespeichert. Leer lassen, um ihn zu behalten.',
	'Official validation is on. Your licence authorises it — there is nothing to set up here.' => 'Die offizielle Prüfung ist aktiv. Ihre Lizenz berechtigt dazu — hier ist nichts einzurichten.',
	'Validation is not authorised yet. Activate your licence, or enter a key below if you run your own copy of the service.' => 'Die Prüfung ist noch nicht freigeschaltet. Aktivieren Sie Ihre Lizenz, oder tragen Sie unten einen Schlüssel ein, wenn Sie eine eigene Kopie des Dienstes betreiben.',
	'Leave empty. Only needed if you run your own copy of the service.' => 'Leer lassen. Nur nötig, wenn Sie eine eigene Kopie des Dienstes betreiben.',

	// KDV kategorileri.
	'Standard rate' => 'Regelsteuersatz',
	'Zero rated goods' => 'Nullsatzbesteuerte Lieferung',
	'Exempt from VAT' => 'Von der Umsatzsteuer befreit',
	'VAT reverse charge' => 'Steuerschuldnerschaft des Leistungsempfängers',
	'VAT exempt intra-community supply' => 'Steuerfreie innergemeinschaftliche Lieferung',
	'Export, outside the scope of VAT' => 'Ausfuhr, nicht im Anwendungsbereich der Umsatzsteuer',
	'Services outside the scope of VAT' => 'Leistungen außerhalb des Anwendungsbereichs der Umsatzsteuer',
	'Canary Islands general indirect tax' => 'Allgemeine indirekte Steuer der Kanarischen Inseln (IGIC)',
	'Ceuta and Melilla tax' => 'Steuer von Ceuta und Melilla (IPSI)',

	// Belge turleri.
	'Commercial invoice' => 'Handelsrechnung',
	'Credit note' => 'Rechnungskorrektur',
	'Corrected invoice' => 'Berichtigte Rechnung',
	'Self-billed invoice' => 'Gutschrift (Selbstabrechnung)',
	'Refund' => 'Erstattung',

	// Fatura uzerindeki yasal ibareler.
	'Reverse charge: VAT is due from the recipient.' => 'Steuerschuldnerschaft des Leistungsempfängers: Die Umsatzsteuer schuldet der Empfänger.',
	'Intra-Community supply, exempt under Article 138 of Council Directive 2006/112/EC.' => 'Innergemeinschaftliche Lieferung, steuerfrei nach Artikel 138 der Richtlinie 2006/112/EG des Rates.',
	'Export outside the European Union, exempt from VAT.' => 'Ausfuhr in ein Drittland außerhalb der Europäischen Union, von der Umsatzsteuer befreit.',
	'Exempt from VAT.' => 'Von der Umsatzsteuer befreit.',
	'Not subject to VAT.' => 'Nicht der Umsatzsteuer unterliegend.',

	// Vergi kararlarinin gerekcesi.
	'The store is based outside the EU, so the supply is outside the scope of EU VAT.' => 'Der Shop hat seinen Sitz außerhalb der EU, daher fällt die Lieferung nicht in den Anwendungsbereich der EU-Umsatzsteuer.',
	'Domestic sale with a VAT rate applied.' => 'Inlandsverkauf mit angewandtem Umsatzsteuersatz.',
	'Cross-border sale to an EU consumer, taxed at the destination rate under OSS.' => 'Grenzüberschreitender Verkauf an einen EU-Verbraucher, besteuert zum Satz des Bestimmungslandes im Rahmen des OSS.',
	'The buyer is outside the EU, so the supply is treated as an export.' => 'Der Käufer befindet sich außerhalb der EU, daher gilt die Lieferung als Ausfuhr.',
	'Assumed to be a service because every item is virtual or downloadable.' => 'Als Dienstleistung eingestuft, weil alle Positionen virtuell oder herunterladbar sind.',
	'Assumed to be goods because the order contains shippable items.' => 'Als Lieferung von Gegenständen eingestuft, weil die Bestellung versandfähige Positionen enthält.',
	'Cross-border EU sale with no VAT and no valid buyer VAT number.' => 'Grenzüberschreitender EU-Verkauf ohne Umsatzsteuer und ohne gültige USt-IdNr. des Käufers.',
	'Domestic sale with no VAT applied.' => 'Inlandsverkauf ohne angewandte Umsatzsteuer.',

	// PDF sablonu — bunlari ALICI okur.
	'Deklera built-in template' => 'Mitgeliefertes Deklera-Layout',
	'Invoice' => 'Rechnung',
	'Number' => 'Nummer',
	'Date' => 'Datum',
	'VAT number' => 'USt-IdNr.',
	'Bill to' => 'Rechnungsempfänger',
	'Description' => 'Bezeichnung',
	'Qty' => 'Menge',
	'Unit price' => 'Einzelpreis',
	'VAT' => 'USt.',
	'Net' => 'Netto',
	'Net total' => 'Nettobetrag',
	'Total' => 'Gesamtbetrag',

	// Alici kimligi kurallari.
	'Customer identity' => 'Identität des Kunden',
	'The order has no customer name.' => 'Die Bestellung enthält keinen Kundennamen.',
	'The buyer name is mandatory on every invoice.' => 'Der Name des Käufers ist auf jeder Rechnung Pflicht.',
	'Open the order and fill in the billing name or company.' => 'Öffnen Sie die Bestellung und tragen Sie den Rechnungsnamen oder die Firma ein.',
	'The order has no billing country.' => 'Die Bestellung enthält kein Rechnungsland.',
	'The buyer country determines the VAT treatment. Without it the tax category cannot be decided.' => 'Das Land des Käufers bestimmt die umsatzsteuerliche Behandlung. Ohne es lässt sich die Steuerkategorie nicht festlegen.',
	'Open the order and set the billing country.' => 'Öffnen Sie die Bestellung und setzen Sie das Rechnungsland.',
	'This is a cross-border EU business sale with no VAT, but the customer VAT number is missing.' => 'Dies ist ein grenzüberschreitender EU-Geschäftsverkauf ohne Umsatzsteuer, aber die USt-IdNr. des Kunden fehlt.',
	'When VAT is not charged on an intra-community supply, the buyer VAT identifier is mandatory. Without it the exemption cannot be justified and the invoice is rejected.' => 'Wird bei einer innergemeinschaftlichen Lieferung keine Umsatzsteuer berechnet, ist die USt-IdNr. des Käufers Pflicht. Ohne sie lässt sich die Steuerbefreiung nicht begründen und die Rechnung wird abgelehnt.',
	'Open the order and add the VAT number to the billing details, then start collecting it at checkout.' => 'Öffnen Sie die Bestellung, ergänzen Sie die USt-IdNr. in den Rechnungsdaten und erfassen Sie sie künftig an der Kasse.',
	'The customer VAT number "%s" is not in a valid EU format.' => 'Die USt-IdNr. „%s“ des Kunden hat kein gültiges EU-Format.',
	'An EU VAT number starts with the two-letter country code, for example DE123456789.' => 'Eine EU-USt-IdNr. beginnt mit dem zweistelligen Länderkürzel, zum Beispiel DE123456789.',
	'Open the order and correct the VAT number in the billing details.' => 'Öffnen Sie die Bestellung und korrigieren Sie die USt-IdNr. in den Rechnungsdaten.',
	'The VAT number is registered in %1$s but the billing country is %2$s.' => 'Die USt-IdNr. ist in %1$s registriert, das Rechnungsland ist jedoch %2$s.',
	'A mismatch is legitimate for branch offices, but it is also a common sign of a mistyped VAT number.' => 'Bei Niederlassungen ist eine Abweichung zulässig, sie ist aber auch ein häufiges Anzeichen für einen Tippfehler in der USt-IdNr.',
	'Open the order and confirm both values with the customer.' => 'Öffnen Sie die Bestellung und bestätigen Sie beide Angaben mit dem Kunden.',

	// Fatura temelleri.
	'Invoice essentials' => 'Rechnungsgrundlagen',
	'The order has no invoice number.' => 'Die Bestellung hat keine Rechnungsnummer.',
	'Every e-invoice must carry a unique invoice number. Without it the document cannot be issued.' => 'Jede E-Rechnung muss eine eindeutige Rechnungsnummer tragen. Ohne sie kann das Dokument nicht ausgestellt werden.',
	'Check your order numbering plugin, or set a number with the deklera/invoice_number filter.' => 'Prüfen Sie Ihr Plugin für die Bestellnummerierung oder setzen Sie eine Nummer über den Filter deklera/invoice_number.',
	'The order currency "%s" is not a valid ISO 4217 code.' => 'Die Bestellwährung „%s“ ist kein gültiger ISO-4217-Code.',
	'The invoice currency must be a three-letter ISO 4217 code such as EUR or PLN.' => 'Die Rechnungswährung muss ein dreistelliger ISO-4217-Code wie EUR oder PLN sein.',
	'Fix the store currency under WooCommerce > Settings > General.' => 'Korrigieren Sie die Shop-Währung unter WooCommerce > Einstellungen > Allgemein.',
	'The order has no billable lines.' => 'Die Bestellung enthält keine abrechenbaren Positionen.',
	'An invoice must contain at least one line. Orders with only zero-value items cannot be invoiced.' => 'Eine Rechnung muss mindestens eine Position enthalten. Bestellungen mit ausschließlich wertlosen Positionen können nicht abgerechnet werden.',
	'Open the order and check that it still contains its products.' => 'Öffnen Sie die Bestellung und prüfen Sie, ob sie ihre Produkte noch enthält.',

	// Ulusal profil.
	'National profile requirements' => 'Anforderungen des nationalen Profils',
	'Your store has no billing phone number.' => 'Für Ihren Shop ist keine Telefonnummer hinterlegt.',
	"Germany's XRechnung profile makes the seller contact phone mandatory (BR-DE-6). Without it the official validator rejects the invoice, even though the EU standard itself allows it to be empty." => 'Das deutsche XRechnung-Profil macht die Telefonnummer des Verkäufers zur Pflicht (BR-DE-6). Ohne sie weist der offizielle Prüfdienst die Rechnung zurück, obwohl der EU-Standard selbst ein leeres Feld zulässt.',
	'Add it in the Settings box at the bottom of this page.' => 'Tragen Sie sie im Einstellungsfeld unten auf dieser Seite ein.',

	// Magaza kimligi.
	'Store identity' => 'Identität des Shops',
	'Your store has no business name.' => 'Für Ihren Shop ist kein Firmenname hinterlegt.',
	'The seller name appears on every invoice and is mandatory.' => 'Der Name des Verkäufers erscheint auf jeder Rechnung und ist Pflicht.',
	'Set the site title under Settings > General.' => 'Setzen Sie den Website-Titel unter Einstellungen > Allgemein.',
	'Your store has no country set.' => 'Für Ihren Shop ist kein Land hinterlegt.',
	'The seller country decides which national e-invoicing rules apply to you. Nothing can be generated without it.' => 'Das Land des Verkäufers entscheidet, welche nationalen E-Rechnungs-Regeln für Sie gelten. Ohne es kann nichts erzeugt werden.',
	'Set it under WooCommerce > Settings > General > Store address.' => 'Legen Sie es unter WooCommerce > Einstellungen > Allgemein > Shop-Adresse fest.',
	'Your store postal address is incomplete.' => 'Die Postanschrift Ihres Shops ist unvollständig.',
	'The seller street address and city are mandatory on every EU e-invoice.' => 'Straße und Ort des Verkäufers sind auf jeder EU-E-Rechnung Pflicht.',
	'Complete the address under WooCommerce > Settings > General > Store address.' => 'Vervollständigen Sie die Adresse unter WooCommerce > Einstellungen > Allgemein > Shop-Adresse.',
	'Your store has no VAT number configured.' => 'Für Ihren Shop ist keine USt-IdNr. konfiguriert.',
	'A seller VAT identifier is required on EU e-invoices. WooCommerce has no field for it, so Deklera stores it separately.' => 'Auf EU-E-Rechnungen ist eine USt-IdNr. des Verkäufers erforderlich. WooCommerce hat dafür kein Feld, deshalb speichert Deklera sie separat.',
	'Your store VAT number "%s" is not in a valid EU format.' => 'Die USt-IdNr. „%s“ Ihres Shops hat kein gültiges EU-Format.',
	'An EU VAT number starts with the two-letter country code, for example FR12345678901.' => 'Eine EU-USt-IdNr. beginnt mit dem zweistelligen Länderkürzel, zum Beispiel FR12345678901.',
	'Correct it in the Settings box at the bottom of this page.' => 'Korrigieren Sie sie im Einstellungsfeld unten auf dieser Seite.',

	// KDV tutarliligi.
	'VAT category and rate' => 'Steuerkategorie und Steuersatz',
	'VAT category %1$s is used together with a rate of %2$s%%.' => 'Die Steuerkategorie %1$s wird zusammen mit einem Satz von %2$s %% verwendet.',
	'Categories that carry no VAT must have a rate of exactly zero. A non-zero rate makes the invoice internally inconsistent.' => 'Kategorien ohne Umsatzsteuer müssen einen Satz von genau null haben. Ein Satz ungleich null macht die Rechnung in sich widersprüchlich.',
	'Review the tax rates under WooCommerce > Settings > Tax for this customer country.' => 'Prüfen Sie die Steuersätze für dieses Kundenland unter WooCommerce > Einstellungen > Steuern.',
	'A standard-rate VAT line carries a rate of zero.' => 'Eine Position mit Regelsteuersatz weist einen Satz von null aus.',
	'The standard category requires a rate above zero. A zero rate here usually means no tax rule matched the customer address.' => 'Die Regelkategorie verlangt einen Satz über null. Ein Nullsatz bedeutet hier meist, dass keine Steuerregel zur Kundenadresse gepasst hat.',
	'Check that a tax rate exists for this country under WooCommerce > Settings > Tax.' => 'Prüfen Sie unter WooCommerce > Einstellungen > Steuern, ob für dieses Land ein Steuersatz existiert.',
	'No VAT was charged on a sale to a private customer in %s.' => 'Bei einem Verkauf an einen Privatkunden in %s wurde keine Umsatzsteuer berechnet.',
	'Under the One Stop Shop rules a sale to an EU consumer is taxed at the rate of their country. Charging no VAT means either the tax rate is missing or the exemption cannot be justified.' => 'Nach den One-Stop-Shop-Regeln wird ein Verkauf an einen EU-Verbraucher zum Satz seines Landes besteuert. Keine Umsatzsteuer zu berechnen bedeutet, dass entweder der Steuersatz fehlt oder die Befreiung nicht begründet werden kann.',
	'Add a tax rate for this country under WooCommerce > Settings > Tax, or confirm you are below the OSS threshold.' => 'Legen Sie unter WooCommerce > Einstellungen > Steuern einen Satz für dieses Land an oder bestätigen Sie, dass Sie unter der OSS-Schwelle liegen.',
	'This order was treated as an intra-community service.' => 'Diese Bestellung wurde als innergemeinschaftliche Dienstleistung behandelt.',
	'This order was treated as an intra-community supply of goods.' => 'Diese Bestellung wurde als innergemeinschaftliche Lieferung von Gegenständen behandelt.',
	'Goods use category K and services use category AE. Deklera decides this from whether the items are shippable or virtual, which is a reasonable guess but not always right.' => 'Für Gegenstände gilt Kategorie K, für Dienstleistungen Kategorie AE. Deklera entscheidet danach, ob die Positionen versandfähig oder virtuell sind – eine vernünftige Annahme, aber nicht immer zutreffend.',
	'If the classification is wrong, override it with the deklera/tax_category filter.' => 'Ist die Einstufung falsch, überschreiben Sie sie mit dem Filter deklera/tax_category.',

	// Toplamlar.
	'Invoice totals' => 'Rechnungssummen',
	'The invoice lines add up to %1$s but the order total is %2$s, a difference of %3$s.' => 'Die Rechnungspositionen ergeben %1$s, die Bestellsumme beträgt jedoch %2$s – eine Differenz von %3$s.',
	'Validators reject an invoice whose totals do not reconcile, even by one cent. This usually comes from an order-level discount, a coupon or a refund that is not represented on any line.' => 'Prüfdienste weisen eine Rechnung zurück, deren Summen nicht aufgehen – auch bei einem Cent. Ursache ist meist ein Rabatt, ein Gutschein oder eine Erstattung auf Bestellebene, die in keiner Position abgebildet ist.',
	'Open the order and compare the line totals with the order total.' => 'Öffnen Sie die Bestellung und vergleichen Sie die Positionssummen mit der Bestellsumme.',
	'The VAT breakdown totals %1$s but WooCommerce recorded %2$s.' => 'Die Steueraufschlüsselung ergibt %1$s, WooCommerce hat jedoch %2$s erfasst.',
	'The sum of the VAT breakdown must equal the VAT charged. A gap means a rate was applied that Deklera could not reconstruct from the order.' => 'Die Summe der Steueraufschlüsselung muss der berechneten Umsatzsteuer entsprechen. Eine Abweichung bedeutet, dass ein Satz angewandt wurde, den Deklera aus der Bestellung nicht rekonstruieren konnte.',
	'Review the tax lines on the order and the rounding setting under WooCommerce > Settings > Tax.' => 'Prüfen Sie die Steuerzeilen der Bestellung und die Rundungseinstellung unter WooCommerce > Einstellungen > Steuern.',

	// Denetim izi.
	'Document generated' => 'Dokument erzeugt',
	'Generation failed' => 'Erzeugung fehlgeschlagen',
	'Document downloaded' => 'Dokument heruntergeladen',
	'Queued for generation' => 'Zur Erzeugung eingereiht',
	'Rejected by official validation' => 'Von der offiziellen Validierung abgelehnt',
	'Attached to customer email' => 'An die Kunden-E-Mail angehängt',
	'Removed after the retention period' => 'Nach Ablauf der Aufbewahrungsfrist entfernt',
	'Sent to KSeF' => 'An KSeF gesendet',
	'Registered by KSeF' => 'Von KSeF registriert',
	'Rejected by KSeF' => 'Von KSeF abgelehnt',

	// Belge dili kurali.
	'Invoice language' => 'Rechnungssprache',
	'This invoice would be issued in your store language, not %s.' => 'Diese Rechnung würde in Ihrer Shop-Sprache ausgestellt, nicht in %s.',
	'Deklera writes the invoice in the buyer\'s language and ships German, French and Polish. WordPress can only switch to a language it has installed, so without the language pack the document falls back to your store language.' => 'Deklera schreibt die Rechnung in der Sprache des Käufers und liefert Deutsch, Französisch und Polnisch mit. WordPress kann nur in eine Sprache wechseln, die installiert ist; ohne das Sprachpaket fällt das Dokument auf Ihre Shop-Sprache zurück.',
	'Install it under Settings > General > Site Language, or add it under Dashboard > Updates. The site needs to reach WordPress.org once to download it.' => 'Installieren Sie sie unter Einstellungen > Allgemein > Sprache der Website, oder ergänzen Sie sie unter Dashboard > Aktualisierungen. Die Website muss WordPress.org einmal erreichen, um sie zu laden.',

	'__cogul__' => array(
		'All %s recent order would be accepted.' => array(
			'Die letzte %s Bestellung würde angenommen.',
			'Alle %s letzten Bestellungen würden angenommen.',
		),
		'%1$s of your last %2$s orders would be rejected.' => array(
			'%1$s Ihrer letzten %2$s Bestellungen würde abgelehnt.',
			'%1$s Ihrer letzten %2$s Bestellungen würden abgelehnt.',
		),
		'%s order' => array(
			'%s Bestellung',
			'%s Bestellungen',
		),
		'and %s more' => array(
			'und %s weitere',
			'und %s weitere',
		),
	),
);
