@echo off
REM WordPress.org surum etiketi — cift tiklanarak calistirilir.
REM
REM Etiket YUKLENMEZ: sunucuda trunk'tan kopyalanir, dosya gitmez, aninda
REM biter. SVN'de etiket almanin dogru yolu budur.
REM
REM Tam URL yazilir, ^/trunk kisayolu DEGIL: batch dosyasinda ^ kacis
REM karakteridir ve svn'e URL yerine yerel yol gecirip
REM "E205009: Local, non-commit operations do not take a log message"
REM hatasi verdirir. Bir kez verdirdi.

setlocal

set SURUM=%~1
if "%SURUM%"=="" set SURUM=0.3.10

set DEPO=https://plugins.svn.wordpress.org/deklera

echo.
echo Deklera %SURUM% etiketi olusturuluyor (sunucuda kopya).
echo Kullanici adi: ekremtekerek
echo.

docker run --rm -it alpine:3 sh -c "apk add --no-cache subversion >/dev/null && svn copy %DEPO%/trunk %DEPO%/tags/%SURUM% -m 'Tag %SURUM%' --username ekremtekerek --config-option servers:global:http-timeout=1800"

echo.
if errorlevel 1 (
  echo Etiket olusturulamadi. Yukaridaki hatayi okuyun.
) else (
  echo Etiket olusturuldu: %DEPO%/tags/%SURUM%
  echo Eklenti birkac dakika icinde su adreste gorunur:
  echo   https://wordpress.org/plugins/deklera
)

echo.
pause
endlocal
