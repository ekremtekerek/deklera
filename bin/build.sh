#!/usr/bin/env bash
# Deklera - dagitim arsivi uretir (WordPress.org / Freemius).
#
# Kullanim: bash bin/build.sh [surum] [--premium]
#
# Uretilen zip'in kokunde "deklera/" dizini bulunur; WordPress eklenti
# arsivlerinin beklenen bicimi budur.
#
# Hazirlik dizini depo icindedir (build/), boylece composer servisinin var
# olan /repo baglantisi kullanilir ve ek mount gerekmez.
set -euo pipefail

# Git Bash konteyner icindeki yollari Windows yoluna cevirmeye calisir.
export MSYS_NO_PATHCONV=1

cd "$(dirname "$0")/.."

BUILD="build"
STAGE="$BUILD/deklera"

# Iki varyant uretilir ve ayrimi tek bir bayrak belirler:
#
#   bash bin/build.sh              -> ucretsiz surum, WordPress.org'a gider
#   bash bin/build.sh --premium    -> ucretli surum, Freemius'a gider
#
# Fark yalnizca Freemius SDK'sinin is_premium bayragidir. Bu bayrak bir OZELLIK
# KAPISI DEGILDIR - o yanilgiya dusulmustu, olcumle duzeltildi:
#
#   can_use_premium_code() { return is_trial() || has_features_enabled_license(); }
#
# Yani lisansi olan bir kullanicida Pro, is_premium kapali olsa da acilir.
#
# Bayragin isi, calisan yapinin hangisi oldugunu isaretlemektir. SDK guncelleme
# isteginde "is_premium() || _can_download_premium()" diye bakar; premium paket
# musterinin indirdigi urundur. Freemius'a yuklenen zip bu yuzden premium
# olmalidir - aksi halde surumler ucretsiz yapi olarak dagitilir.
PREMIUM=0
ARGS=()

for arg in "$@"; do
  if [ "$arg" = "--premium" ]; then
    PREMIUM=1
  else
    ARGS+=("$arg")
  fi
done

VERSION="${ARGS[0]:-$(grep -oE '^ \* Version: +[0-9A-Za-z.-]+' plugin/deklera/deklera.php | awk '{print $3}')}"

if [ "$PREMIUM" = "1" ]; then
  SUFFIX="-premium"
  echo "==> Deklera $VERSION PREMIUM paketleniyor"
else
  SUFFIX=""
  echo "==> Deklera $VERSION paketleniyor"
fi

# Yalnizca hazirlik dizini silinir, build/ dizini degil: ucretsiz ve premium
# varyantlar art arda uretiliyor ve ilkinin zip'i ikincisini uretirken
# silinmemeli.
#
# Windows'ta dosya kilidi yuzunden silme gecici olarak basarisiz olabilir ve
# set -e tum yapiyi dusurur. Birkac kez denenir.
for attempt in 1 2 3; do
  rm -rf "$STAGE" 2>/dev/null || true
  [ -d "$STAGE" ] || break
  sleep 2
done

if [ -d "$STAGE" ]; then
  echo "HATA: $STAGE silinemedi. Docker konteynerleri dosyayi tutuyor olabilir." >&2
  exit 1
fi

mkdir -p "$STAGE"

echo "==> Kaynak kopyalaniyor"
tar -cf - -C plugin/deklera \
  --exclude=vendor \
  --exclude=vendor-prefixed \
  --exclude=tests \
  --exclude=.gitignore \
  --exclude=phpunit.xml.dist \
  --exclude=.phpunit.cache \
  . | tar -xf - -C "$STAGE"

if [ "$PREMIUM" = "1" ]; then
  # Yalnizca HAZIRLIK dizinindeki kopya degistirilir; depodaki kaynak
  # ucretsiz surumdur ve oyle kalir.
  FS_FILE="$STAGE/src/License/Freemius.php"

  sed -i "s/'is_premium'       => false,/'is_premium'       => true,/" "$FS_FILE"

  if ! grep -q "'is_premium'       => true," "$FS_FILE"; then
    echo "HATA: is_premium bayragi degistirilemedi. Freemius.php'deki hizalama degismis olabilir." >&2
    exit 1
  fi

  echo "==> is_premium bayragi acildi"
fi

compose() { docker compose run --rm -T -w "/repo/$1" composer "${@:2}"; }

# Kurulum, Strauss ve otomatik yukleyici uretimi bilerek deps.sh uzerinden
# calisir: hepsi dizinleri PHP'nin RecursiveDirectoryIterator'uyla gezer ve
# Windows bind mount'u o yineleyicide buyuk dizinleri eksik dondurur. Dogrudan
# calistirilirlarsa sinif dosyalari sessizce pakete girmez. Bkz. bin/deps.sh
echo "==> Bagimliliklar kuruluyor ve izole ediliyor (Strauss)"
compose "." sh bin/deps.sh "/repo/$STAGE" >/dev/null

echo "==> Gelistirme paketleri temizleniyor"
# vendor/ icinde KALMASI gerekenler: onekLENMEYEN uretim paketleri.
# Freemius SDK bunlardan biridir - SDK surum tahkimi global sinifi paylasmaya
# dayanir, oneklenirse lisanslama bozulur. Bu yuzden "composer disinda her seyi
# sil" yerine uretim listesinden onekli olanlari cikararak calisiyoruz.
KEEP="$BUILD/.keep-list"
compose "$STAGE" composer show --no-dev --name-only --no-interaction 2>/dev/null   | tr -d '' | awk 'NF' > "$KEEP"

find "$STAGE/vendor" -mindepth 1 -maxdepth 1 -type d -not -name composer | while read -r dir; do
  vendor_name="$(basename "$dir")"
  for pkg in $(ls "$dir" 2>/dev/null); do
    full="$vendor_name/$pkg"
    if grep -qx "$full" "$KEEP" 2>/dev/null && [ ! -d "$STAGE/vendor-prefixed/$full" ]; then
      continue
    fi
    rm -rf "$dir/$pkg"
  done
  rmdir "$dir" 2>/dev/null || true
done

# vendor/composer/ yukaridaki dongude atlanir, cunku otomatik yukleyici
# dosyalarini barindirir ve onlar kalmalidir. Ama ayni dizinde Strauss'un
# cektigi composer paketleri de vardir (composer/composer, composer/pcre,
# composer/semver ...). Atlanan dizin hic taranmadigi icin Composer'in kendisi
# eklentiyle birlikte dagitiliyordu. Burada yalnizca ALT DIZINLER, yani
# paketler degerlendirilir; dosyalara dokunulmaz.
find "$STAGE/vendor/composer" -mindepth 1 -maxdepth 1 -type d | while read -r pkg_dir; do
  full="composer/$(basename "$pkg_dir")"
  if grep -qx "$full" "$KEEP" 2>/dev/null && [ ! -d "$STAGE/vendor-prefixed/$full" ]; then
    continue
  fi
  rm -rf "$pkg_dir"
done

rm -f "$KEEP"

# Eklentide bulunmamasi gereken dosya turlerini cikar.
#
# NEDEN
#
# WordPress.org 10 Eylul 2026'da 0.3.6'yi bu gerekceyle geri cevirdi: bir
# eklentinin icinde php/js/css/txt/md, birkac medya ve veri dosyasi beklenir;
# .sch ve .xslt beklenmez. Ornek olarak on iki dosya gosterildi ama inceleme
# ekibi "ayni sorunun butun orneklerini paylasmayabiliriz" diyor -- yani
# yalnizca gosterilenleri silmek yetmez.
#
# Silinenlerin hicbiri calisma aninda okunmuyor. zugferd'in schema/ dizini
# (sch, xslt, xsd) yalnizca ZugferdProfiles icindeki bir dizide ISIMLE
# geciyor; hicbir kod acmiyor, ve o dizideki dogrulayici siniflari
# (ZugferdDocumentValidator, ZugferdKositValidator) bu eklentide
# kullanilmiyor. Geri kalanlar surekli tumlestirme ve gelistirme artefakti.
#
# KALANLAR VE SEBEPLERI
#
#   *.xmp  -- facturx_extension_schema.xmp, Factur-X'in PDF/A-3 icine gomulen
#             XMP uzanti semasi. ZugferdDocumentPdfBuilder onu OKUYOR;
#             silinirse Fransa ciktisi bozulur.
#   *.xsd  -- intermedia/ksef-fa3/schema/FA3.xsd, Polonya FA(3) belgesi
#             uretilirken resmi semaya karsi dogrulanir (Fa3Builder::SCHEMA).
#
# Ikisi de incelemeye aciklanmali; "gereksiz dosya" degiller.
echo "==> Beklenmeyen dosya turleri cikariliyor"

rm -rf "$STAGE/vendor-prefixed/horstoeko/zugferd/src/schema"

# symfony/validator'in XML esleme semasi: yalnizca XmlFileLoader kullanir,
# bu eklenti oznitelik tabanli eslemeyi kullanir.
rm -rf "$STAGE/vendor-prefixed/symfony/validator/Mapping/Loader/schema"

# tfpdf ile gelen ornek cikti; kutuphane onu hicbir yerde okumuyor.
rm -f "$STAGE/vendor-prefixed/setasign/tfpdf/ex.pdf"

onceki=$(find "$STAGE" -type f | wc -l)

# .yml SILINMEZ. Ilk denemede silindi ve XML uretimi tamamen bozuldu:
# zugferd'in src/yaml/ dizinindeki 270 dosya JMS serializer'in ust verisidir,
# kutuphane onlari addMetadataDir ile KAYIT EDIYOR. Onlarsiz belge yine
# uretiliyor -- yalnizca yanlis uretiliyor, ve hicbir hata cikmiyor.
# Uc ulke sinavi (build/sinav-budama.php) bunu yakaladi. Ayni gerekce
# .xlf icin de gecerli olabilir; dokunulmuyor.
#
# Kural: yalnizca OKUNMADIGI DOGRULANAN silinir.
find "$STAGE" \( \
  -name '*.map' -o -name '*.dist' -o -name '*.neon' -o -name '*.sch' -o \
  -name '*.xslt' -o -name '*.htm' -o -name '.gitignore' -o \
  -name '.gitattributes' -o -name '.editorconfig' -o -name '.php-cs-fixer*' \
  \) -delete

echo "    $(( onceki - $(find "$STAGE" -type f | wc -l) )) dosya cikarildi"

# Gerekli olanlar hala yerinde mi. Sessizce silinmis bir sema, Fransa ya da
# Polonya ciktisini calisma aninda bozar ve bunu ancak musteri gorur.
for gerekli in \
  "vendor-prefixed/horstoeko/zugferd/src/assets/facturx_extension_schema.xmp" \
  "vendor-prefixed/horstoeko/zugferd/src/assets/sRGB2014.icc" \
  "vendor-prefixed/intermedia/ksef-fa3/schema/FA3.xsd"; do
  if [ ! -f "$STAGE/$gerekli" ]; then
    echo "HATA: budama gerekli dosyayi sildi: $gerekli" >&2
    exit 1
  fi
done

# Serializer ust verisi: sayisi dususe belge sessizce bozulur.
ustveri=$(find "$STAGE/vendor-prefixed/horstoeko/zugferd/src/yaml" -name '*.yml' | wc -l)

if [ "$ustveri" -lt 200 ]; then
  echo "HATA: serializer ust verisi eksik ($ustveri dosya)." >&2
  exit 1
fi

# Temizlikten sonra classmap yeniden uretilmeli. Bu adim da izole calisir:
# Deklera\Vendor\* siniflarinin psr-4 karsiligi yoktur, yalnizca classmap'ten
# cozulurler; budanmis bir classmap calisma aninda olumcul hatadir.
compose "." sh bin/dump-autoload.sh "/repo/$STAGE" --no-dev --optimize >/dev/null

# composer.lock gitmez; kilit dosyasi gelistirme artefaktidir ve buyuktur.
#
# composer.json ise KALIR. WordPress.org'un otomatik taramasi, vendor/ dizini
# olup composer.json olmayan pakete "missing_composer_json_file" uyarisi
# veriyor - inceleyen kisi vendor/ icinde ne oldugunu dogrulayamiyor.
# Dosyayi birakmak uyariyi dolanmak degil, tam da taramanin istedigi seyi
# vermek. Bagimliliklarin vendor-prefixed/ altinda oneklendigini de
# composer.json'daki strauss bolumu acikliyor.
rm -f "$STAGE/composer.lock"


# Ceviriler derleniyor.
#
# .po dosyalari depoda izlenir, .mo ve .l10n.php izlenmez: ikisi de uretilmis
# ciktidir. Bu adim olmazsa paket cevirileri TASIR ama WordPress OKUYAMAZ --
# ve eksiklik hicbir hata vermez, arayuz sessizce Ingilizce kalir.
#
# Yalnizca Pro pakette ise yarar; ucretsiz surumde ceviriler
# translate.wordpress.org'dan dil paketi olarak gelir (bkz. docs/I18N.md 9).
# Ikisi de burada uretilir, ucretsiz olanlar asagida cikarilir.
echo "==> Ceviriler derleniyor"
MSYS_NO_PATHCONV=1 docker compose run --rm -T \
  -v "$(pwd)/$STAGE/languages:/lang" \
  --entrypoint sh wpcli -c \
  "wp i18n make-mo /lang >/dev/null && wp i18n make-php /lang >/dev/null"

mo_sayisi=$(find "$STAGE/languages" -name '*.mo' | wc -l)
po_sayisi=$(find "$STAGE/languages" -name '*.po' | wc -l)

if [ "$mo_sayisi" != "$po_sayisi" ]; then
  echo "HATA: $po_sayisi .po dosyasi var ama $mo_sayisi .mo uretildi." >&2
  exit 1
fi

echo "    $mo_sayisi dil derlendi"
# Ucretsiz pakete DERLENMIS ceviri konmaz.
#
# Sebep davranissal, kozmetik degil: Plugin::load_translations() serbest
# yapida is_premium() yanlis oldugu icin ERKEN DONER. Yani .mo dosyalari
# ucretsiz pakete girse bile HIC OKUNMAZ -- olu agirlik. WordPress.org'da
# ceviriler zaten translate.wordpress.org'dan dil paketi olarak gelir
# (bkz. docs/I18N.md 9).
#
# Yan faydasi: Plugin Check uretilmis .l10n.php dosyalarini PHP sanip
# icindeki suzgec adlarini gecersiz onek diye bildiriyordu. Alti uyari
# incelemeciye gurultu olarak gidiyordu; artik dosya yok.
#
# .pot KALIR: GlotPress ceviri kaynagi olarak onu okur.
if [ -z "$SUFFIX" ]; then
  find "$STAGE/languages" -type f ! -name '*.pot' -delete
  echo "==> Ucretsiz paket: derlenmis ceviriler cikarildi"
fi

echo "==> Arsivleniyor"

# Once varsa eski arsiv silinir.
#
# "zip -r" var olan bir arsivi GUNCELLER: yeni dosyalari ekler, degisenleri
# tazeler, ama ARTIK OLMAYAN dosyalari SILMEZ. Paketten bir dosya cikarildiginda
# eski zip'te sonsuza kadar kalir ve kimse fark etmez. Tam olarak bu oldu:
# ucretsiz paketten derlenmis ceviriler cikarildi, hazirlik dizininde yoklardi,
# ama zip'te duruyorlardi ve boyut hic degismedigi icin fark edilmedi.
rm -f "$BUILD/deklera-$VERSION$SUFFIX.zip"

# zip her makinede kurulu degil (Git Bash'te yok, GNU tar zip uretemez);
# konteynerdekini kullaniyoruz.
compose "$BUILD" sh -c "zip -qr deklera-$VERSION$SUFFIX.zip deklera"

echo
echo "Hazir: $BUILD/deklera-$VERSION$SUFFIX.zip"
printf "  boyut : %s KB\n" "$(du -k "$BUILD/deklera-$VERSION$SUFFIX.zip" | cut -f1)"
printf "  dosya : %s\n" "$(unzip -l "$BUILD/deklera-$VERSION$SUFFIX.zip" | tail -1 | awk '{print $2}')"
