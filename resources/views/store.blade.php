@extends('layouts.store')

@section('title', 'Goddess Club Store — Official Reality Club Merch')

@section('content')
<main id="top">
  <section class="hero" id="drops">
    <img class="hero-photo" src="https://images.unsplash.com/photo-1560527341-725efe8887d9?auto=format&fit=crop&w=1800&q=85" alt="Atmospheric red-lit live music venue">
    <div class="hero-shade"></div>
    <div class="hero-content">
      <div class="eyebrow"><span></span>THE OFFICIAL REALITY CLUB MERCH STORE</div>
      <h1>For the ones<br>who <em>feel it all.</em></h1>
      <p>More than music. A little piece of the feeling.<br>Wear it loud. Keep it close.</p>
      <button class="primary" type="button" data-category-link="all">SHOP THE LIMITED DROP <span>&nearr;</span></button>
      <div class="hero-foot">INDEPENDENT MUSIC. TIMELESS CONNECTION.</div>
    </div>
    <div class="hero-stamp">REALITY<br><b>CLUB</b><span>EST. 2016 &middot; JAKARTA</span></div>
    <div class="hero-bottom">
      <span>THE GODDESS COLLECTION &mdash; VOL. 01</span>
      <span>SCROLL TO EXPLORE &darr;</span>
    </div>
  </section>

  <div class="ticker" aria-label="Store highlights">
    <span>MADE FOR THE FRONT ROW</span>
    <span>&#10022;</span>
    <span>WORN LONG AFTER THE ENCORE</span>
    <span>&#10022;</span>
    <span>REALITY CLUB, FOREVER</span>
    <span>&#10022;</span>
    <span>MADE FOR THE FRONT ROW</span>
  </div>

  <section class="collection" id="drop">
    <div class="section-top">
      <div>
        <div class="eyebrow muted">THE GOOD STUFF DOESN&rsquo;T STAY LONG</div>
        <h2>The latest <em>obsessions.</em></h2>
      </div>
      <button class="text-link" type="button" data-category-filter="all">SHOP ALL MERCH &nearr;</button>
    </div>

    <div class="filters" role="tablist" aria-label="Product categories">
      <button class="active" type="button" data-category-filter="all" role="tab">ALL RELEASES</button>
      <button type="button" data-category-filter="apparel" role="tab">APPAREL</button>
      <button type="button" data-category-filter="music" role="tab">MUSIC &amp; VINYL</button>
      <button type="button" data-category-filter="accessories" role="tab">ACCESSORIES</button>
      <span>SMALL BATCHES. BIG FEELINGS.</span>
    </div>

    <div class="product-grid" id="product-grid">
      @forelse ($categories as $category)
        @foreach ($category->products as $product)
          @php
            $defaultSize = $product->variants->first()->size() ?? 'M';
            $badge = $product->label();
            $isGold = strcasecmp((string) $product->tag, 'Limited edition') === 0;
          @endphp
          <article class="product-card" data-product-card data-category="{{ $category->slug }}">
            <div class="product-image {{ $product->kind }}">
              @if ($badge)
                <span class="badge {{ $isGold ? 'gold' : '' }}">{{ $badge }}</span>
              @endif
              <img src="{{ $product->image }}" alt="{{ $product->name }}" loading="lazy">
              <a class="product-image-link" href="{{ route('product.show', $product->slug) }}" aria-label="{{ $product->name }}"></a>
              @if ($product->kind === 'tee')
                <div class="shirt-print">REALITY<br><i>Club</i><small>THE GODDESS TOUR</small></div>
              @endif
              <button class="quick-add" type="button" data-add-to-cart data-product-id="{{ $product->id }}" data-size="{{ $defaultSize }}">QUICK ADD <span>+</span></button>
            </div>
            <div class="product-meta">
              <span>{{ $category->name }}</span>
              <span>VOL. 01</span>
            </div>
            <div class="product-title">
              <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
              <span>${{ number_format($product->price, 2) }}</span>
            </div>
          </article>
        @endforeach
      @empty
        <p class="product-empty">NOTHING HERE YET. THE NEXT DROP IS ALMOST READY.</p>
      @endforelse
    </div>
  </section>

  <section class="categories">
    <div class="category" data-category-link="apparel">
      <span>01 / WEAR THE FEELING</span>
      <h3>Apparel &amp; wear <b>&nearr;</b></h3>
    </div>
    <div class="category" data-category-link="music">
      <span>02 / KEEP IT ON REPEAT</span>
      <h3>Music &amp; vinyl <b>&nearr;</b></h3>
    </div>
    <div class="category" data-category-link="accessories">
      <span>03 / THE LITTLE THINGS</span>
      <h3>Accessories <b>&nearr;</b></h3>
    </div>
  </section>

  <section class="fanclub" id="story">
    <div class="fan-star">&#10035;</div>
    <div>
      <div class="eyebrow">YOU&rsquo;RE ONE OF US NOW.</div>
      <h2>Good music.<br><em>Even better company.</em></h2>
      <p>Join the club. First dibs on new drops, little surprises,<br>and letters from the band. No noise, just the good stuff.</p>
    </div>
    <form id="newsletter-form">
      <label for="newsletter-email">A LITTLE CLOSER TO THE CLUB</label>
      <div class="email-input">
        <input id="newsletter-email" type="email" placeholder="Your email address" required>
        <button type="submit" aria-label="Join newsletter">&nearr;</button>
      </div>
      <small id="newsletter-note">BY SIGNING UP, YOU AGREE TO OUR PRIVACY POLICY.</small>
    </form>
  </section>
</main>
@endsection
