/* ===== BUNDLE: product-detail.js + Produk_detail.js =====
   Catatan: Urutan dijaga. Bagian product-detail.js dulu (render data),
   lalu UI interaksi dari Produk_detail.js. */

// ==== BEGIN product-detail.js ====
function getParam(name){
  const url = new URL(window.location.href);
  return url.searchParams.get(name);
}

function formatRupiah(num){
  if (typeof num === 'string') {
    return num.startsWith('Rp') ? num : 'Rp ' + num;
  }
  return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function parsePriceToNumber(price){
  if (typeof price === 'number') return price;
  const digits = String(price).replace(/[^0-9]/g, '');
  return Number(digits || 0);
}

const wishlist = JSON.parse(localStorage.getItem('wishlist')||'[]').map(String);
let cart = JSON.parse(localStorage.getItem('cart')||'[]');

function shuffleArray(a){
  for(let i=a.length-1;i>0;i--){
    const j=Math.floor(Math.random()*(i+1));
    [a[i],a[j]]=[a[j],a[i]];
  }
  return a;
}

function resolveDetailUrl(id){
  const path = window.location.pathname;
  if (path.includes('/Product/')) return `../Produk_detail.html?id=${encodeURIComponent(id)}`;
  return `Produk_detail.html?id=${encodeURIComponent(id)}`;
}

function renderProduct(){
  const id = getParam('id');
  const root = document.querySelector('.product-section')
            || document.getElementById('product-root')
            || document.querySelector('.product-detail')
            || document.body;

  if(!id){ if (root) root.style.display = 'none'; return; }

  const hideSelectors = ['.products-container', '.pagination-container', '.top-row'];
  hideSelectors.forEach(sel => {
    document.querySelectorAll(sel).forEach(el => el && (el.style.display = 'none'));
  });

  if (root) root.style.display = '';

  const dataset = (typeof PRODUCTS !== 'undefined') ? PRODUCTS : ((typeof PRODUCTS_DATA !== 'undefined') ? PRODUCTS_DATA : []);
  let prod = dataset.find(p => String(p.id) === String(id) || p.id === parseInt(id));
  // Fallback: jika id numerik dan tidak cocok dengan field id, gunakan index (1-based)
  if (!prod && /^\d+$/.test(String(id))) {
    const idx = Math.max(0, parseInt(id, 10) - 1);
    if (idx >= 0 && idx < dataset.length) prod = dataset[idx];
  }

  if(!prod){
    const backUrl = (window.location.pathname.includes('/Product/')) ? 'Product/product.html' : 'product.html';
    document.body.innerHTML = `<div class="container"><p>Produk tidak ditemukan. <a href="${backUrl}">Kembali ke katalog</a></p></div>`;
    return;
  }

  const imgEl = document.getElementById('product-img') || document.getElementById('main-img');
  const nameEl = document.getElementById('product-name');
  const priceEl = document.getElementById('product-price');
  const descEl = document.getElementById('product-desc');
  const specsWrap = document.getElementById('product-specs');
  const breadcrumbEl = document.getElementById('breadcrumb-product');

  if (imgEl) { imgEl.src = prod.img || prod.image || ''; imgEl.alt = prod.name || ''; }
  if (nameEl) { nameEl.textContent = prod.name || ''; }
  if (breadcrumbEl) { breadcrumbEl.textContent = prod.name || ''; }
  if (priceEl) { priceEl.textContent = formatRupiah(prod.price); }
  if (descEl) { descEl.textContent = prod.description || ''; }

  const thumbs = (Array.isArray(prod.images) && prod.images.length)
    ? prod.images
    : [prod.thumb || prod.img, prod.thumb || prod.img, prod.img].filter(Boolean);
  const thumbList = document.getElementById('thumbnail-list');
  if (thumbList && thumbs.length) {
    thumbList.innerHTML = '';
    thumbs.forEach((src, idx) => {
      const t = document.createElement('img');
      t.src = src;
      if (idx === 0) t.classList.add('active');
      t.addEventListener('click', () => {
        document.querySelectorAll('#thumbnail-list img').forEach(i=>i.classList.remove('active'));
        t.classList.add('active');
        if (imgEl) imgEl.src = src;
      });
      thumbList.appendChild(t);
    });
  }

  if (specsWrap) {
    specsWrap.innerHTML = '';
    if (prod.specs && typeof prod.specs === 'object') {
      for(const k in prod.specs){
        const box = document.createElement('div');
        const label = document.createElement('h4');
        label.textContent = k;
        const val = document.createElement('img');
        val.alt = k;
        val.style.display = 'none';
        box.appendChild(label);
        specsWrap.appendChild(box);
      }
    }
  }
  const specContent = document.getElementById('spec-content');
  if (specContent) {
    if (prod.specs && typeof prod.specs === 'object') {
      const table = document.createElement('table');
      Object.entries(prod.specs).forEach(([k,v])=>{
        const tr = document.createElement('tr');
        if (Array.isArray(v)) {
          const list = `<ul>${v.map(item=>`<li>${item}</li>`).join('')}</ul>`;
          tr.innerHTML = `<td>${k}</td><td>${list}</td>`;
        } else {
          tr.innerHTML = `<td>${k}</td><td>${v}</td>`;
        }
        table.appendChild(tr);
      });
      specContent.innerHTML = '';
      specContent.appendChild(table);
    } else {
      specContent.textContent = 'Spesifikasi belum tersedia.';
    }
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

  // Render motifs dari data
  const motifsWrap = document.querySelector('.model_produk');
  if (motifsWrap && Array.isArray(prod.motifs) && prod.motifs.length) {
    motifsWrap.innerHTML = '';
    prod.motifs.forEach((m, idx) => {
      const div = document.createElement('div');
      if (idx === 0) div.classList.add('active');
      const img = document.createElement('img');
      img.src = m.img || '';
      img.alt = m.name || '';
      const h4 = document.createElement('h4');
      h4.textContent = m.name || '';
      div.appendChild(img);
      div.appendChild(h4);
      motifsWrap.appendChild(div);
    });
  }

  // Render models dari data
  const modelsWrap = document.querySelector('.model_produk_fun');
  if (modelsWrap && Array.isArray(prod.models) && prod.models.length) {
    // simpan judul jika ada
    const title = modelsWrap.querySelector('h4')?.textContent || 'Pilih model';
    modelsWrap.innerHTML = '';
    const titleEl = document.createElement('h4');
    titleEl.textContent = title;
    modelsWrap.appendChild(titleEl);
    prod.models.forEach((name, idx) => {
      const div = document.createElement('div');
      div.className = 'Model_1';
      if (idx === 0) div.classList.add('active');
      const h4 = document.createElement('h4');
      h4.textContent = name;
      div.appendChild(h4);
      modelsWrap.appendChild(div);
    });
  }

  // Helper: hitung harga berdasarkan pilihan motif + model
  function getSelectedVariant() {
    const motifActive = document.querySelector('.model_produk > div.active h4');
    const modelActive = document.querySelector('.model_produk_fun > .Model_1.active h4');
    const motif = motifActive ? motifActive.textContent.trim() : (prod.motifs?.[0]?.name || '');
    const model = modelActive ? modelActive.textContent.trim() : (prod.models?.[0] || '');
    let price = parsePriceToNumber(prod.price);
    if (prod.pricing && prod.pricing[model] && prod.pricing[model][motif] != null) {
      price = parsePriceToNumber(prod.pricing[model][motif]);
    }
    return { motif, model, price };
  }

  function updateDisplayedPrice() {
    const { price } = getSelectedVariant();
    if (priceEl) priceEl.textContent = formatRupiah(price);
  }

  // Set listener untuk update harga ketika motif / model diklik
  document.querySelectorAll('.model_produk > div').forEach(div => {
    div.addEventListener('click', () => {
      div.parentElement.querySelectorAll('div').forEach(b=>b.classList.remove('active'));
      div.classList.add('active');
      updateDisplayedPrice();
    });
  });
  document.querySelectorAll('.model_produk_fun > .Model_1').forEach(div => {
    div.addEventListener('click', () => {
      document.querySelectorAll('.model_produk_fun > .Model_1').forEach(b=>b.classList.remove('active'));
      div.classList.add('active');
      updateDisplayedPrice();
    });
  });
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
    const saved = JSON.parse(localStorage.getItem('wishlist')||'[]').map(String);
    setWishText(saved.includes(String(prod.id)));
    wbtn.addEventListener('click', ()=>{
      let w = JSON.parse(localStorage.getItem('wishlist')||'[]').map(String);
      if(w.includes(String(prod.id))) w = w.filter(x=>x!==String(prod.id));
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
    qtyMinus.addEventListener('click', ()=>{ qty = Math.max(1, qty-1); qtyTotalEl.textContent = String(qty); });
    qtyPlus.addEventListener('click', ()=>{ qty = qty+1; qtyTotalEl.textContent = String(qty); });
  }

  const cartBtn = document.getElementById('add-cart-btn') || document.querySelector('.add-cart');
  if (cartBtn) {
    cartBtn.addEventListener('click', ()=>{
      const variant = getSelectedVariant();
      const key = `${prod.id}|${variant.model}|${variant.motif}`;
      const existing = cart.find(x=>String(x.key)===String(key));
      const priceNum = parsePriceToNumber(variant.price);
      if(existing) existing.qty += qty; else cart.push({ key, id: prod.id, name: prod.name, model: variant.model, motif: variant.motif, price: priceNum, qty });
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
      const existing = cart.find(x=>String(x.key)===String(key));
      const itemObj = { key, id: prod.id, name: prod.name, model: variant.model, motif: variant.motif, price: priceNum, qty };
      if(existing) existing.qty = (existing.qty||0) + qty; else cart.push(itemObj);
      localStorage.setItem('cart', JSON.stringify(cart));
      // Only checkout this one item
      localStorage.setItem('checkoutSelection', JSON.stringify([itemObj]));
      // Go to checkout page
      window.location.href = 'Checkout/checkout.html';
    });
  }

  const track = document.getElementById('recommendations-track') || document.querySelector('.rekomendasi-track');
  if (track) {
    track.innerHTML = '';
    const others = dataset.filter(p=>String(p.id)!==String(prod.id));
    shuffleArray(others);
    others.slice(0,6).forEach(p=>{
      const card = document.createElement('div');
      card.className = 'produk-card';
      card.innerHTML = `
        <div class="produk-header">
          <i>★</i>
          <div>
            <h4>${p.category || 'Produk'}</h4>
            <p>${p.name}</p>
          </div>
        </div>
        <div class="produk-body">
          <img src="${p.thumb || p.img || p.image || ''}" alt="${p.name || ''}">
          <h5>${p.name || ''}</h5>
          <div class="subtitle">${formatRupiah(p.price)}</div>
          <div class="buttons">
            <button class="add-cart">Tambah</button>
            <button class="btn-detail">Detail</button>
          </div>
        </div>
      `;
      card.querySelector('.add-cart').addEventListener('click', ()=>{
        const priceNum = parsePriceToNumber(p.price);
        const existing = cart.find(x=>String(x.id)===String(p.id));
        if(existing) existing.qty += 1; else cart.push({ id: p.id, name: p.name, price: priceNum, qty: 1 });
        localStorage.setItem('cart', JSON.stringify(cart));
        if (typeof showToast === 'function') {
          showToast((p.name || 'Produk') + ' ditambahkan ke keranjang.', 'success');
        } else {
          alert((p.name || 'Produk') + ' ditambahkan ke keranjang.');
        }
      });
      card.querySelector('.btn-detail').addEventListener('click', ()=>{ window.location.href = resolveDetailUrl(p.id); });
      track.appendChild(card);
    });
  }
}

renderProduct();
// ==== END product-detail.js ====

/* ============================
   1️⃣ MODEL SELECT ACTIVE STATE
   ============================ */
document
  .querySelectorAll(".model_produk > div, .model_produk_fun > div")
  .forEach((box) => {
    box.addEventListener("click", () => {
      box.parentElement
        .querySelectorAll("div")
        .forEach((b) => b.classList.remove("active"));
      box.classList.add("active");
    });
  });

/* ============================
   2️⃣ TABS + INFO BUTTON TOGGLE
   ============================ */
document.addEventListener("DOMContentLoaded", () => {
  const specTab = document.getElementById("tab-spec");
  const descTab = document.getElementById("tab-desc");
  const specContent = document.getElementById("spec-content");
  const descContent = document.getElementById("desc-content");
  const tabsContainer = document.querySelector(".tabs");
  const tabContent = document.querySelector(".tab-content");
  const infoBtn = document.querySelector(".info-btn");

  // Initial state: info section hidden, button prompts to open
  if (tabsContainer) tabsContainer.style.display = "none";
  if (tabContent) tabContent.style.display = "none";
  if (specContent) specContent.style.display = "block";
  if (descContent) descContent.style.display = "none";
  if (infoBtn) infoBtn.textContent = "Lihat informasi produk";

  if (specTab && descTab && specContent && descContent) {
    specTab.addEventListener("click", () => {
      specTab.classList.add("active");
      descTab.classList.remove("active");
      specContent.style.display = "block";
      descContent.style.display = "none";
    });

    descTab.addEventListener("click", () => {
      descTab.classList.add("active");
      specTab.classList.remove("active");
      specContent.style.display = "none";
      descContent.style.display = "block";
    });
  }

  if (infoBtn) {
    infoBtn.addEventListener("click", () => {
      if (!tabsContainer || !tabContent) return;
      const willOpen = tabsContainer.style.display === "none";
      tabsContainer.style.display = willOpen ? "flex" : "none"; // tabs are flex row
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

