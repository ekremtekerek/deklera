#!/usr/bin/env bash
# Temiz kurulum sinavi - docs/RELEASE.md "Temiz kurulum sinavi" bolumunun
# betiklestirilmis hali.
#
# NEDEN BETIK
#
# Sinav elle yapildiginda bir adim atlaniyor ve atlandigi anlasilmiyor.
# Kaldirma temizligi pakette hic calismiyordu ve bunu ancak adimlarin tamami
# kosuldugunda gorduk; 0.3.8'de de iki ayar geride kaliyordu ve ciktiyi
# okuyan olmasa fark edilmeyecekti.
#
# Bu yuzden betik yalnizca RAPOR ETMEZ, KARAR VERIR: beklenmeyen her sonuc
# sayilir ve sonunda cikis kodu ile bildirilir.
#
# Kullanim: bash bin/temiz-kurulum-sinavi.sh <surum>
set -uo pipefail

SURUM="${1:?kullanim: temiz-kurulum-sinavi.sh <surum>}"
KOK="$(cd "$(dirname "$0")/.." && pwd)"
ZIP="build/deklera-${SURUM}-premium.zip"

cd "$KOK"
[ -f "$ZIP" ] || { echo "paket yok: $ZIP" >&2; exit 1; }

C="docker compose -f docker-compose.clean.yml -p deklera-clean"
export MSYS_NO_PATHCONV=1

HATA=0

wpcli() { $C run --rm -T --user root wpcli "$@" 2>/dev/null; }

# Bir beklentiyi olcer.
#   olc <ad> <beklenen> <gercek>
olc() {
	if [ "$2" = "$3" ]; then
		printf '  ok    %s\n' "$1"
	else
		printf '  HATA  %s — beklenen "%s", gelen "%s"\n' "$1" "$2" "$3"
		HATA=$((HATA + 1))
	fi
}

echo "==> Ortam sifirlaniyor"
$C down -v >/dev/null 2>&1 || true
$C up -d db >/dev/null 2>&1

echo "==> Veritabani bekleniyor"
until [ "$($C ps -a --format '{{.Service}} {{.Health}}' 2>/dev/null | awk '$1=="db"{print $2}')" = "healthy" ]; do
	sleep 5
done

$C up -d >/dev/null 2>&1

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
wpcli wp plugin install "/build/$(basename "$ZIP")" --activate --allow-root --path=/var/www/html >/dev/null

echo
echo "1. Kurulum"
olc "eklenti etkin" "active" \
	"$(wpcli wp plugin get deklera --field=status --allow-root --path=/var/www/html | tr -d '\r')"
olc "surum dogru" "$SURUM" \
	"$(wpcli wp plugin get deklera --field=version --allow-root --path=/var/www/html | tr -d '\r')"

# Bos [] donerse kaldirma temizligi HIC calismaz; bir kez tam olarak bu oldu.
KAYIT="$(wpcli wp option get uninstall_plugins --format=json --allow-root --path=/var/www/html | tr -d '\r')"
case "$KAYIT" in
	*deklera*) printf '  ok    kaldirma kaydi var\n' ;;
	*) printf '  HATA  kaldirma kaydi yok: %s\n' "$KAYIT"; HATA=$((HATA + 1)) ;;
esac

echo
echo "2. Belge uretimi"
wpcli wp eval-file /build/sinav-kur.php --allow-root --path=/var/www/html >/dev/null
SIPARIS="$(wpcli wp eval 'echo (int) current( get_posts( array( "post_type" => "shop_order", "posts_per_page" => 1, "fields" => "ids", "post_status" => "any" ) ) );' --allow-root --path=/var/www/html | tr -d '\r')"
URETIM="$(wpcli wp eval-file /build/sinav-taban.php "$SIPARIS" --allow-root --path=/var/www/html)"

echo "$URETIM" | grep -q "belge uretildi" \
	&& printf '  ok    belge uretildi\n' \
	|| { printf '  HATA  belge uretilmedi\n'; HATA=$((HATA + 1)); }
echo "$URETIM" | grep -q "butun  : EVET" \
	&& printf '  ok    dosya butun\n' \
	|| { printf '  HATA  butunluk dogrulanmadi\n'; HATA=$((HATA + 1)); }

echo
echo "3. Kaldirma temizligi"
wpcli sh -c "
wp option update deklera_delete_data_on_uninstall 1 --allow-root --path=/var/www/html >/dev/null
wp plugin uninstall deklera --deactivate --allow-root --path=/var/www/html
" >/dev/null

# Ayrimin anlami: kullanici AYARLARINI silmek istedi, FATURALARINI degil.
for AYAR in deklera_seller_phone deklera_seller_contact deklera_seller_vat_number deklera_validator_endpoint; do
	if wpcli wp option get "$AYAR" --allow-root --path=/var/www/html >/dev/null 2>&1; then
		printf '  HATA  %s duruyor, silinmeliydi\n' "$AYAR"
		HATA=$((HATA + 1))
	else
		printf '  ok    %s silindi\n' "$AYAR"
	fi
done

wpcli wp option get deklera_archive_key --allow-root --path=/var/www/html >/dev/null 2>&1 \
	&& printf '  ok    deklera_archive_key duruyor\n' \
	|| { printf '  HATA  deklera_archive_key silinmis\n'; HATA=$((HATA + 1)); }

DOSYA="$(wpcli sh -c 'find /var/www/html/wp-content/uploads -path "*deklera*" -type f | wc -l' | tr -d '\r ')"
[ "${DOSYA:-0}" -gt 0 ] \
	&& printf '  ok    arsiv dosyalari duruyor (%s)\n' "$DOSYA" \
	|| { printf '  HATA  arsiv dosyalari silinmis\n'; HATA=$((HATA + 1)); }

SATIR="$(wpcli wp db query "SELECT COUNT(*) FROM wp_deklera_documents" --skip-column-names --allow-root --path=/var/www/html | tr -d '\r ')"
[ "${SATIR:-0}" -gt 0 ] \
	&& printf '  ok    belge tablosu duruyor (%s satir)\n' "$SATIR" \
	|| { printf '  HATA  belge tablosu bos ya da yok\n'; HATA=$((HATA + 1)); }

echo
if [ "$HATA" -gt 0 ]; then
	echo "$HATA kontrol basarisiz — surum cikarilmaz."
	exit 1
fi

echo "Temiz kurulum sinavi gecti."
