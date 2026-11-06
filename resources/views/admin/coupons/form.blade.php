@extends('layouts.admin')

@section('title', ($coupon->exists ? 'Edit coupon' : 'New coupon').' — Goddess Club Admin')

@section('content')
<div class="admin-title">
  <div>
    <div class="eyebrow"><span></span>PROMOTIONS</div>
    <h1>{{ $coupon->exists ? 'Edit coupon' : 'New coupon' }}</h1>
  </div>
  <div class="admin-actions">
    <a class="admin-btn ghost" href="{{ route('admin.coupons.index') }}">&larr; BACK TO LIST</a>
  </div>
</div>

<form class="admin-form" method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
  @csrf
  @if ($coupon->exists)
    @method('PUT')
  @endif

  <div class="admin-panel">
    <h2>Coupon details</h2>

    <div class="admin-grid-2">
      <label class="field">CODE
        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" placeholder="GODDESS10" required>
      </label>

      <label class="field">TYPE
        <select name="type" required>
          <option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Percentage</option>
          <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount</option>
        </select>
      </label>
    </div>

    <div class="admin-grid-2">
      <label class="field">VALUE
        <input type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon->value) }}" required>
      </label>

      <label class="field">MIN ORDER AMOUNT
        <input type="number" step="0.01" min="0" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}">
      </label>
    </div>

    <div class="admin-grid-2">
      <label class="field">MAX DISCOUNT
        <input type="number" step="0.01" min="0" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}">
      </label>

      <label class="field">USAGE LIMIT
        <input type="number" min="0" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}">
      </label>
    </div>

    <div class="admin-grid-2">
      <label class="field">STARTS AT
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}">
      </label>

      <label class="field">EXPIRES AT
        <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}">
      </label>
    </div>

    <label class="field" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active)) style="width:auto;margin:0;padding:0">
      ACTIVE
    </label>
  </div>

  <div class="admin-actions">
    <button class="admin-btn" type="submit">{{ $coupon->exists ? 'SAVE CHANGES' : 'CREATE COUPON' }}</button>
    <a class="admin-btn ghost" href="{{ route('admin.coupons.index') }}">CANCEL</a>
  </div>
</form>
@endsection
