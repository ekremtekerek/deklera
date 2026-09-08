/*
 * Ziyaretçiye sayfanın kendi dilinde de var olduğunu söyler.
 *
 * NEDEN YÖNLENDİRME DEĞİL
 *
 * Otomatik yönlendirme iki şeyi birden bozar. Tarayıcısı Almanca olan ama
 * İngilizce okumayı tercih eden ziyaretçiyi istemediği sayfaya atar; ve
 * reklamın hangi sayfaya düştüğünü ölçülemez hâle getirir — kampanya
 * bağlantısı /de/ diyorsa oraya gitmelidir, betiğin fikrine göre değil.
 *
 * Bu yüzden yalnızca haber verir. Kapatılınca bir daha görünmez.
 *
 * HEDEFİ SAYFA SÖYLER
 *
 * Yol, dil kısmını yolun SONUNDAN keserek bulunamaz: /de/ için işe yarardı
 * ama /de/check/ için yanlış sonuç verirdi. Bunun yerine her sayfa iki şey
 * bildirir: site köküne göreli yolu (data-root) ve kendi konumu (data-page).
 * Böylece hedef doğrudan kurulur ve site bir alt dizinde barındırılsa da
 * (GitHub Pages'te /deklera/) çalışır.
 */
(function () {
  'use strict';

  var ANAHTAR = 'deklera-lang';

  var METIN = {
    en: ['This page is available in English.', 'Read it in English'],
    de: ['Diese Seite gibt es auch auf Deutsch.', 'Zur deutschen Fassung'],
    fr: ['Cette page existe aussi en français.', 'Voir la version française'],
    pl: ['Ta strona jest dostępna także po polsku.', 'Zobacz wersję polską'],
  };

  var kok = document.documentElement.getAttribute('data-root');

  if (null === kok) {
    return;
  }

  var sayfa = document.documentElement.getAttribute('data-page') || '';
  var burada = (document.documentElement.lang || 'en').slice(0, 2);

  // Tarayıcının ilk tercihi; 'de-AT' gibi değerlerden dil kısmı alınır.
  var istenen = ((navigator.languages && navigator.languages[0]) || navigator.language || '')
    .slice(0, 2)
    .toLowerCase();

  if (!METIN[istenen] || istenen === burada) {
    return;
  }

  try {
    if (localStorage.getItem(ANAHTAR) === 'kapali') {
      return;
    }
  } catch (e) {
    // Depolama kapalıysa şerit yine gösterilir; kapatma kalıcı olmaz sadece.
  }

  var hedef = kok + (istenen === 'en' ? '' : istenen + '/') + sayfa;

  var serit = document.createElement('div');
  serit.className = 'langbar';
  serit.setAttribute('lang', istenen);

  var metin = document.createElement('span');
  metin.textContent = METIN[istenen][0] + ' ';

  var bag = document.createElement('a');
  bag.href = hedef;
  bag.hreflang = istenen;
  bag.textContent = METIN[istenen][1] + ' →';

  var kapat = document.createElement('button');
  kapat.type = 'button';
  kapat.className = 'langbar-x';
  kapat.setAttribute('aria-label', 'Close');
  kapat.textContent = '×';

  kapat.addEventListener('click', function () {
    serit.remove();

    try {
      localStorage.setItem(ANAHTAR, 'kapali');
    } catch (e) {
      // Kapatma bu oturumda geçerli; kalıcı olmaması kabul edilebilir.
    }
  });

  metin.appendChild(bag);
  serit.appendChild(metin);
  serit.appendChild(kapat);

  document.body.insertBefore(serit, document.body.firstChild);
})();
