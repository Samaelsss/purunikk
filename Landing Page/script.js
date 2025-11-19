// script.js
(function () {
  // -----------------------------
  // 1. Scroll progress (top + bottom)
  // -----------------------------
  function initScrollProgress() {
    var bars = Array.prototype.slice.call(
      document.querySelectorAll(
        ".scroll-progress__bar, .scroll-progress-container .scroll-progress-bar, .scroll-progress-container-bottom .scroll-progress-bar"
      )
    );
    if (!bars.length) return;

    function update() {
      var doc = document.documentElement;
      var scrollTop = window.pageYOffset || doc.scrollTop || 0;
      var windowHeight = window.innerHeight || doc.clientHeight || 0;
      var scrollHeight = Math.max(
        doc.scrollHeight,
        doc.offsetHeight,
        doc.clientHeight
      );
      var maxScroll = Math.max(scrollHeight - windowHeight, 1);
      var progress = Math.min(Math.max(scrollTop / maxScroll, 0), 1);
      var width = progress * 100 + "%";

      bars.forEach(function (bar) {
        bar.style.width = width;
      });
    }

    update();
    window.addEventListener("scroll", update);
    window.addEventListener("resize", update);
  }

  // -----------------------------
  // 2. Bottom nav (smooth scroll + active)
  // expects buttons with:
  // class="bottom-nav__item" or "bottom-nav-item"
  // and data-section="home|shop|reviews|contact"
  // -----------------------------
  function initBottomNav() {
    var nav =
      document.querySelector(".bottom-nav__inner") ||
      document.querySelector(".navbar-bg");
    if (!nav) return;

    var items = Array.prototype.slice.call(
      nav.querySelectorAll(".bottom-nav__item, .bottom-nav-item")
    );
    if (!items.length) {
      items = Array.prototype.slice.call(nav.querySelectorAll("button"));
    }

    var sections = {};

    items.forEach(function (item) {
      var key = item.getAttribute("data-section");
      if (!key) {
        var span = item.querySelector("span");
        if (span) key = span.textContent.trim().toLowerCase();
      }
      if (!key) return;

      var sectionEl =
        document.getElementById(key) ||
        document.querySelector('[data-section-id="' + key + '"]');
      if (sectionEl) {
        sections[key] = sectionEl;
        item.dataset.sectionKey = key;
      }
    });

    function setActive(key) {
      items.forEach(function (item) {
        var itemKey = item.dataset.sectionKey || item.getAttribute("data-section");
        var isActive = itemKey === key;

        if (item.classList.contains("bottom-nav__item")) {
          item.classList.toggle("bottom-nav__item--active", !!isActive);
        } else if (item.classList.contains("bottom-nav-item")) {
          item.classList.toggle("bottom-nav-item--active", !!isActive);
        } else {
          item.classList.toggle("is-active", !!isActive);
        }
      });
    }

    items.forEach(function (item) {
      item.addEventListener("click", function () {
        var key = item.dataset.sectionKey || item.getAttribute("data-section");
        if (!key) return;

        var target = sections[key];
        if (target) {
          var rect = target.getBoundingClientRect();
          var offset = 80; // adjust if your header height changes
          var top =
            rect.top +
            (window.pageYOffset || document.documentElement.scrollTop) -
            offset;

          window.scrollTo({ top: top, behavior: "smooth" });
        }

        setActive(key);
      });
    });

    function onScroll() {
      var scrollPos =
        window.pageYOffset || document.documentElement.scrollTop || 0;
      var currentKey = null;

      Object.keys(sections).forEach(function (key) {
        var el = sections[key];
        var offsetTop = el.offsetTop;
        if (scrollPos >= offsetTop - 150) {
          currentKey = key;
        }
      });

      if (currentKey) setActive(currentKey);
    }

    window.addEventListener("scroll", onScroll);
    onScroll();
  }

  // -----------------------------
  // 3. Blob (mouse-reactive)
  // expects:
  // <div class="cta-blob">
  //   <div class="blob-bg animate-blob-wobble"></div>
  // </div>
  // inside your CTA section
  // -----------------------------
  function initBlob() {
    var blobWrapper =
      document.querySelector(".cta-blob") ||
      document.querySelector(".blob-wrapper");
    var blobShape =
      (blobWrapper && blobWrapper.querySelector(".blob-bg")) ||
      document.querySelector(".blob-bg");

    var blobEl = blobWrapper || blobShape;
    if (!blobEl) return;

    var maxMove = 30;
    var maxRotate = 6;

    function handleMouseMove(e) {
      var rect = blobEl.getBoundingClientRect();
      var centerX = rect.left + rect.width / 2;
      var centerY = rect.top + rect.height / 2;

      var dx = (e.clientX - centerX) / window.innerWidth;
      var dy = (e.clientY - centerY) / window.innerHeight;

      var x = dx * maxMove;
      var y = dy * maxMove;
      var rotate = dx * maxRotate;

      blobEl.style.transform =
        "translate(" + x + "px, " + y + "px) rotate(" + rotate + "deg)";
    }

    window.addEventListener("mousemove", handleMouseMove);
  }

  // -----------------------------
  // 4. CTA interactions (spotlight + tilt)
  // -----------------------------
  function initCtaInteractive() {
    // spotlight on stat cards
    var cards = Array.prototype.slice.call(document.querySelectorAll('.cta__stat'));
    cards.forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var rect = card.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        card.style.setProperty('--mx', mx + 'px');
        card.style.setProperty('--my', my + 'px');
      });
      card.addEventListener('mouseleave', function(){
        card.style.removeProperty('--mx');
        card.style.removeProperty('--my');
      });
    });

    // spotlight on review cards
    var reviews = Array.prototype.slice.call(document.querySelectorAll('.review-card'));
    reviews.forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var rect = card.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        card.style.setProperty('--mx', mx + 'px');
        card.style.setProperty('--my', my + 'px');
      });
      card.addEventListener('mouseleave', function(){
        card.style.removeProperty('--mx');
        card.style.removeProperty('--my');
      });
    });

    // spotlight on product cards
    var products = Array.prototype.slice.call(document.querySelectorAll('.product-card'));
    products.forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var rect = card.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        card.style.setProperty('--mx', mx + 'px');
        card.style.setProperty('--my', my + 'px');
      });
      card.addEventListener('mouseleave', function(){
        card.style.removeProperty('--mx');
        card.style.removeProperty('--my');
      });
    });

    // spotlight on hero image cards
    var heroCards = Array.prototype.slice.call(document.querySelectorAll('.hero__image-card'));
    heroCards.forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var rect = card.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        card.style.setProperty('--mx', mx + 'px');
        card.style.setProperty('--my', my + 'px');
      });
      card.addEventListener('mouseleave', function(){
        card.style.removeProperty('--mx');
        card.style.removeProperty('--my');
      });
    });

    // spotlight on history image circle
    var historyCircle = document.querySelector('.history__image-circle');
    if (historyCircle) {
      historyCircle.addEventListener('mousemove', function(e){
        var rect = historyCircle.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        historyCircle.style.setProperty('--mx', mx + 'px');
        historyCircle.style.setProperty('--my', my + 'px');
      });
      historyCircle.addEventListener('mouseleave', function(){
        historyCircle.style.removeProperty('--mx');
        historyCircle.style.removeProperty('--my');
      });
    }

    // spotlight on history address box
    var historyAddress = document.querySelector('.history__address');
    if (historyAddress) {
      historyAddress.addEventListener('mousemove', function(e){
        var rect = historyAddress.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        historyAddress.style.setProperty('--mx', mx + 'px');
        historyAddress.style.setProperty('--my', my + 'px');
      });
      historyAddress.addEventListener('mouseleave', function(){
        historyAddress.style.removeProperty('--mx');
        historyAddress.style.removeProperty('--my');
      });
    }

    // spotlight on team cards
    var teamCards = Array.prototype.slice.call(document.querySelectorAll('.team-card'));
    teamCards.forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var rect = card.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        card.style.setProperty('--mx', mx + 'px');
        card.style.setProperty('--my', my + 'px');
      });
      card.addEventListener('mouseleave', function(){
        card.style.removeProperty('--mx');
        card.style.removeProperty('--my');
      });
    });

    // subtle tilt for the CTA group
    var cta = document.querySelector('.cta');
    if (!cta) return;

    var maxTilt = 6;
    cta.addEventListener('mousemove', function(e){
      var rect = cta.getBoundingClientRect();
      var cx = rect.left + rect.width / 2;
      var cy = rect.top + rect.height / 2;
      var dx = (e.clientX - cx) / rect.width;  // -0.5..0.5 normalized
      var dy = (e.clientY - cy) / rect.height; // -0.5..0.5 normalized
      var rx = dy * -maxTilt;
      var ry = dx * maxTilt;
      cta.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
    });
    cta.addEventListener('mouseleave', function(){
      cta.style.transform = 'rotateX(0) rotateY(0)';
    });

    // subtle tilt for the products container
    var productsContainer = document.querySelector('.products');
    if (productsContainer) {
      var maxTiltProducts = 3;
      productsContainer.addEventListener('mousemove', function(e){
        var rect = productsContainer.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        var dx = (e.clientX - cx) / rect.width;
        var dy = (e.clientY - cy) / rect.height;
        var rx = dy * -maxTiltProducts;
        var ry = dx * maxTiltProducts;
        productsContainer.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
      });
      productsContainer.addEventListener('mouseleave', function(){
        productsContainer.style.transform = 'rotateX(0) rotateY(0)';
      });
    }

    // subtle tilt for the hero container
    var heroContainer = document.querySelector('.hero');
    if (heroContainer) {
      var maxTiltHero = 4;
      heroContainer.addEventListener('mousemove', function(e){
        var rect = heroContainer.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        var dx = (e.clientX - cx) / rect.width;
        var dy = (e.clientY - cy) / rect.height;
        var rx = dy * -maxTiltHero;
        var ry = dx * maxTiltHero;
        heroContainer.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
      });
      heroContainer.addEventListener('mouseleave', function(){
        heroContainer.style.transform = 'rotateX(0) rotateY(0)';
      });
    }

    // subtle tilt for the history container
    var historyContainer = document.querySelector('.history');
    if (historyContainer) {
      var maxTiltHistory = 3;
      historyContainer.addEventListener('mousemove', function(e){
        var rect = historyContainer.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        var dx = (e.clientX - cx) / rect.width;
        var dy = (e.clientY - cy) / rect.height;
        var rx = dy * -maxTiltHistory;
        var ry = dx * maxTiltHistory;
        historyContainer.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
      });
      historyContainer.addEventListener('mouseleave', function(){
        historyContainer.style.transform = 'rotateX(0) rotateY(0)';
      });
    }

    // subtle tilt for the team container
    var teamContainer = document.querySelector('.team');
    if (teamContainer) {
      var maxTiltTeam = 3;
      teamContainer.addEventListener('mousemove', function(e){
        var rect = teamContainer.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        var dx = (e.clientX - cx) / rect.width;
        var dy = (e.clientY - cy) / rect.height;
        var rx = dy * -maxTiltTeam;
        var ry = dx * maxTiltTeam;
        teamContainer.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
      });
      teamContainer.addEventListener('mouseleave', function(){
        teamContainer.style.transform = 'rotateX(0) rotateY(0)';
      });
    }
  }

  // -----------------------------
  // 5. Products infinite carousel
  // -----------------------------
  function initProductCarousel() {
    var track = document.querySelector('.products__grid');
    if (!track) return;

    var prefersReduced = false;
    try {
      prefersReduced = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    } catch (e) {}

    var items = Array.prototype.slice.call(track.querySelectorAll('.product-card'));
    if (!items.length) return;

    // Duplicate cards to allow seamless looping
    var cloneCount = items.length;
    for (var i = 0; i < cloneCount; i++) {
      var clone = items[i].cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      track.appendChild(clone);
    }

    var segmentWidth = 0;
    function updateSegmentWidth() {
      segmentWidth = track.scrollWidth / 2;
    }
    updateSegmentWidth();
    window.addEventListener('resize', updateSegmentWidth);

    // Auto-scroll logic
    var speedPerMs = prefersReduced ? 0.02 : 0.08; // px per ms
    var isPointerDown = false;
    var startX = 0;
    var startScrollLeft = 0;
    var manualOverrideUntil = 0;
    var lastTime = null;

    function now() {
      return window.performance && window.performance.now ? window.performance.now() : Date.now();
    }

    function getPageX(e) {
      if (e.touches && e.touches.length) return e.touches[0].pageX;
      if (e.changedTouches && e.changedTouches.length) return e.changedTouches[0].pageX;
      return e.pageX || 0;
    }

    function pointerDown(e) {
      isPointerDown = true;
      track.classList.add('is-dragging');
      startX = getPageX(e);
      startScrollLeft = track.scrollLeft;
      manualOverrideUntil = now() + 3500;
    }

    function pointerMove(e) {
      if (!isPointerDown) return;
      if (e.cancelable && typeof e.preventDefault === 'function') {
        e.preventDefault();
      }
      var x = getPageX(e);
      var walk = (startX - x);
      track.scrollLeft = startScrollLeft + walk;
    }

    function pointerUp() {
      isPointerDown = false;
      track.classList.remove('is-dragging');
    }

    track.addEventListener('mousedown', pointerDown);
    track.addEventListener('touchstart', pointerDown, { passive: true });
    window.addEventListener('mousemove', pointerMove);
    window.addEventListener('touchmove', pointerMove, { passive: false });
    window.addEventListener('mouseup', pointerUp);
    window.addEventListener('touchend', pointerUp);
    track.addEventListener('mouseleave', pointerUp);
    window.addEventListener('blur', pointerUp);

    function loop(timestamp) {
      if (!track) return;
      if (lastTime == null) {
        lastTime = timestamp;
      }
      var delta = timestamp - lastTime;
      lastTime = timestamp;

      if (!track) return;

      var totalWidth = track.scrollWidth;
      var visible = track.clientWidth;
      var maxScroll = totalWidth - visible;
      if (maxScroll <= 0) {
        requestAnimationFrame(loop);
        return;
      }

      var t = now();
      var allowAuto = !isPointerDown && (!manualOverrideUntil || t > manualOverrideUntil);

      if (allowAuto) {
        track.scrollLeft += speedPerMs * delta;
      }

      if (segmentWidth > 0) {
        if (track.scrollLeft >= segmentWidth) {
          track.scrollLeft -= segmentWidth;
        } else if (track.scrollLeft < 0) {
          track.scrollLeft += segmentWidth;
        }
      } else if (track.scrollLeft >= maxScroll - 1) {
        track.scrollLeft = 0;
      }

      requestAnimationFrame(loop);
    }

    requestAnimationFrame(loop);
  }

  function initGrass() {
    var section = document.querySelector('.grass-section');
    var canvas = section && section.querySelector('.grass-canvas');
    if (!section || !canvas) return;
    var ctx = canvas.getContext('2d');
    var dpr = Math.max(1, window.devicePixelRatio || 1);
    var blades = [];
    var palette = { primary:'#B08F70', secondary:'#73512C', accent:'#543310', foreground:'#F9F4E1' };
    var mouse = { x:0, y:0, inside:false };
    var time = 0;
    var running = false;

    function getVar(name, fallback) {
      try {
        var v = getComputedStyle(document.body).getPropertyValue(name).trim();
        return v || fallback;
      } catch (e) { return fallback; }
    }
    function updatePalette() {
      palette.primary = getVar('--primary', palette.primary);
      palette.secondary = getVar('--secondary', palette.secondary);
      palette.accent = getVar('--accent', palette.accent);
      palette.foreground = getVar('--primary-foreground', palette.foreground);
    }
    function hexToRgba(hex, a) {
      if (!hex) return 'rgba(0,0,0,' + (a==null?1:a) + ')';
      hex = hex.trim();
      if (hex.startsWith('rgb')) {
        return hex.replace('rgb(', 'rgba(').replace(')', ',' + (a==null?1:a) + ')');
      }
      if (hex[0] === '#') {
        var c = hex.slice(1);
        if (c.length === 3) {
          var r = parseInt(c[0] + c[0], 16),
              g = parseInt(c[1] + c[1], 16),
              b = parseInt(c[2] + c[2], 16);
          return 'rgba(' + r + ',' + g + ',' + b + ',' + (a==null?1:a) + ')';
        } else if (c.length >= 6) {
          var r6 = parseInt(c.substr(0,2), 16),
              g6 = parseInt(c.substr(2,2), 16),
              b6 = parseInt(c.substr(4,2), 16);
          return 'rgba(' + r6 + ',' + g6 + ',' + b6 + ',' + (a==null?1:a) + ')';
        }
      }
      return hex;
    }

    function rand(a, b){ return a + Math.random()*(b-a); }

    function buildBlades(w, h) {
      blades.length = 0;
      var count = Math.max(80, Math.floor(w / 10));
      for (var i=0;i<count;i++){
        var x = (i + Math.random()*0.35) * (w / count);
        blades.push({
          x: x,
          len: rand(h * 0.38, h * 0.78),
          sway: 0,
          target: 0,
          amp: rand(12, 30),
          thick: rand(1.1, 2.4),
          curve: rand(0.15, 0.45),
          phase: Math.random() * Math.PI * 2
        });
      }
    }

    function sizeCanvas(){
      var rect = section.getBoundingClientRect();
      var w = Math.max(1, Math.floor(rect.width));
      var h = Math.max(1, Math.floor(rect.height));
      canvas.width = Math.floor(w * dpr);
      canvas.height = Math.floor(h * dpr);
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      buildBlades(w, h);
      updatePalette();
    }

    function drawBlade(b, alpha, scale){
      var w = canvas.clientWidth || canvas.width / dpr;
      var h = canvas.clientHeight || canvas.height / dpr;
      var baseX = b.x;
      var baseY = h;
      var tipX = baseX + b.sway;
      var tipY = baseY - b.len;
      var ctrl2x = baseX + b.sway * 0.66 + b.curve * 20;
      var ctrl2y = baseY - b.len * 0.66;

      ctx.globalAlpha = alpha;
      ctx.lineWidth = Math.max(0.7, b.thick * scale);
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';

      var g = ctx.createLinearGradient(baseX, baseY, tipX, tipY);
      g.addColorStop(0, hexToRgba(palette.accent, 0.95));
      g.addColorStop(0.55, hexToRgba(palette.secondary, 0.9));
      g.addColorStop(1, hexToRgba(palette.primary, 0.95));
      ctx.strokeStyle = g;

      ctx.beginPath();
      ctx.moveTo(baseX, baseY);
      ctx.bezierCurveTo(baseX, baseY - b.len * 0.2, ctrl2x, ctrl2y, tipX, tipY);
      ctx.stroke();

      ctx.globalAlpha = alpha * 0.9;
      ctx.fillStyle = hexToRgba(palette.foreground, 0.25);
      ctx.beginPath();
      ctx.arc(tipX, tipY, Math.max(0.5, b.thick * 0.35 * scale), 0, Math.PI * 2);
      ctx.fill();
      ctx.globalAlpha = 1;
    }

    function render(ts){
      time = (ts||0) * 0.001;
      var w = canvas.clientWidth || canvas.width / dpr;
      var h = canvas.clientHeight || canvas.height / dpr;
      ctx.clearRect(0, 0, w, h);

      var wind = Math.sin(time * 0.5) * 0.2 + Math.sin(time * 0.9 + 1.23) * 0.1;
      var px = mouse.inside ? mouse.x * w : 0;
      var py = mouse.inside ? mouse.y * h : 0;

      for (var i=0;i<blades.length;i++){
        var b = blades[i];
        var local = Math.sin(time * 0.8 + b.phase) * 0.2;
        var ddx = mouse.inside ? (px - b.x) : 0;
        var proximity = mouse.inside ? Math.max(0, 1 - Math.min(1, Math.abs(ddx) / (w * 0.25))) : 0;
        var toward = mouse.inside ? (ddx / Math.max(1, w)) * 200 * (1 - (py / Math.max(1, h)) * 0.6) * proximity : 0;
        var boost = mouse.inside ? 1.42 : 1.12;
        b.target = (wind * b.amp * 1.0 + local * b.amp * 0.8 + toward) * boost;
        b.sway += (b.target - b.sway) * 0.1;
      }

      for (var layer=0; layer<2; layer++){
        var a = layer === 0 ? 0.55 : 0.9;
        var sc = layer === 0 ? 0.85 : 1.2;
        for (var j=0;j<blades.length;j++) drawBlade(blades[j], a, sc);
      }

      if (running) requestAnimationFrame(render);
    }

    function start(){ if (!running){ running = true; requestAnimationFrame(render); } }
    function stop(){ running = false; }

    section.addEventListener('mousemove', function(e){
      var r = section.getBoundingClientRect();
      mouse.x = (e.clientX - r.left) / Math.max(1, r.width);
      mouse.y = (e.clientY - r.top) / Math.max(1, r.height);
      mouse.inside = mouse.x >= 0 && mouse.x <= 1 && mouse.y >= 0 && mouse.y <= 1;
      start();
    });
    section.addEventListener('mouseenter', function(){ mouse.inside = true; start(); });
    section.addEventListener('mouseleave', function(){ mouse.inside = false; });
    var ro;
    if (window.ResizeObserver) {
      ro = new ResizeObserver(function(){ sizeCanvas(); start(); });
      ro.observe(section);
    } else {
      window.addEventListener('resize', function(){ sizeCanvas(); start(); });
    }

    var mo = new MutationObserver(function(m){ for (var i=0;i<m.length;i++){ if (m[i].attributeName === 'class'){ updatePalette(); } } });
    mo.observe(document.body, { attributes: true });

    sizeCanvas();
    start();
  }

  // -----------------------------
  // Init
  // -----------------------------
  document.addEventListener("DOMContentLoaded", function () {
    // Smooth scroll helper for any [data-scroll] triggers
    function initSmoothScrollers() {
      var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-scroll]'));
      triggers.forEach(function(el){
        el.addEventListener('click', function(){
          var sel = el.getAttribute('data-scroll');
          if (!sel) return;
          var target = document.querySelector(sel);
          if (!target) return;
          var rect = target.getBoundingClientRect();
          var offset = Math.max(80, parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-safe-area')) || 80);
          var top = rect.top + (window.pageYOffset || document.documentElement.scrollTop) - offset;
          window.scrollTo({ top: top, behavior: 'smooth' });
        });
      });
    }

    // Rotating subtitle in hero
    function initHeroRotatingSubtitle() {
      var el = document.querySelector('.hero__rotating');
      if (!el) return;
      var phrases = [
        'Brand anyaman Purun dari Tabalong, Kalimantan Selatan',
        'Didirikan tahun 2017 oleh Sri Munalisa & Mujilah Rahma',
        'Kerajinan Purun, Rotan, dan Eceng Gondok berkualitas tinggi',
        'Memberdayakan pengrajin lokal melalui pasar online & offline'
      ];
      var i = 0;
      var reduce = false;
      try {
        reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
      } catch (e) {}
      function tick(){
        var next = phrases[i % phrases.length];
        if (reduce) {
          el.textContent = next;
          i++;
          return;
        }
        el.classList.remove('hero__rotating--enter');
        el.classList.add('hero__rotating--exit');
        var onOutEnd = function(){
          el.removeEventListener('animationend', onOutEnd);
          el.textContent = next;
          void el.offsetWidth;
          el.classList.remove('hero__rotating--exit');
          el.classList.add('hero__rotating--enter');
          var onInEnd = function(){
            el.removeEventListener('animationend', onInEnd);
          };
          el.addEventListener('animationend', onInEnd, { once: true });
          i++;
        };
        el.addEventListener('animationend', onOutEnd, { once: true });
      }
      tick();
      setInterval(tick, 2200);
    }

    function initThemeToggle() {
      const btns = [
        document.getElementById('themeToggleMobile'),
        document.getElementById('themeToggleTablet'),
        document.getElementById('themeToggleDesktop'),
      ].filter(Boolean);

      const saved = localStorage.getItem('pk-theme');
      if (saved === 'light') {
        document.body.classList.add('light');
        document.body.classList.remove('dark');
      } else if (saved === 'dark') {
        document.body.classList.add('dark');
        document.body.classList.remove('light');
      }

      function setTheme(mode) {
        if (mode === 'light') {
          document.body.classList.add('light');
          document.body.classList.remove('dark');
          localStorage.setItem('pk-theme', 'light');
        } else {
          document.body.classList.remove('light');
          document.body.classList.add('dark');
          localStorage.setItem('pk-theme', 'dark');
        }
      }

      function currentMode() {
        return document.body.classList.contains('light') ? 'light' : 'dark';
      }

      btns.forEach((btn) => {
        btn.addEventListener('click', () => {
          const next = currentMode() === 'light' ? 'dark' : 'light';
          setTheme(next);
        });
      });

      // Default: start in dark if nothing saved
      if (!saved) {
        setTheme('dark');
      }
    }

    initScrollProgress();
    initBottomNav();
    initBlob();
    initProductCarousel();
    initCtaInteractive();
    initSmoothScrollers();
    initHeroRotatingSubtitle();
    initThemeToggle();
    initGrass();

    if (window.lucide && window.lucide.createIcons) {
      window.lucide.createIcons();
    }
  });
})();
