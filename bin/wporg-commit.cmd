@echo off
REM WordPress.org SVN commit — cift tiklanarak calistirilir.
REM
REM Parola bu dosyada YOKTUR ve olmamalidir. svn soracak; WordPress.org
REM profilindeki SVN parolasi (giris parolasi degil).
REM
REM Oncesinde: bash bin/wporg-yukle.sh <surum>  ile calisma kopyasi hazirlanir.

setlocal

set SURUM=%~1
if "%SURUM%"=="" set SURUM=0.3.10

set KOK=%~dp0..
pushd "%KOK%"

if not exist "build\svn\trunk\readme.txt" (
  echo.
  echo HATA: calisma kopyasi yok. Once sunu calistirin:
  echo   bash bin/wporg-yukle.sh %SURUM%
  echo.
  pause
  exit /b 1
)

echo.
echo Deklera %SURUM% WordPress.org dizinine gonderiliyor.
echo.
echo Kullanici adi: ekremtekerek
echo Parola       : WordPress.org profilindeki SVN parolasi
echo.
echo Sertifika sorusu gelirse (p) ile kalici kabul edin.
echo.

docker run --rm -it -v "%CD%:/repo" -w /repo/build/svn alpine:3 sh -c "apk add --no-cache subversion >/dev/null && svn commit --username ekremtekerek -m 'Deklera %SURUM%'"

echo.
if errorlevel 1 (
  echo Gonderim BASARISIZ. Yukaridaki hatayi okuyun.
) else (
  echo Gonderildi. Eklenti birkac dakika icinde su adreste gorunur:
  echo   https://wordpress.org/plugins/deklera
)

echo.
pause
popd
endlocal
