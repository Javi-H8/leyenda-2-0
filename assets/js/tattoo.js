// assets/js/tattoo.js — JavaScript “ultra experto” para Leyenda Barber & Tattoo
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

    const isVisible = el => {
      const rect = el.getBoundingClientRect();
      return rect.top < window.innerHeight && rect.bottom > 0;
    };

    /*** 1. CARDS SWIPER ***/
    const slides = document.querySelectorAll('.cards-swiper .swiper-slide').length;
    new Swiper('.cards-swiper', {
      loop: slides > 3,
      slidesPerView: 'auto',
      spaceBetween: 20,
      speed: 600,
      grabCursor: true,
      watchOverflow: true,
      navigation: {
        nextEl: '.cards-swiper .swiper-button-next',
        prevEl: '.cards-swiper .swiper-button-prev',
      },
      a11y: {
        enabled: true,
        prevSlideMessage: 'Anterior tatuaje',
        nextSlideMessage: 'Siguiente tatuaje',
      },
      breakpoints: {
        320:  { slidesPerView: 1.3 },
        640:  { slidesPerView: 2.3 },
        1024: { slidesPerView: 3.3 }
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
        card.style.transform = `perspective(700px) rotateX(${-y * 6}deg) rotateY(${x * 6}deg) scale(1.02)`;
      });
      card.addEventListener('pointerleave', reset);
      card.addEventListener('pointercancel', reset);
    });

    /*** 3. LAZY ANIMATION WITH OBSERVER ***/
    const animateOnView = els => {
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries, obs) => {
          entries.forEach(e => {
            if (e.isIntersecting) {
              e.target.classList.add('visible');
              obs.unobserve(e.target);
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
      const a = e.target.closest('a[href^="#"]');
      if (!a) return;
      const id = a.getAttribute('href').slice(1);
      const tgt = document.getElementById(id);
      if (tgt) {
        e.preventDefault();
        tgt.scrollIntoView({ behavior: 'smooth', block: 'start' });
        tgt.setAttribute('tabindex', '-1');
        tgt.focus({ preventScroll: true });
      }
    });

    /*** 5. FORM VALIDATION ***/
    const form = document.querySelector('.contact-form');
    if (form) {
      form.addEventListener('submit', e => {
        let valid = true;
        ['name','email','message'].forEach(key => {
          const f = form.elements[key];
          f.classList.remove('error');
          if (!f.value.trim() ||
              (key === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value))
          ) {
            valid = false;
            f.classList.add('error');
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

    /*** 7. IMAGE LAZY LOADING (supports non-lazy browsers) ***/
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
      if ('loading' in HTMLImageElement.prototype) return;
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            img.src = img.dataset.src || img.src;
            obs.unobserve(img);
          }
        });
      });
      io.observe(img);
    });

    /*** 8. KEYBOARD NAVIGATION FOR CARDS ***/
    document.querySelectorAll('.cards-swiper .swiper-slide').forEach((slide, i) => {
      slide.setAttribute('tabindex', '0');
      slide.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') cardsSwiper.slideNext();
        if (e.key === 'ArrowLeft')  cardsSwiper.slidePrev();
      });
    });
  });
})();
