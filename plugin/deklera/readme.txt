=== Deklera ===
Contributors: ekremtekerek
Tags: woocommerce, e-invoicing, e-rechnung, factur-x, ksef
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Requires Plugins: woocommerce
Stable tag: 0.3.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Find out which of your orders would be rejected as e-invoices — before the tax authority does.

== Description ==

**Producing e-invoice XML is the easy part. The hard part is that WooCommerce
order data is rarely clean enough for it** — and you find out weeks later, when
an invoice comes back rejected and you have to work out which of two hundred
business rules you broke.

Deklera starts at that problem, not at the XML.

Install it, open the report, and it tells you in plain language which of your
recent orders would be rejected and why. Then it produces the document your
country requires — and, on the Pro plan, checks it against the **official**
EN 16931 rule set before it is issued.

= The pre-flight check =

This is what the plugin is for. It scans your orders and reports, for each
problem: what happened, why it matters, the exact rule reference, and where to
fix it. No jargon, no "invalid order".

Real examples from the report:

* *"This is a cross-border EU business sale with no VAT, but the customer VAT
  number is missing."* — `BT-48 / BR-AE-09`. Without it the exemption cannot be
  justified and the invoice is rejected.
* *"The invoice lines add up to €120.00 but the order total is €112.50, a
  difference of €7.50."* — `BT-112 / BR-CO-15`. Validators reject totals that
  do not reconcile, even by one cent.

Findings are grouped by root cause, so a store-wide problem is reported once —
not repeated on every order.

Other things it catches:

* Sales to EU consumers where no VAT was charged at all
* Missing store VAT number, incomplete store address
* VAT categories that contradict the rate applied
* Orders with no customer name or no billing country

When Deklera has to make a judgement call — is this a service or goods? — it
says so and marks the order for review instead of guessing silently. You can
override it with a filter.

= Generating documents =

Deklera maps each order to the EN 16931 semantic model and produces the format
your country requires:

* **France** — Factur-X: a PDF/A-3 file with the XML embedded inside it
* **Germany** — XRechnung 3.0 (pure XML)
* **Poland** — KSeF FA(3), submitted to the national platform
* **Other countries** — EN 16931 CII, the European baseline. Read the FAQ below before relying on it.

The tax category (standard, reverse charge, intra-community supply, export) is
derived from where the seller and buyer are, not guessed from the rate alone.
Where a decision is uncertain, Deklera says so instead of silently guessing.

Documents are archived and never overwritten. Regenerating creates a new
version; the old one stays, because quietly replacing an issued invoice is not
something an audit will forgive.

= What this plugin does not do =

**It does not transmit invoices, except to KSeF.** Deklera produces the
document and checks it; delivery goes through your own accredited provider — a
PDP in France, a Peppol access point elsewhere. Poland is the exception,
because there an unsent file is not an invoice at all. If you are looking for a
plugin that sends invoices to a network, this is not it, and you should not buy
it expecting that.

It also **does not guarantee legal compliance**. No software can. Whether a
specific invoice is accepted depends on your registration, your provider and
rules that change over time. What Deklera can honestly promise is narrower and
more useful: it tells you when your data will fail the standard, and it checks
the finished document against the official rule set before you issue it.

= Who this is for =

Shops that already know they need e-invoicing and want to find out, now,
whether their order data is ready — rather than discovering it one rejection
at a time.

France requires e-invoicing from September 2026, small businesses from
September 2027. Poland's KSeF already covers most VAT-registered businesses.
Germany accepts XRechnung and ZUGFeRD today.

== External services ==

**Official validation service (Pro only, optional)**

The Pro version can validate each document against the official EN 16931
Schematron rule set. This validation cannot run inside WordPress: the rule set
compiles to XSLT 2.0, and PHP's XSL extension only supports XSLT 1.0.

When enabled, the invoice XML is sent over HTTPS to a validation service
operated by the plugin author. The XML contains your invoice data, including
seller and buyer names, addresses, VAT numbers and line items. It is processed
in memory to produce the validation report and is not stored.

This service is **off by default** and only runs on the Pro plan after you enter
an endpoint and licence key.

* Service: Deklera validation service
* Terms: https://github.com/ekremtekerek/deklera/blob/main/docs/TERMS.md
* Privacy: https://github.com/ekremtekerek/deklera/blob/main/docs/PRIVACY.md

**KSeF (Poland only, required for Polish stores)**

If your store is based in Poland, each generated FA(3) invoice is submitted to
the Polish Ministry of Finance's National e-Invoice System (KSeF). This is not
optional for Polish stores: an FA(3) file has no legal standing until KSeF
accepts it and assigns a number.

The invoice is encrypted on your site before it leaves it, and sent over HTTPS.
It contains your invoice data: seller and buyer names, addresses, tax
identifiers and line items. You choose the environment; the test environment
has no legal effect.

* Service: KSeF (Krajowy System e-Faktur), Ministerstwo Finansów
* Test: https://api-test.ksef.mf.gov.pl
* Production: https://api.ksef.mf.gov.pl
* Terms: https://ksef.mf.gov.pl

Stores outside Poland never contact KSeF.

The free version performs no other external requests, apart from the language
pack downloads that WordPress itself makes.

== Installation ==

1. Install and activate WooCommerce.
2. Install and activate Deklera.
3. Go to **WooCommerce → Deklera** and enter your VAT number, with the country
   prefix — `FR40303265045`, not `40303265045`. WooCommerce has no field for
   this, so Deklera stores it.
4. Check that your store address is complete under **WooCommerce → Settings →
   General**. Street, city, postcode and country are all mandatory on an
   invoice; if one is missing, Deklera will refuse to produce documents and
   tell you why.
5. Read the pre-flight report.

On the Pro plan there is nothing more to set up. Activating your licence
switches official validation on; the plugin authorises itself with that
licence, so there is no second key to paste.

**The full guide** — what the findings mean, when documents are produced and
where they are stored, credit notes, the Polish KSeF flow, and the available
filters — is at
https://github.com/ekremtekerek/deklera/blob/main/docs/GUIDE.md

== Frequently Asked Questions ==

= Does it work without a PDF invoice plugin? =

Yes. Deklera includes a plain fallback template. If you already use a PDF
invoice plugin such as WooCommerce PDF Invoices & Packing Slips, Deklera embeds
the XML into that plugin's PDF instead, so your own design and branding are kept.

The built-in template embeds its own font, so it writes any European alphabet
and the finished Factur-X passes PDF/A-3 validation, which France requires. When
another plugin produces the PDF, PDF/A conformance is up to that plugin.

= Will my invoices be accepted? =

Deklera produces documents that conform to EN 16931 and, on the Pro plan,
verifies them against the official rule set before they leave your site. Whether
a specific tax authority accepts a specific invoice also depends on your
registration, your provider and rules that change over time. No plugin can
promise that, and any that does is not being honest with you.

= Does it send my invoices anywhere? =

If your store is in Poland, yes: FA(3) invoices are submitted to KSeF, because
there an invoice does not legally exist until KSeF has accepted it. That is the
whole point of the Polish system.

Everywhere else, no. On Pro, the document is sent to the validation service
described under **External services**, and only if you enable it.

= How is this different from the other invoice plugins? =

Most WooCommerce invoice plugins already produce the file, and several produce
it for nothing. So does Deklera: the entire free version is the format work —
pre-flight report, Factur-X, XRechnung, credit notes, versioned archive — and
nothing in it is switched off.

That is the entry ticket, not the product. Producing a file is easy. Knowing
whether the data behind it will survive the rules is not, and that is the part
that costs you weeks when it goes wrong.

So Deklera does the part nobody else checks. The report tells you which orders
would be rejected **before** you issue them. Pro runs the finished document
through the official rule set — the one that compiles to XSLT 2.0, which PHP
cannot execute, which is why it runs as a service rather than on your site.

If you already pay to send invoices over Peppol, this does not replace that.
It is the check you run first, so that what you send comes back accepted.

= Which countries are supported? =

France (Factur-X, *facture électronique*), Germany (XRechnung, *E-Rechnung*)
and Poland (KSeF FA(3)) are fully supported, and each one is measured against
that country's own official validator before a release goes out.

Other EU countries receive EN 16931 CII output, the common semantic standard
behind all of them. **Read that as the European baseline, not as your national
profile.** Several member states run their own mandatory format and their own
platform — Italy's FatturaPA through the SdI is the clearest example — and
Deklera does not produce those. If your country runs its own system, confirm
that EN 16931 CII is accepted there before you rely on this plugin for it.

**Poland** is supported, and it works differently from the others. KSeF is not
just a format: an FA(3) invoice does not legally exist until KSeF has accepted
it and assigned a number. So for Poland, Deklera does send: it submits each
invoice to KSeF, waits for the number and records it against the order.

This is the one case where the plugin transmits, because producing the file
without sending it would leave you holding something that looks like an
invoice and is not one.

You need a KSeF token from your KSeF account. Start in the test environment —
invoices sent there have no legal effect — and switch to production when you
are satisfied.

One thing to check if you issue VAT-exempt invoices: KSeF requires the legal
basis for the exemption and keeps three separate fields for it — a Polish act,
an EU directive, or another basis. Deklera reads your exemption reason and
picks the matching field; if the text names no recognisable provision, it uses
"other". That is a best effort, not a legal opinion, so have your accountant
confirm the basis you record is the right one.

= Is the free version actually usable? =

Yes, and not in the "crippled demo" sense. The free version does everything
the plugin itself is capable of: it scans your orders, reports every problem
it finds, generates real Factur-X and XRechnung documents, produces credit
notes for refunds, archives every version with a hash, and generates a
document automatically when an order completes. Nothing in the code is
switched off by a licence.

Pro adds one thing, because it is the one thing the plugin cannot do on its
own: validation against the **official** EN 16931 rule set before a document
is issued. That rule set compiles to XSLT 2.0 and PHP's XSL extension only
supports XSLT 1.0, so the check runs on a hosted service instead of on your
site. See **External services** above.

== Screenshots ==

1. The pre-flight report: how many recent orders would be rejected, and why.
2. Findings grouped by root cause, each with the EN 16931 rule reference.
3. The e-invoice box on the order screen, with document versions and history.

== Changelog ==

= 0.3.9 =
* Packaging only, no functional change. The download no longer carries build
  and continuous-integration files from the bundled libraries — Schematron,
  XSLT and CI configuration that nothing in the plugin reads. The WordPress.org
  review asked for this, and the download is a good deal smaller for it.

= 0.3.8 =
* When the official rule set refuses a document, the order screen now names
  the rule and quotes what it said. Before, it said only "Invalid" and the
  reason sat in the database where nobody could see it — which is unhelpful
  exactly when it matters most.
* Pro no longer needs a separate validation key. The plugin authorises itself
  with the licence you already activated, so there is nothing extra to paste,
  and a cancelled or lapsed subscription now stops validation on its own.
* An existing validation key still works and still wins, for shops running
  their own copy of the validation service.
* Two settings — the invoice contact name and telephone number — were left
  behind when the plugin was uninstalled with "delete my settings" turned on.
  They are removed now. Invoices, archived documents and the key that verifies
  them are kept, as before.

= 0.3.7 =
* Packaging only, no functional change. The free download no longer carries
  compiled translation files: they were never read, because the translation
  loader only runs in the premium build, and on WordPress.org translations
  come from translate.wordpress.org anyway. The download is smaller for it.
* The upgrade notice for 0.3.0 was longer than the 300 characters the plugin
  directory allows, so it was shortened.

= 0.3.6 =
* Clearer about what is and is not covered. France, Germany and Poland are
  measured against each country's own official validator before a release
  goes out; everywhere else you get EN 16931 CII, which is the European
  baseline and not a national profile. Some member states mandate their own
  format and their own platform — Italy's FatturaPA through the SdI is the
  clearest example — and Deklera does not produce those. Better to know that
  before you buy than after.

= 0.3.5 =
* Your customer now actually receives the invoice. The attachment feature was
  wired to the order-completed email, but that email is sent before the
  document exists — generation runs in the background so the customer never
  waits for it. The result was an attachment that never attached. Deklera now
  sends WooCommerce's Invoice email once the document is archived, with the
  file on it.
* Regenerating an invoice does not email the customer again, and a credit note
  is not sent under an "Invoice" heading. Both would tell the customer
  something you did not mean to say. Use the order screen to send those.
* The new email can be switched off with the deklera/email_after_generation
  filter if you deliver invoices your own way.

= 0.3.4 =
* German, French and Polish translations. This matters more than an admin
  screen: the invoice is written in the buyer's language, so a German
  customer was until now receiving a PDF labelled in English. They now get
  Rechnung, Nettobetrag and USt-IdNr., a French customer Facture and
  Total HT, a Polish one Faktura and Razem netto.
* The legal wording follows each country's own convention rather than a
  literal translation — reverse charge appears as Steuerschuldnerschaft des
  Leistungsempfängers, autoliquidation, odwrotne obciążenie; a credit note
  as Rechnungskorrektur, facture d'avoir, faktura korygująca.
* A new pre-flight check warns when an invoice would go out in the wrong
  language. WordPress can only switch to a language it has installed, so
  without the language pack the document quietly falls back to your store
  language — and you would not find out.

= 0.3.3 =
* Invoices now pass the national validators, not only the EU baseline. This
  release began as a question — would a real tax authority accept what we
  produce? — and the answer, measured against the official rule sets, was
  no in two places.
* Germany: the output failed the official XRechnung 3.0.2 rules on six
  counts. Every one of them is a field the EU standard leaves optional and
  Germany makes mandatory: the seller contact name and phone, the seller and
  buyer electronic addresses, and the payment means. All six are now filled,
  and the same document passes the official rule set with nothing left.
* A new pre-flight check tells a German store when its billing phone number
  is missing, because that one cannot be filled in for you — and if it is
  missing you would only find out when the invoice is refused.
* France: the Factur-X PDF failed PDF/A-3 validation. The cause was a single
  line in the specification — every font used must be embedded in the file —
  and the built-in template used fonts that, by design, are not. The template
  now embeds its own font, and the finished document passes.
* The same change lifts the old Latin-1 limit. The built-in template writes
  any European alphabet: Polish, Czech, Hungarian, Romanian, Greek, Cyrillic.
  It no longer refuses to produce a PDF because of a customer's name.
* Pro: validation now checks German documents against Germany's own rules,
  not just the EU baseline. Other countries are unaffected.

= 0.3.2 =
* A user guide, linked from the settings screen and the readme: what the
  pre-flight findings mean, when documents are produced and where they are
  stored, credit notes, the Polish KSeF flow, and the available filters.
* Pro setup no longer asks you to guess. The validation service address is
  filled in by default, and the screen says where the validation key comes
  from and that it is not the licence key that activated the plugin.

= 0.3.1 =
* Pro: the first validation after the service has been idle is no longer
  lost. The hosted validator sleeps when unused and takes longer to answer
  the request that wakes it; that request now gets a second attempt instead
  of being reported as unreachable.

= 0.3.0 =
* The plugin has been renamed. The previous name turned out to conflict with
  a trademark, so it had to change everywhere: the plugin name, the settings,
  the hooks and the database tables.
* Because the tables are renamed, a site upgrading from 0.2.x starts with
  empty settings and an empty document archive. The old rows are left in the
  database untouched, but the plugin no longer reads them. See the upgrade
  notice.
* Pro licences must be activated again after upgrading. The licence record is
  stored under the plugin slug, and the slug changed with the name, so the
  plugin cannot find the old one.
* The built-in PDF template now uses FPDF 1.9.0 instead of 1.8.2.
* The free version no longer calls load_plugin_textdomain(). WordPress loads
  translations for wordpress.org plugins on its own; the call remains in the
  Pro version, which ships its own translation files.

= 0.2.1 =
* Poland: the legal basis for a VAT exemption now goes to the field KSeF
  expects — a Polish act, an EU directive, or "other" — instead of always
  "other".

= 0.2.0 =
* Poland: KSeF FA(3) generation and submission. Invoices are sent to the
  national platform, the KSeF number is recorded against the order and shown
  on the order screen.
* Documents that have been sent are never sent twice, even if a retry happens
  after a timeout or a crash.
* Settings for the KSeF token and environment; the test environment is the
  default and invoices sent there have no legal effect.

= 0.1.0 =
* First release.
* Pre-flight check with five rule groups covering seller identity, customer
  identity, VAT categories, invoice totals and required fields.
* EN 16931 mapping with tax category resolution for domestic, OSS, reverse
  charge, intra-community and export scenarios.
* Factur-X (PDF/A-3) and XRechnung 3.0 output.
* Document archive with SHA-256 integrity checks, versioning and an audit trail.
* Optional validation against the official EN 16931 Schematron rule set.

== Upgrade Notice ==

= 0.3.8 =
Pro no longer needs a separate validation key: your licence now authorises
official validation on its own. If you had pasted a key, it keeps working. The
order screen also names the rule when a document is refused.

= 0.3.2 =
Adds a user guide and fixes the Pro setup screen, which previously left you
with two empty fields and no explanation. Nothing to do after upgrading.

= 0.3.1 =
Fixes a Pro-only problem: the first validation of the day could be reported
as "service unavailable". Nothing to do after upgrading.

= 0.3.0 =
The plugin is now called Deklera. Settings and the archive do not carry over:
re-enter your VAT number, your KSeF token, and on Pro your licence. Invoices
already registered with KSeF keep their numbers and are unaffected.

= 0.2.1 =
Polish stores that issue VAT-exempt invoices should reissue any exempt invoice
sent with 0.2.0; the exemption basis was recorded in the wrong field.

= 0.2.0 =
Adds Poland (KSeF). Polish stores must enter a KSeF token; other stores are
unaffected.

= 0.1.0 =
First release.
