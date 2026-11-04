/* global gsap, ScrollTrigger */
/* SheShield landing: scroll story, parallax, assistant. All motion is transform or opacity. */
(function () {
  'use strict';

  const { reduced, aurora, lenis } = window.SS;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const $ = (s) => document.querySelector(s);
  const year = $('#year');
  if (year) year.textContent = new Date().getFullYear();

  /* Live counts from the database; the block stays hidden if the request fails. */
  fetch('../api/public_stats.php').then((r) => (r.ok ? r.json() : null)).then((stats) => {
    if (!stats || stats.reports === null) return;
    const box = $('#liveStats');
    box.querySelectorAll('[data-stat]').forEach((n) => {
      n.dataset.count = stats[n.dataset.stat] ?? 0;
      n.textContent = n.dataset.count;
    });
    box.hidden = false;
    ScrollTrigger.refresh();
    if (!reduced) {
      box.querySelectorAll('[data-count]').forEach((n) => {
        const end = Number(n.dataset.count);
        const o = { v: 0 };
        gsap.to(o, { v: end, duration: 1.3, ease: 'power2.out', scrollTrigger: { trigger: box, start: 'top 92%', once: true }, onUpdate: () => { n.textContent = Math.round(o.v); } });
      });
    }
  }).catch(() => { /* optional block */ });

  /* Assistant */
  const answers = {
    'What does SOS do?': 'SOS opens right away, with no sign-in. You can call 112, 1091 or 100 in one tap, and send your exact location by WhatsApp or SMS to anyone you choose. It does not message anyone automatically, so please send it yourself.',
    'How do I report?': 'Use "Report an incident". You can include your details or report anonymously, and attach photos or documents. You get a report ID once it is saved.',
    'Is reporting anonymous?': 'Yes, if you choose it. An anonymous report is saved without being linked to your account. Your report is visible to the SheShield admin team.',
    'What is Walk With Us?': 'Tell us your pickup, destination and time, and you get a walk ID by email. You can also volunteer as a walker.',
    'Who should I call?': '112 for any emergency, 1091 for the women\'s helpline, and 100 for police. Each number on this page is one tap away.',
    'Find a safe space': 'Open Safe Spaces from your dashboard to see places the community has marked as safe, and add ones you know.',
  };
  const toggle = $('#chatToggle');
  const panel = $('#chatPanel');
  const log = $('#chatLog');
  const quick = $('#chatQuick');
  const addMsg = (text, who) => {
    const m = document.createElement('div');
    m.className = `msg ${who}`;
    m.textContent = text;
    log.appendChild(m);
    log.scrollTop = log.scrollHeight;
    if (!reduced) gsap.from(m, { y: 10, opacity: 0, duration: 0.3, ease: 'power2.out' });
  };
  Object.keys(answers).forEach((q) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.textContent = q;
    b.addEventListener('click', () => { addMsg(q, 'me'); setTimeout(() => addMsg(answers[q], 'bot'), 260); });
    quick.appendChild(b);
  });
  const setChat = (open) => {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    if (open && !reduced) gsap.from(panel, { y: 16, opacity: 0, scale: 0.98, duration: 0.35, ease: 'power3.out', transformOrigin: 'bottom right', clearProps: 'all' });
  };
  toggle.addEventListener('click', () => setChat(panel.hidden));
  $('#chatClose').addEventListener('click', () => setChat(false));

  /* Night to dawn: the aurora follows page progress, even when motion is reduced. */
  ScrollTrigger.create({
    trigger: document.body, start: 'top top', end: 'bottom bottom',
    onUpdate: (self) => aurora.setProgress(self.progress),
  });

  /* Route: one scrubbed timeline. Scrolling draws the line and moves the walker. */
  (function walkRoute() {
    const line = $('#walkLine');
    const dot = $('#walkDot');
    const stops = document.querySelectorAll('.stop');
    const steps = document.querySelectorAll('[data-step]');
    if (!line || !dot) return;
    const total = line.getTotalLength();
    // Where each stop sits along the path, so a stop lights exactly when the dot arrives.
    const marks = [0, 0.3, 0.6, 1];
    const place = (p) => {
      line.style.strokeDashoffset = String(1 - p);
      const pt = line.getPointAtLength(total * p);
      dot.setAttribute('transform', `translate(${pt.x} ${pt.y})`);
      stops.forEach((s, i) => s.classList.toggle('on', p >= marks[i] - 0.005));
    };
    place(reduced ? 1 : 0);
    if (reduced) return;
    const mm = gsap.matchMedia();
    mm.add('(min-width: 901px)', () => {
      const state = { p: 0 };
      gsap.to(state, {
        p: 1, ease: 'none',
        scrollTrigger: { trigger: '.walk-steps', start: 'top 62%', end: 'bottom 72%', scrub: 0.6 },
        onUpdate: () => place(state.p),
      });
      steps.forEach((step) => {
        gsap.fromTo(step, { opacity: 0.35, y: 30 }, {
          opacity: 1, y: 0, ease: 'power2.out',
          scrollTrigger: { trigger: step, start: 'top 78%', end: 'top 50%', scrub: 0.4 },
        });
      });
    });
    mm.add('(max-width: 900px)', () => {
      gsap.set(steps, { opacity: 1 });
      steps.forEach((step) => gsap.from(step, { y: 26, opacity: 0, duration: 0.7, ease: 'power3.out', scrollTrigger: { trigger: step, start: 'top 90%', once: true } }));
    });
  }());

  /* SOS demo: the page scroll drives the phone, screen by screen. */
  (function demo() {
    const screens = gsap.utils.toArray('.screen');
    const steps = gsap.utils.toArray('#demoSteps li');
    if (!screens.length) return;
    gsap.set(screens, { autoAlpha: 0 });
    gsap.set(screens[0], { autoAlpha: 1 });
    if (reduced) return;
    const tl = gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: {
        trigger: '.demo', start: 'top top', end: 'bottom bottom', scrub: 0.4,
      },
      // Runs on the smoothed timeline, so the caption always matches the screen on show.
      onUpdate() {
        const active = Math.min(steps.length - 1, Math.floor(this.time() + 0.1));
        steps.forEach((s, i) => s.classList.toggle('on', i === active));
      },
    });
    screens.forEach((screen, i) => {
      if (i === 0) return;
      const at = i - 0.18;
      tl.to(screens[i - 1], { autoAlpha: 0, y: -24, scale: 0.97, duration: 0.18 }, at);
      tl.fromTo(screen, { autoAlpha: 0, y: 28, scale: 0.97 }, { autoAlpha: 1, y: 0, scale: 1, duration: 0.18 }, at + 0.1);
    });
    tl.to({}, { duration: 0.3 });
    gsap.fromTo('.phone', { rotateZ: -3, y: 24 }, { rotateZ: 3, y: -24, ease: 'none', scrollTrigger: { trigger: '.demo', start: 'top top', end: 'bottom bottom', scrub: 0.6 } });
  }());

  /* Spheres drift at their own speed, so the frost in front of them keeps changing. */
  if (!reduced) {
    document.querySelectorAll('[data-orb]').forEach((orb) => {
      gsap.to(orb, { y: Number(orb.dataset.orb) * 3, ease: 'none', scrollTrigger: { trigger: orb.parentElement, start: 'top bottom', end: 'bottom top', scrub: 0.6 } });
    });
  }

  /* Scroll-velocity marquee. Only ticks while on screen. */
  (function marquee() {
    const rows = [...document.querySelectorAll('.marquee-row')];
    if (!rows.length) return;
    const data = rows.map((row) => {
      const track = row.querySelector('.marquee-track');
      track.parentElement.appendChild(track.cloneNode(true));
      track.parentElement.appendChild(track.cloneNode(true));
      const tracks = row.querySelectorAll('.marquee-track');
      return { row, tracks, dir: Number(row.dataset.speed), x: 0, width: 0 };
    });
    const measure = () => data.forEach((d) => { d.width = d.tracks[0].offsetWidth; });
    measure();
    window.addEventListener('resize', measure, { passive: true });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);
    if (reduced) return;

    let on = false;
    ScrollTrigger.create({ trigger: '.marquee', start: 'top bottom', end: 'bottom top', onToggle: (s) => { on = s.isActive; } });
    const setters = data.map((d) => gsap.quickSetter(d.row, 'x', 'px'));
    gsap.ticker.add((_, dt) => {
      if (!on) return;
      const v = lenis ? Math.min(Math.abs(lenis.velocity), 60) : 0;
      data.forEach((d, i) => {
        d.x += d.dir * (0.045 + v * 0.018) * dt;
        if (d.width) {
          // Wrap into [-width, 0) so the cloned tracks always cover the viewport.
          d.x = ((d.x % d.width) - d.width) % d.width;
        }
        setters[i](d.x);
      });
    });
  }());

  /* Bento visuals grow in; the about image drifts within its frame. */
  if (!reduced) {
    document.querySelectorAll('.viz-bars').forEach((box) => {
      gsap.from(box.children, { scaleY: 0, transformOrigin: 'bottom', duration: 0.9, ease: 'power3.out', stagger: 0.05, clearProps: 'transform', scrollTrigger: { trigger: box, start: 'top 90%', once: true } });
    });
    const img = $('#aboutImg');
    if (img) gsap.fromTo(img, { yPercent: -14 }, { yPercent: 0, ease: 'none', scrollTrigger: { trigger: img, start: 'top bottom', end: 'bottom top', scrub: 0.5 } });
  }

  /* Hero */
  if (reduced) return;
  const pass = $('#passCard');
  const stage = $('#heroStage');
  gsap.from('.hero-clock', { y: -10, opacity: 0, duration: 0.7, ease: 'power3.out', delay: 0.05 });
  gsap.from(pass, { y: 60, opacity: 0, rotate: 6, duration: 1.2, ease: 'power4.out', delay: 0.35 });
  gsap.from('#floatSos', { x: 40, opacity: 0, duration: 0.9, ease: 'power3.out', delay: 0.75 });
  gsap.from('#floatLoc', { x: -40, opacity: 0, duration: 0.9, ease: 'power3.out', delay: 0.9 });

  // Scrolling out: layers leave at different speeds.
  const heroScroll = { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 0.5 };
  gsap.to('#heroCopy', { yPercent: -16, opacity: 0.2, ease: 'none', scrollTrigger: heroScroll });
  gsap.to(stage, { yPercent: -30, ease: 'none', scrollTrigger: heroScroll });
  gsap.to('#floatSos', { yPercent: -160, ease: 'none', scrollTrigger: heroScroll });
  gsap.to('#floatLoc', { yPercent: 70, ease: 'none', scrollTrigger: heroScroll });

  // Pointer tilt: quickTo reuses one tween per axis, and it stops while off screen.
  if (finePointer) {
    const rx = gsap.quickTo(pass, 'rotationX', { duration: 0.7, ease: 'power3' });
    const ry = gsap.quickTo(pass, 'rotationY', { duration: 0.7, ease: 'power3' });
    const hero = $('.hero');
    let inView = true;
    ScrollTrigger.create({ trigger: hero, start: 'top bottom', end: 'bottom top', onToggle: (s) => { inView = s.isActive; } });
    hero.addEventListener('pointermove', (e) => {
      if (!inView) return;
      ry((e.clientX / window.innerWidth - 0.5) * 16);
      rx((e.clientY / window.innerHeight - 0.5) * -12);
    }, { passive: true });
  }
}());
