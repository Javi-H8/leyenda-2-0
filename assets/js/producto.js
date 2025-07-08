/**
 * assets/js/producto.js
 * Interactividad avanzada para la página de producto.
 * Solo UI: galería, ripple, variantes, cantidad.
 * El 'add to cart' lo gestiona exclusivamente carrito.js.
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

  // Salimos si falta algo esencial
  if (!variantSelect || !qtyInput || !addCartBtn || !priceEl || !stockNotice) {
    return;
  }

  // — Helpers —
  function clampQuantity() {
    const max = Number(qtyInput.max) || 1;
    let val = parseInt(qtyInput.value, 10) || 1;
    qtyInput.value = Math.min(Math.max(val, 1), max);
    // ➔ Actualizamos data-quantity para carrito.js
    addCartBtn.dataset.quantity = qtyInput.value;
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
    qtyInput.max       = stock;
    qtyInput.value     = 1;
    addCartBtn.disabled= stock === 0;

    // ➔ Actualizamos data-variant-id y data-quantity
    addCartBtn.dataset.variantId = variantSelect.value;
    addCartBtn.dataset.quantity  = qtyInput.value;
  }

  // — Ripple effect (mousedown) —
  addCartBtn.addEventListener('mousedown', function(e) {
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

  // — Variantes / Precio / Stock —
  variantSelect.addEventListener('change', () => {
    updateVariantInfo();
  });

  // — Cantidad —
  qtyInput.addEventListener('input', clampQuantity);

  // — Inicialización —
  // 1) Información inicial de variante
  updateVariantInfo();

  // 2) Asegúrate de que el botón tiene la clase necesaria para carrito.js:
  //    <button id="btn-carrito" class="btn btn-primary add-to-cart">Añadir al carrito</button>
  if (!addCartBtn.classList.contains('add-to-cart')) {
    addCartBtn.classList.add('add-to-cart');
  }
});
