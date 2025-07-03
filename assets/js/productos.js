// assets/js/productos.js
// Versión: 2.1.3
// Descripción: Página de productos con filtros AJAX, History API, columnas (por defecto 1),
// ripple, auto-resize títulos, lazy-load, prefetch, infinite scroll sin duplicados,
// accesibilidad y optimización de imágenes.

(function(window, document){
  'use strict';

  const ProductPage = {
    // ── Configuración ───────────────────────────────────────────
    config: {
      selectors: {
        filtros:     '#filtros',
        categoria:   '#categoria',
        buscador:    '#buscador',
        lista:       '#lista-productos',
        colBtns:     '.column-switcher .col-btn',
        titulo:      '#lista-productos .card-content h2',
        linkCard:    '#lista-productos .card a.card-img',
        productCard: '#lista-productos .card',
        image:       '.card-img img'
      },
      debounceDelay: 500,
      minFontPx:     12,
      maxTitleLines: 2,
      infinite: {
        enabled:    true,
        sentinelId: 'scroll-sentinel',
        paramPage:  'page'
      }
    },

    // ── Estado interno ─────────────────────────────────────────
    state: {
      debounceTimer:    null,
      loadingInfinite:  false,
      currentPage:      1,
      filterController: null,
      loadedLinks:      new Set()
    },

    // ── Referencia al observer de infinite scroll ───────────────
    _infObserver: null,

    // ── Inicialización ────────────────────────────────────────
    init: function(){
      this.cacheElems();
      this.initFilters();
      this.initColumnSwitcher();      // default = 1 columna
      this.initRipple();
      this.initTrackLoaded();         // evita duplicados
      // tareas pesadas:
      const idle = cb => ('requestIdleCallback' in window) ? requestIdleCallback(cb) : cb();
      idle(() => {
        this.initAutoResizeTitles();
        this.initLazyLoadImages();
        this.initPrefetch();
        this.initOptimizeImages();
        this.initCardNavigation();
      });
      if (this.config.infinite.enabled) this.initInfiniteScroll();
      window.addEventListener('popstate', () => {
        this.loadProducts(location.search, false);
      });
      this.initKeyboardShortcuts();
    },

    // ──────────────────────────────────────────────────────────────
    cacheElems: function(){
      const s = this.config.selectors;
      this.$categoria = document.querySelector(s.categoria);
      this.$buscador  = document.querySelector(s.buscador);
      this.$lista     = document.querySelector(s.lista);
      this.$colBtns   = document.querySelectorAll(s.colBtns);
    },

    // ──────────────────────────────────────────────────────────────
    initFilters: function(){
      if (!this.$categoria || !this.$buscador) return;
      this.$categoria.addEventListener('change', () => this.onFilterChange());
      this.$buscador.addEventListener('input', () => {
        clearTimeout(this.state.debounceTimer);
        this.state.debounceTimer = setTimeout(() => this.onFilterChange(),
                                             this.config.debounceDelay);
      });
    },

    onFilterChange: function(){
      if (this.state.filterController) {
        this.state.filterController.abort();
      }
      this.state.filterController = new AbortController();

      const params = new URLSearchParams(location.search);
      params.set('categoria', this.$categoria.value);
      const q = this.$buscador.value.trim();
      if (q) params.set('busqueda', q);
      else   params.delete('busqueda');
      // resetear paginación
      params.set(this.config.infinite.paramPage, 1);
      this.state.currentPage = 1;

      const qs = params.toString() ? '?' + params.toString() : '';
      this.loadProducts(qs, true, this.state.filterController.signal);
    },

    async loadProducts(qs, pushState, signal){
      try {
        this.$lista.classList.add('loading');
        const resp = await fetch(location.pathname + qs, {
          credentials: 'same-origin',
          signal
        });
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const html = await resp.text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const nueva = doc.querySelector(this.config.selectors.lista);
        if (nueva) {
          this.$lista.innerHTML = nueva.innerHTML;
          this.initRipple();
          this.initTrackLoaded();
          this.initAutoResizeTitles();
          this.initLazyLoadImages();
          this.initPrefetch();
          this.initOptimizeImages();
          this.initCardNavigation();
        }
        if (pushState) history.pushState(null, '', qs);
      } catch (err) {
        if (err.name !== 'AbortError') console.error('loadProducts:', err);
      } finally {
        this.$lista.classList.remove('loading');
      }
    },

    // ──────────────────────────────────────────────────────────────
    initColumnSwitcher: function(){
      if (!this.$lista || !this.$colBtns.length) return;
      const saved = localStorage.getItem('productosCols') || '1';
      this.applyLayout(saved);
      this.$colBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          this.applyLayout(btn.dataset.cols);
          localStorage.setItem('productosCols', btn.dataset.cols);
        });
      });
    },

    applyLayout: function(cols){
      const map = {
        '1': 'uno-por-linea',
        '2': 'dos-por-linea',
        '3': 'tres-por-linea',
        '4': 'cuatro-por-linea'
      };
      Object.values(map).forEach(c => this.$lista.classList.remove(c));
      if (map[cols]) this.$lista.classList.add(map[cols]);
      this.$colBtns.forEach(b => {
        const active = b.dataset.cols === cols;
        b.classList.toggle('active', active);
        b.setAttribute('aria-pressed', active);
      });
    },

    // ──────────────────────────────────────────────────────────────
    initRipple: function(){
      // eliminar restos de ripples anteriores
      document.querySelectorAll('.ripple').forEach(r => r.remove());
    },

    // ──────────────────────────────────────────────────────────────
    initAutoResizeTitles: function(){
      document.querySelectorAll(this.config.selectors.titulo).forEach(el => {
        const base = getComputedStyle(el).getPropertyValue('--fs-card-title')
                     || getComputedStyle(el).fontSize;
        let fontPx = parseFloat(base);
        const lh   = parseFloat(getComputedStyle(el).lineHeight) || fontPx * 1.2;
        el.style.fontSize = fontPx + 'px';
        while (Math.round(el.scrollHeight / lh) > this.config.maxTitleLines
               && fontPx > this.config.minFontPx) {
          fontPx -= 1;
          el.style.fontSize = fontPx + 'px';
        }
      });
    },

    // ──────────────────────────────────────────────────────────────
    initLazyLoadImages: function(){
      const imgs = document.querySelectorAll(this.config.selectors.image);
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries, obs) => {
          entries.forEach(entry => {
            if (entry.isIntersecting && entry.target.dataset.src) {
              entry.target.src = entry.target.dataset.src;
              entry.target.removeAttribute('data-src');
              obs.unobserve(entry.target);
            }
          });
        }, { rootMargin: '200px' });
        imgs.forEach(img => img.dataset.src && io.observe(img));
      }
    },

    // ──────────────────────────────────────────────────────────────
    initPrefetch: function(){
      document.querySelectorAll(this.config.selectors.linkCard)
        .forEach(link => {
          let done = false;
          link.addEventListener('mouseenter', () => {
            if (done) return;
            const l = document.createElement('link');
            l.rel  = 'prefetch';
            l.href = link.href;
            document.head.appendChild(l);
            done = true;
          });
        });
    },

    // ──────────────────────────────────────────────────────────────
    initOptimizeImages: function(){
      document.querySelectorAll(this.config.selectors.image).forEach(img => {
        img.decoding = 'async';
        if (!img.hasAttribute('loading')) img.loading = 'lazy';
      });
    },

    // ──────────────────────────────────────────────────────────────
    initTrackLoaded: function(){
      this.state.loadedLinks.clear();
      document.querySelectorAll(this.config.selectors.linkCard)
        .forEach(link => this.state.loadedLinks.add(link.href));
    },

    // ──────────────────────────────────────────────────────────────
    initCardNavigation: function(){
      document.querySelectorAll(this.config.selectors.productCard)
        .forEach(card => {
          card.style.cursor = 'pointer';
          card.addEventListener('click', e => {
            if (e.target.closest('a, button, input')) return;
            const link = card.querySelector(this.config.selectors.linkCard);
            if (link) window.location.href = link.href;
          });
        });
    },

    // ──────────────────────────────────────────────────────────────
    initInfiniteScroll: function(){
      if (!this.$lista) return;
      if (this._infObserver) return;        // ya inicializado

      const sentinel = document.createElement('div');
      sentinel.id = this.config.infinite.sentinelId;
      this.$lista.after(sentinel);

      this._infObserver = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting && !this.state.loadingInfinite) {
          this._infObserver.unobserve(sentinel);
          this.loadMore().finally(() => {
            this._infObserver.observe(sentinel);
          });
        }
      }, { rootMargin: '200px' });

      this._infObserver.observe(sentinel);
    },

    async loadMore(){
      if (this.state.loadingInfinite) return;
      this.state.loadingInfinite = true;
      this.state.currentPage++;

      try {
        const params = new URLSearchParams(location.search);
        params.set(this.config.infinite.paramPage, this.state.currentPage);
        const resp = await fetch(location.pathname + '?' + params.toString(), {
          credentials: 'same-origin'
        });
        if (!resp.ok) throw new Error('Error infinite scroll');

        const html = await resp.text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const nuevos = doc.querySelectorAll(this.config.selectors.productCard);

        if (nuevos.length === 0) {
          // fin de páginas, desconectar observer
          this._infObserver.disconnect();
        } else {
          nuevos.forEach(card => {
            const link = card.querySelector(this.config.selectors.linkCard);
            if (link && !this.state.loadedLinks.has(link.href)) {
              this.$lista.appendChild(card);
              this.state.loadedLinks.add(link.href);
            }
          });
          this.initAutoResizeTitles();
          this.initLazyLoadImages();
          this.initPrefetch();
          this.initOptimizeImages();
          this.initCardNavigation();
        }
      } catch(err) {
        console.error('loadMore:', err);
      } finally {
        this.state.loadingInfinite = false;
      }
    },

    // ──────────────────────────────────────────────────────────────
    initKeyboardShortcuts: function(){
      document.addEventListener('keydown', e => {
        if (['1','2','3','4'].includes(e.key) && document.activeElement === document.body) {
          this.applyLayout(e.key);
          localStorage.setItem('productosCols', e.key);
        }
      });
    }
  };

  document.addEventListener('DOMContentLoaded', () => ProductPage.init());

})(window, document);
