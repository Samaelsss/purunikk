// ui/modal.js - themed confirm modal returning Promise<boolean>
(function(){
  function confirmModal({title='Konfirmasi', message='', confirmText='Ya', cancelText='Batal'}={}){
    return new Promise((resolve)=>{
      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop';
      const modal = document.createElement('div');
      modal.className = 'modal';
      const h = document.createElement('h4');
      h.textContent = title;
      const p = document.createElement('p');
      p.textContent = message;
      const actions = document.createElement('div');
      actions.className = 'actions';
      const btnCancel = document.createElement('button');
      btnCancel.className = 'btn';
      btnCancel.textContent = cancelText;
      const btnOk = document.createElement('button');
      btnOk.className = 'btn btn-primary';
      btnOk.textContent = confirmText;
      actions.appendChild(btnCancel); actions.appendChild(btnOk);
      modal.appendChild(h); modal.appendChild(p); modal.appendChild(actions);
      backdrop.appendChild(modal);
      document.body.appendChild(backdrop);
      requestAnimationFrame(()=> backdrop.classList.add('show'));
      function cleanup(){ backdrop.classList.remove('show'); setTimeout(()=> backdrop.remove(), 150); }
      btnCancel.addEventListener('click', ()=>{ cleanup(); resolve(false); });
      btnOk.addEventListener('click', ()=>{ cleanup(); resolve(true); });
      backdrop.addEventListener('click', (e)=>{ if(e.target===backdrop){ cleanup(); resolve(false); } });
      document.addEventListener('keydown', function onKey(e){ if(e.key==='Escape'){ document.removeEventListener('keydown', onKey); cleanup(); resolve(false);} });
    });
  }
  window.confirmModal = confirmModal;
})();
