@echo off
REM WordPress.org SVN commit — cift tiklanarak calistirilir.
REM
REM Parola bu dosyada YOKTUR ve olmamalidir. svn soracak; WordPress.org
REM profilindeki SVN parolasi (giris parolasi degil).
REM
REM NEDEN UC ADIM
REM
REM Ilk deneme tek islemde 4378 dosya gondermeye calisti ve sunucu islemi
REM kapatirken zaman asimina ugradi (E175012). Bolerek gonderiyoruz:
REM
REM   1. assets  — 7 dosya. Saniyeler surer ve parolanin dogru oldugunu
REM                30 dakika beklemeden gosterir.
REM   2. trunk   — asil yuk.
REM   3. tags    — YUKLENMEZ. Sunucuda trunk'tan kopyalanir; dosya gitmez,
REM                aninda biter. SVN'de etiket almanin dogru yolu da budur.
REM                Tam URL yazilir, ^/trunk kisayolu DEGIL: batch dosyasinda
REM                ^ kacis karakteridir ve svn'e yerel yol gecirir.
REM
REM Ucu de tek konteynerde kosuyor, yani parola bir kez soruluyor: svn kimligi
REM konteynerin kendi ~/.subversion dizinine onbellekliyor ve konteyner
REM kapaninca o da siliniyor. Makinede parola izi kalmiyor.

setlocal

REM Varsayilan surum HER SURUMDE guncellenir. Cift tiklanan bir betige
REM arguman verilmez; burasi geride kalirsa sessizce eski surumu gonderir ve
REM bunu ancak dizinde yanlis numarayi gorunce anlarsiniz.
set SURUM=%~1
if "%SURUM%"=="" set SURUM=0.3.11

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
echo Parola bir kez sorulacak. Sertifika sorusu gelirse (p) ile kabul edin.
echo trunk adimi uzun surer; noktalar ilerledigi surece calisiyordur.
echo.

docker run --rm -it -v "%CD%:/repo" -w /repo/build/svn alpine:3 sh -c "apk add --no-cache subversion >/dev/null && SVNOPT='--username ekremtekerek --config-option servers:global:http-timeout=1800' && echo '== 1/3 assets ==' && svn commit assets -m 'Deklera %SURUM% assets' $SVNOPT && echo '== 2/3 trunk ==' && svn commit trunk -m 'Deklera %SURUM%' $SVNOPT && echo '== 3/3 etiket (sunucuda kopya) ==' && svn copy https://plugins.svn.wordpress.org/deklera/trunk https://plugins.svn.wordpress.org/deklera/tags/%SURUM% -m 'Tag %SURUM%' $SVNOPT"

echo.
if errorlevel 1 (
  echo Gonderim BASARISIZ. Yukaridaki hatayi okuyun.
  echo Bir adim gectiyse tekrar calistirmak zararsiz: gonderilmis olan
  echo adim 'no changes' deyip gecer.
) else (
  echo Gonderildi. Eklenti birkac dakika icinde su adreste gorunur:
  echo   https://wordpress.org/plugins/deklera
)

echo.
pause
popd
endlocal
