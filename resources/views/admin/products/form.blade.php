@extends('layouts.admin')

@section('title', ($product->exists ? 'Edit product' : 'New product').' — Goddess Club Admin')

@section('content')
<div class="admin-title">
  <div>
    <div class="eyebrow"><span></span>CATALOGUE</div>
    <h1>{{ $product->exists ? 'Edit product' : 'New product' }}</h1>
  </div>
  <div class="admin-actions">
    <a class="admin-btn ghost" href="{{ route('admin.products.index') }}">&larr; BACK TO LIST</a>
  </div>
</div>

<form class="admin-form" method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
  @csrf
  @if ($product->exists)
    @method('PUT')
  @endif

  <div class="admin-panel">
    <h2>Basics</h2>

    <label class="field">NAME
      <input type="text" name="name" value="{{ old('name', $product->name) }}" required>
    </label>

    <div class="admin-grid-2">
      <label class="field">CATEGORY
        <select name="category_id" required>
          <option value="">Select category…</option>
          @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
          @endforeach
        </select>
      </label>

      <label class="field">SLUG
        <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" required>
      </label>
    </div>

    <label class="field">DESCRIPTION
      <textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea>
    </label>

    <div class="admin-grid-2">
      <label class="field">PRICE (USD)
        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required>
      </label>

      <label class="field">STOCK
        <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}" required>
      </label>
    </div>

    <div class="admin-grid-2">
      <label class="field">STATUS
        <select name="status" required>
          @foreach (['active', 'draft', 'archived'] as $status)
            <option value="{{ $status }}" @selected(old('status', $product->status) === $status)>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </label>

      <label class="field">BADGE / TAG
        <input type="text" name="tag" value="{{ old('tag', $product->tag) }}" placeholder="Bestseller / Limited edition / Fan favorite">
      </label>
    </div>

    <div class="admin-grid-2">
      <label class="field">IMAGE URL
        <input type="text" name="image" value="{{ old('image', $product->image) }}" placeholder="https://…">
      </label>

      <label class="field">VISUAL KIND
        <input type="text" name="kind" value="{{ old('kind', $product->kind) }}" placeholder="tee / vinyl / accessory">
      </label>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Sizes &amp; variants</h2>
    <p class="muted" style="margin-bottom:16px">One row per size. Leave the size blank to skip the row.</p>

    @php
      $existing = $product->exists ? $product->variants : collect();
      $rows = max(3, $existing->count());
    @endphp

    @for ($i = 0; $i < $rows; $i++)
      @php
        $variant = $existing->get($i);
        $sizeValue = old("sizes.$i", $variant?->size());
      @endphp
      <div class="variant-row">
        <label class="field" style="margin:0">SIZE
          <input type="text" name="sizes[]" value="{{ $sizeValue === 'One Size' && ! $variant ? '' : $sizeValue }}" placeholder="S / M / L">
        </label>
        <label class="field" style="margin:0">PRICE
          <input type="number" step="0.01" min="0" name="variant_prices[]" value="{{ old("variant_prices.$i", $variant?->price ?? $product->price) }}">
        </label>
        <label class="field" style="margin:0">STOCK
          <input type="number" min="0" name="variant_stocks[]" value="{{ old("variant_stocks.$i", $variant?->stock ?? $product->stock) }}">
        </label>
      </div>
    @endfor

    <input type="hidden" name="weight_gram" value="300">
  </div>

  <div class="admin-actions">
    <button class="admin-btn" type="submit">{{ $product->exists ? 'SAVE CHANGES' : 'CREATE PRODUCT' }}</button>
    <a class="admin-btn ghost" href="{{ route('admin.products.index') }}">CANCEL</a>
  </div>
</form>
@endsection
