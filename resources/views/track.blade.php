@extends('layouts.store')

@section('title', 'Track your order — Goddess Club Store')

@section('content')
<main class="tracking">
  <div class="eyebrow"><span></span>ON ITS WAY TO YOU</div>
  <h1>Follow the feeling.</h1>
  <p>Your next favorite thing is getting closer.</p>

  <form id="track-form">
    <input id="track-input" type="text" placeholder="Order number (e.g. GC-1024)" value="{{ $reference }}" required>
    <button class="primary" type="submit">TRACK ORDER <span>&nearr;</span></button>
  </form>

  <div id="track-result" @if (! $order) hidden @endif>
    @if ($order)
      <h3>Order {{ $order->reference() }}</h3>
      <p class="muted">{{ $order->invoice_number }} @if ($order->tracking_number) &middot; {{ $order->tracking_number }} @endif</p>
      <div class="timeline">
        @foreach ($timeline as $index => $step)
          <div class="{{ $step['done'] ? 'done' : '' }} {{ $step['current'] ? 'current' : '' }}">
            <b>{!! $step['done'] ? '&check;' : $index + 1 !!}</b>
            <section>
              <h3>{{ $step['label'] }}</h3>
              <p>{{ $step['description'] }}</p>
            </section>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</main>
@endsection
