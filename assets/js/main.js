// assets/js/main.js
document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // ================================
  // 1. MENÚ RESPONSIVE (hamburguesa)
  // ================================
  // Botón con clase .hamburger
  const hamButton = document.querySelector('.hamburger');
  // Navegación con clase .nav-menu
  const mobileNav = document.querySelector('.nav-menu');

  if (hamButton && mobileNav) {
    hamButton.addEventListener('click', () => {
      // alterna la clase "active" para mostrar/ocultar el menú
      mobileNav.classList.toggle('active');
      // actualizamos aria-expanded para accesibilidad
      const expanded = mobileNav.classList.contains('active');
      hamButton.setAttribute('aria-expanded', expanded);
    });
  }

  // ================================
  // 2. HERO SLIDER (auto + pausa on hover)
  // ================================
  const slides       = Array.from(document.querySelectorAll('#slider .slide'));
  let   currentIndex = 0;
  let   slideTimer;

  const showSlide = idx => {
    slides.forEach(s => s.classList.remove('active'));
    slides[idx]?.classList.add('active');
  };

  const nextSlide = () => {
    currentIndex = (currentIndex + 1) % slides.length;
    showSlide(currentIndex);
  };

  if (slides.length) {
    // inicia el bucle de slides
    slideTimer = setInterval(nextSlide, 5000);
    // pausa al pasar el ratón, reanuda al salir
    slides.forEach(slide => {
      slide.addEventListener('mouseenter', () => clearInterval(slideTimer));
      slide.addEventListener('mouseleave', () => {
        slideTimer = setInterval(nextSlide, 5000);
      });
    });
  }

  // ================================
  // 3. CUENTA ATRÁS
  // ================================
  function startCountdown(endDateStr, selector) {
    const endDate = new Date(endDateStr);
    const container = document.querySelector(selector);
    if (!container) return;

    // crea la estructura inicial
    const parts = ['days','hours','minutes','seconds'];
    container.innerHTML = parts.map(p => `
      <div class="time">
        <span data-${p}>0</span>
        <div class="label">${p.toUpperCase()}</div>
      </div>
    `).join('');

    // actualiza valores cada segundo
    const update = () => {
      const now = new Date();
      const diff = Math.max(0, endDate - now);

      const d = Math.floor(diff / 1000 / 60 / 60 / 24);
      const h = Math.floor((diff / 1000 / 60 / 60) % 24);
      const m = Math.floor((diff / 1000 / 60) % 60);
      const s = Math.floor((diff / 1000) % 60);

      container.querySelector('[data-days]').textContent    = d;
      container.querySelector('[data-hours]').textContent   = h;
      container.querySelector('[data-minutes]').textContent = m;
      container.querySelector('[data-seconds]').textContent = s;
    };

    update();
    setInterval(update, 1000);
  }

  // fijamos la fecha límite
  startCountdown('2025-07-01T00:00:00', '#countdown');

  // ================================
  // 4. SWIPER JS (productos destacados)
  // ================================
  if (window.Swiper) {
    new Swiper('.products-carousel.swiper', {
      slidesPerView: 'auto',
      spaceBetween: 20,
      loop: true,
      freeMode: true,
      autoplay: {
        delay: 7000,
        disableOnInteraction: false,
      },
      navigation: {
        nextEl: '.products-carousel .swiper-button-next',
        prevEl: '.products-carousel .swiper-button-prev',
      },
      pagination: {
        el: '.products-carousel .swiper-pagination',
        clickable: true,
      },
      breakpoints: {
        320:  { slidesPerView: 1 },
        768:  { slidesPerView: 2 },
        1024: { slidesPerView: 3 },
      },
    });
  }
}); // fin DOMContentLoaded

// ======================================
// 5. REFUERZO AUTOPLAY VÍDEO NEWSLETTER
// ======================================
// En el HTML el vídeo tiene clase "newsletter-bg"
const newsletterVid = document.querySelector('.newsletter-bg');
if (newsletterVid) {
  newsletterVid.muted = true;
  newsletterVid.play().catch(() => {
    console.warn('Autoplay de newsletter bloqueado');
  });
}
