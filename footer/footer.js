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

  // 4) Mobile brand layout (logo + text side-by-side below chips)
  const mobileBrand = root.querySelector('.footer__brand');
  const mobileLogoNode = root.querySelector('.footer__logo');
  const mobileTitle = mobileBrand && mobileBrand.querySelector('.footer__title');
  const mobileTagline = mobileBrand && mobileBrand.querySelector('.footer__tagline');
  const mobileSocial = mobileBrand && mobileBrand.querySelector('.footer__social');
  const mobileCta = root.querySelector('.footer__cta');
  const mobileChips = root.querySelector('.footer__chips');
  const footerInner = root.querySelector('.footer__inner');

  function applyMobileBrandLayout(){
    if (!mobileBrand || !mobileLogoNode || !mobileTitle || !mobileTagline || !mobileSocial || !mobileCta || !mobileChips || !footerInner) return;
    if (mobileBrand.dataset.mobileBrandApplied === '1') return;

    // Ensure logo lives inside the brand block
    if (!mobileBrand.contains(mobileLogoNode)) {
      mobileBrand.insertBefore(mobileLogoNode, mobileBrand.firstChild);
    }

    // Move brand directly below chips
    mobileChips.insertAdjacentElement('afterend', mobileBrand);

    // Wrap text content into its own column (if not already wrapped)
    if (!mobileBrand.querySelector('.footer__brand-text')) {
      const textWrapper = document.createElement('div');
      textWrapper.className = 'footer__brand-text';

      mobileBrand.insertBefore(textWrapper, mobileTitle);
      textWrapper.appendChild(mobileTitle);
      textWrapper.appendChild(mobileTagline);
      textWrapper.appendChild(mobileSocial);
    }

    mobileBrand.classList.add('footer__brand--row');
    mobileBrand.dataset.mobileBrandApplied = '1';
  }

  function revertMobileBrandLayout(){
    if (!mobileBrand || !mobileLogoNode || !footerInner) return;
    if (mobileBrand.dataset.mobileBrandApplied !== '1') return;

    // Remove helper layout class
    mobileBrand.classList.remove('footer__brand--row');

    // Unwrap text wrapper if present
    const textWrapper = mobileBrand.querySelector('.footer__brand-text');
    if (textWrapper) {
      while (textWrapper.firstChild) {
        mobileBrand.insertBefore(textWrapper.firstChild, textWrapper);
      }
      textWrapper.parentNode.removeChild(textWrapper);
    }

    // Move logo and brand back to the top of footer__inner (logo then brand)
    footerInner.insertBefore(mobileLogoNode, footerInner.firstChild || null);
    footerInner.insertBefore(mobileBrand, mobileLogoNode.nextSibling);

    mobileBrand.dataset.mobileBrandApplied = '0';
  }

  const mq650 = window.matchMedia && window.matchMedia('(max-width: 650px)');

  if (mq650) {
    const syncBrandLayout = function(e){
      if (e.matches) {
        applyMobileBrandLayout();
      } else {
        revertMobileBrandLayout();
      }
    };

    syncBrandLayout(mq650);

    if (typeof mq650.addEventListener === 'function') {
      mq650.addEventListener('change', syncBrandLayout);
    } else if (typeof mq650.addListener === 'function') {
      mq650.addListener(syncBrandLayout);
    }
  } else if (window.innerWidth <= 650) {
    applyMobileBrandLayout();
  }
})();
