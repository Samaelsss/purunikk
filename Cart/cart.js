// Cart/cart.js - render keranjang dari localStorage dan interaksi
// Struktur item yang didukung:
// { key, id, name, model, motif, price, qty, img/thumb/image }

(function(){
  const rupiah = (v) => 'Rp ' + Number(v||0).toLocaleString('id-ID');
  const getCart = () => JSON.parse(localStorage.getItem('cart')||'[]');
  const setCart = (arr) => localStorage.setItem('cart', JSON.stringify(arr));
  const getWishlist = () => JSON.parse(localStorage.getItem('wishlist')||'[]').map(String);
  const setWishlist = (arr) => localStorage.setItem('wishlist', JSON.stringify(arr));

  const listEl = document.getElementById('cart-list');
  const sumCountEl = document.getElementById('summary-count');
  const sumSubtotalEl = document.getElementById('summary-subtotal');
  const clearBtn = document.getElementById('clear-cart');
  const checkoutBtn = document.getElementById('checkout-btn');
  const toggleAllBtn = document.getElementById('toggle-select-all');
  const tpl = document.getElementById('cart-item-tpl');

  // Selection helpers
  const selectionKey = 'cartSelection';
  const getSelection = () => JSON.parse(localStorage.getItem(selectionKey)||'[]').map(String);
  const setSelection = (arr) => localStorage.setItem('cartSelection', JSON.stringify(arr.map(String)));

  function normalizeCartItem(it){
    return {
      key: it.key || String(it.id),
      id: it.id,
      name: it.name || 'Produk',
      model: it.model || '-',
      motif: it.motif || '-',
      price: Number(it.price || 0),
      qty: Math.max(1, Number(it.qty || 1)),
      thumb: it.thumb || it.img || it.image || 'Product/img/IMG_4099.jpg'
    };
  }

  // Toggle select all (single registration)
  toggleAllBtn?.addEventListener('click', ()=>{
    const c = getCart();
    const allKeys = c.map(it=> String(it.key||it.id));
    const sel = new Set(getSelection());
    const allSelected = allKeys.length && allKeys.every(k=> sel.has(k));
    if (allSelected) {
      setSelection([]);
    } else {
      setSelection(allKeys);
    }
    render();
  });

  // Proceed to Checkout (only selected items) — outside render loop
  checkoutBtn?.addEventListener('click', ()=>{
    const c = getCart();
    if (!c.length) {
      if (typeof showToast === 'function') showToast('Keranjang masih kosong.', 'warning');
      else alert('Keranjang masih kosong.');
      return;
    }
    const selection = getSelection();
    const items = selection.length ? c.filter(x=> selection.includes(String(x.key||x.id))) : c;
    if (!items.length) {
      if (typeof showToast === 'function') showToast('Pilih minimal satu barang untuk checkout.', 'warning');
      else alert('Pilih minimal satu barang untuk checkout.');
      return;
    }
    localStorage.setItem('checkoutSelection', JSON.stringify(items));
    window.location.href = '../Checkout/checkout.html';
  });

  function computeSubtotal(arr){
    return arr.reduce((acc, it)=> acc + Number(it.price||0)*Number(it.qty||0), 0);
  }

  function getSelectedItems(all){
    const selRaw = localStorage.getItem(selectionKey);
    const hasSelKey = selRaw !== null;
    const selSet = new Set((selRaw ? JSON.parse(selRaw) : []).map(String));
    if (!hasSelKey) return all; // default: all selected
    return all.filter(it => selSet.has(String(it.key||it.id)));
  }

  function updateSummaryForCurrent(){
    const all = getCart().map(normalizeCartItem);
    const selected = getSelectedItems(all);
    const subtotal = computeSubtotal(selected);
    sumCountEl.textContent = selected.length + ' Pesanan';
    sumSubtotalEl.textContent = rupiah(subtotal);
  }

  function render(){
    let cart = getCart().map(normalizeCartItem);
    // IMPORTANT: detect if selection key exists to differentiate default-all vs empty selection
    const selRaw = localStorage.getItem(selectionKey);
    const hasSelKey = selRaw !== null;
    let sel = new Set((selRaw ? JSON.parse(selRaw) : []).map(String));
    listEl.innerHTML = '';

    if (!cart.length){
      listEl.innerHTML = '<div class="empty-cart">Keranjangmu masih kosong.</div>';
      updateSummaryForCurrent();
      return;
    }

    cart.forEach((it)=>{
      const node = tpl.content.cloneNode(true);
      const el = node.querySelector('.cart-item');
      el.dataset.key = it.key;
      el.querySelector('.item-thumb').src = it.thumb;
      el.querySelector('.item-thumb').alt = it.name;
      el.querySelector('.item-name').textContent = it.name;
      const variantEl = el.querySelector('.item-variant');
      if (variantEl) {
        variantEl.innerHTML = `<span>Motif: ${it.motif}</span><span>Model: ${it.model}</span>`;
      }
      el.querySelector('.item-price').textContent = rupiah(it.price);
      el.querySelector('.qty-value').textContent = String(it.qty);
      el.querySelector('.item-total-value').textContent = rupiah(it.price * it.qty);

      // Selection checkbox
      const selCb = el.querySelector('.select-item');
      if (selCb) {
        const keyStr = String(it.key);
        selCb.checked = hasSelKey ? sel.has(keyStr) : true; // if no saved selection -> default all ON
        selCb.addEventListener('change', ()=>{
          const current = new Set(getSelection());
          if (selCb.checked) current.add(keyStr); else current.delete(keyStr);
          setSelection(Array.from(current));
          // update select-all button label immediately
          const c = getCart();
          const allKeys = c.map(x=> String(x.key||x.id));
          const allSelected = allKeys.length && allKeys.every(k=> current.has(k));
          if (toggleAllBtn) toggleAllBtn.textContent = allSelected ? 'Batal pilih semua' : 'Pilih semua';
          // update summary to reflect new selection
          updateSummaryForCurrent();
        });
      }

      // Qty handlers
      el.querySelector('.btn-minus').addEventListener('click', ()=>{
        let c = getCart();
        const idx = c.findIndex(x=> (x.key||String(x.id)) === it.key);
        if (idx>-1){ c[idx].qty = Math.max(1, Number(c[idx].qty||1)-1); setCart(c); render(); }
      });
      
      el.querySelector('.btn-plus').addEventListener('click', ()=>{
        let c = getCart();
        const idx = c.findIndex(x=> (x.key||String(x.id)) === it.key);
        if (idx>-1){ c[idx].qty = Number(c[idx].qty||1)+1; setCart(c); render(); }
      });

      // Remove item
      el.querySelector('.remove-item').addEventListener('click', ()=>{
        let c = getCart().filter(x=> (x.key||String(x.id)) !== it.key);
        setCart(c); render();
      });

      // Move to wishlist (toggle add)
      el.querySelector('.move-to-wishlist').addEventListener('click', ()=>{
        const idStr = String(it.id);
        let w = getWishlist();
        if (!w.includes(idStr)) w.push(idStr); else w = w.filter(x=>x!==idStr);
        setWishlist(w);
        if (typeof showToast === 'function') {
          showToast('Produk dipindahkan ke wishlist.', 'info');
        } else {
          alert('Produk dipindahkan ke wishlist.');
        }
      });

      listEl.appendChild(node);
    });

    // Update select-all button label based on current selection
    const allKeys = cart.map(it=> String(it.key||it.id));
    const currentSel = new Set(getSelection());
    const allSelected = allKeys.length && allKeys.every(k=> currentSel.has(k));
    if (toggleAllBtn) {
      toggleAllBtn.textContent = allSelected ? 'Batal pilih semua' : 'Pilih semua';
    }

    updateSummaryForCurrent();
  }

  // Clear cart
  clearBtn?.addEventListener('click', async ()=>{
    let proceed = true;
    if (typeof confirmModal === 'function') {
      proceed = await confirmModal({ title: 'Kosongkan Keranjang', message: 'Kosongkan semua item di keranjang?', confirmText: 'Kosongkan', cancelText: 'Batal' });
    } else {
      proceed = confirm('Kosongkan semua item di keranjang?');
    }
    if (proceed){
      setCart([]); render();
      if (typeof showToast === 'function') showToast('Keranjang dikosongkan.', 'info');
    }
  });

  // Go render on load
  render();
})();
