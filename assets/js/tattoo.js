(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    /*** UTILITIES ***/
    const throttle = (fn, wait = 50) => {
      let last = 0;
      return (...args) => {
        const now = Date.now();
        if (now - last >= wait) {
          fn.apply(this, args);
          last = now;
        }
      };
    };

    const supportsIntersectionObserver = 'IntersectionObserver' in window;

    /*** 1. CARDS SWIPER (Loop infinito + centrado + avance por slide) ***/
const cardsSwiper = new Swiper('.cards-swiper', {
  loop: true,
  centeredSlides: true,
  slidesPerView: 'auto',       // AUTO porque el ancho ya lo controla CSS
  spaceBetween: 20,
  speed: 600,
  grabCursor: true,
  slideToClickedSlide: true,   // centra al hacer click en cualquier slide
  navigation: {
    nextEl: '.cards-swiper .swiper-button-next',
    prevEl: '.cards-swiper .swiper-button-prev',
  },
  breakpoints: {
    320:  { slidesPerView: 1.2 },
    640:  { slidesPerView: 2.2 },
    1024: { slidesPerView: 3.2 }
  }
});


    /*** 2. TILT EFFECT ***/
    document.querySelectorAll('.cards-swiper .card').forEach(card => {
      const reset = () => {
        card.style.transition = 'transform 0.4s ease';
        card.style.transform = '';
        setTimeout(() => card.style.transition = '', 400);
      };
      card.addEventListener('pointermove', e => {
        const { left, top, width, height } = card.getBoundingClientRect();
        const x = ((e.clientX - left) / width  - 0.5) * 2;
        const y = ((e.clientY - top ) / height - 0.5) * 2;
        card.style.transform = 'perspective(700px) ' +
          'rotateX(' + (-y * 6) + 'deg) ' +
          'rotateY(' + (x * 6) + 'deg) ' +
          'scale(1.02)';
      });
      card.addEventListener('pointerleave', reset);
      card.addEventListener('pointercancel', reset);
    });

    /*** 3. LAZY ANIMATION WITH OBSERVER ***/
    const animateOnView = els => {
      if (supportsIntersectionObserver) {
        const io = new IntersectionObserver((entries, obs) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
              obs.unobserve(entry.target);
            }
          });
        }, { threshold: 0.15, rootMargin: '0px 0px -80px 0px' });
        els.forEach(el => io.observe(el));
      } else {
        els.forEach(el => el.classList.add('visible'));
      }
    };
    animateOnView(document.querySelectorAll('.gallery-item, .method-card'));

    /*** 4. SMOOTH SCROLL WITH ACCESSIBILITY ***/
    document.body.addEventListener('click', e => {
      const anchor = e.target.closest('a[href^="#"]');
      if (!anchor) return;
      const id = anchor.getAttribute('href').slice(1);
      const target = document.getElementById(id);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
      }
    });

    /*** 5. FORM VALIDATION ***/
    const form = document.querySelector('.contact-form');
    if (form) {
      form.addEventListener('submit', e => {
        let valid = true;
        ['name', 'email', 'message'].forEach(name => {
          const field = form.elements[name];
          field.classList.remove('error');
          if (!field.value.trim() ||
              (name === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value))) {
            valid = false;
            field.classList.add('error');
          }
        });
        if (!valid) {
          e.preventDefault();
          form.querySelector('.error').focus();
        }
      });
    }

    /*** 6. STUDIO PARALLAX ***/
    const studioBg = document.querySelector('.studio-bg');
    if (studioBg) {
      window.addEventListener('scroll', throttle(() => {
        studioBg.style.transform = `translateY(${window.scrollY * 0.25}px)`;
      }, 25));
    }

    /*** 7. IMAGE LAZY LOADING (fallback) ***/
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
      if ('loading' in HTMLImageElement.prototype) return;
      const src = img.dataset.src || img.src;
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            img.src = src;
            obs.unobserve(img);
          }
        });
      });
      io.observe(img);
    });

    /*** 8. KEYBOARD NAVIGATION FOR CARDS ***/
    document.querySelectorAll('.cards-swiper .swiper-slide').forEach(slide => {
      slide.setAttribute('tabindex', '0');
      slide.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') cardsSwiper.slideNext();
        if (e.key === 'ArrowLeft')  cardsSwiper.slidePrev();
      });
    });

    /*** 9. UPDATE ON RESIZE ***/
    window.addEventListener('resize', throttle(() => {
      cardsSwiper.update();
    }, 200));

  });
})();
