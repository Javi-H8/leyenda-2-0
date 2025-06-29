// assets/js/productos.js
// 1) Filtrado cliente  2) Carrusel (main-img solo)  3) Switcher columnas (1 y 2) + efectos visuales en tarjetas

document.addEventListener('DOMContentLoaded', () => {
  const grid      = document.getElementById('lista-productos');
  const buscador  = document.getElementById('buscador');
  const selector  = document.getElementById('categoria');
  const cards     = Array.from(grid.querySelectorAll('.card'));
  const buttons   = Array.from(document.querySelectorAll('.column-switcher .col-btn'));

  // — Filtrado por texto y categoría —
  function filtrar() {
    const text = buscador.value.trim().toLowerCase();
    const cat  = selector.value;
    cards.forEach(card => {
      const title = card.querySelector('h2').textContent.toLowerCase();
      const okText = title.includes(text);
      const okCat  = (cat === 'all') || (card.dataset.categoria === cat);
      card.style.display = (okText && okCat) ? '' : 'none';
    });
  }
  buscador.addEventListener('input', filtrar);
  selector.addEventListener('change', filtrar);

  // — Switcher de columnas (solo 1 y 2) —
  const mapClass = { '1': 'uno-por-linea', '2': 'dos-por-linea' };
  function applyCols(n) {
    Object.values(mapClass).forEach(c => grid.classList.remove(c));
    grid.classList.add(mapClass[n] || mapClass['2']);
    buttons.forEach(btn => {
      const active = btn.dataset.cols === n;
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-pressed', active);
    });
    localStorage.setItem('productosCols', n);
  }
  buttons.forEach(btn => btn.addEventListener('click', () => applyCols(btn.dataset.cols)));
  applyCols(localStorage.getItem('productosCols') || '2');
  filtrar();

  // — EFECTOS VISUALES EN TARJETAS — 

  // 1. Fade-in con IntersectionObserver
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  cards.forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(card);
  });

  // 2. “Pop” al hover
  cards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform += ' scale(1.03)';
      card.style.boxShadow = '0 12px 24px rgba(0,0,0,0.15)';
    });
    card.addEventListener('mouseleave', () => {
      // restaurar solo transform translateY si ya visible
      card.style.transform = 'translateY(0)';
      card.style.boxShadow = '';
    });
  });

  // 3. Ripple al click
  cards.forEach(card => {
    card.addEventListener('click', e => {
      const rect = card.getBoundingClientRect();
      const circle = document.createElement('span');
      const d = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - d/2;
      const y = e.clientY - rect.top  - d/2;
      circle.style.width = circle.style.height = d + 'px';
      circle.style.left = x + 'px';
      circle.style.top  = y + 'px';
      circle.className = 'ripple';
      const rip = card.querySelector('.ripple');
      if (rip) rip.remove();
      card.appendChild(circle);
    });
  });

  // 4. Tilt suave con el ratón
  cards.forEach(card => {
    card.addEventListener('mousemove', e => {
      const { width, height, left, top } = card.getBoundingClientRect();
      const x = (e.clientX - left) / width  - 0.5;
      const y = (e.clientY - top)  / height - 0.5;
      const rx = y * 6;
      const ry = x * 6;
      card.style.transform = `perspective(500px) translateY(0) rotateX(${-rx}deg) rotateY(${ry}deg) scale(1.02)`;
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0)';
    });
  });


});

// ====== EFECTOS VISUALES ======

// 1) Fade-in al entrar en viewport
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('fade-in');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.card').forEach(card => {
  card.style.opacity = '0';
  card.style.transform = 'translateY(20px)';
  observer.observe(card);
});

// 2) Ripple al click
document.querySelectorAll('.card').forEach(card => {
  card.addEventListener('click', e => {
    const circle = document.createElement('span');
    const d = Math.max(card.clientWidth, card.clientHeight);
    circle.style.width = circle.style.height = `${d}px`;
    const rect = card.getBoundingClientRect();
    circle.style.left = `${e.clientX - rect.left - d/2}px`;
    circle.style.top  = `${e.clientY - rect.top  - d/2}px`;
    circle.classList.add('ripple');
    const old = card.querySelector('.ripple');
    if (old) old.remove();
    card.appendChild(circle);
  });
});
