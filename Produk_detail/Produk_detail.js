/* ===== BUNDLE: product-detail.js + Produk_detail.js =====
   Catatan: Urutan dijaga. Bagian product-detail.js dulu (render data),
   lalu UI interaksi dari Produk_detail.js. */

// ==== BEGIN product-detail.js ====
function getParam(name) {
  const url = new URL(window.location.href);
  return url.searchParams.get(name);
}

function formatRupiah(num) {
  if (typeof num === 'string') {
    return num.startsWith('Rp') ? num : 'Rp ' + num;
  }
  return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function parsePriceToNumber(price) {
  if (typeof price === 'number') return price;
  const digits = String(price).replace(/[^0-9]/g, '');
  return Number(digits || 0);
}

function resolveProductImagePath(path) {
  if (!path) return '';
  let p = String(path).replace(/\\/g, '/');
  if (/^(https?:)?\/\//.test(p) || p.startsWith('data:')) return p;
  while (p.startsWith('./')) {
    p = p.slice(2);
  }
  if (p.startsWith('../uploads/')) {
    p = p.replace(/^\.\.\//g, '');
  }
  if (p.startsWith('/uploads/')) {
    p = p.replace(/^\//, '');
  }
  if (p.startsWith('uploads/')) {
    return p;
  }
  if (p.startsWith('Product/')) {
    return p;
  }
  if (p.startsWith('img/')) {
    return 'Product/' + p;
  }
  return p;
}

const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]').map(String);
let cart = JSON.parse(localStorage.getItem('cart') || '[]');

function shuffleArray(a) {
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

function resolveDetailUrl(id) {
  const currentPath = window.location.pathname;
  const purunikkIndex = currentPath.toLowerCase().indexOf('/purunikk/');

  if (purunikkIndex !== -1) {
    // Extract base path up to /purunikk/
    const basePath = currentPath.substring(0, purunikkIndex + '/purunikk/'.length);
    return `${basePath}Produk_detail.html?id=${encodeURIComponent(id)}`;
  } else {
    // Fallback: try relative path
    const path = window.location.pathname.toLowerCase();
    if (path.includes('/product/')) {
      return `../Produk_detail.html?id=${encodeURIComponent(id)}`;
    }
    return `Produk_detail.html?id=${encodeURIComponent(id)}`;
  }
}

async function renderProduct() {
  const id = getParam('id');
  const root = document.querySelector('.product-section')
    || document.getElementById('product-root')
    || document.querySelector('.product-detail')
    || document.body;

  if (!id) { if (root) root.style.display = 'none'; return; }

  const hideSelectors = ['.products-container', '.pagination-container', '.top-row'];
  hideSelectors.forEach(sel => {
    document.querySelectorAll(sel).forEach(el => el && (el.style.display = 'none'));
  });

  // Theme toggle for light/dark modes with persistence
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

    if (!saved) { setTheme('dark'); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeToggle);
  } else {
    initThemeToggle();
  }

  if (root) root.style.display = '';


  const dataset = (typeof PRODUCTS_DATA !== 'undefined' && Array.isArray(PRODUCTS_DATA)) ? PRODUCTS_DATA : [];
  let prod = dataset.find(p => String(p.id) === String(id));
  // Fallback: jika id numerik dan tidak cocok dengan field id, gunakan index (1-based)
  if (!prod && /^\d+$/.test(String(id))) {
    const idx = Math.max(0, parseInt(id, 10) - 1);
    if (idx >= 0 && idx < dataset.length) prod = dataset[idx];
  }
  if (!prod) {
    try {
      const stored = localStorage.getItem('pk-last-product');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && String(parsed.id) === String(id)) {
          prod = parsed;
        }
      }
    } catch (e) { }
  }

  // If still not found, try to fetch from database API
  if (!prod) {
    try {
      const res = await fetch('http://localhost/purunikk/admin/products_api.php');
      if (res.ok) {
        const data = await res.json();
        if (Array.isArray(data)) {
          // Find product by ID
          const dbProduct = data.find(p => String(p.id) === String(id));
          if (dbProduct) {
            // Transform database product to match expected format
            const priceNum = typeof dbProduct.price === 'number' ? dbProduct.price : parseFloat(dbProduct.price) || 0;
            prod = {
              id: dbProduct.id,
              name: dbProduct.name || '',
              subtitle: dbProduct.category || 'Koleksi Unggulan',
              category: dbProduct.category || 'Produk',
              description: dbProduct.description || '',
              price: priceNum,
              icon: getCategoryIcon(dbProduct.category),
              img: dbProduct.image_path || '',
              image: dbProduct.image_path || '',
              image_path: dbProduct.image_path || ''
            };

            // Helper function to get category icon (if not defined elsewhere)
            function getCategoryIcon(category) {
              const c = String(category || '').toLowerCase();
              if (c.includes('tas')) return '👜';
              if (c.includes('dompet')) return '👛';
              if (c.includes('keranjang') || c.includes('basket')) return '🧺';
              if (c.includes('furniture') || c.includes('kursi') || c.includes('meja')) return '🪑';
              return '🧺';
            }
          }
        }
      }
    } catch (e) {
      console.error('Failed to fetch product from API:', e);
    }
  }

  if (!prod) {
    const backUrl = (window.location.pathname.includes('/Product/')) ? 'Product/product.html' : 'product.html';
    document.body.innerHTML = `<div class="container"><p>Produk tidak ditemukan. <a href="${backUrl}">Kembali ke katalog</a></p></div>`;
    return;
  }

  // Ambil semua gambar warna untuk produk ini dari database
  let colorImages = [];
  try {
    if (prod.id) {
      const res = await fetch('http://localhost/purunikk/admin/product_images_api.php?product_id=' + encodeURIComponent(prod.id));
      if (res.ok) {
        const data = await res.json();
        if (Array.isArray(data)) {
          colorImages = data;
        }
      }
    }
  } catch (e) {
    console.error('Gagal memuat gambar warna produk:', e);
  }

  // Ambil semua model untuk produk ini dari database
  let modelsFromDb = [];
  try {
    if (prod.id) {
      const resModels = await fetch('http://localhost/purunikk/admin/product_models_api.php?product_id=' + encodeURIComponent(prod.id));
      if (resModels.ok) {
        const dataModels = await resModels.json();
        if (Array.isArray(dataModels)) {
          modelsFromDb = dataModels;
        }
      }
    }
  } catch (e) {
    console.error('Gagal memuat model produk:', e);
  }

  // Ambil semua varian generik (kategori + opsi) untuk produk ini
  let variantOptions = [];
  try {
    if (prod.id) {
      const resVariants = await fetch('http://localhost/purunikk/admin/product_variant_options_api.php?product_id=' + encodeURIComponent(prod.id));
      if (resVariants.ok) {
        const dataVariants = await resVariants.json();
        if (Array.isArray(dataVariants)) {
          variantOptions = dataVariants;
        }
      }
    }
  } catch (e) {
    console.error('Gagal memuat varian produk:', e);
  }

  const imgEl = document.getElementById('product-img') || document.getElementById('main-img');
  const nameEl = document.getElementById('product-name');
  const priceEl = document.getElementById('product-price');
  const descEl = document.getElementById('product-desc');
  const breadcrumbEl = document.getElementById('breadcrumb-product');
  const mainImgSrc = resolveProductImagePath(prod.img || prod.image || prod.image_path || prod.thumb || '');
  if (imgEl) { imgEl.src = mainImgSrc; imgEl.alt = prod.name || ''; }
  if (nameEl) { nameEl.textContent = prod.name || ''; }
  if (breadcrumbEl) { breadcrumbEl.textContent = prod.name || ''; }
  if (priceEl) { priceEl.textContent = formatRupiah(prod.price); }
  if (descEl) { descEl.textContent = prod.description || ''; }

  // Build thumbnail list: main image first, then color images or other variants
  let rawThumbs = [];
  const mainImage = prod.image_path || prod.img || prod.image || prod.thumb;
  
  if (mainImage) {
    rawThumbs.push(mainImage);
  }
  
  if (Array.isArray(colorImages) && colorImages.length) {
    colorImages.forEach(ci => {
      const imgPath = ci.image_path;
      if (imgPath && imgPath !== mainImage) {
        rawThumbs.push(imgPath);
      }
    });
  } else if (Array.isArray(prod.images) && prod.images.length) {
    prod.images.forEach(img => {
      if (img && img !== mainImage) {
        rawThumbs.push(img);
      }
    });
  }
  
  const thumbs = rawThumbs.map(resolveProductImagePath);
  const thumbList = document.getElementById('thumbnail-list');
  if (thumbList && thumbs.length) {
    thumbList.innerHTML = '';
    thumbs.forEach((src, idx) => {
      const t = document.createElement('img');
      t.src = src;
      if (idx === 0) t.classList.add('active');
      t.addEventListener('click', () => {
        document.querySelectorAll('#thumbnail-list img').forEach(i => i.classList.remove('active'));
        t.classList.add('active');
        if (imgEl) imgEl.src = src;
      });
      thumbList.appendChild(t);
    });
  }

  const descContent = document.getElementById('desc-content');
  if (descContent) { descContent.textContent = prod.description || ''; }

  // Tambahan: sold & rating
  const soldEl = document.getElementById('Jumlah_terjual');
  if (soldEl && prod.sold != null) soldEl.textContent = String(prod.sold);
  const ratingWrap = document.querySelector('.Bintang');
  if (ratingWrap && typeof prod.rating === 'number') {
    ratingWrap.innerHTML = '';
    for (let i = 1; i <= 5; i++) {
      const star = document.createElement('i');
      star.className = i <= prod.rating ? 'fa-solid fa-star' : 'fa-regular fa-star';
      ratingWrap.appendChild(star);
    }
  }

  // ===== Render varian (semua kategori) dari database =====
  const variantContainer = document.getElementById('variant-categories-container');

  function groupVariantOptions(options) {
    const groups = {};
    (options || []).forEach((opt) => {
      const key = (opt.category_name || '').trim();
      if (!key) return;
      if (!groups[key]) groups[key] = [];
      groups[key].push(opt);
    });
    return groups;
  }

  function renderVariantCategory(categoryName, list) {
    if (!list || !list.length) return null;

    const categoryDiv = document.createElement('div');
    categoryDiv.className = 'model_produk';
    categoryDiv.dataset.categoryName = categoryName;

    const titleEl = document.createElement('h4');
    titleEl.textContent = categoryName;
    categoryDiv.appendChild(titleEl);

    const isMotifsCategory = /(motif|warna|color)/i.test(categoryName);
    const isModelCategory = /model/i.test(categoryName);

    if (isModelCategory) {
      categoryDiv.classList.add('model-category');
    }

    list.forEach((opt, idx) => {
      const div = document.createElement('div');
      if (idx === 0) div.classList.add('active');

      const optionPrice = opt.option_price && parseFloat(opt.option_price) > 0 ? parseFloat(opt.option_price) : null;
      const imageSrc = opt.image_path ? resolveProductImagePath(opt.image_path) : '';

      if (!isMotifsCategory) {
        div.classList.add('Model_1');
      }

      if (imageSrc) {
        div.dataset.imgSrc = imageSrc;
      }

      const h4 = document.createElement('h4');
      h4.textContent = opt.option_name || '';
      div.appendChild(h4);

      if (optionPrice) {
        div.dataset.optionPrice = optionPrice;
      }

      categoryDiv.appendChild(div);
    });

    return categoryDiv;
  }

  if (variantContainer && Array.isArray(variantOptions) && variantOptions.length) {
    variantContainer.innerHTML = '';
    const variantGroups = groupVariantOptions(variantOptions);

    Object.entries(variantGroups).forEach(([categoryName, items]) => {
      const categorySection = renderVariantCategory(categoryName, items);
      if (categorySection) {
        variantContainer.appendChild(categorySection);
      }
    });
  } else if (variantContainer) {
    variantContainer.innerHTML = '';
    if (Array.isArray(prod.motifs) && prod.motifs.length) {
      const motifsDiv = renderVariantCategory('Pilih motif', prod.motifs.map(m => ({
        option_name: m.name,
        image_path: m.img,
      })));
      if (motifsDiv) variantContainer.appendChild(motifsDiv);
    }
    if (Array.isArray(modelsFromDb) && modelsFromDb.length) {
      const modelsDiv = renderVariantCategory('Pilih Model', modelsFromDb.map(m => ({
        option_name: m.model_name,
        image_path: m.image_path,
      })));
      if (modelsDiv) variantContainer.appendChild(modelsDiv);
    } else if (Array.isArray(prod.models) && prod.models.length) {
      const modelsDiv = renderVariantCategory('Pilih Model', prod.models.map(name => ({
        option_name: name,
      })));
      if (modelsDiv) variantContainer.appendChild(modelsDiv);
    }
  }

  // Kumpulkan semua gambar varian dan tambahkan ke thumbnail-list
  function populateThumbnailsFromVariants() {
    if (!thumbList) return;

    const variantImages = new Set();
    const variantDivs = document.querySelectorAll('#variant-categories-container .model_produk > div:not(h4)');
    
    variantDivs.forEach((div) => {
      const imgSrc = div.dataset.imgSrc;
      if (imgSrc) {
        variantImages.add(imgSrc);
      }
    });

    const combinedThumbs = [...thumbs];
    variantImages.forEach((src) => {
      if (!combinedThumbs.includes(src)) {
        combinedThumbs.push(src);
      }
    });

    if (thumbList && combinedThumbs.length) {
      thumbList.innerHTML = '';
      combinedThumbs.forEach((src, idx) => {
        const t = document.createElement('img');
        t.src = src;
        if (idx === 0) t.classList.add('active');
        t.addEventListener('click', () => {
          document.querySelectorAll('#thumbnail-list img').forEach(i => i.classList.remove('active'));
          t.classList.add('active');
          if (imgEl) imgEl.src = src;
        });
        thumbList.appendChild(t);
      });
    }
  }

  populateThumbnailsFromVariants();

  // Helper: hitung harga berdasarkan pilihan varian (support semua kategori + harga varian)
  function getSelectedVariant() {
    let basePrice = parsePriceToNumber(prod.price);
    let selectedVariants = {};
    let variantPrices = [];

    const variantDivs = document.querySelectorAll('#variant-categories-container .model_produk');
    variantDivs.forEach((categoryDiv) => {
      const categoryName = categoryDiv.dataset.categoryName || '';
      const activeDiv = categoryDiv.querySelector('div.active');
      const activeText = activeDiv ? activeDiv.querySelector('h4')?.textContent?.trim() : '';

      if (activeText) {
        selectedVariants[categoryName] = activeText;

        const optionPrice = activeDiv?.dataset?.optionPrice;
        if (optionPrice && parseFloat(optionPrice) > 0) {
          variantPrices.push(parseFloat(optionPrice));
        }
      }
    });

    let motif = selectedVariants[Object.keys(selectedVariants).find(k => /(motif|warna|color)/i.test(k))] || (prod.motifs?.[0]?.name || '');
    let model = selectedVariants[Object.keys(selectedVariants).find(k => /model/i.test(k))] || '';

    if (!model) {
      if (Array.isArray(prod.models) && prod.models.length) {
        model = prod.models[0];
      } else if (Array.isArray(modelsFromDb) && modelsFromDb.length) {
        model = modelsFromDb[0].model_name || '';
      }
    }

    let price = basePrice;

    if (variantPrices.length > 0) {
      price = variantPrices[0];
    } else if (prod.pricing && prod.pricing[model] && prod.pricing[model][motif] != null) {
      price = parsePriceToNumber(prod.pricing[model][motif]);
    }

    return { motif, model, price, selectedVariants };
  }

  function updateDisplayedPrice() {
    const { price } = getSelectedVariant();
    if (priceEl) priceEl.textContent = formatRupiah(price);
  }

  function wireVariantClickHandlers() {
    document.querySelectorAll('#variant-categories-container .model_produk > div:not(h4)').forEach((div) => {
      div.addEventListener('click', () => {
        const categoryDiv = div.parentElement;
        const categoryName = categoryDiv.dataset.categoryName || '';
        const isModelCategory = /model/i.test(categoryName);

        categoryDiv.querySelectorAll('div:not(h4)').forEach(b => b.classList.remove('active'));
        div.classList.add('active');

        if (imgEl && div.dataset.imgSrc) {
          imgEl.src = div.dataset.imgSrc;
        }

        updateDisplayedPrice();

        if (isModelCategory) {
          const thumbImgs = document.querySelectorAll('#thumbnail-list img');
          const targetSrc = div.dataset.imgSrc;
          if (imgEl && targetSrc) {
            imgEl.src = targetSrc;
          }
          if (thumbImgs.length) {
            const divIndex = Array.from(categoryDiv.querySelectorAll('div:not(h4)')).indexOf(div);
            if (thumbImgs[divIndex]) {
              thumbImgs.forEach(i => i.classList.remove('active'));
              thumbImgs[divIndex].classList.add('active');
            }
          }
        }
      });
    });
  }

  wireVariantClickHandlers();
  // Inisialisasi harga varian awal
  updateDisplayedPrice();
  const wbtn = document.getElementById('add-wishlist-btn') || document.getElementById('favorite');
  if (wbtn) {
    const setWishText = (active) => {
      if (wbtn.id === 'favorite') {
        wbtn.innerHTML = active ? '<i class="fa-solid fa-heart"></i>' : '<i class="fa-regular fa-heart"></i>';
      } else {
        wbtn.textContent = active ? '❤ Tersimpan' : '❤ Wishlist';
      }
    };
    const saved = JSON.parse(localStorage.getItem('wishlist') || '[]').map(String);
    setWishText(saved.includes(String(prod.id)));
    wbtn.addEventListener('click', () => {
      let w = JSON.parse(localStorage.getItem('wishlist') || '[]').map(String);
      if (w.includes(String(prod.id))) w = w.filter(x => x !== String(prod.id));
      else w.push(String(prod.id));
      localStorage.setItem('wishlist', JSON.stringify(w));
      setWishText(w.includes(String(prod.id)));
    });
  }

  const qtyMinus = document.getElementById('qty-minus') || document.querySelector('.pengurangan');
  const qtyPlus = document.getElementById('qty-plus') || document.querySelector('.penambahan');
  const qtyTotalEl = document.getElementById('qty-total') || document.getElementById('total_produk');
  let qty = 1;
  if (qtyTotalEl) qtyTotalEl.textContent = String(qty);
  if (qtyMinus && qtyPlus && qtyTotalEl) {
    qtyMinus.addEventListener('click', () => { qty = Math.max(1, qty - 1); qtyTotalEl.textContent = String(qty); });
    qtyPlus.addEventListener('click', () => { qty = qty + 1; qtyTotalEl.textContent = String(qty); });
  }

  const cartBtn = document.getElementById('add-cart-btn') || document.querySelector('.add-cart');
  if (cartBtn) {
    cartBtn.addEventListener('click', () => {
      const variant = getSelectedVariant();
      const key = `${prod.id}|${variant.model}|${variant.motif}`;
      const existing = cart.find(x => String(x.key) === String(key));
      const priceNum = parsePriceToNumber(variant.price);
      const variantImage = imgEl ? imgEl.src : prod.img;
      if (existing) {
        existing.qty += qty;
      } else {
        cart.push({
          key,
          id: prod.id,
          name: prod.name,
          model: variant.model,
          motif: variant.motif,
          selectedVariants: variant.selectedVariants,
          price: priceNum,
          qty,
          thumb: variantImage
        });
      }
      localStorage.setItem('cart', JSON.stringify(cart));
      if (typeof showToast === 'function') {
        showToast((prod.name || 'Produk') + ' ditambahkan ke keranjang.', 'success');
      } else {
        alert((prod.name || 'Produk') + ' ditambahkan ke keranjang.');
      }
    });
  }

  // Buy Now -> push current selection and navigate to Checkout
  const buyNowBtn = document.querySelector('.buy-now');
  if (buyNowBtn) {
    buyNowBtn.addEventListener('click', () => {
      const variant = getSelectedVariant();
      const key = `${prod.id}|${variant.model}|${variant.motif}`;
      const priceNum = parsePriceToNumber(variant.price);
      const variantImage = imgEl ? imgEl.src : prod.img;
      const existing = cart.find(x => String(x.key) === String(key));
      const itemObj = {
        key,
        id: prod.id,
        name: prod.name,
        model: variant.model,
        motif: variant.motif,
        selectedVariants: variant.selectedVariants,
        price: priceNum,
        qty,
        thumb: variantImage
      };
      if (existing) existing.qty = (existing.qty || 0) + qty; else cart.push(itemObj);
      localStorage.setItem('cart', JSON.stringify(cart));
      // Only checkout this one item
      localStorage.setItem('checkoutSelection', JSON.stringify([itemObj]));
      // Go to checkout page
      window.location.href = 'Checkout/checkout.html';
    });
  }

  const chatSellerBtn = document.querySelector('.chat-seller');
  if (chatSellerBtn) {
    chatSellerBtn.addEventListener('click', () => {
      const variant = getSelectedVariant();
      const message = `Halo, saya tertarik dengan produk: ${prod.name}%0AModel: ${variant.model}%0AMotif: ${variant.motif}%0AHarga: ${formatRupiah(variant.price)}%0A%0AApakah produk ini masih tersedia?`;
      const waLink = `https://wa.me/6285249746506?text=${message}`;
      window.open(waLink, '_blank');
    });
  }

  // Recommendations: prefer grid with Product card styles; fallback to old scroller if absent
  // Use PRODUCTS_DATA (full catalog) if available; otherwise fall back to current dataset
  const recDataset = (typeof PRODUCTS_DATA !== 'undefined' && Array.isArray(PRODUCTS_DATA) && PRODUCTS_DATA.length)
    ? PRODUCTS_DATA
    : dataset;
  const grid = document.getElementById('productsGrid');
  if (grid) {
    grid.innerHTML = '';
    const others = recDataset.filter(p => String(p.id) !== String(prod.id));
    shuffleArray(others);
    others.slice(0, 8).forEach(p => {
      const icon = p.icon || '';
      const subtitle = p.subtitle || '';
      const categoryText = (p.category || 'Produk');
      const imgSrc = resolveProductImagePath(p.img || p.image || p.image_path || p.thumb || '');
      const priceStr = typeof p.price === 'string' && p.price.trim().startsWith('Rp') ? p.price : formatRupiah(p.price);
      const card = document.createElement('div');
      card.className = 'product-card';
      card.setAttribute('data-id', String(p.id));
      card.innerHTML = `
        <div class="card-header">
          <div class="card-header-icon">${icon}</div>
          <div class="card-header-text">
            <h3>${categoryText.charAt(0).toUpperCase() + categoryText.slice(1)}</h3>
            <p>${subtitle}</p>
          </div>
        </div>
        <div class="card-image">
          <img src="${imgSrc}" alt="${p.name || ''}" loading="lazy">
        </div>
        <div class="card-content-product">
          <h2 class="card-title-product">${p.name || ''}</h2>
          <p class="card-price">${priceStr}</p>
          <div class="card-buttons-product">
            <button class="btn btn-secondary">Detail</button>
            <button class="btn btn-primary">Beli</button>
          </div>
        </div>
      `;
      // Click whole card to detail if buttons not clicked
      card.addEventListener('click', (e) => {
        if (!e.target.closest('.btn')) {
          try {
            localStorage.setItem('pk-last-product', JSON.stringify(p));
          } catch (err) { }
          window.location.href = resolveDetailUrl(p.id);
        }
      });
      // Button handlers mirroring product.js behavior
      card.querySelector('.btn.btn-secondary').addEventListener('click', (e) => {
        e.stopPropagation();
        try {
          localStorage.setItem('pk-last-product', JSON.stringify(p));
        } catch (err) { }
        window.location.href = resolveDetailUrl(p.id);
      });
      card.querySelector('.btn.btn-primary').addEventListener('click', (e) => {
        e.stopPropagation();
        alert(`Produk ${p.name || ''} ditambahkan ke keranjang!`);
      });
      grid.appendChild(card);
    });
  } else {
    const track = document.getElementById('recommendations-track') || document.querySelector('.rekomendasi-track');
    if (track) {
      track.innerHTML = '';
      const others = recDataset.filter(p => String(p.id) !== String(prod.id));
      shuffleArray(others);
      others.slice(0, 6).forEach(p => {
        const card = document.createElement('div');
        card.className = 'produk-card';
        const imgSrc = resolveProductImagePath(p.thumb || p.img || p.image || p.image_path || '');
        card.innerHTML = `
          <div class="produk-header">
            <i>★</i>
            <div>
              <h4>${p.category || 'Produk'}</h4>
              <p>${p.name}</p>
            </div>
          </div>
          <div class="produk-body">
            <img src="${imgSrc}" alt="${p.name || ''}">
            <h5>${p.name || ''}</h5>
            <div class="subtitle">${formatRupiah(p.price)}</div>
            <div class="buttons">
              <button class="add-cart">Tambah</button>
              <button class="btn-detail">Detail</button>
            </div>
          </div>
        `;
        card.querySelector('.add-cart').addEventListener('click', () => {
          const priceNum = parsePriceToNumber(p.price);
          const existing = cart.find(x => String(x.id) === String(p.id));
          if (existing) existing.qty += 1; else cart.push({ id: p.id, name: p.name, price: priceNum, qty: 1 });
          localStorage.setItem('cart', JSON.stringify(cart));
          if (typeof showToast === 'function') {
            showToast((p.name || 'Produk') + ' ditambahkan ke keranjang.', 'success');
          } else {
            alert((p.name || 'Produk') + ' ditambahkan ke keranjang.');
          }
        });
        card.querySelector('.btn-detail').addEventListener('click', () => { window.location.href = resolveDetailUrl(p.id); });
        track.appendChild(card);
      });
    }
  }

  document.dispatchEvent(new CustomEvent('productDataLoaded'));
}

renderProduct();
// ==== END product-detail.js ====

/* ============================
   1️⃣ MODEL SELECT ACTIVE STATE
   ============================ */
document.addEventListener("DOMContentLoaded", () => {
  document
    .querySelectorAll("#variant-categories-container .model_produk > div:not(h4)")
    .forEach((box) => {
      box.addEventListener("click", () => {
        box.parentElement
          .querySelectorAll("div:not(h4)")
          .forEach((b) => b.classList.remove("active"));
        box.classList.add("active");
      });
    });
});

/* ============================
   2️⃣ TABS + INFO BUTTON TOGGLE
   ============================ */
document.addEventListener("DOMContentLoaded", () => {
  const descContent = document.getElementById("desc-content");
  const tabContent = document.querySelector(".tab-content");
  const infoBtn = document.querySelector(".info-btn");

  if (tabContent) tabContent.style.display = "none";
  if (infoBtn) infoBtn.textContent = "Lihat informasi produk";

  if (infoBtn) {
    infoBtn.addEventListener("click", () => {
      if (!tabContent) return;
      const willOpen = tabContent.style.display === "none";
      tabContent.style.display = willOpen ? "block" : "none";
      infoBtn.textContent = willOpen ? "Tutup informasi produk" : "Lihat informasi produk";
    });
  }
});

/* ============================
   3️⃣ REKOMENDASI AUTO SCROLL
   ============================ */
document.addEventListener("DOMContentLoaded", () => {
  const track = document.querySelector(".rekomendasi-track");
  const leftBtn = document.querySelector(".scroll-btn.left");
  const rightBtn = document.querySelector(".scroll-btn.right");

  if (!track) return;

  if (!track.dataset.cloned) {
    const cards = [...track.children];
    cards.forEach(card => track.appendChild(card.cloneNode(true)));
    track.dataset.cloned = "true";
  }

  let scrollSpeed = 1.1;
  let scrollInterval;

  function startScroll() {
    stopScroll();
    scrollInterval = setInterval(() => {
      track.scrollLeft += scrollSpeed;
      if (track.scrollLeft >= track.scrollWidth / 2) {
        track.scrollLeft = 0;
      }
    }, 15);
  }

  function stopScroll() { clearInterval(scrollInterval); }

  startScroll();

  track.addEventListener("mouseenter", stopScroll);
  track.addEventListener("mouseleave", startScroll);

  leftBtn?.addEventListener("click", () => {
    stopScroll();
    track.scrollBy({ left: -250, behavior: "smooth" });
    setTimeout(startScroll, 2000);
  });

  rightBtn?.addEventListener("click", () => {
    stopScroll();
    track.scrollBy({ left: 250, behavior: "smooth" });
    setTimeout(startScroll, 2000);
  });

  let isDown = false; let startX; let scrollLeft;
  track.addEventListener("mousedown", (e) => { isDown = true; startX = e.pageX - track.offsetLeft; scrollLeft = track.scrollLeft; stopScroll(); });
  track.addEventListener("mouseleave", () => (isDown = false));
  track.addEventListener("mouseup", () => { isDown = false; startScroll(); });
  track.addEventListener("mousemove", (e) => {
    if (!isDown) return; e.preventDefault(); const x = e.pageX - track.offsetLeft; const walk = (x - startX) * 1.5; track.scrollLeft = scrollLeft - walk;
  });
});

/* ============================
   4️⃣ IMAGE THUMBNAILS + ZOOM
   ============================ */
document.addEventListener("DOMContentLoaded", () => {
  const thumbnails = document.querySelectorAll(".thumbnail-list img");
  const mainImg = document.getElementById("main-img");
  const mainWrapper = document.querySelector(".main-image-wrapper");

  if (!mainImg || !mainWrapper) return;

  thumbnails.forEach((thumb) => {
    thumb.addEventListener("click", () => {
      thumbnails.forEach(t => t.classList.remove("active"));
      thumb.classList.add("active");
      mainImg.src = thumb.src;
    });
  });

  let isZoomed = false; let isDragging = false; let startX, startY, moveX = 0, moveY = 0;

  mainWrapper.addEventListener("click", () => {
    isZoomed = !isZoomed;
    if (isZoomed) { mainImg.style.transform = "scale(2)"; mainImg.style.cursor = "grab"; }
    else { mainImg.style.transform = "scale(1)"; mainImg.style.cursor = "default"; moveX = moveY = 0; mainImg.style.transformOrigin = "center center"; mainImg.style.left = "0"; mainImg.style.top = "0"; }
  });

  mainWrapper.addEventListener("mousedown", (e) => {
    if (!isZoomed) return; isDragging = true; mainImg.style.cursor = "grabbing"; startX = e.pageX - moveX; startY = e.pageY - moveY;
  });
  mainWrapper.addEventListener("mouseup", () => { isDragging = false; if (isZoomed) mainImg.style.cursor = "grab"; });
  mainWrapper.addEventListener("mouseleave", () => { isDragging = false; if (isZoomed) mainImg.style.cursor = "grab"; });
  mainWrapper.addEventListener("mousemove", (e) => {
    if (!isDragging || !isZoomed) return; e.preventDefault(); moveX = e.pageX - startX; moveY = e.pageY - startY; mainImg.style.transform = `scale(2) translate(${moveX / 2}px, ${moveY / 2}px)`;
  });
});
