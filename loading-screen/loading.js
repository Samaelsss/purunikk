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

  const finishLoading = () => {
    loadingScreen.classList.add('hidden');
    if (loadingTarget) {
      loadingTarget.classList.add('loaded');
    }
    setTimeout(() => {
      loadingScreen.style.display = 'none';
    }, 50);
  };

  const loadingInterval = setInterval(() => {
    loadingProgress += Math.random() * 15;
    if (loadingProgress >= 100) {
      loadingProgress = 100;
    }
    if (bar) {
      bar.style.width = loadingProgress + '%';
    }

    if (loadingProgress >= 100) {
      clearInterval(loadingInterval);
      setTimeout(finishLoading, 100);
    }
  }, 100);
})();
