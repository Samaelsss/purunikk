(function () {
  const loadingScreen = document.getElementById('loadingScreen');
  if (!loadingScreen) return;

  // Prefer an explicit loading target, fall back to product-page or main/body
  const loadingTarget =
    document.querySelector('[data-loading-target]') ||
    document.querySelector('.product-page') ||
    document.querySelector('main') ||
    document.body;

  let loadingProgress = 0;
  const bar = loadingScreen.querySelector('.loading-progress');
  let loadingIntervalId;
  let hasFinished = false;

  const finishLoading = () => {
    if (hasFinished) return;
    hasFinished = true;
    
    if (loadingIntervalId) clearInterval(loadingIntervalId);
    loadingProgress = 100;
    if (bar) {
      bar.style.width = '100%';
    }
    loadingScreen.classList.add('hidden');
    if (loadingTarget) {
      loadingTarget.classList.add('loaded');
    }
    setTimeout(() => {
      loadingScreen.style.display = 'none';
    }, 300);
  };

  const startProgressAnimation = () => {
    loadingIntervalId = setInterval(() => {
      loadingProgress += Math.random() * 15;
      if (loadingProgress >= 95) {
        loadingProgress = 95;
      }
      if (bar) {
        bar.style.width = loadingProgress + '%';
      }
    }, 100);
  };

  startProgressAnimation();

  // Listen for custom productDataLoaded event (from Produk_detail.js)
  document.addEventListener('productDataLoaded', finishLoading);

  // Fallback: ensure loading completes after 3 seconds max
  setTimeout(finishLoading, 3000);
})();
