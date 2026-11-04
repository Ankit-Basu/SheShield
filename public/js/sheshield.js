/* global gsap, ScrollTrigger */
/* ═══════════════════════════════════════════════════════════════════
   SheShield core — aurora, smooth scroll, GSAP motion, toast, dialog, SOS
   Ports of React Bits ideas (Aurora, SplitText, ScrollReveal, SpotlightCard,
   Magnet, CountUp) to plain JS, because this is a multi-page PHP app and a
   React runtime on every page would cost more than the effects themselves.
   ═══════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const root = document.documentElement;
  const BASE = root.dataset.base || '';
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saveData = !!(navigator.connection && navigator.connection.saveData);
  const lowEnd = (navigator.hardwareConcurrency || 8) <= 2 || (navigator.deviceMemory || 8) <= 2;
  const hasGsap = typeof window.gsap !== 'undefined';
  const icon = (name) => `<svg class="i" aria-hidden="true"><use href="${BASE}public/icons.svg#${name}"/></svg>`;

  root.classList.add('js');

  /* ─── Aurora ────────────────────────────────────────────────────
     A small fragment shader drawn to a tiny canvas and stretched by the
     compositor. Frame budget governor: if frames run long it lowers its own
     rate, and on sustained trouble it freezes on the current frame. */
  const aurora = (function () {
    const host = document.querySelector('.ss-backdrop');
    if (!host) return { setProgress() {}, recolor() {} };

    const calm = host.dataset.mode === 'calm';
    const canvas = document.createElement('canvas');
    const gl = !saveData && canvas.getContext('webgl', {
      alpha: false, antialias: false, depth: false, stencil: false,
      powerPreference: 'low-power', preserveDrawingBuffer: false,
    });
    if (!gl) return { setProgress() {}, recolor() {} };

    const vert = 'attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}';
    const frag = `precision mediump float;
      uniform vec2 r;uniform float t,d;uniform vec3 a,b,c,base;
      vec3 m(vec3 x){return x-floor(x/289.)*289.;}vec2 m(vec2 x){return x-floor(x/289.)*289.;}
      vec3 pm(vec3 x){return m((x*34.+1.)*x);}
      float sn(vec2 v){const vec4 C=vec4(.211324865,.366025403,-.577350269,.024390243);
        vec2 i=floor(v+dot(v,C.yy)),x0=v-i+dot(i,C.xx);vec2 i1=x0.x>x0.y?vec2(1.,0.):vec2(0.,1.);
        vec4 x12=x0.xyxy+C.xxzz;x12.xy-=i1;i=m(i);
        vec3 pp=pm(pm(i.y+vec3(0.,i1.y,1.))+i.x+vec3(0.,i1.x,1.));
        vec3 mm=max(.5-vec3(dot(x0,x0),dot(x12.xy,x12.xy),dot(x12.zw,x12.zw)),0.);mm=mm*mm;mm=mm*mm;
        vec3 x=2.*fract(pp*C.www)-1.,h=abs(x)-.5,ox=floor(x+.5),a0=x-ox;
        mm*=1.79284291-.85373472*(a0*a0+h*h);vec3 g;g.x=a0.x*x0.x+h.x*x0.y;g.yz=a0.yz*x12.xz+h.yz*x12.yw;
        return 130.*dot(mm,g);}
      void main(){vec2 u=gl_FragCoord.xy/r;
        float n=sn(vec2(u.x*1.5+t*.05,t*.09))*.55+sn(vec2(u.x*3.+t*.08,u.y*.5-t*.05))*.25;
        float crest=.62+n*.3-d*.38;
        float band=smoothstep(crest-.42,crest+.34,u.y);
        float glow=smoothstep(.12,.92,sn(vec2(u.x*1.1-t*.03,u.y*1.2+t*.05))*.5+.5);
        vec3 ramp=mix(a,b,smoothstep(.1,.6,u.x+n*.25));ramp=mix(ramp,c,smoothstep(.45,1.,u.x-n*.2)*(.45+d*.55));
        float low=(1.-smoothstep(0.,.55,u.y))*d;
        vec3 col=mix(base,ramp,band*(.44+glow*.2));
        col=mix(col,mix(b,c,.7),low*.28);
        float vig=1.-smoothstep(.3,1.25,length((u-vec2(.5,.55))*vec2(1.,1.15)));
        gl_FragColor=vec4(col*(.62+.38*vig),1.);}`;

    const compile = (type, src) => { const s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    const prog = gl.createProgram();
    gl.attachShader(prog, compile(gl.VERTEX_SHADER, vert));
    gl.attachShader(prog, compile(gl.FRAGMENT_SHADER, frag));
    gl.linkProgram(prog);
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return { setProgress() {}, recolor() {} };
    gl.useProgram(prog);
    gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
    const loc = gl.getAttribLocation(prog, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    const u = {};
    ['r', 't', 'd', 'a', 'b', 'c', 'base'].forEach((k) => { u[k] = gl.getUniformLocation(prog, k); });

    const hex = (v) => { const n = parseInt(v.trim().slice(1), 16); return [(n >> 16 & 255) / 255, (n >> 8 & 255) / 255, (n & 255) / 255]; };
    const recolor = () => {
      const s = getComputedStyle(root);
      gl.uniform3fv(u.a, hex(s.getPropertyValue('--aurora-1')));
      gl.uniform3fv(u.b, hex(s.getPropertyValue('--aurora-2')));
      gl.uniform3fv(u.c, hex(s.getPropertyValue('--aurora-3')));
      gl.uniform3fv(u.base, hex(s.getPropertyValue('--aurora-base')));
      draw();
    };

    const resize = () => {
      // Long edge of ~320px: the gradient has no detail to lose when stretched.
      const scale = Math.min(1, 320 / Math.max(window.innerWidth, window.innerHeight));
      canvas.width = Math.max(2, Math.round(window.innerWidth * scale));
      canvas.height = Math.max(2, Math.round(window.innerHeight * scale));
      gl.viewport(0, 0, canvas.width, canvas.height);
      gl.uniform2f(u.r, canvas.width, canvas.height);
      draw();
    };

    let progress = 0; let shown = 0; let time = Math.random() * 40;
    function draw() {
      gl.uniform1f(u.t, time);
      gl.uniform1f(u.d, shown);
      gl.drawArrays(gl.TRIANGLES, 0, 3);
    }

    let frameGap = calm ? 1000 / 15 : 1000 / 30;
    let last = 0; let slow = 0; let frozen = reduced || lowEnd;
    function loop(now) {
      if (frozen) return;
      requestAnimationFrame(loop);
      const dt = now - last;
      if (document.hidden || dt < frameGap) return;
      // Over-budget frames mean the main thread or GPU is struggling.
      if (last && dt > frameGap + 34) { slow += 1; } else if (slow > 0) { slow -= 0.25; }
      if (slow > 24) {
        slow = 0;
        root.classList.add('ss-lite');
        if (frameGap < 1000 / 12) { frameGap *= 2; } else { frozen = true; return; }
      }
      last = now;
      time += Math.min(dt, 120) / 1000 * (calm ? 0.55 : 0.8);
      shown += (progress - shown) * 0.08;
      draw();
    }

    host.appendChild(canvas);
    window.addEventListener('resize', resize, { passive: true });
    gl.uniform1f(u.d, 0);
    resize();
    recolor();
    requestAnimationFrame(() => canvas.classList.add('on'));
    requestAnimationFrame(loop);

    return {
      setProgress(v) { progress = v; if (frozen) { shown = v; draw(); } },
      recolor,
      pause(flag) { if (flag) { frozen = true; } else if (!reduced && !lowEnd && frozen) { frozen = false; last = 0; requestAnimationFrame(loop); } },
    };
  }());

  /* ─── Theme ───────────────────────────────────────────────────── */
  function setTheme(name) {
    root.dataset.theme = name;
    try { localStorage.setItem('ss-theme', name); } catch (e) { /* private mode */ }
    aurora.recolor();
    syncSwatches();
  }
  function syncSwatches() {
    document.querySelectorAll('.theme-swatch').forEach((b) => {
      b.setAttribute('aria-pressed', String(b.dataset.set === (root.dataset.theme || 'dawn')));
    });
  }
  document.addEventListener('click', (e) => {
    const b = e.target.closest('.theme-swatch');
    if (b) setTheme(b.dataset.set);
  });
  syncSwatches();

  /* ─── Toast ───────────────────────────────────────────────────── */
  let toastHost;
  function showToast(message, type = 'success') {
    if (!toastHost) {
      toastHost = document.createElement('div');
      toastHost.className = 'toasts';
      toastHost.setAttribute('role', 'status');
      toastHost.setAttribute('aria-live', 'polite');
      document.body.appendChild(toastHost);
    }
    const icons = { success: 'circle-check', error: 'circle-alert', warning: 'triangle-alert', emergency: 'siren', info: 'info' };
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = icon(icons[type] || 'info');
    const text = document.createElement('div');
    text.textContent = message;
    t.appendChild(text);
    toastHost.appendChild(t);
    if (hasGsap && !reduced) gsap.from(t, { y: 16, opacity: 0, duration: 0.4, ease: 'power3.out' });
    setTimeout(() => {
      if (hasGsap && !reduced) gsap.to(t, { opacity: 0, x: 24, duration: 0.25, onComplete: () => t.remove() });
      else t.remove();
    }, type === 'error' ? 6000 : 4200);
  }
  window.showToast = showToast;

  /* ─── Smooth scroll + GSAP wiring ─────────────────────────────── */
  let lenis = null;
  if (hasGsap) {
    gsap.registerPlugin(window.ScrollTrigger);
    gsap.config({ force3D: 'auto' });
    gsap.defaults({ overwrite: 'auto' });
    if (!reduced && typeof window.Lenis !== 'undefined') {
      lenis = new window.Lenis({ duration: 1.05, smoothWheel: true, wheelMultiplier: 1, syncTouch: false });
      lenis.on('scroll', window.ScrollTrigger.update);
      gsap.ticker.add((time) => lenis.raf(time * 1000));
      gsap.ticker.lagSmoothing(500, 33);
    }
  }
  function scrollTo(target) {
    if (lenis) lenis.scrollTo(target, { offset: -90 });
    else if (typeof target === 'string') { const el = document.querySelector(target); if (el) el.scrollIntoView(); }
  }
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a || a.getAttribute('href').length < 2) return;
    if (document.querySelector(a.getAttribute('href'))) { e.preventDefault(); scrollTo(a.getAttribute('href')); }
  });

  /* ─── Text splitting (SplitText port) ─────────────────────────── */
  function splitWords(el) {
    if (el.dataset.splitDone) return el.querySelectorAll('.w > span');
    el.dataset.splitDone = '1';
    el.setAttribute('aria-label', el.textContent.replace(/\s+/g, ' ').trim());
    const walk = (node) => {
      [...node.childNodes].forEach((child) => {
        if (child.nodeType === 3) {
          const frag = document.createDocumentFragment();
          child.textContent.split(/(\s+)/).forEach((part) => {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
            const w = document.createElement('span');
            w.className = 'w';
            w.setAttribute('aria-hidden', 'true');
            w.style.cssText = 'display:inline-block;overflow:clip;overflow-clip-margin:.18em;vertical-align:top;padding-bottom:.08em;margin-bottom:-.08em';
            const inner = document.createElement('span');
            inner.style.display = 'inline-block';
            inner.textContent = part;
            w.appendChild(inner);
            frag.appendChild(w);
          });
          child.replaceWith(frag);
        } else if (child.nodeType === 1 && child.tagName !== 'BR') {
          walk(child);
        }
      });
    };
    walk(el);
    return el.querySelectorAll('.w > span');
  }

  /* ─── Declarative motion ──────────────────────────────────────── */
  function initMotion(scope = document) {
    if (!hasGsap) { scope.querySelectorAll('[data-count]').forEach((n) => { n.textContent = n.dataset.count + (n.dataset.suffix || ''); }); return; }

    // Headlines: words rise out of a clip (first paint, no scroll needed).
    scope.querySelectorAll('[data-split]').forEach((el) => {
      const words = splitWords(el);
      if (reduced) return;
      const onScroll = el.dataset.split === 'scroll';
      gsap.from(words, {
        yPercent: 112, duration: 0.95, ease: 'power4.out', stagger: 0.045,
        delay: onScroll ? 0 : parseFloat(el.dataset.delay || 0.05),
        scrollTrigger: onScroll ? { trigger: el, start: 'top 86%', once: true } : undefined,
      });
    });

    // Cards and blocks: batched so one observer serves every element.
    const items = gsap.utils.toArray(scope.querySelectorAll('[data-reveal]'));
    if (items.length && !reduced) {
      gsap.set(items, { y: 26, opacity: 0 });
      window.ScrollTrigger.batch(items, {
        start: 'top 92%',
        once: true,
        onEnter: (batch) => gsap.to(batch, { y: 0, opacity: 1, duration: 0.8, ease: 'power3.out', stagger: 0.06, clearProps: 'transform,opacity' }),
      });
    }

    // Numbers count up when first seen.
    scope.querySelectorAll('[data-count]').forEach((n) => {
      const end = parseFloat(n.dataset.count) || 0;
      const suffix = n.dataset.suffix || '';
      if (reduced || end === 0) { n.textContent = end + suffix; return; }
      const obj = { v: 0 };
      n.textContent = '0' + suffix;
      gsap.to(obj, {
        v: end, duration: 1.3, ease: 'power2.out',
        scrollTrigger: { trigger: n, start: 'top 95%', once: true },
        onUpdate: () => { n.textContent = Math.round(obj.v) + suffix; },
      });
    });

    // Chart marks grow from their baseline when scrolled into view.
    scope.querySelectorAll('[data-grow]').forEach((box) => {
      if (reduced) return;
      const axis = box.dataset.grow === 'x' ? 'scaleX' : 'scaleY';
      const marks = box.querySelectorAll('.bar, .hbar-fill, .segbar > div');
      gsap.from(marks, {
        [axis]: 0, duration: 0.9, ease: 'power3.out', stagger: 0.05, clearProps: 'transform',
        scrollTrigger: { trigger: box, start: 'top 90%', once: true },
      });
    });

    // Magnet port: pointer-only, driven by quickTo so it never re-creates tweens.
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches && !reduced) {
      scope.querySelectorAll('[data-magnet]').forEach((el) => {
        const x = gsap.quickTo(el, 'x', { duration: 0.5, ease: 'power3' });
        const y = gsap.quickTo(el, 'y', { duration: 0.5, ease: 'power3' });
        el.addEventListener('pointermove', (e) => {
          const r = el.getBoundingClientRect();
          x((e.clientX - r.left - r.width / 2) * 0.22);
          y((e.clientY - r.top - r.height / 2) * 0.32);
        });
        el.addEventListener('pointerleave', () => { x(0); y(0); });
      });
    }
  }

  /* ─── Spotlight: one delegated listener, writes only to the hovered card ─ */
  if (window.matchMedia('(hover: hover)').matches && !reduced) {
    let card = null; let ev = null; let queued = false;
    document.addEventListener('pointermove', (e) => {
      ev = e;
      if (queued) return;
      queued = true;
      requestAnimationFrame(() => {
        queued = false;
        card = ev.target.closest ? ev.target.closest('.spot') : null;
        if (!card) return;
        const r = card.getBoundingClientRect();
        card.style.setProperty('--mx', `${ev.clientX - r.left}px`);
        card.style.setProperty('--my', `${ev.clientY - r.top}px`);
      });
    }, { passive: true });
  }

  /* ─── Dialog ──────────────────────────────────────────────────── */
  let lastFocus = null;
  const dialog = {
    open(id) {
      const el = typeof id === 'string' ? document.getElementById(id) : id;
      if (!el) return;
      lastFocus = document.activeElement;
      el.classList.add('open');
      el.setAttribute('aria-hidden', 'false');
      if (lenis) lenis.stop();
      aurora.pause(true);
      const card = el.querySelector('.dialog-card');
      if (hasGsap && !reduced && card) gsap.fromTo(card, { y: 22, scale: 0.97, opacity: 0 }, { y: 0, scale: 1, opacity: 1, duration: 0.45, ease: 'power3.out', clearProps: 'all' });
      const focusable = el.querySelector('[data-autofocus], button, a[href], input');
      if (focusable) focusable.focus({ preventScroll: true });
    },
    close(id) {
      const el = typeof id === 'string' ? document.getElementById(id) : id;
      if (!el) return;
      el.classList.remove('open');
      el.setAttribute('aria-hidden', 'true');
      if (lenis) lenis.start();
      aurora.pause(false);
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    },
  };
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const open = document.querySelector('.dialog.open');
      if (open) dialog.close(open);
    }
    if (e.key === 'Tab') { // keep focus inside an open dialog
      const open = document.querySelector('.dialog.open');
      if (!open) return;
      const nodes = [...open.querySelectorAll('button, a[href], input, select, textarea')].filter((n) => n.offsetParent !== null);
      if (!nodes.length) return;
      const first = nodes[0]; const last = nodes[nodes.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });
  document.addEventListener('click', (e) => {
    if (e.target.classList && e.target.classList.contains('dialog')) dialog.close(e.target);
    const closer = e.target.closest('[data-dialog-close]');
    if (closer) dialog.close(closer.closest('.dialog'));
  });

  /* ─── SOS ─────────────────────────────────────────────────────────
     Call buttons work immediately and never wait for GPS. Tapping SOS logs
     an alert and prepares share links; it does not auto-message anyone, and
     the copy says so, because a false promise is dangerous here. */
  const SOS = (function () {
    let lat = null; let lng = null; let el = null;

    function build() {
      el = document.createElement('div');
      el.className = 'dialog';
      el.id = 'sosDialog';
      el.setAttribute('role', 'alertdialog');
      el.setAttribute('aria-modal', 'true');
      el.setAttribute('aria-labelledby', 'sosTitle');
      el.innerHTML = `
        <div class="dialog-card glass glass-blur sos-card">
          <button class="btn btn-ghost btn-icon dialog-close" data-dialog-close aria-label="Close SOS">${icon('x')}</button>
          <div class="sos-beacon">${icon('siren')}</div>
          <h2 id="sosTitle" class="display" style="font-size:28px;margin-bottom:6px">Emergency SOS</h2>
          <p class="muted small" id="sosStatus" style="margin-bottom:14px"></p>
          <a href="tel:112" class="btn btn-sos btn-lg btn-block sos-call" data-autofocus>${icon('phone-call')} Call 112 now</a>
          <div id="sosFound" hidden>
            <div class="sos-coords">
              <div><span class="eyebrow">Latitude</span><b id="sosLat"></b></div>
              <div><span class="eyebrow">Longitude</span><b id="sosLng"></b></div>
            </div>
            <div class="sos-actions">
              <button class="btn btn-wa" id="sosWa">${icon('message-circle')} Send location on WhatsApp</button>
              <button class="btn btn-outline" id="sosSms">${icon('message-square')} Send by SMS</button>
              <button class="btn btn-outline" id="sosCopy">${icon('copy')} <span>Copy location</span></button>
              <a class="btn btn-outline" id="sosMap" style="grid-column:1/-1" target="_blank" rel="noopener">${icon('map-pin')} Open in Google Maps</a>
            </div>
          </div>
          <div id="sosFailed" hidden style="margin-top:12px">
            <div class="alert alert-warn" style="text-align:left">${icon('triangle-alert')}<div id="sosFailMsg"></div></div>
            <button class="btn btn-outline btn-block" id="sosRetry" style="margin-top:8px">${icon('rotate-cw')} Try location again</button>
          </div>
          <div class="sos-actions" style="margin-top:10px">
            <a href="tel:1091" class="btn btn-outline">${icon('phone')} Women's helpline 1091</a>
            <a href="tel:100" class="btn btn-outline">${icon('phone')} Police 100</a>
          </div>
        </div>`;
      document.body.appendChild(el);
      el.querySelector('#sosRetry').addEventListener('click', locate);
      el.querySelector('#sosWa').addEventListener('click', () => window.open(`https://wa.me/?text=${encodeURIComponent(message())}`, '_blank', 'noopener'));
      el.querySelector('#sosSms').addEventListener('click', () => { window.location.href = `sms:?body=${encodeURIComponent(message())}`; });
      el.querySelector('#sosCopy').addEventListener('click', async (e) => {
        const label = e.currentTarget.querySelector('span');
        try { await navigator.clipboard.writeText(message()); label.textContent = 'Copied'; } catch (err) { label.textContent = 'Copy failed'; }
        setTimeout(() => { label.textContent = 'Copy location'; }, 2000);
      });
    }

    const mapUrl = () => `https://www.google.com/maps?q=${lat},${lng}`;
    const message = () => `SOS. I need help. My location: ${mapUrl()} (${new Date().toLocaleString('en-IN')}). Sent from SheShield.`;
    const status = (text) => { el.querySelector('#sosStatus').textContent = text; };

    function locate() {
      el.querySelector('#sosFound').hidden = true;
      el.querySelector('#sosFailed').hidden = true;
      status('Getting your location. You can call right away, you do not need to wait.');
      if (!navigator.geolocation) { fail('This browser cannot share location. Call using the buttons here.'); return; }
      navigator.geolocation.getCurrentPosition((pos) => {
        lat = pos.coords.latitude.toFixed(6);
        lng = pos.coords.longitude.toFixed(6);
        el.querySelector('#sosLat').textContent = lat;
        el.querySelector('#sosLng').textContent = lng;
        el.querySelector('#sosMap').href = mapUrl();
        el.querySelector('#sosFound').hidden = false;
        status('Location found. Send it to someone you trust.');
        fetch(`${BASE}emergency/emergency_handler.php`, {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
          body: JSON.stringify({ latitude: lat, longitude: lng }),
        }).then((r) => r.json()).then((d) => {
          if (d.success) status('Location found and alert logged. Send it to someone you trust.');
        }).catch(() => { /* sharing and calling still work */ });
      }, (err) => {
        const why = { 1: 'Location permission is blocked. Allow it in your browser to share your position.', 2: 'Your position is unavailable. Check GPS.', 3: 'Getting your location timed out.' };
        fail(why[err.code] || 'Could not get your location.');
      }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    }
    function fail(msg) {
      status('Calling still works.');
      el.querySelector('#sosFailMsg').textContent = msg;
      el.querySelector('#sosFailed').hidden = false;
    }

    return {
      open() {
        if (!el) build();
        dialog.open(el);
        locate();
      },
    };
  }());
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-sos]')) { e.preventDefault(); SOS.open(); }
  });

  /* ─── Mobile sidebar ──────────────────────────────────────────── */
  document.addEventListener('click', (e) => {
    const side = document.getElementById('dashSidebar');
    if (!side) return;
    if (e.target.closest('[data-side-toggle]')) side.classList.toggle('open');
    else if (side.classList.contains('open') && !e.target.closest('#dashSidebar')) side.classList.remove('open');
  });

  /* ─── Small JSON helper with honest failure handling ──────────── */
  async function post(url, body) {
    const isForm = body instanceof FormData;
    const res = await fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: isForm ? {} : { 'Content-Type': 'application/json' },
      body: isForm ? body : JSON.stringify(body),
    });
    let data = {};
    try { data = await res.json(); } catch (e) { throw new Error('The server sent an unexpected response.'); }
    const ok = res.ok && (data.status === 'success' || data.success === true);
    if (!ok) throw new Error(data.message || data.error || 'Something went wrong. Please try again.');
    return data;
  }

  function busy(btn, on, label) {
    if (on) {
      btn.dataset.html = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<svg class="i spin" aria-hidden="true"><use href="${BASE}public/icons.svg#rotate-cw"/></svg> ${label || 'Working'}`;
    } else {
      btn.disabled = false;
      if (btn.dataset.html) btn.innerHTML = btn.dataset.html;
    }
  }

  window.SS = { aurora, lenis, dialog, SOS, toast: showToast, post, busy, icon, scrollTo, reduced, initMotion, splitWords, BASE };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initMotion());
  else initMotion();
}());
