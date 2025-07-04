/*!
 * header-advanced.js
 * ----------------------------------------
 * 1. Lazy-load de imágenes con efecto blur-up.
 * 2. Prefetch de recursos al hacer hover sobre enlaces.
 * 3. Prefetch automático de la "siguiente" página.
 * 4. Inserción de resource hints (preconnect/preload).
 * 5. Fallbacks con requestIdleCallback y timeouts suaves.
 */
;(function() {
  'use strict';

  const BASE = document.querySelector('meta[name=base-url]').content;

  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  /* 1) Resource Hints dinámicos                                                 */
  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  function injectResourceHints() {
    const head = document.head;
    const hints = [
      { rel: 'preconnect', href: BASE },
      { rel: 'preconnect', href: `${BASE}/assets/images` },
      { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
      { rel: 'dns-prefetch', href: BASE },
      // preload de CSS crítico si lo consideras
      // { rel: 'preload', href: `${BASE}/assets/css/header.css`, as: 'style' },
    ];
    hints.forEach(h => {
      const link = document.createElement('link');
      Object.entries(h).forEach(([k,v]) => link.setAttribute(k, v));
      head.appendChild(link);
    });
  }

  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  /* 2) Lazy-load de imágenes con Blur-Up                                        */
  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  function setupLazyImages() {
    const imgs = [...document.querySelectorAll('img')];
    if (!imgs.length) return;

    // Inyectamos estilos de blur
    const style = document.createElement('style');
    style.textContent = `
      img.lazy-adv { transition: filter .4s ease, opacity .4s ease; }
      img.lazy-adv.blur { filter: blur(20px) opacity(.6); }
    `;
    document.head.appendChild(style);

    imgs.forEach(img => {
      if (img.dataset.lazyAdv) return;
      img.dataset.lazyAdv = 'true';
      img.dataset.srcOrig = img.src;
      img.src = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
      img.classList.add('lazy-adv', 'blur');
    });

    const loadImg = img => {
      img.src = img.dataset.srcOrig;
      img.onload = () => img.classList.remove('blur');
    };

    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver((entries, o) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            loadImg(e.target);
            o.unobserve(e.target);
          }
        });
      }, { rootMargin: '100px' });
      imgs.forEach(i => obs.observe(i));
    } else {
      // Fallback rápido
      setTimeout(() => imgs.forEach(loadImg), 200);
    }
  }

  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  /* 3) Prefetch de enlaces al hover                                              */
  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  function setupLinkPrefetch() {
    const nav = document.getElementById('main-nav');
    if (!nav) return;
    nav.querySelectorAll('a[href^="/"]').forEach(a => {
      let prefetched = false;
      const url = a.href;
      a.addEventListener('mouseenter', () => {
        if (prefetched) return;
        prefetched = true;
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
      });
    });
  }

  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  /* 4) Prefetch Next Page automático                                             */
  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  function prefetchNextPage() {
    // Asume que tu servidor envía un <link rel="next"> en el head
    const next = document.querySelector('link[rel=next]');
    if (!next) return;
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = next.href;
    document.head.appendChild(link);
  }

  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  /* 5) Orquestación en Idle o DOMContentLoaded                                   */
  /*––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––*/
  function init() {
    injectResourceHints();
    setupLinkPrefetch();
    prefetchNextPage();

    // No bloqueamos DCL: imágenes en idle
    const doImgs = () => setupLazyImages();
    if ('requestIdleCallback' in window) {
      requestIdleCallback(doImgs, { timeout: 500 });
    } else {
      document.addEventListener('DOMContentLoaded', doImgs);
    }
  }

  if (document.readyState !== 'loading') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }

      // ---------------------------------------------------
  // 5. OPTIMIZACIONES AVANZADAS DE CARGA
  // ---------------------------------------------------

  // 5.1. Auto-lazy-load nativo y prevenir CLS
  document.querySelectorAll('img:not([loading])').forEach(img => {
    img.setAttribute('loading', 'lazy');
    if (!img.hasAttribute('width') && img.naturalWidth) {
      img.setAttribute('width', img.naturalWidth);
      img.setAttribute('height', img.naturalHeight);
    }
  });

  // 5.2. Fallback con IntersectionObserver si no hay soporte nativo
  if (!('loading' in HTMLImageElement.prototype) && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(({ target, isIntersecting }) => {
        if (isIntersecting) {
          target.src = target.dataset.src;
          target.removeAttribute('data-src');
          obs.unobserve(target);
        }
      });
    }, { rootMargin: '200px 0px' });

    document.querySelectorAll('img[data-src]').forEach(img => io.observe(img));
  }

  // 5.3. Definir helper para deferir tareas con requestIdleCallback
  function onIdle(fn) {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(fn, { timeout: 500 });
    } else {
      setTimeout(fn, 1000);
    }
  }

  // 5.4. Prefetch de la imagen del slider activo en idle
  onIdle(() => {
    const activeSlideImg = document.querySelector('#slider .slide.active img');
    if (activeSlideImg) {
      const l = document.createElement('link');
      l.rel  = 'preload';
      l.as   = 'image';
      l.href = activeSlideImg.src;
      document.head.appendChild(l);
    }
    // aquí puedes añadir más prefetch de recursos no críticos...
  });


})();
