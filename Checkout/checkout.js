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

// --- Receipt helpers ---
let lastReceiptText = '';
function prepareReceipt(fullname, address, phone){
  const messageLines = cart.map(c=>`• ${c.name} (${c.model} - ${c.motif}) x${c.qty} = ${formatRupiah(parsePriceToNumber(c.price)*c.qty)}`);
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
        <div class="meta">Model: ${item.model || '-'} · Motif: ${item.motif || '-'}</div>
      </div>
      <div class="price">${formatRupiah(price * qty)}</div>
      <div class="qty">x ${qty}</div>
    `;
    list.appendChild(div);
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
    // Pastikan modal terlihat sebelum print (menghindari PDF kosong)
    modal?.classList.add('show');
    modal?.setAttribute('aria-hidden','false');
    // Beri waktu reflow singkat agar CSS print menangkap konten
    setTimeout(()=>{ window.print(); }, 50);
  });
  btnWa && (btnWa.onclick = () => {
    const wa = `https://wa.me/?text=${text}`;
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
