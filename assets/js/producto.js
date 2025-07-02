// assets/js/producto.js
'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const variantSelect = document.getElementById('variante');
  const qtyInput      = document.getElementById('cantidad');
  const addCartBtn    = document.getElementById('btn-carrito');
  const msgEl         = document.getElementById('producto-message');
  if (!variantSelect || !qtyInput || !addCartBtn || !msgEl) return;

  const getMeta = name => document.querySelector(`meta[name="${name}"]`)?.content || '';

  // Mensajes breves
  function showMessage(text, type='info') {
    msgEl.textContent = text;
    msgEl.className = `producto-message ${type}`;
    clearTimeout(msgEl._timer);
    msgEl._timer = setTimeout(() => {
      msgEl.textContent = '';
      msgEl.className = 'producto-message';
    }, 3000);
  }

  // Ripple (mousedown solo dibuja el efecto)
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

  // ÚNICO listener de click para AJAX
  addCartBtn.addEventListener('click', async e => {
    e.preventDefault();  // ¡imprescindible!

    const productId = variantSelect.value;
    const quantity  = parseInt(qtyInput.value, 10) || 1;
    const BASE_URL  = getMeta('base-url');
    const csrf      = getMeta('csrf-token');

    if (!productId) {
      showMessage('Selecciona una variante', 'error');
      return;
    }

    addCartBtn.disabled = true;
    showMessage('Agregando…','info');

    try {
      const resp = await fetch(`${BASE_URL}/api/cart.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          action: 'add',
          csrf: csrf,
          productId: productId,
          quantity: quantity
        })
      });
      if (!resp.ok) {
        const err = await resp.json().catch(()=>null);
        throw new Error(err?.error||resp.statusText);
      }
      const data = await resp.json();
      if (!data.success) throw new Error(data.error||'Error al añadir');

      showMessage('Añadido al carrito','success');

      // Actualizar badge si existe
      const badge = document.querySelector('.cart-badge');
      if (badge && Array.isArray(data.items)) {
        const total = data.items.reduce((sum,i)=> sum + i.quantity, 0);
        badge.textContent = total;
      }
    } catch(err) {
      console.error(err);
      showMessage(`Error: ${err.message}`,'error');
    } finally {
      addCartBtn.disabled = false;
    }
  });
});
