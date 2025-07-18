// assets/js/auth.js
// ----------------------
// • Autofocus en el primer campo
// • Validación HTML5 accesible
// • Ripple effect en botón submit

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  const form = document.querySelector('.auth-form');
  if (!form) return;

  // 1) Autofocus
  const firstInput = form.querySelector('input[autofocus], input[type="email"]');
  if (firstInput) firstInput.focus();

  // 2) Validación HTML5
  form.addEventListener('invalid', event => {
    event.preventDefault();
    const input = event.target;
    input.classList.add('invalid');
    input.setAttribute('aria-invalid', 'true');
  }, true);

  form.addEventListener('input', event => {
    const input = event.target;
    if (input.checkValidity()) {
      input.classList.remove('invalid');
      input.removeAttribute('aria-invalid');
    }
  });

  // 3) Ripple effect en submit
  form.querySelectorAll('button[type="submit"]').forEach(btn => {
    btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.addEventListener('pointerdown', e => {
      const circle = document.createElement('span');
      circle.className = 'ripple';
      const d = Math.max(btn.clientWidth, btn.clientHeight);
      circle.style.width = circle.style.height = d + 'px';
      const rect = btn.getBoundingClientRect();
      circle.style.left = `${e.clientX - rect.left - d/2}px`;
      circle.style.top  = `${e.clientY - rect.top  - d/2}px`;
      btn.appendChild(circle);
      circle.addEventListener('animationend', () => circle.remove());
    }, { passive: true });
  });
});
