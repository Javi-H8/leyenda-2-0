// assets/js/carrito.js
;(function() {
  'use strict';

   // ■■■■■■■■■■ Evitar doble inicialización ■■■■■■■■■■
 if (window.cartControllerInit) {
   return;
 }
    window.cartControllerInit = true;
 // ■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■

  /**
   * CartController encapsula toda la lógica de interacción con el carrito:
   * - Página de carrito: update quantity, remove item, clear cart.
   * - Cabecera: añadir al carrito y badge dinámico.
   */
  class CartController {
    constructor() {
      // Configuración inyectada en header.php
      const { url, csrf } = window.CART_AJAX || {};
      if (!url || !csrf) {
        console.error('CART_AJAX no está definido');
        return;
      }

      this.endpoint = url;
      this.csrf     = csrf;
      this.container = document.getElementById('cart-container');
      this.toast     = this._createToast();
      this._bindGlobalActions();
      if (this.container) this._bindCartPageActions();
    }

    // ====================
    //  UTILIDADES PRIVADAS
    // ====================

    /**
     * Crea el contenedor de toast para notificaciones.
     */
    _createToast() {
      const t = document.createElement('div');
      t.id = 'cart-toast';
      Object.assign(t.style, {
        position:    'fixed',
        top:         '1rem',
        right:       '1rem',
        padding:     '.75rem 1rem',
        borderRadius:'4px',
        color:       '#fff',
        opacity:     '0',
        transition:  'opacity 0.3s',
        zIndex:      '9999'
      });
      document.body.appendChild(t);
      return t;
    }

    /**
     * Muestra un mensaje en el toast.
     * @param {string} msg
     * @param {boolean} success
     */
    _showToast(msg, success = true) {
      this.toast.textContent = msg;
      this.toast.style.background = success ? '#27ae60' : '#e74c3c';
      this.toast.style.opacity = '1';
      clearTimeout(this.toast._timeout);
      this.toast._timeout = setTimeout(() => {
        this.toast.style.opacity = '0';
      }, 3000);
    }

    /**
     * Realiza una llamada AJAX al endpoint de carrito.
     * @param {string} action - 'update'|'remove'|'clear'
     * @param {number} [variantId]
     * @param {number} [quantity]
     * @returns {Promise<object>} respuesta JSON
     */
    async _apiCart(action, variantId = null, quantity = null) {
      const payload = { action, csrf: this.csrf };
      if (variantId != null) payload.variant_id = variantId;
      if (quantity  != null) payload.quantity   = quantity;

      const res = await fetch(this.endpoint, {
        method:      'POST',
        credentials: 'same-origin',
        headers:     { 'Content-Type': 'application/json' },
        body:        JSON.stringify(payload),
      });

      if (!res.ok) {
        const text = await res.text().catch(() => res.statusText);
        throw new Error(`HTTP ${res.status}: ${text}`);
      }

      const data = await res.json();
      if (!data.success) {
        throw new Error(data.error || 'Error desconocido');
      }
      return data;
    }

    /**
     * Actualiza el badge del carrito en el header.
     * Crea el <span> si no existía.
     * @param {number} count
     */
    _updateBadge(count) {
      const link = document.querySelector('.site-header__cart .cart-link');
      if (!link) return;
      let badge = link.querySelector('.cart-badge');
      if (!badge) {
        badge = document.createElement('span');
        badge.classList.add('cart-badge');
        link.appendChild(badge);
      }
      badge.textContent = count;
      badge.setAttribute('aria-label', `${count} ítems en el carrito`);
    }

    // ====================
    //  ACCIONES EN HEADER
    // ====================

    /**
     * Fija el listener en todos los botones .add-to-cart de la página.
     */
    _bindGlobalActions() {
      document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', async e => {
          e.preventDefault();
          const variantId = Number(btn.dataset.variantId);
          const quantity  = Number(btn.dataset.quantity || 1);

          try {
            const data = await this._apiCart('add', variantId, quantity);
            this._updateBadge(data.cartCount);
            this._showToast('Producto añadido al carrito');
          } catch (err) {
            console.error(err);
            this._showToast(err.message, false);
          }
        });
      });
    }

    // ====================
    //  ACCIONES EN PÁGINA DE CARRITO
    // ====================

    /**
     * Si estamos en /pages/carrito.php enlaza:
     * - click en botones remove
     * - aumento/bajada cantidad (buttons + inputs)
     * - clear cart
     */
    _bindCartPageActions() {
      // Delegación de eventos para remove & qty buttons
      this.container.addEventListener('click', async e => {
        const tr = e.target.closest('tr[data-id]');
        if (!tr) return;
        const id = Number(tr.dataset.id);

        // Eliminar item
        if (e.target.matches('.btn-remove')) {
          await this._handleRemove(tr, id);
        }

        // Incrementar / decrementar
        if (e.target.matches('.qty-decrease, .qty-increase')) {
          const input = tr.querySelector('.qty-input');
          let qty = Number(input.value);
          qty += e.target.classList.contains('qty-decrease') ? -1 : 1;
          qty = Math.max(1, Math.min(qty, Number(input.max)));
          input.value = qty;
          await this._handleUpdate(tr, id, qty);
        }
      });

      // Cambio manual en input
      this.container.addEventListener('change', async e => {
        if (!e.target.matches('.qty-input')) return;
        const input = e.target;
        let qty = Number(input.value) || 1;
        qty = Math.max(1, Math.min(qty, Number(input.max)));
        input.value = qty;
        const tr = input.closest('tr[data-id]');
        await this._handleUpdate(tr, Number(tr.dataset.id), qty);
      });

      // Vaciar carrito
      const btnClear = document.getElementById('btn-clear');
      if (btnClear) {
        btnClear.addEventListener('click', async () => {
          if (!confirm('¿Seguro que quieres vaciar el carrito?')) return;
          try {
            await this._apiCart('clear');
            this._updateBadge(0);
            this.container.querySelector('tbody').innerHTML = '';
            this._recalculateTotal();
            this._showToast('Carrito vaciado');
          } catch (err) {
            console.error(err);
            this._showToast(err.message, false);
          }
        });
      }
    }

    /**
     * Maneja la actualización de cantidad en un <tr>.
     */
    async _handleUpdate(tr, id, qty) {
      this._disableRow(tr, true);
      try {
        const data = await this._apiCart('update', id, qty);
        this._refreshRowSubtotal(tr, qty);
        this._recalculateTotal();
        this._updateBadge(data.cartCount);
        this._showToast('Cantidad actualizada');
      } catch (err) {
        console.error(err);
        this._showToast(err.message, false);
      } finally {
        this._disableRow(tr, false);
      }
    }

    /**
     * Maneja la eliminación de un <tr>.
     */
      async _handleRemove(tr, id) {
        this._disableRow(tr, true);
        try {
          const data = await this._apiCart('remove', id);
          
          // Eliminar la fila del DOM
          tr.remove();
          
          // ① Recalcular total global
          this._recalculateTotal();
        
          
          // ② Actualizar badge
          this._updateBadge(data.cartCount);
          
          this._showToast('Artículo eliminado');
          
          // Si ya no hay filas, podrías mostrar un mensaje de "Carrito vacío"
          if (!this.container.querySelector('tbody tr')) {
            // ... lógica adicional (opcional) ...
          }
        } catch (err) {
          console.error(err);
          this._showToast(err.message, false);
        } finally {
          this._disableRow(tr, false);
        }
      }

    /** Calcula y refresca el subtotal de una fila tras un qty change */
    _refreshRowSubtotal(tr, qty) {
      const priceCell = tr.querySelector('td:nth-child(4)');
      const unitPrice = parseFloat(
        priceCell.textContent.replace('€','').replace(',','.')
      ) || 0;
      const subEl = tr.querySelector('.subtotal');
      subEl.textContent = (unitPrice * qty)
        .toFixed(2).replace('.',',') + ' €';
    }

    /** Recalcula el total global desde los subtotales del DOM */
    _recalculateTotal() {
      const subs = Array.from(this.container.querySelectorAll('.subtotal'));
      const total = subs.reduce((sum, el) => {
        return sum + (parseFloat(el.textContent.replace('€','').replace(',','.')) || 0);
      }, 0);
      const totalEl = document.querySelector('.cart-summary .total-line dd');
      if (totalEl) {
        totalEl.textContent = total.toFixed(2).replace('.',',') + ' €';
      }
    }

    /** Deshabilita/rehabilita inputs y buttons de una fila */
    _disableRow(tr, disable) {
      tr.querySelectorAll('input, button').forEach(el => el.disabled = disable);
      tr.style.opacity = disable ? '0.6' : '1';
      
    }
  }

  // Inicializamos cuando el DOM esté listo
  document.addEventListener('DOMContentLoaded', () => new CartController());

})();
