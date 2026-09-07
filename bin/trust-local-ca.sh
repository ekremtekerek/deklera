#!/usr/bin/env sh
# Yerel TLS kesme sertifikasini konteynerin guven deposuna ekler.
#
# NEDEN BU BETIK VAR
#
# Bu makinede Avast Web/Mail Shield giden TLS baglantilarini ortadan boluyor:
# repo.packagist.org sertifikasini kendi koku ile yeniden imzaliyor. Konteyner
# o koku tanimadigi icin composer soyle patliyor:
#
#   curl error 60 while downloading https://repo.packagist.org/packages.json:
#   SSL certificate ... unable to get local issuer certificate (20)
#
# Hata yaniltici: ag calisiyor, paket var, sorun yalnizca guven zinciri. Cozum
# antivirusu kapatmak DEGIL (kurumsal makine, yetki yok) - kokunu konteynere
# tanitmak. Kok sertifika Windows deposundan su komutla cikarilir:
#
#   powershell -c "\$c = Get-ChildItem Cert:\LocalMachine\Root |
#     Where-Object { \$_.Subject -like '*Avast*' } | Select-Object -First 1;
#     [IO.File]::WriteAllText('build/local-ca.pem',
#       \"-----BEGIN CERTIFICATE-----`n\" +
#       [Convert]::ToBase64String(\$c.RawData, 'InsertLineBreaks') +
#       \"`n-----END CERTIFICATE-----`n\")"
#
# Dosya build/ altinda durur ve surum kontrolune GIRMEZ: makineye ozeldir,
# baska bir makinede yanlis olur. Yoksa betik sessizce gecer - TLS'i kesen bir
# antivirus olmayan makinede zaten gerek yok.
#
# Kullanim: trust-local-ca.sh [pem-yolu]
CA="${1:-/repo/build/local-ca.pem}"

[ -f "$CA" ] || exit 0
command -v update-ca-certificates >/dev/null 2>&1 || exit 0

cp "$CA" /usr/local/share/ca-certificates/local-ca.crt 2>/dev/null || exit 0
update-ca-certificates >/dev/null 2>&1 || exit 0

echo "==> Yerel CA guven deposuna eklendi ($CA)"
