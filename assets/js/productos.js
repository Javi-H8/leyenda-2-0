/**
 * assets/js/producto.js
 * Interactividad avanzada y AJAX para la página de producto
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  // — Elementos clave —
  const thumbs        = document.querySelectorAll('.gallery-thumbs .thumb');
  const mainImages    = document.querySelectorAll('.gallery-main .main-img');
  const variantSelect = document.getElementById('variante');
  const qtyInput      = document.getElementById('cantidad');
  const priceEl       = document.getElementById('precio-actual');
  const stockNotice   = document.getElementById('stock-aviso');
  const addCartBtn    = document.getElementById('btn-carrito');
  const msgEl         = document.getElementById('producto-message');

  // Salimos si alguno falta
  if (!variantSelect || !qtyInput || !addCartBtn || !priceEl || !stockNotice || !msgEl) {
    return;
  }

  // — Helpers —
  const getMeta = name => document.querySelector(`meta[name="${name}"]`)?.content || '';

  function showMessage(text, type = 'info') {
    msgEl.textContent = text;
    msgEl.className = `producto-message ${type}`;
    clearTimeout(msgEl._timer);
    msgEl._timer = setTimeout(() => {
      msgEl.textContent = '';
      msgEl.className = 'producto-message';
    }, 3000);
  }

  // Ripple effect (mouse down)
  addCartBtn.addEventListener('mousedown', function rippleEffect(e) {
    const circle = document.createElement('span');
    const d = Math.max(this.clientWidth, this.clientHeight);
    circle.style.width = circle.style.height = `${d}px`;
    circle.style.left = `${e.clientX - this.getBoundingClientRect().left - d/2}px`;
    circle.style.top  = `${e.clientY - this.getBoundingClientRect().top  - d/2}px`;
    circle.classList.add('ripple');
    this.appendChild(circle);
    setTimeout(() => circle.remove(), 600);
  });

  // — Galería de miniaturas —
  thumbs.forEach(btn => {
    btn.addEventListener('click', () => {
      const idx = btn.dataset.index;
      mainImages.forEach(img =>
        img.classList.toggle('visible', img.dataset.index === idx)
      );
      thumbs.forEach(b =>
        b.classList.toggle('active', b === btn)
      );
    });
  });
  if (thumbs.length) thumbs[0].click();

  // — Variantes, precio y stock —
  function clampQuantity() {
    const max = Number(qtyInput.max) || 1;
    let val = parseInt(qtyInput.value, 10) || 1;
    qtyInput.value = Math.min(Math.max(val, 1), max);
  }

  function updateVariantInfo() {
    const opt   = variantSelect.selectedOptions[0];
    const stock = Number(opt.dataset.stock) || 0;
    const price = Number(opt.dataset.precio).toFixed(2).replace('.', ',');

    priceEl.textContent     = `€${price}`;
    stockNotice.textContent = stock === 0
      ? 'Agotado'
      : stock <= 2
        ? `Últimas ${stock} unidades`
        : '';
    addCartBtn.disabled = stock === 0;
    qtyInput.max       = stock;
    clampQuantity();
  }

  variantSelect.addEventListener('change', () => {
    updateVariantInfo();
    qtyInput.value = 1;
  });
  qtyInput.addEventListener('input', clampQuantity);

  // Inicializamos
  updateVariantInfo();

  // — ÚNICO listener AJAX: añadir al carrito —
  addCartBtn.addEventListener('click', async e => {
    e.preventDefault(); // evita doble envío

    const variantId = variantSelect.value;
    const quantity  = parseInt(qtyInput.value, 10) || 1;
    const BASE_URL  = getMeta('base-url');
    const csrf      = getMeta('csrf-token');

    if (!variantId) {
      showMessage('Selecciona una variante válida', 'error');
      return;
    }

    addCartBtn.disabled = true;
    showMessage('Agregando…', 'info');

    try {
      const resp = await fetch(`${BASE_URL}/api/cart.php`, {
        method:      'POST',
        credentials: 'same-origin',
        headers:     { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action:    'add',
          csrf:      csrf,
          productId: variantId,  // ojo: se envía como productId
          quantity:  quantity
        })
      });

      if (!resp.ok) {
        const err = await resp.json().catch(() => null);
        throw new Error(err?.error || resp.statusText);
      }

      const data = await resp.json();
      if (!data.success) {
        throw new Error(data.error || 'Operación fallida');
      }

      showMessage('Producto añadido al carrito', 'success');

      // Actualizar badge
      const badge = document.querySelector('.cart-badge');
      if (badge && Array.isArray(data.items)) {
        const totalItems = data.items.reduce((sum, it) => sum + it.quantity, 0);
        badge.textContent = totalItems;
      }

    } catch (err) {
      console.error('Add to cart error:', err);
      showMessage(`Error: ${err.message}`, 'error');
    } finally {
      addCartBtn.disabled = false;
    }
  });

});
 