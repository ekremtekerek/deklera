@echo off
REM readme.txt duzeltmesini WordPress.org'a gonderir — cift tiklanir.
REM
REM Dizin aramasi buyuk olcude etiketlere ve kisa aciklamaya bakar, yani
REM readme.txt keşfedilmenin kendisidir. Bir metin duzeltmesi yeni surum
REM cikarmayi hak etmiyor; bunun yerine hem trunk hem de yayindaki etiket
REM guncelleniyor. Dizin hangisini okursa okusun dogru olsun diye ikisi de.
REM
REM Iki calisma kopyasi var:
REM   build/svn         — tam kopya, trunk burada
REM   build/svn-etiket  — yalnizca tags/<yayindaki surum>/readme.txt (seyrek)
REM
REM Seyrek kopya YENI SURUMDE ETIKET DEGISTIRMELI, yoksa duzeltme eski
REM etikete gider ve dizin yayindaki surumu okudugu icin hicbir sey
REM degismis gorunmez. Su an tags/0.3.11'e bakiyor. Degistirmek icin:
REM   svn switch --ignore-ancestry ^
REM     https://plugins.svn.wordpress.org/deklera/tags/<surum> build/svn-etiket
REM
REM Ikisi tek konteynerde gonderiliyor, parola bir kez soruluyor ve konteyner
REM kapaninca kimlik onbellegi siliniyor.

setlocal

set KOK=%~dp0..
pushd "%KOK%"

if not exist "build\svn\trunk\readme.txt" (
  echo HATA: build\svn calisma kopyasi yok.
  pause
  exit /b 1
)

if not exist "build\svn-etiket\readme.txt" (
  echo HATA: build\svn-etiket seyrek kopyasi yok.
  pause
  exit /b 1
)

echo.
echo readme.txt duzeltmesi gonderiliyor (trunk + tags/0.3.11).
echo Kullanici adi: ekremtekerek
echo.

REM Gonderim mesaji burada duruyor: her readme duzeltmesinde degistirilir,
REM yoksa SVN gecmisinde ard arda ayni satir kalir ve hangi commit neyi
REM degistirdi okunmaz.
set MSG=readme: link to the site, the free checker and the validator measurement

docker run --rm -it -v "%CD%:/repo" -w /repo/build alpine:3 sh -c "apk add --no-cache subversion >/dev/null && SVNOPT='--username ekremtekerek --config-option servers:global:http-timeout=1800' && MSG='%MSG%' && echo '== 1/2 trunk ==' && cd /repo/build/svn && svn commit trunk/readme.txt -m \"$MSG\" $SVNOPT && echo '== 2/2 etiket ==' && cd /repo/build/svn-etiket && svn commit readme.txt -m \"$MSG\" $SVNOPT"

echo.
if errorlevel 1 (
  echo Gonderim BASARISIZ. Yukaridaki hatayi okuyun.
) else (
  echo Gonderildi. Dizin sayfasi birkac dakika icinde guncellenir.
)

echo.
pause
popd
endlocal
