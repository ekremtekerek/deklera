#!/usr/bin/env bash
# WordPress.org eklenti dizinine SVN ile yukleme hazirligi.
#
# NEDEN BETIK
#
# SVN bir SURUM sistemidir, git gibi bir calisma sistemi degil: dizine giden
# her sey yayindaki eklenti olur. Elle yapilan bir kopyalamada yanlis dosya
# gondermek ya da tags/ ile trunk/ arasinda fark birakmak kolaydir ve ikisi de
# ancak musteri indirdiginde gorulur.
#
# NEDEN DOCKER
#
# Bu makinede svn kurulu degil ve yonetici hakki yok. Konteyner icinden
# kosmak makineye hicbir sey kurmadan ayni isi yapiyor.
#
# NE YAPMAZ
#
# COMMIT ETMEZ. Commit icin WordPress.org SVN parolasi gerekir; parola bu
# betige girmez, girmemelidir. Betik calisma kopyasini hazirlar, degisiklikleri
# listeler ve commit komutunu ekrana yazar.
#
# Kullanim: bash bin/wporg-yukle.sh <surum>
set -euo pipefail

SURUM="${1:?kullanim: wporg-yukle.sh <surum>}"
KOK="$(cd "$(dirname "$0")/.." && pwd)"
ZIP="build/deklera-${SURUM}.zip"
CALISMA="build/svn"

cd "$KOK"
[ -f "$ZIP" ] || { echo "paket yok: $ZIP" >&2; exit 1; }

export MSYS_NO_PATHCONV=1

# Konteyner icinde svn: her calistirmada kurulum yapmamak icin tek oturumda
# bircok komut kosuluyor.
svn_ile() {
	docker run --rm -v "$KOK:/repo" -w /repo alpine:3 sh -c "
		apk add --no-cache subversion >/dev/null 2>&1
		$1
	"
}

SVN_SECENEK="--non-interactive --trust-server-cert-failures=unknown-ca,cn-mismatch,expired,not-yet-valid"

echo "==> Depo aliniyor"
rm -rf "$CALISMA"
svn_ile "svn checkout $SVN_SECENEK https://plugins.svn.wordpress.org/deklera /repo/$CALISMA"

echo "==> Paket aciliyor"
rm -rf build/wporg-trunk
mkdir -p build/wporg-trunk
( cd build/wporg-trunk && unzip -q "../../$ZIP" )

[ -d build/wporg-trunk/deklera ] || { echo "HATA: pakette deklera/ dizini yok" >&2; exit 1; }

echo "==> trunk dolduruluyor"
# Once icerigi temizle, sonra doldur: eski surumden kalan bir dosyanin dizinde
# yasamaya devam etmesi sessiz bir hatadir.
mkdir -p "$CALISMA/trunk"
find "$CALISMA/trunk" -mindepth 1 -maxdepth 1 ! -name '.svn' -exec rm -rf {} +
cp -r build/wporg-trunk/deklera/. "$CALISMA/trunk/"

echo "==> assets dolduruluyor"
mkdir -p "$CALISMA/assets"
# icon-300x300 gonderilmez: dizin 128 ve 256 kullanir, otekini yok sayar.
for varlik in banner-772x250.png banner-1544x500.png icon-128x128.png \
	icon-256x256.png screenshot-1.jpg screenshot-2.jpg screenshot-3.jpg; do
	[ -f "assets/$varlik" ] || { echo "HATA: assets/$varlik yok" >&2; exit 1; }
	cp "assets/$varlik" "$CALISMA/assets/$varlik"
done

echo "==> Surum etiketi: tags/$SURUM"
rm -rf "${CALISMA:?}/tags/$SURUM"
mkdir -p "$CALISMA/tags"
cp -r "$CALISMA/trunk" "$CALISMA/tags/$SURUM"
find "$CALISMA/tags/$SURUM" -name '.svn' -type d -prune -exec rm -rf {} + 2>/dev/null || true

echo "==> Yeni dosyalar kaydediliyor"
svn_ile "cd /repo/$CALISMA && svn add --force --parents . --auto-props --no-ignore -q 2>/dev/null || true"

echo
echo "==> Durum"
svn_ile "cd /repo/$CALISMA && svn status | head -20; echo '...'; svn status | wc -l | sed 's/^/toplam degisiklik: /'"

echo
echo "==> Denetim"
# readme.txt'deki Stable tag ile etiketin ayni olmasi sart: tutmazsa dizin
# eski surumu yayinlamaya devam eder ve hicbir hata gorunmez.
KARARLI=$(grep -m1 '^Stable tag:' "$CALISMA/trunk/readme.txt" | sed 's/Stable tag: *//' | tr -d '\r')

if [ "$KARARLI" != "$SURUM" ]; then
	echo "HATA: readme.txt Stable tag '$KARARLI', etiket '$SURUM'." >&2
	exit 1
fi

echo "  ok    Stable tag = $SURUM"
echo "  ok    trunk dosya sayisi: $(find "$CALISMA/trunk" -type f -not -path '*/.svn/*' | wc -l)"
echo "  ok    tags/$SURUM dosya sayisi: $(find "$CALISMA/tags/$SURUM" -type f | wc -l)"
echo "  ok    assets: $(find "$CALISMA/assets" -type f -not -path '*/.svn/*' | wc -l) dosya"

cat <<SON

Calisma kopyasi hazir: $CALISMA

Commit SENDE. Parola bu betige girmez. Su komutu kendi terminalinde calistir
(kullanici adi ekremtekerek, parola WordPress.org profilindeki SVN parolasi):

  docker run --rm -it -v "$KOK:/repo" -w /repo/$CALISMA alpine:3 sh -c \\
    "apk add --no-cache subversion >/dev/null && \\
     svn commit --username ekremtekerek \\
       -m 'Deklera $SURUM' $SVN_SECENEK"

Commit sonrasi eklenti birkac dakika icinde
https://wordpress.org/plugins/deklera adresinde gorunur.
SON
