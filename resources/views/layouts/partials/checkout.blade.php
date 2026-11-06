<div class="overlay" id="checkout-overlay" hidden>
  <div class="modal modal-card" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
    <button class="close" type="button" data-close-overlay aria-label="Close checkout">&times;</button>

    <div id="checkout-form-wrap">
      <div class="eyebrow"><span></span>SECURE CHECKOUT</div>
      <h2 id="checkout-title">You have great taste.</h2>
      <p>Your bag is ready. Tell us where to send it and we will keep the music moving.</p>

      <div class="form-error" id="checkout-error" hidden></div>

      <p id="checkout-guest-note" class="admin-note" @if (auth()->check()) hidden @endif>
        <a href="{{ route('login') }}" style="color:inherit;text-decoration:underline">Sign in</a>
        to place your order and keep track of every drop.
      </p>

      <form id="checkout-form" @if (auth()->guest()) hidden @endif>
        <label class="field">Email address
          <input type="email" name="email" value="{{ auth()->user()?->email }}" placeholder="you@email.com" required>
        </label>

        <label class="field">Recipient name
          <input type="text" name="recipient_name" value="{{ auth()->user()?->name }}" placeholder="Your name" required>
        </label>

        <label class="field">Phone number
          <input type="tel" name="phone" placeholder="+62 812 3456 7890" required>
        </label>

        <label class="field">Shipping address
          <textarea name="full_address" placeholder="Street, city, postal code" required></textarea>
        </label>

        <div class="field-row">
          <label class="field">Postal code
            <input type="text" name="postal_code" placeholder="12730" required>
          </label>
          <label class="field">Payment
            <select name="payment_method" disabled>
              <option>Midtrans — QRIS / GoPay / VA / Card</option>
            </select>
          </label>
        </div>

        <div class="payment-note">
          <span>&#9672;</span>
          <div>
            <strong>Payment powered by Midtrans Snap (Sandbox)</strong>
            <small>QRIS, GoPay, ShopeePay, Virtual Account (BCA/Mandiri/BNI/BRI), &amp; Card.</small>
          </div>
        </div>

        <button class="primary" type="submit" style="width:100%">PLACE ORDER &amp; PAY <span>&nearr;</span></button>
      </form>
    </div>

    <div id="checkout-success" hidden></div>
  </div>
</div>
