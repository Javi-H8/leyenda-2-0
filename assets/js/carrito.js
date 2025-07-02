/**
 * assets/js/carrito.js
 * AJAX y DOM para la página de carrito
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('cart-container');
  const msgEl     = document.getElementById('cart-message');
  const baseUrl   = document.querySelector('meta[name="base-url"]').content;
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  if (!container) return;

  // Mostrar mensaje temporal
  function showMessage(text, type='info') {
    msgEl.textContent = text;
    msgEl.className = `cart-message ${type}`;
    clearTimeout(msgEl._timer);
    msgEl._timer = setTimeout(() => {
      msgEl.textContent = '';
      msgEl.className = 'cart-message';
    }, 3000);
  }

  // Recalcular total en DOM
  function updateTotal() {
    const subtotals = container.querySelectorAll('.subtotal');
    let total = 0;
    subtotals.forEach(td => {
      total += parseFloat(td.textContent.replace('€','').replace(',','.')) || 0;
    });
    const totalEl = container.querySelector('.cart-total');
    if (totalEl) totalEl.textContent = total.toFixed(2).replace('.',',') + ' €';
  }

  // AJAX call helper
  async function apiCart(action, variantId=null, quantity=null) {
    const payload = { action, csrf: csrfToken };
    if (variantId !== null) payload.variantId = variantId;
    if (quantity  !== null) payload.quantity  = quantity;

    const res = await fetch(`${baseUrl}/api/cart.php`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    return res.json();
  }

  // 1) Change quantity
  container.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', async e => {
      const tr = e.target.closest('tr');
      const id = tr.dataset.id;
      let qty = parseInt(e.target.value, 10);
      if (isNaN(qty) || qty < 1) qty = 1;

      try {
        const resp = await apiCart('update', id, qty);
        if (!resp.success) throw new Error(resp.error);
        // Update subtotal cell
        const unitPrice = parseFloat(tr.querySelector('td:nth-child(4)').textContent.replace('€','').replace(',','.'));
        const newSub = (unitPrice * qty).toFixed(2).replace('.',',') + ' €';
        tr.querySelector('.subtotal').textContent = newSub;
        updateTotal();
        showMessage('Cantidad actualizada', 'success');
      } catch (err) {
        showMessage(`Error: ${err.message}`, 'error');
      }
    });
  });

  // 2) Remove item
  container.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', async e => {
      const tr = e.target.closest('tr');
      const id = tr.dataset.id;
      try {
        const resp = await apiCart('remove', id);
        if (!resp.success) throw new Error(resp.error);
        tr.remove();
        updateTotal();
        showMessage('Artículo eliminado', 'success');
        if (!container.querySelector('tbody tr').length) {
          window.location.reload();
        }
      } catch (err) {
        showMessage(`Error: ${err.message}`, 'error');
      }
    });
  });

  // 3) Clear cart
  document.getElementById('btn-clear')?.addEventListener('click', async () => {
    try {
      const resp = await apiCart('clear');
      if (!resp.success) throw new Error(resp.error);
      window.location.reload();
    } catch (err) {
      showMessage(`Error: ${err.message}`, 'error');
    }
  });
});
