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

  // ── Hero Carousel : navigation prev/next + dots + autoplay ──
  var carousel = document.querySelector('.ql-carousel');
  if (carousel) {
    var track = carousel.querySelector('.ql-carousel__track');
    var slides = carousel.querySelectorAll('.ql-carousel__slide');
    var dots = carousel.querySelectorAll('.ql-carousel__dot');
    var prevBtn = carousel.querySelector('.ql-carousel__nav--prev');
    var nextBtn = carousel.querySelector('.ql-carousel__nav--next');
    var total = slides.length;
    var current = 0;
    var autoplayId = null;

    function goTo(index) {
      current = (index + total) % total;
      if (slides[current]) {
        track.scrollTo({ left: slides[current].offsetLeft, behavior: 'smooth' });
      }
      dots.forEach(function(d, i) { d.classList.toggle('is-active', i === current); });
    }

    if (prevBtn) prevBtn.addEventListener('click', function() { resetAutoplay(); goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() { resetAutoplay(); goTo(current + 1); });
    dots.forEach(function(dot, i) {
      dot.addEventListener('click', function() { resetAutoplay(); goTo(i); });
    });

    // Sync dots on manual scroll/swipe
    var scrollTimeout;
    track.addEventListener('scroll', function() {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(function() {
        var idx = Math.round(track.scrollLeft / track.offsetWidth);
        if (idx !== current) {
          current = idx;
          dots.forEach(function(d, i) { d.classList.toggle('is-active', i === current); });
        }
      }, 100);
    }, { passive: true });

    // Autoplay 6s (pause au hover, respecte reduced-motion)
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function startAutoplay() {
      if (reduceMotion) return;
      autoplayId = setInterval(function() { goTo(current + 1); }, 6000);
    }
    function resetAutoplay() {
      clearInterval(autoplayId);
      startAutoplay();
    }
    carousel.addEventListener('mouseenter', function() { clearInterval(autoplayId); });
    carousel.addEventListener('mouseleave', startAutoplay);
    startAutoplay();
  }

  // ── Modal Bureau des Plaintes ───────────────────────────────
  var plainteTrigger = document.querySelector('.ql-plainte-trigger');
  var plainteModal = document.getElementById('ql-plainte-modal');
  if (plainteTrigger && plainteModal) {
    var closeEls = plainteModal.querySelectorAll('[data-close]');
    var firstInput = plainteModal.querySelector('select, input, textarea');

    function openModal() {
      plainteModal.removeAttribute('hidden');
      plainteTrigger.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
      if (firstInput) { setTimeout(function(){ firstInput.focus(); }, 50); }
    }
    function closeModal() {
      plainteModal.setAttribute('hidden', '');
      plainteTrigger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
      plainteTrigger.focus();
    }

    plainteTrigger.addEventListener('click', openModal);
    closeEls.forEach(function(el) { el.addEventListener('click', closeModal); });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !plainteModal.hasAttribute('hidden')) { closeModal(); }
    });

    // Intercepter les liens "/bureau-des-plaintes/" dans les articles :
    // au lieu de naviguer, on ouvre la modal directement.
    document.addEventListener('click', function(e) {
      var a = e.target.closest && e.target.closest('a');
      if (!a) return;
      var href = a.getAttribute('href') || '';
      // Match /bureau-des-plaintes/ ou l'URL complète
      if (/\/bureau-des-plaintes\/?(\?|#|$)/.test(href)) {
        // Laisser passer si on est déjà sur la page Bureau des plaintes
        if (/\/bureau-des-plaintes\//.test(window.location.pathname)) return;
        e.preventDefault();
        openModal();
      }
    });
  }

  // ── Auto-popup plainte en fin d'article (single.php) ────────
  // Quand le lecteur atteint le bas de l'article, on ouvre la modal.
  // Une seule fois par session (sessionStorage) pour ne pas harceler.
  var singlePostBody = document.querySelector('.ql-post__body');
  var plainteBtnAuto = document.querySelector('.ql-plainte-trigger');
  if (singlePostBody && plainteBtnAuto && 'IntersectionObserver' in window) {
    try {
      if (!sessionStorage.getItem('ql-plainte-shown')) {
        var sentinel = document.createElement('div');
        sentinel.setAttribute('aria-hidden', 'true');
        sentinel.style.cssText = 'height:1px;width:100%;pointer-events:none;';
        singlePostBody.appendChild(sentinel);

        var plainteObs = new IntersectionObserver(function(entries) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              try { sessionStorage.setItem('ql-plainte-shown', '1'); } catch (e) {}
              plainteBtnAuto.click();
              plainteObs.disconnect();
            }
          });
        }, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });
        plainteObs.observe(sentinel);
      }
    } catch (e) { /* sessionStorage peut être bloqué, on ignore */ }
  }

  // ── Liens externes dans les articles → popup petite taille ──
  // Convention : tout lien absolu http(s) pointant vers un autre
  // domaine que quartierlibre.org et situé dans le corps d'un article
  // s'ouvre dans une fenêtre popup de 680×480px (centrée).
  // Sur mobile, les navigateurs ignorent les dimensions → ouvre
  // simplement un nouvel onglet (comportement gracieux).
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a');
    if (!a) return;

    // Uniquement dans le corps d'un article
    var inArticle = a.closest('.ql-post__content, .ql-article__body, .ql-post__body');
    if (!inArticle) return;

    var href = a.getAttribute('href') || '';
    if (!/^https?:\/\//i.test(href)) return; // relatif ou anchor → pas touché

    var linkHost;
    try { linkHost = new URL(href, window.location.href).host; } catch (err) { return; }

    // Même domaine → lien interne, comportement normal
    if (linkHost === window.location.host) return;

    // Exclure les boutons de partage sociaux (ils gèrent leur propre ouverture)
    if (a.closest('.ql-post__share, .ql-share, .ql-social')) return;

    // Externe → popup centrée, petite taille
    e.preventDefault();
    var w = 680, h = 480;
    var left = Math.max(0, (window.screen.availWidth  - w) / 2);
    var top  = Math.max(0, (window.screen.availHeight - h) / 2);
    var features = 'width=' + w + ',height=' + h +
                   ',left=' + left + ',top=' + top +
                   ',resizable=yes,scrollbars=yes,toolbar=no,menubar=no,status=no,location=yes';
    var win = window.open(href, 'ql-external-popup', features);
    if (!win) {
      // Popup bloquée → fallback nouvel onglet
      window.open(href, '_blank', 'noopener');
    } else {
      try { win.opener = null; } catch (_) {}
    }
  });

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
