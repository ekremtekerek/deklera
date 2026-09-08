/*
 * Kontrol sayfasının mantığı. Dört dil bunu paylaşır.
 *
 * Sayfaya özgü tek şey metinlerdir; her sayfa kendi dilindekileri
 * window.DEKLERA_METIN içinde tanımlar ve bu dosya onları okur. Mantığı
 * dört kez kopyalamak, bir düzeltmeyi üç yerde unutmak demek olurdu.
 */
(function () {
  'use strict';

  var UC = 'https://konform-validator.onrender.com/v1/try';
  var M = window.DEKLERA_METIN || {};

  var xml = document.getElementById('xml');
  var profile = document.getElementById('profile');
  var go = document.getElementById('go');
  var durum = document.getElementById('status');
  var out = document.getElementById('out');

  if (!xml || !go || !out) {
    return;
  }

  /**
   * Öğe üretir. Metin her zaman textContent ile konur: yanıt dışarıdan gelen
   * bir belgeden türüyor ve HTML olarak yorumlanmamalı.
   */
  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined) n.textContent = text;
    return n;
  }

  function temizle() {
    while (out.firstChild) out.removeChild(out.firstChild);
  }

  /**
   * Denetleyici konumu tam ad alanlariyla verir:
   * /Q{urn:un:unece:...:100}SellerTradeParty[1]. Kupeler bilgi tasimiyor,
   * satiri okunmaz uzunluga cikariyor; atilir.
   */
  function konum(yol) {
    return String(yol).replace(/Q[{][^}]*[}]/g, '');
  }

  function bulgu(f) {
    var kart = el('div', 'finding');
    kart.appendChild(el('span', 'badge ' + (f.flag === 'warning' ? 'b-warn' : 'b-bad'),
      f.flag === 'warning' ? M.uyari : M.reddedilir));
    kart.appendChild(el('span', 'f-title', f.rule || M.gecersizBelge));
    kart.appendChild(el('p', 'f-what', f.message || ''));
    if (f.location) kart.appendChild(el('p', 'f-rule', konum(f.location)));
    return kart;
  }

  function goster(veri) {
    temizle();

    var hatalar = veri.errors || [];
    var uyarilar = veri.warnings || [];
    var kutu = el('div', 'verdict' + (veri.valid ? ' verdict-ok' : ''));

    kutu.appendChild(el('p', 'verdict-line', veri.valid
      ? M.temiz
      : (hatalar.length === 1 ? M.birBulgu : M.bulgular.replace('%d', hatalar.length))));

    var alt = el('p', 'muted', M.kuralSeti + ' EN 16931 ' + veri.rules_version
      + (veri.profile === 'xrechnung' ? ' + XRechnung 3.0.2 (' + veri.xrechnung_version + ')' : '')
      + ' · ' + veri.duration_ms + ' ms');
    alt.style.margin = '0';
    kutu.appendChild(alt);

    if (uyarilar.length) {
      var not = el('p', 'muted', M.uyariNotu);
      not.style.margin = '0.4rem 0 0';
      kutu.appendChild(not);
    }

    out.appendChild(kutu);

    hatalar.concat(uyarilar).forEach(function (f) {
      out.appendChild(bulgu(f));
    });

    if (typeof veri.remaining === 'number') {
      out.appendChild(el('p', 'muted', M.kalan.replace('%d', veri.remaining)));
    }
  }

  go.addEventListener('click', function () {
    var metin = xml.value.trim();

    if (metin === '') {
      durum.textContent = M.oncePyapistir;
      return;
    }

    temizle();
    go.disabled = true;
    durum.textContent = M.kontrolEdiliyor;

    fetch(UC, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify({ xml: metin, profile: profile.value }),
    })
      .then(function (r) {
        return r.json().then(function (veri) { return { ok: r.ok, status: r.status, veri: veri }; });
      })
      .then(function (c) {
        durum.textContent = '';

        if (c.status === 429) {
          out.appendChild(el('p', 'muted',
            M.kotaDoldu
              .replace('%d', c.veri.limit)
              .replace('%m', String(Math.ceil((c.veri.retry_after_seconds || 0) / 60)))));
          return;
        }

        if (!c.ok) {
          out.appendChild(el('p', 'muted', M.okunamadi + ' ' + (c.veri.error || c.status)));
          return;
        }

        goster(c.veri);
      })
      .catch(function () {
        durum.textContent = '';
        out.appendChild(el('p', 'muted', M.cevapYok));
      })
      .finally(function () {
        go.disabled = false;
      });
  });
})();
