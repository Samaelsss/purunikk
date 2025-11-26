// Loading Screen Management shared module
(function () {
  const loadingScreen = document.getElementById('loadingScreen');
  const productPage = document.querySelector('.product-page');
  if (!loadingScreen || !productPage) return;

  let loadingProgress = 0;
  const bar = loadingScreen.querySelector('.loading-progress');

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

      setTimeout(() => {
        loadingScreen.classList.add('hidden');
        productPage.classList.add('loaded');

        setTimeout(() => {
          loadingScreen.style.display = 'none';
        }, 800);
      }, 500);
    }
  }, 200);
})();
