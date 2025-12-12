
const PRODUCT_IMAGE_FILES = [
    'img/IMG_4090.jpg',
    'img/IMG_4091.jpg',
    'img/IMG_4094.jpg',
    'img/IMG_4095.jpg',
    'img/IMG_4098.jpg',
    'img/IMG_4099.jpg',
    'img/IMG_4103.jpg',
    'img/IMG_4104.jpg',
    'img/IMG_4105.jpg',
    'img/IMG_4106.jpg'
];

// Dataset produk utama untuk katalog dan rekomendasi.
// Awalnya kosong dan akan diisi hanya dari database (products_api.php).
let PRODUCTS_DATA = [];

function formatRupiah(num) {
    if (typeof num === 'string') {
        return num.trim().startsWith('Rp') ? num : 'Rp ' + num;
    }
    try {
        return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
    } catch (e) {
        return 'Rp ' + String(num || 0);
    }
}

function parsePriceToNumber(price) {
    if (typeof price === 'number') return price;
    const digits = String(price).replace(/[^0-9]/g, '');
    return Number(digits || 0);
}

function getCart() {
    try {
        return JSON.parse(localStorage.getItem('cart') || '[]');
    } catch (e) {
        return [];
    }
}

function setCart(arr) {
    try {
        localStorage.setItem('cart', JSON.stringify(arr));
    } catch (e) { }
}

function getCategoryIcon(category) {
    const c = String(category || '').toLowerCase();
    if (c.includes('tas')) return '👜';
    if (c.includes('dompet')) return '👛';
    if (c.includes('keranjang') || c.includes('basket')) return '🧺';
    if (c.includes('furniture') || c.includes('kursi') || c.includes('meja')) return '🪑';
    return '🧺';
}

const ITEMS_PER_PAGE = 16;
let currentPage = 1;
let currentCategory = 'all';
let filteredProducts = PRODUCTS_DATA;
let totalPages = Math.ceil(PRODUCTS_DATA.length / ITEMS_PER_PAGE) || 1;

// Map UI categories to filtering strategy
const UI_CATEGORY_MAP = {
    all: (p) => true,
    tas: (p) => /tas|bag|sling|backpack|tote/i.test(`${p.name} ${p.subtitle}`),
    dompet: (p) => /dompet|clutch|wallet/i.test(`${p.name} ${p.subtitle}`),
    furniture: (p) => /kursi|chair|sofa|meja|table|rak|shelf|lemari|console|bed|headboard|stool|ottoman|lampu|lamp|pendant|ceiling|floor|sconce|divider|panel|cermin|mirror/i.test(`${p.name} ${p.subtitle}`),
    keranjang: (p) => /keranjang|basket|breadbasket/i.test(`${p.name} ${p.subtitle}`)
};

// Get filtered products based on category
function getFilteredProducts() {
    const predicate = UI_CATEGORY_MAP[currentCategory];
    if (!predicate) return PRODUCTS_DATA;
    return PRODUCTS_DATA.filter(predicate);
}

// Smoothly scroll viewport to the products section instead of the very top
function scrollToProductsSection() {
    const container = document.querySelector('.products-container');
    if (!container) return;
    const rect = container.getBoundingClientRect();
    const offset = window.pageYOffset || document.documentElement.scrollTop || 0;
    // Keep a small margin above the category bar so it sits nicely under the navbar
    const targetTop = offset + rect.top - 40;
    window.scrollTo({ top: targetTop, behavior: 'smooth' });
}

// Get products for current page
function getPageProducts(page) {
    const startIndex = (page - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    return filteredProducts.slice(startIndex, endIndex);
}

// Render product cards
function renderProductCards(page) {
    const productsGrid = document.getElementById('productsGrid');
    const products = getPageProducts(page);

    productsGrid.innerHTML = products.map(product => `
        <div class="product-card" data-id="${product.id}">
            <div class="card-header">
                <div class="card-header-icon">${product.icon}</div>
                <div class="card-header-text">
                    <h3>${product.category.charAt(0).toUpperCase() + product.category.slice(1)}</h3>
                    <p>${product.subtitle}</p>
                </div>
            </div>
            <div class="card-image">
                <img src="${product.image || product.img}" alt="${product.name}" loading="lazy">
            </div>
            <div class="card-content-product">
                <h2 class="card-title-product">${product.name}</h2>
                <p class="card-subtitle-product">${product.subtitle}</p>
                <p class="card-price">${product.price}</p>
                <div class="card-buttons-product">
                    <button class="btn btn-secondary">Detail</button>
                    <button class="btn btn-primary">Beli</button>
                </div>
            </div>
        </div>
    `).join('');

    // Add click handlers for cards
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (!e.target.closest('.btn')) {
                const productId = card.dataset.id;
                const product = PRODUCTS_DATA.find(p => String(p.id) === String(productId));
                if (product) {
                    try {
                        localStorage.setItem('pk-last-product', JSON.stringify(product));
                    } catch (e) { }
                    // arahkan ke Produk_detail.html
                    goToDetail(productId);
                }
            }
        });
    });

    // Add button click handlers
    document.querySelectorAll('.btn-secondary').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const productId = e.target.closest('.product-card').dataset.id;
            const product = PRODUCTS_DATA.find(p => String(p.id) === String(productId));
            if (product) {
                try {
                    localStorage.setItem('pk-last-product', JSON.stringify(product));
                } catch (e) { }
                // arahkan ke Produk_detail.html
                goToDetail(productId);
            }
        });
    });

    document.querySelectorAll('.btn-primary').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const card = e.target.closest('.product-card');
            if (!card) return;
            const productId = card.dataset.id;
            const product = PRODUCTS_DATA.find(p => String(p.id) === String(productId));
            if (product) {
                let cart = getCart();
                const priceNum = parsePriceToNumber(product.price);
                const key = String(product.id);
                const existing = cart.find(it => String(it.key || it.id) === key);
                if (existing) {
                    existing.qty = (existing.qty || 1) + 1;
                } else {
                    cart.push({
                        key,
                        id: product.id,
                        name: product.name,
                        model: product.model || '-',
                        motif: product.motif || '-',
                        price: priceNum,
                        qty: 1,
                        thumb: product.img || product.image || product.image_path || product.thumb || ''
                    });
                }
                setCart(cart);
                if (typeof showToast === 'function') {
                    showToast(`Produk ${product.name} ditambahkan ke keranjang.`, 'success');
                } else {
                    alert(`Produk ${product.name} ditambahkan ke keranjang!`);
                }
            }
        });
    });

    updateNavigationButtons();
}

// Update pagination UI
function updatePaginationUI() {
    const paginationNumbers = document.getElementById('paginationNumbers');
    if (!paginationNumbers) return;
    let paginationHTML = '';

    // Update total pages based on filtered products
    totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE) || 1;

    // Show first page
    paginationHTML += `<button class="pagination-number ${currentPage === 1 ? 'active' : ''}" data-page="1">1</button>`;

    // Show ellipsis and middle pages
    if (totalPages <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 2; i <= totalPages; i++) {
            paginationHTML += `<button class="pagination-number ${currentPage === i ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
    } else {
        // Show ellipsis and limited pages
        if (currentPage > 3) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }

        let startPage = Math.max(2, currentPage - 1);
        let endPage = Math.min(totalPages - 1, currentPage + 1);

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<button class="pagination-number ${currentPage === i ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (currentPage < totalPages - 2) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }

        // Show last page
        paginationHTML += `<button class="pagination-number ${currentPage === totalPages ? 'active' : ''}" data-page="${totalPages}">${totalPages}</button>`;
    }

    paginationNumbers.innerHTML = paginationHTML;

    // Add click handlers
    document.querySelectorAll('.pagination-number').forEach(btn => {
        btn.addEventListener('click', () => {
            currentPage = parseInt(btn.dataset.page);
            renderProductCards(currentPage);
            updatePaginationUI();
            scrollToProductsSection();
        });
    });
}

// Update navigation button states
function updateNavigationButtons() {
    const paginationPrev = document.getElementById('paginationPrev');
    const paginationNext = document.getElementById('paginationNext');
    if (!paginationPrev || !paginationNext) return;

    const canGoPrev = currentPage > 1;
    const canGoNext = currentPage < totalPages;

    paginationPrev.disabled = !canGoPrev;
    paginationNext.disabled = !canGoNext;
}

function loadProductsFromApi() {
    const grid = document.getElementById('productsGrid');
    try {
        const path = window.location.pathname || '';
        const basePrefix = path.includes('/Product/') ? '../' : '';

        fetch('http://localhost/purunikk/admin/products_api.php')
            .then((res) => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then((data) => {
                if (!Array.isArray(data) || !data.length) {
                    if (grid) {
                        grid.innerHTML = '<p class="empty-message">Produk belum tersedia.</p>';
                    }
                    PRODUCTS_DATA = [];
                    filteredProducts = PRODUCTS_DATA;
                    totalPages = 1;
                    updatePaginationUI();
                    return;
                }

                const mapped = data.map((item, index) => {
                    const priceNum = typeof item.price === 'number' ? item.price : parseFloat(item.price) || 0;
                    let imagePath = '';
                    if (item.image_path) {
                        imagePath = basePrefix + String(item.image_path);
                    } else {
                        imagePath = PRODUCT_IMAGE_FILES[index % PRODUCT_IMAGE_FILES.length];
                    }

                    const name = item.name || '';
                    const category = item.category || 'Produk';
                    const description = item.description || '';
                    const subtitle = category || 'Koleksi Unggulan';
                    const icon = getCategoryIcon(category);

                    return {
                        id: item.id,
                        name,
                        subtitle,
                        category,
                        description,
                        price: formatRupiah(priceNum),
                        icon,
                        img: imagePath,
                        image: imagePath
                    };
                });

                PRODUCTS_DATA = mapped;
                filteredProducts = PRODUCTS_DATA;
                totalPages = Math.ceil(PRODUCTS_DATA.length / ITEMS_PER_PAGE) || 1;
                currentPage = 1;
                renderProductCards(currentPage);
                updatePaginationUI();
                initShopHero();

                if (typeof window !== 'undefined' && typeof window.pkOnProductsLoaded === 'function') {
                    try {
                        window.pkOnProductsLoaded(PRODUCTS_DATA.slice());
                    } catch (e) {
                        console.error('pkOnProductsLoaded callback error:', e);
                    }
                }

                document.dispatchEvent(new CustomEvent('productDataLoaded'));
            })
            .catch((err) => {
                console.error('Gagal memuat produk dari API:', err);
            });
    } catch (e) {
        console.error('Fetch API tidak tersedia:', e);
    }
}

// Navigation handlers (prev/next buttons removed; use pagination buttons instead)

const paginationPrevBtn = document.getElementById('paginationPrev');
if (paginationPrevBtn) {
    paginationPrevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderProductCards(currentPage);
            updatePaginationUI();
            scrollToProductsSection();
        }
    });
}

const paginationNextBtn = document.getElementById('paginationNext');
if (paginationNextBtn) {
    paginationNextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderProductCards(currentPage);
            updatePaginationUI();
            scrollToProductsSection();
        }
    });
}

// Category filter handlers for new chips
(function initCategoryChips() {
    const chips = document.querySelectorAll('.category-chip');
    if (!chips.length) return;
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            currentCategory = chip.dataset.category || 'all';
            // Update active state
            document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            // Apply filter
            currentPage = 1;
            filteredProducts = getFilteredProducts();
            renderProductCards(currentPage);
            updatePaginationUI();
            scrollToProductsSection();
        });
    });
})();

// Initialize app: load products only from database
loadProductsFromApi();

// --- Shop Hero helpers ---
function addRecentlyViewed(id) {
    try {
        const key = 'rv-ids';
        const arr = JSON.parse(localStorage.getItem(key) || '[]').map(String);
        const sid = String(id);
        const next = [sid, ...arr.filter(x => x !== sid)].slice(0, 8);
        localStorage.setItem(key, JSON.stringify(next));
    } catch (e) { }
}

function goToDetail(id) {
    addRecentlyViewed(id);

    // Use absolute path from the root to avoid case sensitivity and path resolution issues
    // Get the base path by finding where 'purunikk' is in the current path
    const currentPath = window.location.pathname;
    const purunikkIndex = currentPath.toLowerCase().indexOf('/purunikk/');

    if (purunikkIndex !== -1) {
        // Extract base path up to /purunikk/
        const basePath = currentPath.substring(0, purunikkIndex + '/purunikk/'.length);
        window.location.href = `${basePath}Produk_detail.html?id=${encodeURIComponent(id)}`;
    } else {
        // Fallback: try relative path
        const path = window.location.pathname.toLowerCase();
        if (path.includes('/product/')) {
            window.location.href = `../Produk_detail.html?id=${encodeURIComponent(id)}`;
        } else {
            window.location.href = `Produk_detail.html?id=${encodeURIComponent(id)}`;
        }
    }
}
// pada saat klik kartu produk
// document.addEventListener('click', (e)=>{ ... })
// Ganti pemanggilan ke goToDetail(id)

(() => {
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

        if (!saved) {
            setTheme('dark');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle);
    } else {
        initThemeToggle();
    }
})();

// Render the new shop-hero section if present
function initShopHero() {
    const catEl = document.getElementById('shopCategories');
    const mainEl = document.getElementById('shopBannerMain');
    const tilesEl = document.getElementById('shopBannerTiles');
    const rvEl = document.getElementById('recentlyViewed');
    const sugEl = document.getElementById('suggestions');
    if (!catEl && !mainEl && !tilesEl && !rvEl && !sugEl) return;
    if (!Array.isArray(PRODUCTS_DATA) || !PRODUCTS_DATA.length) return;

    // Build categories list dynamically (all + unique categories)
    if (catEl) {
        const pretty = (s) => String(s || '').replace(/[-_]+/g, ' ').replace(/\b\w/g, m => m.toUpperCase());
        const counts = PRODUCTS_DATA.reduce((acc, p) => { const k = (p.category || 'lain').toLowerCase(); acc[k] = (acc[k] || 0) + 1; return acc; }, {});
        const baseCats = [
            { key: 'all', label: 'Semua', count: PRODUCTS_DATA.length },
            ...Object.entries(counts).map(([key, count]) => ({ key, label: pretty(key), count }))
        ];
        catEl.innerHTML = baseCats.map(c => `<button class="cat-item" data-category="${c.key}"><span>${c.label}</span><span style="margin-left:auto;opacity:.7;font-weight:600;">${c.count}</span></button>`).join('');
        const catButtons = Array.from(catEl.querySelectorAll('.cat-item'));
        if (catButtons.length) catButtons[0].classList.add('active');
        catButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                catButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const target = btn.dataset.category || 'all';
                if (target === 'all') {
                    document.querySelector(`.category-chip[data-category="all"]`)?.click();
                } else {
                    // Map to UI chips if exists, else fall back to 'all'
                    const chip = document.querySelector(`.category-chip[data-category="${target}"]`) || document.querySelector('.category-chip[data-category="all"]');
                    chip?.click();
                }
                window.scrollTo({ top: document.querySelector('.products-container')?.offsetTop || 0, behavior: 'smooth' });
            });
        });
    }

    // Banner main: pick a featured product (first of filtered or first in dataset)
    const datasetFull = PRODUCTS_DATA;
    function bannerCard(p) {
        return `<div class="banner-inner">
      <div class="banner-text">
        <p>Biggest Offer Revealed</p>
        <h2>${p.name}</h2>
        <p>${p.subtitle || p.category || ''}</p>
      </div>
      <img src="${p.img || p.image}" alt="${p.name}">
    </div>`;
    }
    if (mainEl) {
        const picks = [...datasetFull].slice(0, 6);
        let idx = 0;
        const render = () => {
            const p = picks[idx % picks.length] || PRODUCTS_DATA[0];
            mainEl.innerHTML = bannerCard(p);
            idx++;
        };
        render();
        setInterval(render, 6000);
    }
    if (tilesEl) {
        const picks = PRODUCTS_DATA.slice(0, 4);
        tilesEl.innerHTML = picks.map(p => `<div class="tile"><img src="${p.img}" alt="${p.name}"><div><div class="t-title">${p.name}</div><div class="t-sub">${p.price}</div></div></div>`).join('');
    }

    function renderMiniList(el, items) {
        if (!el) return;
        el.innerHTML = items.map(p => `<div class="mini" data-id="${p.id}"><img src="${p.img}" alt="${p.name}"><div><div class="m-title">${p.name}</div><div class="m-price">${p.price}</div></div></div>`).join('');
        el.querySelectorAll('.mini').forEach(div => {
            div.addEventListener('click', () => { goToDetail(div.dataset.id); });
        });
    }

    // Recently viewed from localStorage
    try {
        const ids = JSON.parse(localStorage.getItem('rv-ids') || '[]');
        const rvItems = ids
            .map(id => PRODUCTS_DATA.find(p => String(p.id) === String(id)))
            .filter(Boolean)
            .slice(0, 4);
        renderMiniList(rvEl, rvItems);
    } catch (e) { }

    // Suggestions panel: quick category shortcuts (Purun, Eceng Gondok, Rotan)
    if (sugEl) {
        const catMap = {
            purun: 'Purun',
            'eceng-gondok': 'Eceng Gondok',
            rotan: 'Rotan',
        };

        const availableKeys = Object.keys(catMap).filter(key =>
            PRODUCTS_DATA.some(p => (p.category || '').toLowerCase() === key)
        );

        sugEl.innerHTML = availableKeys.map(key => {
            const count = PRODUCTS_DATA.filter(p => (p.category || '').toLowerCase() === key).length;
            return `<button class="mini" data-category="${key}">
        <div>
          <div class="m-title">${catMap[key]}</div>
          <div class="m-price">${count} produk</div>
        </div>
      </button>`;
        }).join('');

        sugEl.querySelectorAll('.mini').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.category;
                const chip = document.querySelector(`.category-chip[data-category="${key}"]`) ||
                    document.querySelector('.category-chip[data-category="all"]');
                chip?.click();
                window.scrollTo({
                    top: document.querySelector('.products-container')?.offsetTop || 0,
                    behavior: 'smooth',
                });
            });
        });
    }
}
