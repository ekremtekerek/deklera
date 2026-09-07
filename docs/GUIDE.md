# Deklera — user guide

**Deklera – EU E-Invoicing for WooCommerce**

This guide covers everything after installation: what the pre-flight report is
telling you, how documents are produced and where they go, what changes on the
Pro plan, and what Polish stores need to do differently.

If you only have five minutes, read **1**, **2** and **3**.

---

## 1. Set up (five minutes)

1. Install and activate **WooCommerce**, then **Deklera**.
2. Open **WooCommerce → Deklera**.
3. Fill in **Your VAT number**, with the country prefix — `FR40303265045`,
   not `40303265045`. WooCommerce has no field for this, so Deklera stores it.
4. Check that your store address is complete: **WooCommerce → Settings →
   General**. Street, city, postcode and country are all mandatory on an
   invoice. If any is missing, Deklera refuses to produce documents and tells
   you so rather than issuing something that will be rejected.

That is the whole setup for the free version.

---

## 2. Reading the pre-flight report

The report is the point of the plugin. It scans your recent completed orders
and answers one question: **would these be rejected as e-invoices?**

```
3 of your last 5 orders would be rejected.

5            3                  2              0
Checked      Would be rejected  Needs review   Ready to invoice
```

Findings are grouped by **root cause**, not by order. If two hundred orders
share one problem, you see it once, with the affected orders listed under it.
That is deliberate: the fix is usually one change to your checkout, not two
hundred edits.

Each finding tells you four things:

| | |
|---|---|
| What happened | *"This is a cross-border EU business sale with no VAT, but the customer VAT number is missing."* |
| Why it matters | *"Without it the exemption cannot be justified and the invoice is rejected."* |
| How to fix it | *"Open the order and add the VAT number to the billing details, then start collecting it at checkout."* |
| The rule | `BT-48 / BR-AE-09` — the EN 16931 term and business rule, so you can look it up or quote it to your accountant |

### The three verdicts

**Would be rejected.** A hard failure. The document will not be produced, and
the reason is recorded in the audit log. Fix the order data.

**Needs review.** Deklera had to make a judgement call and is telling you so
instead of guessing silently. The most common case is goods versus services:
tax category `K` (intra-community supply of goods) and `AE` (reverse charge on
services) are decided from whether the items are shippable or virtual, which
is a reasonable guess but not always right. **A document is still produced** —
it is flagged, not blocked. If the classification is wrong, override it with
the `deklera/tax_category` filter (section 7).

**Ready to invoice.** Nothing to do.

The report is cached. **Run the check again** refreshes it after you have
fixed something.

---

## 3. Documents: when they appear and where they live

A document is produced automatically when an order reaches **Completed**. The
work happens in the background through Action Scheduler, so the customer never
waits for it.

What you get depends on where **your store** is, not where the buyer is — the
obligation is the seller's:

| Your country | Document |
|---|---|
| France | **Factur-X** — a PDF/A-3 file with the XML embedded inside it |
| Germany | **XRechnung 3.0** — pure XML |
| Poland | **KSeF FA(3)** — see section 6, it works differently |
| Anywhere else in the EU | **EN 16931 CII** — the common semantic standard |

Open any order and look at the **E-invoice** box on the right:

- the current version, its format and size
- **Generate new version**
- **History** — every generation, with timestamps

### Versions are never overwritten

Regenerating creates a **new version**. The old one stays. Quietly replacing an
issued invoice is not something an audit will forgive, so the plugin will not
do it.

Every file is stored with a SHA-256 hash. If a file is later modified or
deleted, Deklera notices and refuses to use it rather than sending something
that no longer matches what was issued.

Files live under `wp-content/uploads/deklera-<random>/`. The random part is
there so the directory cannot be guessed and enumerated. **Back it up** — these
are your invoices.

### Refunds

Refunding an order produces a **credit note**, as a separate document that
refers to the original. The original invoice is not modified. That is how
accounting works: you do not edit a record, you post a counter-record.

---

## 4. What Deklera does not do

**It does not send your invoices anywhere** — except in Poland, where an unsent
file is not an invoice at all (section 6).

Everywhere else, delivery is yours: a PDP in France, a Peppol access point
elsewhere. Deklera produces the document and checks it; getting it to the
buyer or the tax authority goes through your own accredited provider. If you
bought this expecting a transmission network, that is not what it is.

**It does not guarantee legal compliance.** No software can. Whether a specific
invoice is accepted depends on your registration, your provider, and rules that
change. What it can honestly promise is narrower: it tells you when your data
will fail the standard, and on Pro it checks the finished document against the
official rule set before you issue it.

### The built-in PDF template and Latin-1

If you have no PDF invoice plugin, Deklera uses a plain built-in template. It
is limited to the **Latin-1 character set**. If a customer's name contains a
character outside it — Polish, Turkish, Greek, Cyrillic — Deklera **refuses to
produce the PDF** and tells you which field is affected, rather than printing
the name wrongly on a legal document.

Installing a PDF invoice plugin removes that limit. Deklera detects
**WooCommerce PDF Invoices & Packing Slips** and embeds the XML into that
plugin's PDF, so your own design and branding are kept.

---

## 5. Pro: validation against the official rule set

Pro buys one thing, because it is the one thing the plugin cannot do on its
own: checking the finished document against the **official** EN 16931
Schematron rule set — the same rules a tax authority's validator applies —
before the document is issued.

It cannot run inside WordPress. The rule set compiles to XSLT 2.0 and PHP's XSL
extension only supports XSLT 1.0. So the check runs on a hosted service.

### Setting it up

After your licence is activated, open **WooCommerce → Deklera** and scroll to
**Official validation (Pro)**:

- **Validation service address** — already filled in. Leave it alone unless you
  run your own copy of the service (it is open source; see `validator/README.md`).
- **Validation key** — paste the key from your **Pro purchase email**.

> The validation key is **not** the same as the licence key that activated the
> plugin. They are two different strings for two different things. If you have
> lost the purchase email, ask for it and it will be re-sent.

That is it. From then on, every generated document is validated before it is
archived.

### What you see

A valid document is archived normally. An invalid one is reported with the
exact rule that failed — for example:

```
BR-CO-15  Invoice total amount with VAT (BT-112) =
          Invoice total amount without VAT (BT-109) + Invoice total VAT (BT-110)
```

### If the service is slow or unreachable

The service sleeps when it has not been used for a while, so the **first**
validation after a quiet period takes longer — it has to wake up. Deklera
retries automatically, so you should not notice more than a pause of a few
seconds.

If it genuinely cannot be reached, Deklera says so and **still archives the
document**. Validation failing does not block your invoicing.

### What is sent

The invoice XML, over HTTPS: seller and buyer names, addresses, VAT numbers and
line items. It is processed in memory to produce the report and is not stored.
See `docs/PRIVACY.md`.

---

## 6. Poland (KSeF) — read this if your store is in Poland

Poland is the exception to everything in section 4. An FA(3) file **has no
legal existence** until the national platform (KSeF) has accepted it and
assigned a number. Producing the file without sending it would leave you
holding something that looks like an invoice and is not one.

So for Polish stores, Deklera **does** transmit.

### Setting it up

1. Generate a **KSeF token** in your KSeF account.
2. **WooCommerce → Deklera → KSeF**: paste the token and choose the
   environment.
3. Start in **Test**. Invoices sent there have no legal effect. Switch to
   **Production** when you are satisfied.

The token is stored on your site and never shown again on screen.

### What happens

When an order completes, Deklera builds the FA(3) document, encrypts it, opens
a session, sends it, waits for the KSeF number and records it against the
order. The number appears in the **E-invoice** box.

Until a number comes back, the order screen says plainly:

> Not registered with KSeF yet — this file is not a legal invoice.

If KSeF is unreachable the job is retried; a document that has been accepted is
never sent twice, even after a timeout or a crash.

### VAT-exempt invoices

KSeF requires the legal basis for an exemption and keeps **three separate
fields** for it: a Polish act, an EU directive, or another basis. Deklera reads
your exemption reason and picks the matching field; if the text names no
recognisable provision, it uses "other".

That is a best effort, not a legal opinion. **Have your accountant confirm the
basis you record is the right one.**

---

## 7. For developers

Filters, all prefixed `deklera/`:

| Filter | What it changes |
|---|---|
| `deklera/tax_category` | Override the tax category when the goods/services guess is wrong |
| `deklera/plan` | Force the effective plan; for development and automated tests |
| `deklera/validation_timeout` | Seconds allowed for a validation request |
| `deklera/validator_endpoint` | Validation service address |
| `deklera/validator_key` | Validation key, if you would rather not store it in the database |

Actions:

| Action | When |
|---|---|
| `deklera_generate_document` | Queue a document for an order id |
| `deklera_generate_credit_note` | Queue a credit note for a refund id |
| `deklera_document_generated` | After a document is archived |

Requirements: **PHP 8.2+**, **WordPress 6.5+**, **WooCommerce**. The plugin's
dependencies are namespace-isolated, so they cannot collide with another
plugin's copy of the same library.

---

## 8. Uninstalling

Deactivating changes nothing.

Uninstalling removes your **settings**. It does **not** remove your invoices:
the archive directory, the document tables and the key that verifies their
integrity all stay. You asked to remove the settings, not the records.

To also delete the archive, turn on **Delete data on uninstall** before you
uninstall.

---

## 9. Getting help

- Questions and bug reports: <https://github.com/ekremtekerek/deklera/issues>
- Terms: [`docs/TERMS.md`](TERMS.md) · Privacy: [`docs/PRIVACY.md`](PRIVACY.md)

Nothing in this guide is tax or legal advice.
