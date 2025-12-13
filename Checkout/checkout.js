// checkout.js - render summary from cart or from query params

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

function getVariantString(item) {
  if (!item.selectedVariants || Object.keys(item.selectedVariants).length === 0) {
    return `${item.model} - ${item.motif}`;
  }
  const variantParts = Object.entries(item.selectedVariants).map(([key, val]) => `${key}: ${val}`);
  return variantParts.join(' · ');
}

let lastReceiptText = '';
function prepareReceipt(fullname, address, phone){
  const messageLines = cart.map(c=>`• ${c.name} (${getVariantString(c)}) x${c.qty} = ${formatRupiah(parsePriceToNumber(c.price)*c.qty)}`);
  const total = cart.reduce((s,c)=> s + (parsePriceToNumber(c.price)*c.qty), 0);
  const header = `Data Pembeli:%0ANama: ${encodeURIComponent(fullname)}%0AAlamat: ${encodeURIComponent(address)}%0ANo. HP: ${encodeURIComponent(phone)}`;
  const items = `Pesanan:%0A${messageLines.map(encodeURIComponent).join('%0A')}`;
  const totalLine = `Total: ${encodeURIComponent(formatRupiah(total))}`;
  lastReceiptText = `${header}%0A%0A${items}%0A%0A${totalLine}`;

  const modal = document.getElementById('receipt-modal');
  const rName = document.getElementById('r-name');
  const rAddress = document.getElementById('r-address');
  const rPhone = document.getElementById('r-phone');
  const rDate = document.getElementById('r-date');
  const rItems = document.getElementById('r-items');
  const rTotal = document.getElementById('r-total');
  if (rName) rName.textContent = fullname;
  if (rAddress) rAddress.textContent = address;
  if (rPhone) rPhone.textContent = phone;
  if (rDate) rDate.textContent = new Date().toLocaleString('id-ID');
  if (rTotal) rTotal.textContent = formatRupiah(total);
  if (rItems) {
    rItems.innerHTML = '';
    cart.forEach((c, i)=>{
      const line = document.createElement('div');
      line.className = 'receipt-item';
      const qty = Number(c.qty||1);
      const lineTotal = parsePriceToNumber(c.price) * qty;
      line.innerHTML = `<span class="ri-name">${i+1}. ${c.name} x${qty}</span><span class="ri-price">${formatRupiah(lineTotal)}</span>`;
      rItems.appendChild(line);
    });
  }
  modal?.classList.add('show');
  modal?.setAttribute('aria-hidden','false');
  return { text: lastReceiptText };
}

// Prefer a temporary selection when coming from "Beli Sekarang" or Cart selection
const selection = JSON.parse(localStorage.getItem('checkoutSelection')||'null');
const cart = Array.isArray(selection) && selection.length
  ? selection
  : JSON.parse(localStorage.getItem('cart')||'[]');

async function buildVariantDisplay(productId, cartItem) {
  try {
    const res = await fetch('http://localhost/purunikk/admin/product_variant_options_api.php?product_id=' + encodeURIComponent(productId));
    if (!res.ok) return null;
    const variants = await res.json();
    if (!Array.isArray(variants) || !variants.length) return null;

    const groupedVariants = {};
    variants.forEach((v) => {
      const categoryName = (v.category_name || '').trim();
      if (categoryName) {
        if (!groupedVariants[categoryName]) groupedVariants[categoryName] = [];
        groupedVariants[categoryName].push(v);
      }
    });

    const spans = [];
    const selectedVariants = cartItem.selectedVariants || {};
    
    Object.entries(groupedVariants).forEach(([categoryName, items]) => {
      let displayValue = '-';
      
      if (selectedVariants && selectedVariants[categoryName]) {
        displayValue = selectedVariants[categoryName];
      } else {
        const isMotifsCategory = /(motif|warna|color)/i.test(categoryName);
        const isModelCategory = /model/i.test(categoryName);
        
        if (isMotifsCategory && cartItem.motif && cartItem.motif !== '-') {
          displayValue = cartItem.motif;
        } else if (isModelCategory && cartItem.model && cartItem.model !== '-') {
          displayValue = cartItem.model;
        } else {
          displayValue = items[0]?.option_name || '-';
        }
      }
      
      spans.push(`${categoryName}: ${displayValue}`);
    });

    return spans.length ? spans.join(' · ') : null;
  } catch (e) {
    return null;
  }
}

function renderSummary(){
  const list = document.getElementById('summary-list');
  const totalItemsEl = document.getElementById('total-items');
  const subtotalEl = document.getElementById('subtotal');
  if (!list) return;
  list.innerHTML = '';

  let totalQty = 0;
  let subtotal = 0;

  cart.forEach(item => {
    const price = parsePriceToNumber(item.price);
    const qty = Number(item.qty || 1);
    totalQty += qty;
    subtotal += price * qty;

    const div = document.createElement('div');
    div.className = 'summary-item';
    div.innerHTML = `
      <div>
        <div class="name">${item.name || 'Produk'}</div>
        <div class="meta" style="min-height: 1.2em;">
          <span class="variant-loading">Memuat varian...</span>
        </div>
      </div>
      <div class="price">${formatRupiah(price * qty)}</div>
      <div class="qty">x ${qty}</div>
    `;
    list.appendChild(div);

    const metaEl = div.querySelector('.meta');
    buildVariantDisplay(item.id, item).then(variantText => {
      metaEl.innerHTML = variantText || '';
    }).catch(() => {
      metaEl.innerHTML = '';
    });
  });

  totalItemsEl.textContent = `${totalQty} Pesanan`;
  subtotalEl.textContent = formatRupiah(subtotal);
}

renderSummary();

// Fake place order -> generate receipt and actions
const placeBtn = document.getElementById('place-order');
placeBtn?.addEventListener('click', ()=>{
  const nameEl = document.getElementById('fullname');
  const addrEl = document.getElementById('address');
  const phoneEl = document.getElementById('phone');
  const errName = document.getElementById('err-fullname');
  const errAddr = document.getElementById('err-address');
  const errPhone = document.getElementById('err-phone');
  const fullname = (nameEl?.value || '').trim();
  const address = (addrEl?.value || '').trim();
  const phone = (phoneEl?.value || '').trim();

  // reset errors
  if (errName) errName.textContent = '';
  if (errAddr) errAddr.textContent = '';
  if (errPhone) errPhone.textContent = '';

  let hasError = false;
  if (!fullname) { if (errName) errName.textContent = 'Wajib diisi'; nameEl?.focus(); hasError = true; }
  if (!address) { if (errAddr) errAddr.textContent = 'Wajib diisi'; if (!hasError) addrEl?.focus(); hasError = true; }
  if (!phone) { if (errPhone) errPhone.textContent = 'Wajib diisi'; if (!hasError) phoneEl?.focus(); hasError = true; }
  const digits = phone.replace(/\D/g,'');
  if (!hasError && digits.length < 9) { if (errPhone) errPhone.textContent = 'No. HP tidak valid'; phoneEl?.focus(); hasError = true; }
  if (hasError) return;

  const { text } = prepareReceipt(fullname, address, phone);

  const btnPrint = document.getElementById('receipt-print');
  const btnWa = document.getElementById('receipt-wa');
  const closeArea = document.getElementById('receipt-close');
  btnPrint && (btnPrint.onclick = () => {
    const modal = document.getElementById('receipt-modal');
    // Pastikan modal terlihat sebelum print 
    modal?.classList.add('show');
    modal?.setAttribute('aria-hidden','false');
    // Beri waktu reflow singkat agar CSS print menangkap konten
    setTimeout(()=>{ window.print(); }, 50);
  });
  btnWa && (btnWa.onclick = () => {
    const wa = `https://wa.me/6285249746506?text=${text}`;
    window.open(wa, '_blank');
  });
  const modal = document.getElementById('receipt-modal');
  closeArea && (closeArea.onclick = () => {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden','true');
  });
});

// Ensure content exists if user opens browser print directly
window.addEventListener('beforeprint', ()=>{
  const modal = document.getElementById('receipt-modal');
  const hasItems = Array.isArray(cart) && cart.length > 0;
  const nameEl = document.getElementById('fullname');
  const addrEl = document.getElementById('address');
  const phoneEl = document.getElementById('phone');
  const fullname = (nameEl?.value || '').trim();
  const address = (addrEl?.value || '').trim();
  const phone = (phoneEl?.value || '').trim();
  if (hasItems && fullname && address && phone) {
    prepareReceipt(fullname, address, phone);
  } else {
    // If not ready, at least reveal modal container to avoid blank
    modal?.classList.add('show');
    modal?.setAttribute('aria-hidden','false');
  }
});

// Kembalikan state setelah print
window.addEventListener('afterprint', ()=>{
  const modal = document.getElementById('receipt-modal');
  if (modal) {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden','false');
  }
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

  // Default: start in dark if nothing saved
  if (!saved) {
    setTheme('dark');
  }
}

// Initialize theme toggles ASAP
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initThemeToggle);
} else {
  initThemeToggle();
}
