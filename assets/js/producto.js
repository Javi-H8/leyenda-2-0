/**
 * assets/js/producto.js
 * Interactividad moderna para la página de producto
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  // Elementos principales
  const thumbs        = document.querySelectorAll('.gallery-thumbs .thumb');
  const mainImages    = document.querySelectorAll('.gallery-main .main-img');
  const variantSelect = document.getElementById('variante');
  const priceEl       = document.getElementById('precio-actual');
  const stockNotice   = document.getElementById('stock-aviso');
  const inputVariant  = document.getElementById('input-variante');
  const addCartBtn    = document.getElementById('btn-carrito');

  // --- Función: animación de ripple en botón ---
  function rippleEffect(e) {
    const circle = document.createElement('span');
    const diameter = Math.max(this.clientWidth, this.clientHeight);
    const radius = diameter / 2;
    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left   = `${e.clientX - this.offsetLeft - radius}px`;
    circle.style.top    = `${e.clientY - this.offsetTop - radius}px`;
    circle.classList.add('ripple');
    const ripple = this.getElementsByClassName('ripple')[0];
    if (ripple) ripple.remove();
    this.appendChild(circle);
  }

  // --- Función: cambiar imagen principal ---
  function updateMainImage(index) {
    mainImages.forEach(img => {
      img.classList.toggle('visible', img.dataset.index === index);
      if (img.classList.contains('visible')) {
        img.classList.add('fade-in');
        setTimeout(() => img.classList.remove('fade-in'), 300);
      }
    });
    thumbs.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.index === index);
    });
  }

  // --- Función: actualizar precio y stock ---
  function updateVariantInfo() {
    const opt   = variantSelect.selectedOptions[0];
    const stock = Number(opt.dataset.stock);
    const price = Number(opt.dataset.precio).toFixed(2).replace('.', ',');
    priceEl.textContent = `€${price}`;
    inputVariant.value  = opt.value;

    if (stock === 0) {
      stockNotice.textContent = 'Agotado';
      addCartBtn.disabled     = true;
    } else {
      addCartBtn.disabled = false;
      stockNotice.textContent = stock <= 2
        ? `¡Últimas ${stock} unidades!`
        : '';
    }
  }

  // --- Inicializar galería ---
  thumbs.forEach(btn => {
    btn.addEventListener('click', e => {
      const idx = btn.dataset.index;
      updateMainImage(idx);
    });
  });

  // --- Inicializar variantes ---
  variantSelect.addEventListener('change', updateVariantInfo);

  // --- Efecto ripple en botón de carrito ---
  addCartBtn.addEventListener('click', rippleEffect);

  // --- Inicialización al cargar ---
  if (thumbs.length)       updateMainImage('0');
  if (variantSelect)       updateVariantInfo();
});

