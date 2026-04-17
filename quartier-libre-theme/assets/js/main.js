/* Quartier Libre — main.js
   Menu mobile + recherche déroulante. Vanilla, zéro dépendance.
*/
(function () {
  'use strict';

  // ── Burger menu mobile ──────────────────────────────────────
  var burger = document.querySelector('.ql-burger');
  var nav    = document.getElementById('ql-menu-primary');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') === 'true';
      burger.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
      burger.setAttribute('aria-label', open ? 'Ouvrir le menu' : 'Fermer le menu');
    });
  }

  // ── Search panel ────────────────────────────────────────────
  var searchBtn   = document.querySelector('.ql-search-toggle');
  var searchPanel = document.getElementById('ql-search-panel');
  if (searchBtn && searchPanel) {
    searchBtn.addEventListener('click', function () {
      var hidden = searchPanel.hasAttribute('hidden');
      if (hidden) {
        searchPanel.removeAttribute('hidden');
        searchBtn.setAttribute('aria-expanded', 'true');
        var input = searchPanel.querySelector('input[type="search"]');
        if (input) { setTimeout(function () { input.focus(); }, 50); }
      } else {
        searchPanel.setAttribute('hidden', '');
        searchBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ── Barre de progression de lecture (single.php) ────────────
  var progressBar = document.querySelector('.ql-reading-progress span');
  var articleBody = document.querySelector('.ql-single .ql-article__body');
  if (progressBar && articleBody) {
    var raf = null;
    var update = function () {
      raf = null;
      var rect = articleBody.getBoundingClientRect();
      var total = articleBody.offsetHeight - window.innerHeight;
      var scrolled = -rect.top;
      var pct = Math.max(0, Math.min(100, (scrolled / total) * 100));
      progressBar.style.width = pct + '%';
    };
    var onScroll = function () {
      if (!raf) { raf = requestAnimationFrame(update); }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();
  }

  // ── Fermer le menu mobile quand on clique un lien ───────────
  if (nav) {
    nav.addEventListener('click', function (e) {
      var t = e.target;
      if (t && t.tagName === 'A' && window.matchMedia('(max-width: 899px)').matches) {
        nav.classList.remove('is-open');
        if (burger) {
          burger.setAttribute('aria-expanded', 'false');
          burger.setAttribute('aria-label', 'Ouvrir le menu');
        }
      }
    });
  }

})();
