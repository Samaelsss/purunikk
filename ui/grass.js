(function(){
  function initGrass() {
    var section = document.querySelector('.grass-section');
    var canvas = section && section.querySelector('.grass-canvas');
    if (!section || !canvas || !canvas.getContext) return;
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
      if (!rect) return;
      if (rect.height < 40) {
        section.style.minHeight = '200px';
        rect = section.getBoundingClientRect();
      }
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

  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initGrass);
    } else {
      initGrass();
    }
  }
})();
