@extends('layouts.store')

@section('title', $product->name.' — Goddess Club Store')

@section('content')
<main class="detail">
  <a class="text-link" href="{{ route('home') }}#drop">&larr; BACK TO THE COLLECTION</a>

  <div class="detail-grid">
    <div>
      <img class="detail-image" id="detail-image" src="{{ $product->image }}" alt="{{ $product->name }}">
      <div class="thumbs">
        <button class="active" type="button"><img src="{{ $product->image }}" alt="Product view 1" style="object-position:50% 50%"></button>
        <button type="button"><img src="{{ $product->image }}" alt="Product view 2" style="object-position:30% 60%"></button>
        <button type="button"><img src="{{ $product->image }}" alt="Product view 3" style="object-position:70% 35%"></button>
      </div>
    </div>

    <div class="detail-info">
      <div class="eyebrow"><span></span>{{ $product->category->name }} / THE GODDESS COLLECTION</div>
      <h1>{{ $product->name }}</h1>
      <div class="price">${{ number_format($product->price, 2) }}</div>
      <p class="stock">&bull; Only {{ $product->stock }} left &mdash; make it yours.</p>

      <div class="size-label">
        <span>SELECT SIZE</span>
        <button type="button" data-size-guide-open>Size guide &nearr;</button>
      </div>

      <div class="sizes" role="group" aria-label="Select size">
        @php $activeSize = $product->variants->first()->size() ?? 'M'; @endphp
        @foreach ($product->variants as $variant)
          <button type="button" data-size="{{ $variant->size() }}" class="{{ $variant->size() === $activeSize ? 'active' : '' }}">{{ $variant->size() }}</button>
        @endforeach
      </div>

      <div class="purchase">
        <div class="quantity">
          <button type="button" data-quantity-change="-1" aria-label="Decrease quantity">&minus;</button>
          <span id="detail-quantity">{{ $activeSize === 'One Size' ? 1 : 1 }}</span>
          <button type="button" data-quantity-change="1" aria-label="Increase quantity">+</button>
        </div>
        <button class="primary" type="button" id="detail-add" data-product-id="{{ $product->id }}">ADD TO BAG <span>&nearr;</span></button>
      </div>

      <button class="buy-now" type="button" id="detail-buy" data-product-id="{{ $product->id }}">BUY NOW</button>

      <p class="shipping-note">WORLDWIDE SHIPPING &middot; MADE TO BE LOVED</p>

      <details>
        <summary>Description <span>+</span></summary>
        <p>{{ $product->description ?: 'A little piece of the feeling. Official Reality Club merchandise, made in small batches with care.' }}</p>
      </details>
      <details>
        <summary>Shipping info <span>+</span></summary>
        <p>Orders ship within 3&ndash;5 business days. Worldwide delivery available. Shipping is calculated at checkout.</p>
      </details>
      <details>
        <summary>Care instructions <span>+</span></summary>
        <p>Wash cold, inside out. Do not bleach. Hang dry and keep the memories alive.</p>
      </details>
    </div>
  </div>

  @if ($related->isNotEmpty())
    <div class="related">
      @foreach ($related as $item)
        <article class="product-card">
          <div class="product-image {{ $item->kind }}">
            @if ($item->label())
              <span class="badge {{ strcasecmp((string) $item->tag, 'Limited edition') === 0 ? 'gold' : '' }}">{{ $item->label() }}</span>
            @endif
            <img src="{{ $item->image }}" alt="{{ $item->name }}" loading="lazy">
            <a class="product-image-link" href="{{ route('product.show', $item->slug) }}" aria-label="{{ $item->name }}"></a>
            <button class="quick-add" type="button" data-add-to-cart data-product-id="{{ $item->id }}" data-size="{{ $item->variants->first()->size() ?? 'M' }}">QUICK ADD <span>+</span></button>
          </div>
          <div class="product-meta">
            <span>{{ $item->category->name }}</span>
            <span>VOL. 01</span>
          </div>
          <div class="product-title">
            <a href="{{ route('product.show', $item->slug) }}">{{ $item->name }}</a>
            <span>${{ number_format($item->price, 2) }}</span>
          </div>
        </article>
      @endforeach
    </div>
  @endif
</main>
@endsection
