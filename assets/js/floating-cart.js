// assets/js/floating-cart.js
document.addEventListener('DOMContentLoaded', () => {
  const API_URL        = '/api/floating_cart.php';
  const btn            = document.getElementById('floating-cart');
  const badge          = document.getElementById('cart-count');
  const modal          = document.getElementById('floating-cart-modal');
  const closeBtn       = document.getElementById('floating-cart-close');
  const itemsContainer = modal.querySelector('.items');
  const checkoutBtn    = document.getElementById('floating-cart-checkout');

  // 1) Actualiza el badge con la cantidad
  async function updateBadge() {
    try {
      const res  = await fetch(API_URL);
      if (!res.ok) throw new Error();
      const { count } = await res.json();
      badge.textContent   = count;
      badge.style.display = count > 0 ? 'block' : 'none';
    } catch {
      badge.style.display = 'none';
    }
  }

  // 2) Abre modal y carga los ítems
  async function openCart() {
    try {
      const res  = await fetch(API_URL);
      if (!res.ok) throw new Error();
      const data = await res.json();
      itemsContainer.innerHTML = '';
      data.items.forEach(it => {
        const div = document.createElement('div');
        div.className = 'item';
        div.innerHTML = `
          <img src="/assets/images/${it.image}" alt="${it.name}">
          <div class="info">
            <p>${it.name}</p>
            <p>${it.quantity} × ${it.price.toFixed(2).replace('.',',')} €</p>
            <p>Sub: ${it.subtotal.toFixed(2).replace('.',',')} €</p>
          </div>`;
        itemsContainer.appendChild(div);
      });
      checkoutBtn.textContent = `Pagar (${data.total.toFixed(2).replace('.',',')} €)`;
      modal.classList.add('open');
      modal.setAttribute('aria-hidden','false');
    } catch {
      // opcional: showToast('Error al cargar carrito', false);
    }
  }

  // 3) Cierra el modal
  function closeCart() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden','true');
  }

  // 4) Ir a checkout
  function goCheckout() {
    window.location.href = '/pages/checkout.php';
  }

  // 5) Listeners
  btn.addEventListener('click', openCart);
  closeBtn.addEventListener('click', closeCart);
  checkoutBtn.addEventListener('click', goCheckout);

  // 6) Refrescar badge al cambiar carrito
  document.body.addEventListener('producto-agregado', updateBadge);

  // 7) Inicializar badge
  updateBadge();
});
