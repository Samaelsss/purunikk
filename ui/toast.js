// ui/toast.js - simple themed toast
(function(){
  function ensureContainer(){
    let c = document.querySelector('.toast-container');
    if(!c){
      c = document.createElement('div');
      c.className = 'toast-container';
      document.body.appendChild(c);
    }
    return c;
  }
  function showToast(message, type='info', opts={}){
    const c = ensureContainer();
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    const icon = document.createElement('span');
    icon.className = 'icon';
    icon.textContent = type==='success'?'✓': type==='error'?'!':'ℹ';
    const msg = document.createElement('div');
    msg.className = 'msg';
    msg.textContent = message;
    const close = document.createElement('button');
    close.className = 'close';
    close.innerHTML = '&times;';
    close.addEventListener('click', ()=> dismiss());
    t.appendChild(icon); t.appendChild(msg); t.appendChild(close);
    c.appendChild(t);
    const duration = opts.duration || 3000;
    let timer = setTimeout(dismiss, duration);
    function dismiss(){
      clearTimeout(timer);
      t.style.animation = 'toast-out .2s ease forwards';
      setTimeout(()=> t.remove(), 180);
    }
    return { dismiss };
  }
  window.showToast = showToast;
})();
