<?php
/**
 * Fransizca ceviri eslemesi.
 *
 * TERMINOLOJI NOTLARI (degistirmeden once okuyun)
 *
 * - "Credit note" -> "Facture d'avoir". Fransa'da belge turu 381'in adi
 *   budur; "note de credit" Belcika kullanimidir ve Fransiz muhasebe
 *   diline yabancidir.
 * - "Reverse charge" -> "Autoliquidation". Fatura uzerinde gorunmesi
 *   gereken yasal ibare "Autoliquidation"dir (CGI art. 283-2).
 * - "VAT number" -> "numero de TVA intracommunautaire". Kisa baglamlarda
 *   (PDF sutunu) "N TVA" kullanildi.
 * - Belge dizgeleri (Facture, Quantite, Total HT...) ALICININ gozune gider.
 *   Fransiz faturasinda HT = hors taxes, TTC = toutes taxes comprises.
 *
 * @package Deklera
 */

return array(
	// Eklenti kimligi.
	'Deklera' => 'Deklera',
	'https://github.com/ekremtekerek/deklera' => 'https://github.com/ekremtekerek/deklera',
	'Hasan Ekrem Tekerek' => 'Hasan Ekrem Tekerek',
	"Turns WooCommerce orders into legally valid e-invoices for the seller's country and delivers them through the seller's own e-invoicing provider." => "Transforme les commandes WooCommerce en factures électroniques valables juridiquement pour le pays du vendeur et les transmet via le propre prestataire de facturation électronique du vendeur.",

	// Altyapi hatalari.
	'Deklera requires PHP %1$s or later. This server is running PHP %2$s.' => 'Deklera nécessite PHP %1$s ou une version ultérieure. Ce serveur exécute PHP %2$s.',
	'Deklera dependencies are missing. Run "composer install" in the plugin directory.' => "Les dépendances de Deklera sont absentes. Exécutez « composer install » dans le répertoire de l'extension.",

	// Siparis ekrani.
	'E-invoice' => 'Facture électronique',
	'The official rule set rejected this document:' => 'Le jeu de règles officiel a rejeté ce document :',
	'No document generated yet.' => 'Aucun document généré pour le moment.',
	'This order cannot be invoiced yet:' => 'Cette commande ne peut pas encore être facturée :',
	'Version %d' => 'Version %d',
	'File missing or modified' => 'Fichier absent ou modifié',
	'KSeF number:' => 'Numéro KSeF :',
	'Not registered with KSeF yet — this file is not a legal invoice.' => "Pas encore enregistré auprès de KSeF — ce fichier n'est pas une facture légale.",
	'Generate new version' => 'Générer une nouvelle version',
	'Generate document' => 'Générer le document',
	'History' => 'Historique',

	// Yetki ve hata.
	'You are not allowed to download this document.' => "Vous n'êtes pas autorisé à télécharger ce document.",
	'Document not found.' => 'Document introuvable.',
	'The archived file is missing or has been modified since it was created. It cannot be served.' => "Le fichier archivé est absent ou a été modifié depuis sa création. Il ne peut pas être délivré.",
	'You are not allowed to generate documents.' => "Vous n'êtes pas autorisé à générer des documents.",
	'You are not allowed to change these settings.' => "Vous n'êtes pas autorisé à modifier ces réglages.",
	'You are not allowed to run this scan.' => "Vous n'êtes pas autorisé à lancer cette analyse.",

	// On ucus ekrani.
	'Deklera e-invoicing' => 'Facturation électronique Deklera',
	'Deklera — e-invoicing pre-flight check' => 'Deklera — contrôle préalable de facturation électronique',
	'Settings saved. The check was run again.' => 'Réglages enregistrés. Le contrôle a été relancé.',
	'There are no completed orders to check yet. Come back once you have taken your first order.' => "Il n'y a pas encore de commandes terminées à contrôler. Revenez une fois votre première commande enregistrée.",
	'Checked' => 'Contrôlées',
	'Would be rejected' => 'Seraient rejetées',
	'Needs review' => 'À vérifier',
	'Ready to invoice' => 'Prêtes à facturer',
	'Run the check again' => 'Relancer le contrôle',
	'Fix these once, for the whole store' => "À corriger une seule fois, pour toute la boutique",
	'Order data that needs attention' => 'Données de commande à corriger',
	'How to fix:' => 'Comment corriger :',
	'Rule:' => 'Règle :',
	'Affected orders:' => 'Commandes concernées :',
	'For information' => 'Pour information',

	// Ayarlar.
	'Settings' => 'Réglages',
	'Your VAT number' => 'Votre numéro de TVA',
	'WooCommerce has no field for this, so Deklera stores it. Include the country prefix.' => "WooCommerce ne prévoit pas de champ pour cela, Deklera l'enregistre donc. Indiquez le préfixe du pays.",
	'Contact name on the invoice' => 'Nom du contact sur la facture',
	'Leave empty to use the store name. Germany requires this field to be present.' => "Laissez vide pour utiliser le nom de la boutique. L'Allemagne exige que ce champ soit renseigné.",
	'Contact telephone number' => 'Numéro de téléphone du contact',
	'Required for German invoices (XRechnung). There is no sensible default, so it cannot be filled in for you.' => "Obligatoire pour les factures allemandes (XRechnung). Il n'existe pas de valeur par défaut raisonnable, ce champ ne peut donc pas être rempli à votre place.",
	'Save' => 'Enregistrer',

	// KSeF.
	'KSeF (Poland)' => 'KSeF (Pologne)',
	'An FA(3) invoice does not legally exist until KSeF has accepted it and assigned a number. Deklera sends each invoice automatically and records the number.' => "Une facture FA(3) n'existe juridiquement qu'une fois acceptée par KSeF et dotée d'un numéro. Deklera envoie chaque facture automatiquement et enregistre le numéro.",
	'KSeF token' => 'Jeton KSeF',
	'Saved — leave blank to keep it' => 'Enregistré — laissez vide pour le conserver',
	'Not set' => 'Non défini',
	'Generate the token in your KSeF account. It is stored on this site and never shown again.' => "Générez le jeton dans votre compte KSeF. Il est conservé sur ce site et n'est plus jamais affiché.",
	'Environment' => 'Environnement',
	'Test — invoices have no legal effect' => 'Test — les factures sont sans effet juridique',
	'Production — invoices are real' => 'Production — les factures sont réelles',
	'Start with Test. Invoices sent to Production are legally issued and cannot be withdrawn.' => 'Commencez par Test. Les factures envoyées en Production sont juridiquement émises et ne peuvent pas être retirées.',

	// Pro dogrulama.
	'Official validation (Pro)' => 'Validation officielle (Pro)',
	'Validation against the official EN 16931 rule set requires the Pro plan. It cannot run inside WordPress because the rule set needs XSLT 2.0, which PHP does not support.' => "La validation face au jeu de règles officiel EN 16931 requiert la formule Pro. Elle ne peut pas s'exécuter dans WordPress car ce jeu de règles nécessite XSLT 2.0, que PHP ne prend pas en charge.",
	'Read the setup guide' => 'Lire le guide de configuration',
	'Validation service address' => 'Adresse du service de validation',
	'Already set to the service run by the plugin author. Change it only if you run your own copy of it.' => "Renseignée sur le service exploité par l'auteur de l'extension. Ne la modifiez que si vous hébergez votre propre instance.",
	'Validation key' => 'Clé de validation',
	'Saved. Leave empty to keep it.' => 'Enregistrée. Laissez vide pour la conserver.',
	'Official validation is on. Your licence authorises it — there is nothing to set up here.' => 'La validation officielle est active. Votre licence l’autorise — il n’y a rien à configurer ici.',
	'Validation is not authorised yet. Activate your licence, or enter a key below if you run your own copy of the service.' => 'La validation n’est pas encore autorisée. Activez votre licence, ou saisissez une clé ci-dessous si vous hébergez votre propre copie du service.',
	'Leave empty. Only needed if you run your own copy of the service.' => 'Laissez vide. Utile uniquement si vous hébergez votre propre copie du service.',

	// KDV kategorileri.
	'Standard rate' => 'Taux normal',
	'Zero rated goods' => 'Biens au taux zéro',
	'Exempt from VAT' => 'Exonéré de TVA',
	'VAT reverse charge' => 'Autoliquidation de la TVA',
	'VAT exempt intra-community supply' => 'Livraison intracommunautaire exonérée de TVA',
	'Export, outside the scope of VAT' => 'Exportation, hors champ de la TVA',
	'Services outside the scope of VAT' => 'Prestations hors champ de la TVA',
	'Canary Islands general indirect tax' => 'Impôt général indirect des îles Canaries (IGIC)',
	'Ceuta and Melilla tax' => 'Taxe de Ceuta et Melilla (IPSI)',

	// Belge turleri.
	'Commercial invoice' => 'Facture commerciale',
	'Credit note' => "Facture d'avoir",
	'Corrected invoice' => 'Facture rectificative',
	'Self-billed invoice' => 'Facture établie par le client (autofacturation)',
	'Refund' => 'Remboursement',

	// Fatura uzerindeki yasal ibareler.
	'Reverse charge: VAT is due from the recipient.' => 'Autoliquidation : la TVA est due par le preneur.',
	'Intra-Community supply, exempt under Article 138 of Council Directive 2006/112/EC.' => "Livraison intracommunautaire exonérée en application de l'article 138 de la directive 2006/112/CE du Conseil.",
	'Export outside the European Union, exempt from VAT.' => "Exportation hors de l'Union européenne, exonérée de TVA.",
	'Exempt from VAT.' => 'Exonéré de TVA.',
	'Not subject to VAT.' => 'Non soumis à la TVA.',

	// Vergi kararlarinin gerekcesi.
	'The store is based outside the EU, so the supply is outside the scope of EU VAT.' => "La boutique est établie hors de l'UE ; l'opération est donc hors du champ de la TVA de l'UE.",
	'Domestic sale with a VAT rate applied.' => 'Vente nationale avec application d’un taux de TVA.',
	'Cross-border sale to an EU consumer, taxed at the destination rate under OSS.' => "Vente transfrontalière à un consommateur de l'UE, taxée au taux du pays de destination dans le cadre du guichet unique (OSS).",
	'The buyer is outside the EU, so the supply is treated as an export.' => "L'acheteur se trouve hors de l'UE ; l'opération est donc traitée comme une exportation.",
	'Assumed to be a service because every item is virtual or downloadable.' => 'Considérée comme une prestation de services car tous les articles sont virtuels ou téléchargeables.',
	'Assumed to be goods because the order contains shippable items.' => 'Considérée comme une livraison de biens car la commande contient des articles expédiables.',
	'Cross-border EU sale with no VAT and no valid buyer VAT number.' => "Vente transfrontalière dans l'UE sans TVA et sans numéro de TVA valable pour l'acheteur.",
	'Domestic sale with no VAT applied.' => 'Vente nationale sans application de TVA.',

	// PDF sablonu — bunlari ALICI okur.
	'Deklera built-in template' => 'Modèle intégré Deklera',
	'Invoice' => 'Facture',
	'Number' => 'Numéro',
	'Date' => 'Date',
	'VAT number' => 'N° TVA',
	'Bill to' => 'Facturé à',
	'Description' => 'Désignation',
	'Qty' => 'Qté',
	'Unit price' => 'Prix unitaire',
	'VAT' => 'TVA',
	'Net' => 'Montant HT',
	'Net total' => 'Total HT',
	'Total' => 'Total TTC',

	// Alici kimligi kurallari.
	'Customer identity' => 'Identité du client',
	'The order has no customer name.' => 'La commande ne comporte pas de nom de client.',
	'The buyer name is mandatory on every invoice.' => "Le nom de l'acheteur est obligatoire sur toute facture.",
	'Open the order and fill in the billing name or company.' => 'Ouvrez la commande et renseignez le nom ou la société de facturation.',
	'The order has no billing country.' => 'La commande ne comporte pas de pays de facturation.',
	'The buyer country determines the VAT treatment. Without it the tax category cannot be decided.' => "Le pays de l'acheteur détermine le traitement TVA. Sans lui, la catégorie de taxe ne peut pas être déterminée.",
	'Open the order and set the billing country.' => 'Ouvrez la commande et renseignez le pays de facturation.',
	'This is a cross-border EU business sale with no VAT, but the customer VAT number is missing.' => "Il s'agit d'une vente transfrontalière entre entreprises de l'UE sans TVA, mais le numéro de TVA du client est absent.",
	'When VAT is not charged on an intra-community supply, the buyer VAT identifier is mandatory. Without it the exemption cannot be justified and the invoice is rejected.' => "Lorsqu'une livraison intracommunautaire est facturée sans TVA, le numéro de TVA de l'acheteur est obligatoire. Sans lui, l'exonération ne peut pas être justifiée et la facture est rejetée.",
	'Open the order and add the VAT number to the billing details, then start collecting it at checkout.' => 'Ouvrez la commande, ajoutez le numéro de TVA aux informations de facturation, puis collectez-le désormais à la commande.',
	'The customer VAT number "%s" is not in a valid EU format.' => "Le numéro de TVA « %s » du client n'est pas dans un format européen valable.",
	'An EU VAT number starts with the two-letter country code, for example DE123456789.' => 'Un numéro de TVA européen commence par le code pays à deux lettres, par exemple DE123456789.',
	'Open the order and correct the VAT number in the billing details.' => 'Ouvrez la commande et corrigez le numéro de TVA dans les informations de facturation.',
	'The VAT number is registered in %1$s but the billing country is %2$s.' => 'Le numéro de TVA est enregistré en %1$s alors que le pays de facturation est %2$s.',
	'A mismatch is legitimate for branch offices, but it is also a common sign of a mistyped VAT number.' => "Un écart est légitime pour un établissement secondaire, mais c'est aussi un signe fréquent de numéro de TVA mal saisi.",
	'Open the order and confirm both values with the customer.' => 'Ouvrez la commande et confirmez les deux valeurs avec le client.',

	// Fatura temelleri.
	'Invoice essentials' => 'Mentions essentielles de la facture',
	'The order has no invoice number.' => 'La commande ne comporte pas de numéro de facture.',
	'Every e-invoice must carry a unique invoice number. Without it the document cannot be issued.' => "Toute facture électronique doit porter un numéro de facture unique. Sans lui, le document ne peut pas être émis.",
	'Check your order numbering plugin, or set a number with the deklera/invoice_number filter.' => "Vérifiez votre extension de numérotation des commandes, ou définissez un numéro avec le filtre deklera/invoice_number.",
	'The order currency "%s" is not a valid ISO 4217 code.' => "La devise « %s » de la commande n'est pas un code ISO 4217 valable.",
	'The invoice currency must be a three-letter ISO 4217 code such as EUR or PLN.' => 'La devise de la facture doit être un code ISO 4217 à trois lettres, par exemple EUR ou PLN.',
	'Fix the store currency under WooCommerce > Settings > General.' => 'Corrigez la devise de la boutique dans WooCommerce > Réglages > Général.',
	'The order has no billable lines.' => 'La commande ne comporte aucune ligne facturable.',
	'An invoice must contain at least one line. Orders with only zero-value items cannot be invoiced.' => 'Une facture doit contenir au moins une ligne. Les commandes composées uniquement d’articles à valeur nulle ne peuvent pas être facturées.',
	'Open the order and check that it still contains its products.' => "Ouvrez la commande et vérifiez qu'elle contient toujours ses produits.",

	// Ulusal profil.
	'National profile requirements' => 'Exigences du profil national',
	'Your store has no billing phone number.' => 'Aucun numéro de téléphone de facturation n’est renseigné pour votre boutique.',
	"Germany's XRechnung profile makes the seller contact phone mandatory (BR-DE-6). Without it the official validator rejects the invoice, even though the EU standard itself allows it to be empty." => "Le profil allemand XRechnung rend obligatoire le téléphone du contact vendeur (BR-DE-6). Sans lui, le validateur officiel rejette la facture, alors même que la norme européenne autorise ce champ vide.",
	'Add it in the Settings box at the bottom of this page.' => 'Ajoutez-le dans le bloc Réglages en bas de cette page.',

	// Magaza kimligi.
	'Store identity' => 'Identité de la boutique',
	'Your store has no business name.' => 'Aucune raison sociale n’est renseignée pour votre boutique.',
	'The seller name appears on every invoice and is mandatory.' => 'Le nom du vendeur figure sur chaque facture et il est obligatoire.',
	'Set the site title under Settings > General.' => 'Renseignez le titre du site dans Réglages > Général.',
	'Your store has no country set.' => 'Aucun pays n’est renseigné pour votre boutique.',
	'The seller country decides which national e-invoicing rules apply to you. Nothing can be generated without it.' => 'Le pays du vendeur détermine les règles nationales de facturation électronique qui vous sont applicables. Rien ne peut être généré sans lui.',
	'Set it under WooCommerce > Settings > General > Store address.' => "Renseignez-le dans WooCommerce > Réglages > Général > Adresse de la boutique.",
	'Your store postal address is incomplete.' => "L'adresse postale de votre boutique est incomplète.",
	'The seller street address and city are mandatory on every EU e-invoice.' => "La rue et la ville du vendeur sont obligatoires sur toute facture électronique de l'UE.",
	'Complete the address under WooCommerce > Settings > General > Store address.' => "Complétez l'adresse dans WooCommerce > Réglages > Général > Adresse de la boutique.",
	'Your store has no VAT number configured.' => "Aucun numéro de TVA n'est configuré pour votre boutique.",
	'A seller VAT identifier is required on EU e-invoices. WooCommerce has no field for it, so Deklera stores it separately.' => "Un numéro de TVA du vendeur est requis sur les factures électroniques de l'UE. WooCommerce ne prévoit pas de champ pour cela, Deklera l'enregistre donc séparément.",
	'Your store VAT number "%s" is not in a valid EU format.' => "Le numéro de TVA « %s » de votre boutique n'est pas dans un format européen valable.",
	'An EU VAT number starts with the two-letter country code, for example FR12345678901.' => 'Un numéro de TVA européen commence par le code pays à deux lettres, par exemple FR12345678901.',
	'Correct it in the Settings box at the bottom of this page.' => 'Corrigez-le dans le bloc Réglages en bas de cette page.',

	// KDV tutarliligi.
	'VAT category and rate' => 'Catégorie et taux de TVA',
	'VAT category %1$s is used together with a rate of %2$s%%.' => 'La catégorie de TVA %1$s est utilisée avec un taux de %2$s %%.',
	'Categories that carry no VAT must have a rate of exactly zero. A non-zero rate makes the invoice internally inconsistent.' => "Les catégories sans TVA doivent porter un taux strictement nul. Un taux non nul rend la facture incohérente.",
	'Review the tax rates under WooCommerce > Settings > Tax for this customer country.' => 'Vérifiez les taux de taxe pour ce pays client dans WooCommerce > Réglages > TVA.',
	'A standard-rate VAT line carries a rate of zero.' => 'Une ligne au taux normal porte un taux nul.',
	'The standard category requires a rate above zero. A zero rate here usually means no tax rule matched the customer address.' => "La catégorie normale exige un taux supérieur à zéro. Un taux nul signifie généralement qu'aucune règle de taxe ne correspondait à l'adresse du client.",
	'Check that a tax rate exists for this country under WooCommerce > Settings > Tax.' => "Vérifiez qu'un taux de taxe existe pour ce pays dans WooCommerce > Réglages > TVA.",
	'No VAT was charged on a sale to a private customer in %s.' => "Aucune TVA n'a été facturée sur une vente à un particulier en %s.",
	'Under the One Stop Shop rules a sale to an EU consumer is taxed at the rate of their country. Charging no VAT means either the tax rate is missing or the exemption cannot be justified.' => "Dans le cadre du guichet unique (OSS), une vente à un consommateur de l'UE est taxée au taux de son pays. Ne pas facturer de TVA signifie soit que le taux est absent, soit que l'exonération ne peut pas être justifiée.",
	'Add a tax rate for this country under WooCommerce > Settings > Tax, or confirm you are below the OSS threshold.' => "Ajoutez un taux pour ce pays dans WooCommerce > Réglages > TVA, ou confirmez que vous êtes sous le seuil OSS.",
	'This order was treated as an intra-community service.' => 'Cette commande a été traitée comme une prestation de services intracommunautaire.',
	'This order was treated as an intra-community supply of goods.' => 'Cette commande a été traitée comme une livraison intracommunautaire de biens.',
	'Goods use category K and services use category AE. Deklera decides this from whether the items are shippable or virtual, which is a reasonable guess but not always right.' => "Les biens relèvent de la catégorie K et les services de la catégorie AE. Deklera tranche selon que les articles sont expédiables ou virtuels : une supposition raisonnable, mais pas toujours exacte.",
	'If the classification is wrong, override it with the deklera/tax_category filter.' => 'Si le classement est erroné, remplacez-le avec le filtre deklera/tax_category.',

	// Toplamlar.
	'Invoice totals' => 'Totaux de la facture',
	'The invoice lines add up to %1$s but the order total is %2$s, a difference of %3$s.' => 'Les lignes de facture totalisent %1$s alors que le total de la commande est %2$s, soit un écart de %3$s.',
	'Validators reject an invoice whose totals do not reconcile, even by one cent. This usually comes from an order-level discount, a coupon or a refund that is not represented on any line.' => "Les validateurs rejettent une facture dont les totaux ne se recoupent pas, ne serait-ce que d'un centime. Cela provient généralement d'une remise au niveau de la commande, d'un code promo ou d'un remboursement qui n'apparaît sur aucune ligne.",
	'Open the order and compare the line totals with the order total.' => 'Ouvrez la commande et comparez les totaux des lignes avec le total de la commande.',
	'The VAT breakdown totals %1$s but WooCommerce recorded %2$s.' => 'La ventilation de TVA totalise %1$s alors que WooCommerce a enregistré %2$s.',
	'The sum of the VAT breakdown must equal the VAT charged. A gap means a rate was applied that Deklera could not reconstruct from the order.' => "La somme de la ventilation de TVA doit être égale à la TVA facturée. Un écart signifie qu'un taux a été appliqué que Deklera n'a pas pu reconstituer à partir de la commande.",
	'Review the tax lines on the order and the rounding setting under WooCommerce > Settings > Tax.' => 'Vérifiez les lignes de taxe de la commande et le réglage d’arrondi dans WooCommerce > Réglages > TVA.',

	// Denetim izi.
	'Document generated' => 'Document généré',
	'Generation failed' => 'Échec de la génération',
	'Document downloaded' => 'Document téléchargé',
	'Queued for generation' => 'En file d’attente pour génération',
	'Rejected by official validation' => 'Rejeté par la validation officielle',
	'Attached to customer email' => "Joint à l'e-mail client",
	'Removed after the retention period' => 'Supprimé après la durée de conservation',
	'Sent to KSeF' => 'Envoyé à KSeF',
	'Registered by KSeF' => 'Enregistré par KSeF',
	'Rejected by KSeF' => 'Rejeté par KSeF',

	// Belge dili kurali.
	'Invoice language' => 'Langue de la facture',
	'This invoice would be issued in your store language, not %s.' => 'Cette facture serait émise dans la langue de votre boutique, et non en %s.',
	'Deklera writes the invoice in the buyer\'s language and ships German, French and Polish. WordPress can only switch to a language it has installed, so without the language pack the document falls back to your store language.' => 'Deklera rédige la facture dans la langue de l\'acheteur et intègre l\'allemand, le français et le polonais. WordPress ne peut basculer que vers une langue installée ; sans le pack de langue, le document revient à la langue de votre boutique.',
	'Install it under Settings > General > Site Language, or add it under Dashboard > Updates. The site needs to reach WordPress.org once to download it.' => 'Installez-la dans Réglages > Général > Langue du site, ou ajoutez-la dans Tableau de bord > Mises à jour. Le site doit pouvoir joindre WordPress.org une fois pour la télécharger.',

	'__cogul__' => array(
		'All %s recent order would be accepted.' => array(
			'La %s commande récente serait acceptée.',
			'Les %s commandes récentes seraient toutes acceptées.',
		),
		'%1$s of your last %2$s orders would be rejected.' => array(
			'%1$s de vos %2$s dernières commandes serait rejetée.',
			'%1$s de vos %2$s dernières commandes seraient rejetées.',
		),
		'%s order' => array(
			'%s commande',
			'%s commandes',
		),
		'and %s more' => array(
			'et %s autre',
			'et %s autres',
		),
	),
);
