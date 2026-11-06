<div class="overlay" id="size-guide-overlay" hidden>
  <div class="modal modal-card" role="dialog" aria-modal="true" aria-labelledby="size-guide-title">
    <button class="close" type="button" data-close-overlay aria-label="Close size guide">&times;</button>
    <div class="eyebrow"><span></span>FIT &amp; MEASUREMENTS</div>
    <h2 id="size-guide-title">Find your fit.</h2>
    <p>Relaxed unisex fit. Measurements in centimeters.</p>
    <table>
      <thead>
        <tr>
          <th>Size</th>
          <th>Chest</th>
          <th>Length</th>
        </tr>
      </thead>
      <tbody>
        @foreach (['S' => [96, 68], 'M' => [102, 70], 'L' => [108, 72], 'XL' => [114, 74], 'XXL' => [120, 76]] as $size => $measure)
          <tr>
            <td>{{ $size }}</td>
            <td>{{ $measure[0] }}</td>
            <td>{{ $measure[1] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
