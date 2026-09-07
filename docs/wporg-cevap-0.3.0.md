# WordPress.org inceleme cevabı — 0.3.0

**Nasıl gönderilir:** 4 Eylül 2026 tarihli inceleme e-postasına, **aynı iş
parçacığında** yanıt olarak. Yeni e-posta açılmaz, gönderim onay e-postasına
cevap yazılmaz.

E-posta ekibin kendi isteği üzerine kısa tutuldu: yaptığımız her değişikliği
anlatmıyor, yalnızca sordukları üç maddeyi ve slug isteğini içeriyor.

Paket önce https://wordpress.org/plugins/developers/add/ sayfasındaki
**"Upload updated plugin for review"** ile yüklenir, sonra bu cevap yazılır.

---

## Gönderilecek metin

```
Requested slug: deklera

1. Plugin name / trademark

The plugin has been renamed to Deklera. The change is not cosmetic: the
display name, the slug, the text domain, the PHP namespace, the option and
hook prefixes, the database table names and the file names were all changed.
No occurrence of the old name is left in the code or the readme.

Deklera is a coined name. Before choosing it I checked it against the plugin
directory search, a general web search for products and companies using the
name, and for trademarks contained in it. Several candidates were discarded
this way because the name was already in use elsewhere.

Please reserve the slug "deklera". The display name is kept short on purpose
so that the slug and the text domain stay identical, which
translate.wordpress.org requires.

2. Out of date library

setasign/fpdf has been updated from 1.8.2 to 1.9.0, the current release.
This was the only third-party library behind its latest version; the rest of
the bundled dependencies were already current.

3. load_plugin_textdomain()

The call no longer runs in the version hosted here. The free and the paid
version are built from one codebase, and the paid version is distributed
outside the directory with its own .mo files, which nothing else would load.
So instead of deleting the call I guarded it: it now runs only in the premium
build. In the version you are reviewing it is unreachable, and translations
come from translate.wordpress.org.

Version 0.3.0 has been uploaded.
```

---

## Cevaba yazılmayanlar (bilerek)

- Yeniden adlandırmanın kod tarafındaki büyüklüğü (116 dosya). İnceleyenin
  ilgilendiği şey değil; e-posta fazla ayrıntı istemediğini açıkça söylüyor.
- Aday adların eleme tablosu. Gerekçe `docs/adr/0009` içinde; sorulursa
  verilir.
- Veri geçişinin olmadığı. Kurulu kullanıcı yok; `readme.txt` içindeki
  **Upgrade Notice** zaten söylüyor.

## Cevap sonrası

- İnceleme sırasına yeniden girilir; yanıt birkaç gün ile birkaç hafta arası
  sürebilir. **Durum sorulmaz** — sormak sırayı yavaşlatıyor.
- Slug `deklera` olarak ayrılana kadar yüklemede "Text Domain" uyarısı
  görülebilir; e-posta bunun beklenen olduğunu söylüyor.
