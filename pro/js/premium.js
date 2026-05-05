/* ═══════════════════════════════════════════════════════════
   SheShield — Award-Level Interactions
   Lenis · GSAP/ScrollTrigger · Custom Cursor · VanillaTilt
   CountUp · Text Scramble · Magnetic Buttons
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';
  const isMobile = window.innerWidth <= 768 || 'ontouchstart' in window;

  /* ─── 1. Lenis Smooth Scroll ────────────────────────────── */
  const lenis = new Lenis({
    duration: 1.4,
    easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
    touchMultiplier: 1.5
  });
  function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
  requestAnimationFrame(raf);

  gsap.registerPlugin(ScrollTrigger);
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.lagSmoothing(0);

  /* ─── 2. Page Loader Curtain ────────────────────────────── */
  window.addEventListener('DOMContentLoaded', () => {
    gsap.to('#loader', {
      yPercent: -100,
      duration: 1.2,
      ease: 'expo.inOut',
      delay: 0.3,
      onComplete: () => {
        document.getElementById('loader').style.display = 'none';
        animateHeroText();
      }
    });
  });

  /* ─── 3. Custom Cursor ──────────────────────────────────── */
  if (!isMobile) {
    const cursor = document.getElementById('cursor');
    const follower = document.getElementById('cursor-follower');
    let mx = 0, my = 0, fx = 0, fy = 0;

    document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });

    (function moveCursor() {
      if (cursor) {
        cursor.style.left = mx + 'px';
        cursor.style.top = my + 'px';
      }
      // Follower lerps behind
      fx += (mx - fx) * 0.12;
      fy += (my - fy) * 0.12;
      if (follower) {
        follower.style.left = fx + 'px';
        follower.style.top = fy + 'px';
      }
      requestAnimationFrame(moveCursor);
    })();

    // Expand on interactive elements
    document.addEventListener('mouseenter', e => {
      if (e.target.matches('a, button, .service-card, .btn-premium, input, select')) {
        follower && follower.classList.add('expanded');
      }
    }, true);
    document.addEventListener('mouseleave', e => {
      if (e.target.matches('a, button, .service-card, .btn-premium, input, select')) {
        follower && follower.classList.remove('expanded');
      }
    }, true);
  }

  /* ─── 4. Scroll Progress Bar ────────────────────────────── */
  ScrollTrigger.create({
    trigger: document.documentElement,
    start: 'top top',
    end: 'bottom bottom',
    onUpdate: self => {
      const bar = document.getElementById('scrollProgress');
      if (bar) bar.style.width = (self.progress * 100) + '%';
    }
  });

  /* ─── 5. Navbar Scroll Effect ───────────────────────────── */
  const nav = document.querySelector('.glass-nav');
  ScrollTrigger.create({
    start: 60,
    onUpdate: self => {
      if (!nav) return;
      nav.classList.toggle('nav--scrolled', self.scroll() > 60);
    }
  });

  /* ─── 6. Hero Text Animation ────────────────────────────── */
  function animateHeroText() {
    // Animate badge
    gsap.from('.hero-badge', { y: -20, opacity: 0, duration: 0.8, ease: 'back.out(1.7)', delay: 0.2 });

    // Animate word-inner elements (headline reveal)
    gsap.to('.word-inner', {
      translateY: '0%', duration: 1.1,
      ease: 'expo.out', stagger: 0.07, delay: 0.5
    });

    // Subtitle fade up
    gsap.from('.hero-sub', { y: 30, opacity: 0, duration: 0.8, ease: 'power3.out', delay: 1.0 });

    // Buttons fade up
    gsap.from('.hero-buttons', { y: 20, opacity: 0, duration: 0.7, ease: 'power3.out', delay: 1.2 });

    // Stat chips stagger from left
    gsap.from('.stat-chip', { x: -60, opacity: 0, duration: 1, stagger: 0.2, ease: 'expo.out', delay: 1.4 });

    // Scroll hint fade in
    gsap.from('.scroll-hint', { opacity: 0, y: 10, duration: 0.6, delay: 1.8 });
  }
  /* ─── 6.5 Hero Morph Text Animation ──────────────────────── */
  const morphViewport = document.getElementById('morphWords');
  if (morphViewport) {
    const words = ['every woman.', 'every night.', 'every campus.', 'every journey.'];
    let currentIndex = 0;
    
    setInterval(() => {
      const oldCurrent = morphViewport.querySelector('.morph-current:not(.is-exiting)');
      if (oldCurrent) {
        oldCurrent.classList.add('is-exiting');
        oldCurrent.style.position = 'absolute';
      }
      
      currentIndex = (currentIndex + 1) % words.length;
      
      const newSpan = document.createElement('span');
      newSpan.className = 'morph-current is-entering';
      newSpan.textContent = words[currentIndex];
      morphViewport.appendChild(newSpan);
      
      // Force reflow for CSS transition to trigger
      void newSpan.offsetWidth;
      newSpan.classList.remove('is-entering');
      
      // Clean up old element after transition (550ms)
      setTimeout(() => {
        if (oldCurrent && oldCurrent.parentNode) {
          oldCurrent.parentNode.removeChild(oldCurrent);
        }
      }, 550);
      
    }, 2500); // Change word every 2.5s
  }

  /* ─── 7. Parallax (Hero) ────────────────────────────────── */
  if (!isMobile) {
    gsap.to('.hero-image-layer', {
      y: () => window.innerHeight * 0.3,
      ease: 'none',
      scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 1 }
    });
    gsap.to('.hero-headline', {
      y: () => -window.innerHeight * 0.1,
      ease: 'none',
      scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 1 }
    });
    gsap.to('.hero-bg-text', {
      yPercent: 30, ease: 'none',
      scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 1.5 }
    });
  }

  // Scroll hint — fade out after 100px
  let scrollHintHidden = false;
  lenis.on('scroll', ({ scroll }) => {
    if (!scrollHintHidden && scroll > 100) {
      gsap.to('.scroll-hint', { opacity: 0, duration: 0.3 });
      scrollHintHidden = true;
    }
  });

  /* ─── 8. Magnetic Buttons ───────────────────────────────── */
  if (!isMobile) {
    document.querySelectorAll('.magnetic-btn').forEach(btn => {
      btn.addEventListener('mousemove', e => {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        btn.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
      });
      btn.addEventListener('mouseleave', () => {
        btn.style.transform = 'translate(0, 0)';
        btn.style.transition = 'transform 0.5s cubic-bezier(0.23,1,0.32,1)';
      });
      btn.addEventListener('mouseenter', () => {
        btn.style.transition = 'none';
      });
    });
  }

  /* ─── 9. Stats CountUp ──────────────────────────────────── */
  const countUpObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseInt(el.dataset.target, 10);
        const suffix = el.dataset.suffix || '';
        let start = 0;
        const duration = 2000;
        const startTime = performance.now();

        function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }

        function step(now) {
          const elapsed = now - startTime;
          const progress = Math.min(elapsed / duration, 1);
          const current = Math.floor(easeOutExpo(progress) * target);
          el.textContent = current.toLocaleString() + suffix;
          if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        countUpObserver.unobserve(el);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('.count-up').forEach(el => countUpObserver.observe(el));

  /* ─── 10. Text Scramble (Section Headings) ──────────────── */
  const scrambleChars = '!<>-_\\/[]{}—=+*^?#________';
  const scrambleObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        scrambleText(entry.target);
        scrambleObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('.scramble-text').forEach(el => scrambleObserver.observe(el));

  function scrambleText(el) {
    const finalText = el.dataset.value || el.textContent;
    const len = finalText.length;
    let iteration = 0;
    const interval = setInterval(() => {
      el.textContent = finalText.split('').map((char, i) => {
        if (char === ' ') return ' ';
        if (i < iteration) return finalText[i];
        return scrambleChars[Math.floor(Math.random() * scrambleChars.length)];
      }).join('');
      iteration += 1 / 3;
      if (iteration >= len) { clearInterval(interval); el.textContent = finalText; }
    }, 30);
  }

  /* ─── 11. Service Cards — GSAP Stagger + VanillaTilt ────── */
  if (!isMobile) {
    requestIdleCallback(() => {
      gsap.from('.service-card', {
        y: 80, opacity: 0, duration: 0.8,
        stagger: 0.12, ease: 'power3.out',
        scrollTrigger: { trigger: '.services-grid', start: 'top 80%' }
      });
      // VanillaTilt
      if (typeof VanillaTilt !== 'undefined') {
        document.querySelectorAll('.service-card').forEach(card => {
          VanillaTilt.init(card, { max: 8, speed: 400, glare: true, 'max-glare': 0.1 });
        });
      }
    });
  }

  /* ─── 12. About Section Animations ──────────────────────── */
  if (!isMobile) {
    requestIdleCallback(() => {
      gsap.from('.about-text', {
        x: -60, opacity: 0, duration: 1, ease: 'power3.out',
        scrollTrigger: { trigger: '.about-grid', start: 'top 75%' }
      });
      gsap.to('.about-image-wrap', {
        clipPath: 'inset(0 0% 0 0)', duration: 1.2, ease: 'expo.inOut',
        scrollTrigger: { trigger: '.about-grid', start: 'top 70%' }
      });
    });
  }

  /* ─── 13. Smooth Anchor Scrolling via Lenis ─────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      e.preventDefault();
      const target = document.querySelector(a.getAttribute('href'));
      if (target) lenis.scrollTo(target, { offset: -80 });
    });
  });

})();
