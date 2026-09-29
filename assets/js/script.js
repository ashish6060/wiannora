(function(){
  const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>[...c.querySelectorAll(s)];
  const toast=$('#toast');
  function showToast(msg){if(!toast)return;toast.textContent=msg;toast.classList.add('show');clearTimeout(window._toast);window._toast=setTimeout(()=>toast.classList.remove('show'),2200)}
  const menu=$('.mobile-menu'), nav=$('#mainNav');
  menu?.addEventListener('click',()=>{const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',open)});
  const searchToggle=$('[data-search-toggle]'), searchPanel=$('[data-search-panel]'), searchClose=$('[data-search-close]'), searchInput=$('#siteSearch');
  searchToggle?.addEventListener('click',()=>{searchPanel.classList.toggle('open'); if(searchPanel.classList.contains('open')) searchInput?.focus()});
  searchClose?.addEventListener('click',()=>searchPanel.classList.remove('open'));
  searchInput?.addEventListener('keydown',e=>{if(e.key==='Enter'&&e.target.value.trim()) window.location.href='products.php?search='+encodeURIComponent(e.target.value.trim())});
  let bag=Number(localStorage.getItem('wiannoraBag')||0); const bagCount=$('.bag-count'); if(bagCount)bagCount.textContent=bag;
  $$('[data-add]').forEach(btn=>btn.addEventListener('click',()=>{bag++;localStorage.setItem('wiannoraBag',bag);if(bagCount)bagCount.textContent=bag;showToast('Added to your bag ✦')}));
  $('[data-bag]')?.addEventListener('click',()=>showToast(bag?`Your bag has ${bag} item${bag>1?'s':''}. Checkout will be connected later.`:'Your bag is empty.'));
  const filters=$$('.filter'), cards=$$('.shop-grid .product-card');
  function applyFilter(type){cards.forEach(card=>{card.style.display=(type==='all'||card.dataset.category===type)?'':'none'});filters.forEach(f=>f.classList.toggle('active',f.dataset.filter===type))}
  filters.forEach(f=>f.addEventListener('click',()=>applyFilter(f.dataset.filter)));
  const params=new URLSearchParams(location.search), cat=params.get('category'), search=params.get('search'); if(cat&&filters.length)applyFilter(cat); if(search&&cards.length){cards.forEach(card=>card.style.display=card.dataset.name.toLowerCase().includes(search.toLowerCase())?'':'none')}
  const sort=$('#sortProducts'), grid=$('#productGrid'); sort?.addEventListener('change',()=>{const list=[...grid.querySelectorAll('.product-card')];list.sort((a,b)=>{const pa=+a.dataset.price,pb=+b.dataset.price;return sort.value==='low'?pa-pb:sort.value==='high'?pb-pa:0});list.forEach(c=>grid.appendChild(c))});
  $('#contactForm')?.addEventListener('submit',e=>{e.preventDefault();e.target.reset();showToast('Thanks! Your message is ready to be connected to a backend.')});
  $$('.newsletter').forEach(f=>f.addEventListener('submit',()=>showToast('Thanks for joining the glow ✦')));
})();


/* =====================================================
   SHOPPING BAG POPUP
===================================================== */

document.addEventListener('DOMContentLoaded', function () {

    const bagButton = document.querySelector('[data-bag]');
    const cartPopup = document.querySelector('[data-cart-popup]');
    const cartOverlay = document.querySelector('[data-cart-overlay]');
    const cartClose = document.querySelector('[data-cart-close]');

    if (!bagButton || !cartPopup) {
        return;
    }


    function openCart() {

        cartPopup.classList.add('active');

        if (cartOverlay) {
            cartOverlay.classList.add('active');
        }

        cartPopup.setAttribute('aria-hidden', 'false');

        document.body.style.overflow = 'hidden';
    }


    function closeCart() {

        cartPopup.classList.remove('active');

        if (cartOverlay) {
            cartOverlay.classList.remove('active');
        }

        cartPopup.setAttribute('aria-hidden', 'true');

        document.body.style.overflow = '';
    }


    /* OPEN CART */

    bagButton.addEventListener('click', function () {
        openCart();
    });


    /* CLOSE CART */

    if (cartClose) {

        cartClose.addEventListener('click', function () {
            closeCart();
        });

    }


    if (cartOverlay) {

        cartOverlay.addEventListener('click', function () {
            closeCart();
        });

    }


    /* ESC KEY */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeCart();
        }

    });

});