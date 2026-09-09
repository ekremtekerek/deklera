#!/usr/bin/env bash
# Temiz kurulum sinavi - docs/RELEASE.md "Temiz kurulum sinavi" bolumunun
# betiklestirilmis hali.
#
# NEDEN BETIK
#
# Sinav elle yapildiginda bir adim atlaniyor ve atlandigi anlasilmiyor.
# Kaldirma temizligi pakette hic calismiyordu ve bunu ancak adimlarin
# tamami kosuldugunda gorduk. Betik sirayi ve olcumu birlikte tutar.
#
# Kullanim: bash bin/temiz-kurulum-sinavi.sh <surum>
set -euo pipefail

SURUM="${1:?kullanim: temiz-kurulum-sinavi.sh <surum>}"
KOK="$(cd "$(dirname "$0")/.." && pwd)"
ZIP="build/deklera-${SURUM}-premium.zip"

cd "$KOK"
[ -f "$ZIP" ] || { echo "paket yok: $ZIP" >&2; exit 1; }

C="docker compose -f docker-compose.clean.yml -p deklera-clean"
export MSYS_NO_PATHCONV=1

wpcli() { $C run --rm -T --user root wpcli "$@"; }

echo "==> Ortam sifirlaniyor"
$C down -v >/dev/null 2>&1 || true
$C up -d db >/dev/null

echo "==> Veritabani bekleniyor"
until [ "$($C ps -a --format '{{.Service}} {{.Health}}' | awk '$1=="db"{print $2}')" = "healthy" ]; do
  sleep 5
done

$C up -d >/dev/null

echo "==> WordPress dosyalari bekleniyor"
until $C exec -T wordpress test -f /var/www/html/wp-settings.php 2>/dev/null; do sleep 5; done
until $C logs wordpress 2>&1 | grep -q "Complete!"; do sleep 5; done

echo "==> Kurulum"
wpcli sh -c '
cp /build/local-ca.pem /usr/local/share/ca-certificates/local-ca.crt 2>/dev/null && update-ca-certificates >/dev/null 2>&1 || true
wp core install --url=http://localhost:8090 --title=Sinav --admin_user=admin \
  --admin_password=admin --admin_email=test@example.test --skip-email \
  --allow-root --path=/var/www/html
cat /build/local-ca.pem >> /var/www/html/wp-includes/certificates/ca-bundle.crt 2>/dev/null || true
wp plugin install woocommerce --activate --allow-root --path=/var/www/html
' >/dev/null

echo "==> Paket kuruluyor: $ZIP"
wpcli wp plugin install "/build/$(basename "$ZIP")" --activate --allow-root --path=/var/www/html

echo
echo "==> 1. Kaldirma kaydi"
# Bos [] donerse kaldirma temizligi HIC calismaz; bir kez tam olarak bu oldu.
wpcli wp option get uninstall_plugins --format=json --allow-root --path=/var/www/html

echo
echo "==> 2. Belge uretimi"
wpcli wp eval-file /build/sinav-kur.php --allow-root --path=/var/www/html
SIPARIS="$(wpcli wp eval 'echo (int) current( get_posts( array( "post_type" => "shop_order", "posts_per_page" => 1, "fields" => "ids", "post_status" => "any" ) ) );' --allow-root --path=/var/www/html | tr -d '\r')"
wpcli wp eval-file /build/sinav-taban.php "$SIPARIS" --allow-root --path=/var/www/html

echo
echo "==> 3. Kaldirma temizligi"
wpcli sh -c "
wp option update deklera_delete_data_on_uninstall 1 --allow-root --path=/var/www/html >/dev/null
wp plugin uninstall deklera --deactivate --allow-root --path=/var/www/html
"

echo
echo "==> 4. Olcum"
wpcli sh -c '
printf "ayar secenekleri  : "; wp option get deklera_seller_phone --allow-root --path=/var/www/html 2>/dev/null || echo "silinmis (beklenen)"
printf "deklera_archive_key: "; wp option get deklera_archive_key --allow-root --path=/var/www/html >/dev/null 2>&1 && echo "duruyor (beklenen)" || echo "SILINMIS -- HATA"
printf "arsiv dosyalari   : "; find /var/www/html/wp-content/uploads -path "*deklera*" -type f | wc -l
printf "belge tablosu     : "; wp db query "SELECT COUNT(*) FROM wp_deklera_documents" --skip-column-names --allow-root --path=/var/www/html 2>/dev/null || echo "YOK -- HATA"
'
