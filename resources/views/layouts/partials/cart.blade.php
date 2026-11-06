<div class="overlay" id="cart-overlay" hidden>
  <aside class="cart" aria-label="Shopping bag">
    <div class="drawer-heading">
      <h2 id="bag-title">Your bag (0)</h2>
      <button type="button" data-close-overlay aria-label="Close cart">&times;</button>
    </div>

    <div class="cart-items" id="cart-items">
      <p class="cart-empty">Your bag is waiting for a little good music.</p>
    </div>

    <form class="promo" id="promo-form" autocomplete="off">
      <input id="promo-code" name="code" type="text" placeholder="Promo code" aria-label="Promo code">
      <button type="submit">APPLY</button>
    </form>
    <small class="promo-note" id="promo-note">Try GODDESS10 for 10% off</small>

    <div class="subtotal">
      <span>Subtotal</span>
      <span id="cart-subtotal">$0.00</span>
    </div>
    <p class="cart-note">Shipping calculated at checkout.</p>

    <button class="primary" id="checkout-button" type="button" disabled>PROCEED TO CHECKOUT <span>&nearr;</span></button>
  </aside>
</div>
