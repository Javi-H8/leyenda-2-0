/**
 * assets/js/carrito.js
 * Controlador único, delegación de eventos,
 * toast messages, disable UI y AJAX para carrito.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('cart-container');
  const toast     = createToast();
  const csrf      = CART_AJAX.csrf;
  const endpoint  = CART_AJAX.url;

  if (!container) return;

  // 1) Crear toast
  function createToast() {
    const t = document.createElement('div');
    t.id = 'toast';
    Object.assign(t.style, {
      position: 'fixed',
      top: '1rem',
      right: '1rem',
      padding: '0.75rem 1rem',
      borderRadius: '4px',
      color: '#fff',
      opacity: '0',
      transition: 'opacity 0.3s',
      zIndex: '9999'
    });
    document.body.appendChild(t);
    return t;
  }

  // 2) Mostrar mensaje
  function showToast(msg, success = true) {
    toast.textContent = msg;
    toast.style.background = success ? '#27ae60' : '#e74c3c';
    toast.style.opacity = '1';
    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(() => toast.style.opacity = '0', 3000);
  }

  // 3) Llamada AJAX genérica
  async function apiCart(action, variantId = null, quantity = null) {
    const payload = { action, csrf };
    if (variantId != null) payload.variant_id = variantId;
    if (quantity  != null) payload.quantity   = quantity;

    const res = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Error desconocido');
    return json;
  }

  // 4) Recalcular total desde subtotales en DOM
  function updateTotalFromDOM() {
    const subs = container.querySelectorAll('.subtotal');
    let total = 0;
    subs.forEach(el => {
      total += parseFloat(el.textContent.replace('€','').replace(',','.')) || 0;
    });
    const totalEl = document.querySelector('.cart-summary .total-line dd');
    if (totalEl) {
      totalEl.textContent = total.toFixed(2).replace('.', ',') + ' €';
    }
  }

  // 5) Deshabilitar fila
  function disableRow(tr, disable) {
    tr.querySelectorAll('input, button').forEach(el => el.disabled = disable);
    tr.style.opacity = disable ? '0.6' : '1';
  }

  // 6) Actualizar cantidad y subtotal
  async function updateQuantity(tr, id, qty) {
    const priceCell = tr.querySelector('td:nth-child(4)');
    const subEl     = tr.querySelector('.subtotal');
    const unitPrice = parseFloat(priceCell.textContent.replace('€','').replace(',','.')) || 0;
    try {
      disableRow(tr, true);
      await apiCart('update', id, qty);
      subEl.textContent = (unitPrice * qty).toFixed(2).replace('.', ',') + ' €';
      updateTotalFromDOM();
      showToast('Cantidad actualizada');
    } catch (err) {
      showToast(err.message, false);
    } finally {
      disableRow(tr, false);
    }
  }

  // 7) Delegación de eventos en container
  container.addEventListener('click', async e => {
    const tr = e.target.closest('tr[data-id]');
    if (!tr) return;
    const id = tr.dataset.id;

    // a) Eliminar item
    if (e.target.matches('.btn-remove')) {
      try {
        disableRow(tr, true);
        await apiCart('remove', id);
        tr.remove();
        updateTotalFromDOM();
        showToast('Artículo eliminado');
        if (!container.querySelector('tbody tr')) {
          window.location.reload();
        }
      } catch (err) {
        showToast(err.message, false);
      } finally {
        disableRow(tr, false);
      }
    }

    // b) Botones +/–
    if (e.target.matches('.qty-decrease, .qty-increase')) {
      const input = tr.querySelector('.qty-input');
      let qty = parseInt(input.value, 10);
      qty += e.target.classList.contains('qty-decrease') ? -1 : 1;
      qty = Math.max(1, Math.min(qty, parseInt(input.max, 10)));
      input.value = qty;
      await updateQuantity(tr, id, qty);
    }
  });

  // 8) Cambio manual en input
  container.addEventListener('change', async e => {
    if (!e.target.matches('.qty-input')) return;
    const input = e.target;
    const tr = input.closest('tr[data-id]');
    let qty = parseInt(input.value, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    qty = Math.min(qty, parseInt(input.max, 10));
    input.value = qty;
    await updateQuantity(tr, tr.dataset.id, qty);
  });

  // 9) Vaciar carrito
  document.getElementById('btn-clear')?.addEventListener('click', async () => {
    if (!confirm('¿Seguro que quieres vaciar el carrito?')) return;
    try {
      await apiCart('clear');
      window.location.reload();
    } catch (err) {
      showToast(err.message, false);
    }
  });
});
