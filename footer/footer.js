// Scoped interactions for the footer component
(function(){
  const scope = document.currentScript && document.currentScript.closest('.footer-scope') || document.querySelector('.footer-scope');
  const root = scope || document;

  // 1) Reveal on scroll
  const revealEls = [].slice.call(root.querySelectorAll('.reveal-up'));
  if ('IntersectionObserver' in window && revealEls.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    revealEls.forEach(el => io.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('in-view'));
  }

  // 2) Newsletter shine follows cursor
  const newsletter = root.querySelector('.footer-newsletter');
  const shine = newsletter && newsletter.querySelector('.footer-newsletter__shine');
  if (newsletter && shine) {
    function updateShine(e) {
      const rect = newsletter.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      shine.style.setProperty('--x', x + '%');
      shine.style.setProperty('--y', y + '%');
    }
    newsletter.addEventListener('mousemove', updateShine);
    newsletter.addEventListener('touchmove', function(evt){
      if (!evt.touches || !evt.touches[0]) return;
      const t = evt.touches[0];
      updateShine({ clientX: t.clientX, clientY: t.clientY });
    }, { passive: true });
  }

  // 3) Gentle wave float animation via transform
  const wave = root.querySelector('.footer__wave');
  if (wave) {
    let t = 0;
    function tick(){
      t += 0.015;
      const y = Math.sin(t) * 4; // -4..4 px
      wave.style.transform = `translateY(${y}px)`;
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
})();
