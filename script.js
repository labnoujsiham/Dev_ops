
(function () {
  const nav = document.querySelector('.custom-nav');

  function checkScroll() {
    if (window.scrollY > 60) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  }

  window.addEventListener('scroll', checkScroll, { passive: true });
  checkScroll();
})();

(function () {
  const featureCards = document.querySelectorAll('.feature-card');

  function reveal() {
    featureCards.forEach(card => {
      const r = card.getBoundingClientRect();
      if (r.top < window.innerHeight - 80) card.classList.add('show');
    });
  }
  window.addEventListener('scroll', reveal, { passive: true });
  window.addEventListener('load', reveal);
  reveal();
})();

(function () {
  const canvas = document.getElementById('particles');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let w = canvas.width = window.innerWidth;
  let h = canvas.height = document.querySelector('.hero-section').offsetHeight || Math.round(window.innerHeight * 0.9);

  const particles = [];
  const COUNT = Math.min(60, Math.floor(w / 18)); 

  for (let i = 0; i < COUNT; i++) {
    particles.push({
      x: Math.random() * w,
      y: Math.random() * h,
      r: Math.random() * 2 + 0.6,
      vx: (Math.random() - 0.5) * 0.4,
      vy: (Math.random() - 0.5) * 0.4,
      alpha: 0.4 + Math.random() * 0.6
    });
  }

  function resize() {
    w = canvas.width = window.innerWidth;
    h = canvas.height = document.querySelector('.hero-section').offsetHeight || Math.round(window.innerHeight * 0.9);
  }
  window.addEventListener('resize', resize);

  function draw() {
    ctx.clearRect(0, 0, w, h);

    for (let p of particles) {
      p.x += p.vx;
      p.y += p.vy;
      if (p.x < -10) p.x = w + 10;
      if (p.x > w + 10) p.x = -10;
      if (p.y < -10) p.y = h + 10;
      if (p.y > h + 10) p.y = -10;

      ctx.beginPath();
      ctx.globalAlpha = p.alpha * 0.9;
      ctx.fillStyle = 'rgba(180,230,255,0.9)';
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }

    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const a = particles[i], b = particles[j];
        const dx = a.x - b.x, dy = a.y - b.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          ctx.beginPath();
          ctx.globalAlpha = 0.06 + (0.12 * (1 - dist / 120));
          ctx.strokeStyle = 'rgba(180,230,255,1)';
          ctx.lineWidth = 1;
          ctx.moveTo(a.x, a.y);
          ctx.lineTo(b.x, b.y);
          ctx.stroke();
        }
      }
    }

    ctx.globalAlpha = 1;
    requestAnimationFrame(draw);
  }

  draw();
})();
tsParticles.load("tsparticles", {
    background: { color: "#000020" },
    particles: {
        number: { value: 90 },
        color: {
            value: ["#00ffea", "#00ff6a", "#246BFD", "#6524FD", "#00baff"]
        },
        shape: { type: "circle" },
        opacity: {
            value: 0.5
        }
    }
});




