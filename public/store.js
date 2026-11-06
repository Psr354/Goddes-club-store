const money = value => `$${Number(value).toFixed(2)}`;
const grid = document.querySelector('#product-grid');
let cart = [];
let cartLoaded = false;
let detailProduct = null;
let detailQuantity = 1;
let selectedSize = 'M';

// --- Cart API ---
async function loadCart() {
  if (cartLoaded) return;
  const res = await fetch('/api/cart');
  const data = await res.json();
  cart = data.cart || [];
  cartLoaded = true;
  renderCart();
  refreshCartCount();
}

async function refreshCartCount() {
  const res = await fetch('/api/cart');
  const data = await res.json();
  if (res.ok) document.querySelector('#cart-count').textContent = data.cart_count || 0;
}

async function api(path, method = 'GET', body = null) {
  const headers = { 'Content-Type': 'application/json' };
  if (body) {
    headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content;
  }
  const res = await fetch(path, { method, headers, body: body ? JSON.stringify(body) : null });
  return await res.json();
}

// --- Cart actions ---
async function addToCart(productId, size = 'M') {
  const existing = cart.find(item => item.id === productId && item.size === size);
  if (existing) {
    existing.quantity++;
  } else {
    const productRes = await fetch(`/api/product/${productId}`);
    const product = await productRes.json();
    if (product) {
      cart.push({ ...product, quantity: 1, size });
    }
  }
  await refreshCartCount();
  renderCart();
  toast(`${product?.name || 'Product'} added to your bag`);
}

async function removeFromCart(itemId, size) {
  cart = cart.filter(item => !(item.id === itemId && item.size === size));
  await refreshCartCount();
  renderCart();
}

async function updateCartQuantity(itemId, size, quantity) {
  const item = cart.find(i => i.id === itemId && i.size === size);
  if (item) item.quantity = Math.max(1, quantity);
  await refreshCartCount();
  renderCart();
}

async function clearCart() {
  cart = [];
  await refreshCartCount();
  renderCart();
}

// --- Checkout ---
async function checkout() {
  const res = await fetch('/api/checkout', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
    },
    body: JSON.stringify({ items: cart })
  });
  const data = await res.json();
  if (data.success) {
    toast(data.message || 'Order received');
    cart = [];
    renderCart();
  } else {
    toast(data.message || 'Checkout failed');
  }
}

// --- Promo code ---
document.querySelector('#promo-apply')?.addEventListener('click', () => {
  const code = document.querySelector('#promo-code').value.trim();
  fetch('/api/cart', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
    },
    body: JSON.stringify({ coupon_code: code })
  }).then(res => res.json()).then(data => {
    if (data.success) {
      toast(data.message || 'Promo applied: 10% off');
    } else {
      toast(data.message || 'Invalid coupon');
    }
  });
});

// --- Newsletter ---
document.querySelector('#newsletter-email')?.closest('form')?.addEventListener('submit', event => {
  event.preventDefault();
  toast('Welcome to the club.');
});

// --- Product gallery thumb ---
document.addEventListener('click', event => {
  const thumb = event.target.closest('.detail-thumbs button');
  if (thumb) {
    const img = thumb.querySelector('img');
    if (img) {
      document.querySelector('#detail-image').src = img.src;
      document.querySelectorAll('.detail-thumbs button').forEach(b => b.classList.toggle('active', b === thumb));
    }
    return;
  }
  const guideBtn = event.target.closest('#open-size-guide');
  if (guideBtn) {
    document.querySelector('#size-guide-modal').hidden = false;
    return;
  }
  const guideClose = event.target.closest('[data-close="size-guide"]');
  if (guideClose) {
    document.querySelector('#size-guide-modal').hidden = true;
    return;
  }
  const catLink = event.target.closest('[data-category-link]');
  if (catLink) {
    event.preventDefault();
    const category = catLink.dataset.categoryLink;
    document.querySelectorAll('[data-category]').forEach(button => button.classList.remove('active'));
    const activeBtn = document.querySelector(`[data-category="${category}"]`);
    if (activeBtn) activeBtn.classList.add('active');
    renderProducts();
    document.querySelector('#shop')?.scrollIntoView();
    return;
  }
});

// --- Size selection ---
document.addEventListener('click', event => {
  const add = event.target.closest('[data-add]');
  if (add) {
    event.stopPropagation();
    addToCart(Number(add.dataset.add), selectedSize);
    return;
  }
  const detail = event.target.closest('[data-detail]');
  if (detail) {
    openDetail(products.find(p => p.id === Number(detail.dataset.detail)));
    return;
  }
  const size = event.target.closest('[data-size]');
  if (size) {
    selectedSize = size.dataset.size;
    document.querySelectorAll('[data-size]').forEach(button => button.classList.toggle('selected', button === size));
  }
  const detailQuantityButton = event.target.closest('[data-detail-quantity]');
  if (detailQuantityButton) {
    detailQuantity = Math.max(1, detailQuantity + Number(detailQuantityButton.dataset.detailQuantity));
    document.querySelector('#detail-quantity').textContent = detailQuantity;
  }
  const quantity = event.target.closest('[data-quantity]');
  if (quantity) {
    updateCartQuantity(Number(quantity.dataset.quantity), quantity.dataset.size || 'M', Number(quantity.dataset.change));
  }
  if (event.target.matches('[data-close]')) closePanels();
});

// --- Init ---
document.querySelector('#cart-button')?.addEventListener('click', openCart);
document.querySelector('#overlay')?.addEventListener('click', closePanels);
document.querySelector('#detail-add')?.addEventListener('click', () => addToCart(detailProduct?.id, selectedSize));
document.querySelector('#detail-buy')?.addEventListener('click', () => addToCart(detailProduct?.id, selectedSize).then(() => document.querySelector('#checkout-button').click()));

// Load products from API on init
async function initProducts() {
  const res = await fetch('/api/products');
  const productsData = await res.json();
}
initProducts();
