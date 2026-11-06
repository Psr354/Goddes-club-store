/* ==========================================================================
   Goddess Club Store — storefront interactions
   Mirrors the behaviour of the Figma reference build:
   cart drawer, quick add, size picker, promo code, checkout, live tracking.
   ========================================================================== */

(() => {
  'use strict';

  const state = {
    cart: [],
    count: 0,
    subtotal: 0,
    discount: 0,
    promo: null,
    size: 'M',
    quantity: 1,
  };

  const SIZES = ['S', 'M', 'L', 'XL', 'XXL'];

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

  const csrf = () => $('meta[name="csrf-token"]')?.content || '';

  const money = (value) => `$${Number(value || 0).toFixed(2)}`;

  /* ------------------------------------------------------------- toast */

  let toastTimer = null;

  function toast(message, type = 'info') {
    const el = $('#toast');
    if (!el) return;

    el.textContent = message;
    el.classList.toggle('error', type === 'error');
    el.classList.add('show');

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 3200);
  }

  /* --------------------------------------------------------------- api */

  async function api(path, method = 'GET', body = null) {
    const options = {
      method,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    };

    if (body) {
      options.headers['Content-Type'] = 'application/json';
      options.headers['X-CSRF-TOKEN'] = csrf();
      options.body = JSON.stringify(body);
    }

    const response = await fetch(path, options);

    let data = {};
    try {
      data = await response.json();
    } catch (error) {
      data = {};
    }

    return { ok: response.ok, status: response.status, data };
  }

  /* --------------------------------------------------------- overlays */

  function openOverlay(overlay) {
    if (!overlay) return;
    overlay.hidden = false;
    document.body.classList.add('is-locked');
  }

  function closeOverlay(overlay) {
    if (!overlay) return;
    overlay.hidden = true;
    document.body.classList.remove('is-locked');
  }

  function closeAllOverlays() {
    $$('.overlay').forEach((overlay) => {
      overlay.hidden = true;
    });
    document.body.classList.remove('is-locked');
  }

  /* ------------------------------------------------------- cart render */

  function renderCart() {
    const list = $('#cart-items');
    const subtotalEl = $('#cart-subtotal');
    const countEl = $('#cart-count');
    const bagCount = $('#bag-count');
    const titleEl = $('#bag-title');
    const checkoutBtn = $('#checkout-button');

    if (countEl) countEl.textContent = state.count;
    if (bagCount) bagCount.textContent = state.count;
    if (titleEl) titleEl.textContent = `Your bag (${state.count})`;

    const total = Math.max(0, state.subtotal - state.discount);
    if (subtotalEl) subtotalEl.textContent = money(total);

    if (checkoutBtn) checkoutBtn.disabled = state.cart.length === 0;

    if (!list) return;

    if (state.cart.length === 0) {
      list.innerHTML = '<p class="cart-empty">Your bag is waiting for a little good music.</p>';
      return;
    }

    list.innerHTML = state.cart
      .map(
        (item) => `
        <div class="cart-item">
          <img src="${item.image || ''}" alt="${item.name}">
          <div>
            <h3>${item.name}</h3>
            <p>SIZE ${item.size} &middot; ${money(item.price)}</p>
            <div class="quantity">
              <button type="button" data-cart-change="-1" data-variant="${item.variant_id}">&minus;</button>
              <span>${item.quantity}</span>
              <button type="button" data-cart-change="1" data-variant="${item.variant_id}">+</button>
            </div>
          </div>
        </div>`
      )
      .join('');
  }

  function applyCartPayload(data) {
    if (!data) return;
    if (Array.isArray(data.cart)) state.cart = data.cart;
    if (typeof data.cart_count === 'number') state.count = data.cart_count;
    if (typeof data.subtotal === 'number') state.subtotal = data.subtotal;
    renderCart();
  }

  async function loadCart() {
    const { data } = await api('/api/cart');
    applyCartPayload(data);
  }

  async function addToCart(productId, size, quantity = 1) {
    const { ok, data } = await api('/api/cart', 'POST', {
      product_id: Number(productId),
      size: size || null,
      quantity: Number(quantity),
    });

    applyCartPayload(data);

    if (!ok) {
      toast(data.message || 'Could not add that to your bag.', 'error');
      return false;
    }

    toast(data.message || 'Added to your bag.');
    return true;
  }

  async function changeQuantity(variantId, delta) {
    const item = state.cart.find((entry) => entry.variant_id === Number(variantId));
    if (!item) return;

    const next = item.quantity + Number(delta);

    if (next <= 0) {
      const { data } = await api('/api/cart/remove', 'POST', { variant_id: Number(variantId) });
      applyCartPayload(data);
      return;
    }

    const { data } = await api('/api/cart/update', 'POST', {
      variant_id: Number(variantId),
      quantity: next,
    });
    applyCartPayload(data);
  }

  /* ------------------------------------------------------------- promo */

  async function applyPromo(code) {
    const { ok, data } = await api('/api/cart/promo', 'POST', { code });

    const note = $('#promo-note');
    if (note) {
      note.textContent = data.message || '';
      note.classList.toggle('ok', Boolean(ok));
    }

    if (!ok) {
      state.discount = 0;
      state.promo = null;
      renderCart();
      toast(data.message || 'Invalid promo code.', 'error');
      return;
    }

    state.discount = Number(data.discount || 0);
    state.promo = data.code;
    renderCart();
    toast(data.message || 'Promo applied.');
  }

  /* ---------------------------------------------------------- checkout */

  function showCheckout() {
    const overlay = $('#checkout-overlay');
    if (!overlay) return;

    const signedIn = $('[data-authenticated="true"]') !== null;

    const form = $('#checkout-form');
    const guestNote = $('#checkout-guest-note');

    if (guestNote) guestNote.hidden = signedIn;
    if (form) form.hidden = !signedIn;

    openOverlay(overlay);
  }

  async function submitCheckout(form) {
    const payload = {
      recipient_name: form.recipient_name?.value.trim(),
      phone: form.phone?.value.trim(),
      full_address: form.full_address?.value.trim(),
      postal_code: form.postal_code?.value.trim(),
      email: form.email?.value.trim() || undefined,
      coupon_code: state.promo || undefined,
    };

    const errorBox = $('#checkout-error');
    if (errorBox) {
      errorBox.hidden = true;
      errorBox.textContent = '';
    }

    const { ok, data } = await api('/api/checkout', 'POST', payload);

    if (!ok) {
      if (errorBox) {
        errorBox.textContent = data.message || 'Checkout failed. Please try again.';
        errorBox.hidden = false;
      } else {
        toast(data.message || 'Checkout failed.', 'error');
      }
      return;
    }

    closeOverlay($('#checkout-overlay'));
    state.cart = [];
    state.count = 0;
    state.subtotal = 0;
    state.discount = 0;
    state.promo = null;
    renderCart();

    const success = $('#checkout-success');

    // Trigger Midtrans Snap popup modal if available
    if (data.snap_token && window.snap && typeof window.snap.pay === 'function') {
      toast('Membuka gerbang pembayaran Midtrans Snap...');
      window.snap.pay(data.snap_token, {
        onSuccess: function (result) {
          toast('Pembayaran berhasil dikonfirmasi.');
          setTimeout(() => {
            window.location.href = data.track_url || ('/track?order=' + encodeURIComponent(data.invoice_number));
          }, 1200);
        },
        onPending: function (result) {
          toast('Pesanan dibuat. Selesaikan pembayaran sesuai instruksi.');
          setTimeout(() => {
            window.location.href = data.track_url || ('/track?order=' + encodeURIComponent(data.invoice_number));
          }, 1200);
        },
        onError: function (result) {
          toast('Pembayaran gagal atau dibatalkan.', 'error');
          if (success) {
            success.innerHTML = `
              <h2>Payment Incomplete</h2>
              <p>Order <strong>${data.invoice_number}</strong> is placed, but payment was not completed.</p>
              <a class="primary" href="${data.track_url || '#'}">VIEW ORDER &amp; RETRY &rarr;</a>`;
            success.hidden = false;
            openOverlay($('#checkout-overlay'));
          }
        },
        onClose: function () {
          toast('Jendela pembayaran ditutup.');
          if (success) {
            success.innerHTML = `
              <h2>Order Pending Payment</h2>
              <p>Order <strong>${data.invoice_number}</strong> is placed. You can complete your Midtrans payment anytime.</p>
              <a class="primary" href="${data.track_url || '#'}">TRACK / COMPLETE PAYMENT &rarr;</a>`;
            success.hidden = false;
            openOverlay($('#checkout-overlay'));
          }
        }
      });
      return;
    }

    if (success) {
      const payButton = data.snap_url
        ? `<a class="primary" href="${data.snap_url}" style="display:inline-block;margin-bottom:12px;width:100%">PAY WITH MIDTRANS SNAP &rarr;</a>`
        : '';

      success.innerHTML = `
        <h2>You have great taste.</h2>
        <p>Order <strong>${data.invoice_number}</strong> is confirmed. Total: <strong>$${Number(data.grand_total || 0).toFixed(2)}</strong>.</p>
        ${payButton}
        <a class="text-link" href="${data.track_url || '#'}" style="display:inline-block">TRACK YOUR ORDER &nearr;</a>`;
      success.hidden = false;
      openOverlay($('#checkout-overlay'));
    }

    toast(data.message || 'Order received.');
  }

  /* ---------------------------------------------------------- listeners */

  document.addEventListener('click', (event) => {
    const target = event.target;

    // Open the cart drawer
    if (target.closest('#cart-button')) {
      event.preventDefault();
      loadCart();
      openOverlay($('#cart-overlay'));
      return;
    }

    // Close overlays
    if (target.closest('[data-close-overlay]')) {
      event.preventDefault();
      closeOverlay(target.closest('.overlay'));
      return;
    }

    // Clicking the backdrop closes the overlay, but not clicks inside the panel
    if (target.classList.contains('overlay')) {
      closeOverlay(target);
      return;
    }

    // Quick add
    const quickAdd = target.closest('[data-add-to-cart]');
    if (quickAdd) {
      event.preventDefault();
      event.stopPropagation();
      const size = quickAdd.dataset.size || state.size || 'M';
      addToCart(quickAdd.dataset.productId, size, 1);
      return;
    }

    // Size picker
    const sizeButton = target.closest('[data-size]');
    if (sizeButton) {
      state.size = sizeButton.dataset.size;
      $$('[data-size]').forEach((button) => {
        button.classList.toggle('active', button === sizeButton);
        button.classList.toggle('selected', button === sizeButton);
      });
      return;
    }

    // Quantity stepper on the product page
    const quantityButton = target.closest('[data-quantity-change]');
    if (quantityButton) {
      state.quantity = Math.max(1, state.quantity + Number(quantityButton.dataset.quantityChange));
      const label = $('#detail-quantity');
      if (label) label.textContent = state.quantity;
      return;
    }

    // Cart line quantity
    const cartChange = target.closest('[data-cart-change]');
    if (cartChange) {
      changeQuantity(cartChange.dataset.variant, cartChange.dataset.cartChange);
      return;
    }

    // Add to bag from the detail page
    if (target.closest('#detail-add')) {
      event.preventDefault();
      const productId = $('#detail-add')?.dataset.productId;
      if (productId) {
        addToCart(productId, state.size, state.quantity).then((added) => {
          if (added) openOverlay($('#cart-overlay'));
        });
      }
      return;
    }

    // Buy now
    if (target.closest('#detail-buy')) {
      event.preventDefault();
      const productId = $('#detail-buy')?.dataset.productId;
      if (productId) {
        addToCart(productId, state.size, state.quantity).then((added) => {
          if (added) showCheckout();
        });
      }
      return;
    }

    // Proceed to checkout
    if (target.closest('#checkout-button')) {
      event.preventDefault();
      showCheckout();
      return;
    }

    // Size guide
    if (target.closest('[data-size-guide-open]')) {
      event.preventDefault();
      openOverlay($('#size-guide-overlay'));
      return;
    }

    // Category filters
    const filter = target.closest('[data-category-filter]');
    if (filter) {
      event.preventDefault();
      const category = filter.dataset.categoryFilter;
      $$('[data-category-filter]').forEach((button) => {
        button.classList.toggle('active', button === filter);
      });
      $$('[data-product-card]').forEach((card) => {
        card.hidden = category !== 'all' && card.dataset.category !== category;
      });
      return;
    }

    // Category shortcuts (the big 3-column block)
    const categoryLink = target.closest('[data-category-link]');
    if (categoryLink) {
      const target_ = categoryLink.dataset.categoryLink;
      const filterButton = $(`[data-category-filter="${target_}"]`);
      if (filterButton) {
        filterButton.click();
      }
      const drop = document.getElementById('drop');
      if (drop) drop.scrollIntoView({ behavior: 'smooth' });
      return;
    }

    // Product thumbnails
    const thumb = target.closest('.thumbs button');
    if (thumb) {
      const image = thumb.querySelector('img');
      const main = $('#detail-image');
      if (image && main) {
        main.src = image.src;
        $$('.thumbs button').forEach((button) => button.classList.toggle('active', button === thumb));
      }
    }
  });

  // Promo code
  document.addEventListener('submit', (event) => {
    const form = event.target;

    if (form.id === 'promo-form') {
      event.preventDefault();
      const code = $('#promo-code')?.value.trim();
      if (code) applyPromo(code);
      return;
    }

    if (form.id === 'checkout-form') {
      event.preventDefault();
      submitCheckout(form);
      return;
    }

    if (form.id === 'newsletter-form') {
      event.preventDefault();
      const note = $('#newsletter-note');
      if (note) {
        note.textContent = "YOU'RE ON THE LIST. WELCOME TO THE CLUB.";
      }
      toast('Welcome to the club.');
      return;
    }

    if (form.id === 'track-form') {
      event.preventDefault();
      trackOrder($('#track-input')?.value.trim());
    }
  });

  // Escape closes the topmost overlay
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const open = $$('.overlay').filter((overlay) => !overlay.hidden).pop();
    if (open) closeOverlay(open);
  });

  /* --------------------------------------------------------- tracking */

  async function trackOrder(reference) {
    const box = $('#track-result');
    if (!box || !reference) return;

    const { ok, data } = await api('/api/track', 'POST', { order: reference });

    if (!ok) {
      box.innerHTML = `<p class="admin-note error">${data.message || 'Order not found.'}</p>`;
      box.hidden = false;
      return;
    }

    const steps = (data.timeline || [])
      .map(
        (step, index) => `
        <div class="${step.done ? 'done' : ''} ${step.current ? 'current' : ''}">
          <b>${step.done ? '&check;' : index + 1}</b>
          <section>
            <h3>${step.label}</h3>
            <p>${step.description}</p>
          </section>
        </div>`
      )
      .join('');

    box.innerHTML = `
      <h3>Order ${data.reference}</h3>
      <p class="muted">${data.invoice_number}${data.tracking_number ? ' &middot; ' + data.tracking_number : ''}</p>
      <div class="timeline">${steps}</div>`;
    box.hidden = false;
  }

  /* ------------------------------------------------------------- init */

  function init() {
    // Keep the size selection and the size guide in sync with the page.
    const selected = $('[data-size].active, [data-size].selected');
    if (selected) state.size = selected.dataset.size;

    const qty = $('#detail-quantity');
    if (qty) state.quantity = Number(qty.textContent) || 1;

    loadCart();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.GoddessStore = { state, loadCart, addToCart, toast, openOverlay, closeOverlay, SIZES };
})();
