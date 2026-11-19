@extends('layouts.admin')

@section('title', ($product->exists ? 'Edit: '.$product->name : 'New product').' — Goddess Club Admin')

@push('styles')
<style>
  .admin-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(320px, 1fr);
    gap: 24px;
    align-items: start;
    margin-top: 10px;
  }
  @media (max-width: 980px) {
    .admin-form-grid {
      grid-template-columns: 1fr;
    }
  }

  /* Image Upload & Preview Styling */
  .media-box {
    background: #14120f;
    border: 1px solid #463d2d;
    border-radius: 4px;
    padding: 18px;
  }
  .image-preview-wrapper {
    width: 100%;
    height: 280px;
    background: #0d0c0a;
    border: 1px dashed #4a402e;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    margin-bottom: 16px;
    transition: border-color 0.2s;
  }
  .image-preview-wrapper img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
  }
  .preview-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #786d56;
    text-align: center;
    padding: 20px;
  }
  .preview-empty-state svg {
    margin-bottom: 10px;
    stroke: #786d56;
  }
  .preview-empty-state span {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1px;
    color: #a79a82;
  }
  .preview-empty-state small {
    font-size: 10px;
    color: #786d56;
    margin-top: 4px;
  }

  .upload-dropzone {
    border: 1px dashed #574930;
    background: #191713;
    padding: 18px 14px;
    text-align: center;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .upload-dropzone:hover, .upload-dropzone.dragover {
    border-color: #e5a93b;
    background: #231f18;
  }
  .upload-dropzone-icon {
    margin-bottom: 8px;
    display: inline-block;
  }
  .upload-dropzone-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    color: #e5a93b;
    margin-bottom: 4px;
  }
  .upload-dropzone-subtitle {
    font-size: 10px;
    color: #8e836e;
    margin: 0;
  }

  .file-selected-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    background: #242019;
    border: 1px solid #574930;
    padding: 10px 14px;
    border-radius: 3px;
    margin-top: 10px;
    font-size: 11px;
  }
  .file-selected-bar span {
    color: #e5a93b;
    word-break: break-all;
  }
  .file-selected-bar button {
    background: none;
    border: none;
    color: #a79a82;
    cursor: pointer;
    font-size: 14px;
    padding: 0 4px;
    line-height: 1;
  }
  .file-selected-bar button:hover {
    color: #d92323;
  }

  /* Variant Rows Styling */
  .variant-header-row {
    display: grid;
    grid-template-columns: 1.3fr 1.1fr 1fr 42px;
    gap: 12px;
    padding: 0 4px 8px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.8px;
    color: #a79a82;
    border-bottom: 1px solid #332d22;
    margin-bottom: 12px;
  }
  .variant-item-row {
    display: grid;
    grid-template-columns: 1.3fr 1.1fr 1fr 42px;
    gap: 12px;
    align-items: center;
    margin-bottom: 10px;
  }
  .variant-item-row .field {
    margin-bottom: 0 !important;
  }
  .variant-del-btn {
    background: #1b1814;
    border: 1px solid #4a402e;
    color: #8e836e;
    height: 40px;
    width: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.15s;
    padding: 0;
  }
  .variant-del-btn:hover {
    border-color: #d92323;
    color: #ef4444;
    background: #2a1515;
  }

  /* Action Sidebar Card */
  .publish-card {
    background: #191713;
    border: 1px solid #463d2d;
    padding: 22px;
    border-radius: 2px;
  }
  .publish-card .btn-submit {
    width: 100%;
    padding: 14px;
    font-size: 12px;
    margin-bottom: 10px;
  }
  .publish-card .btn-cancel {
    width: 100%;
    padding: 12px;
    text-align: center;
    box-sizing: border-box;
  }
  .meta-detail-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    padding: 8px 0;
    border-bottom: 1px solid #2e281e;
    color: #8e836e;
  }
  .meta-detail-row b {
    color: #cbb58b;
    font-weight: 500;
  }
</style>
@endpush

@section('content')
<div class="admin-title">
  <div>
    <div class="eyebrow muted"><span></span>KATALOG PRODUK</div>
    <h1>{{ $product->exists ? 'Edit: '.$product->name : 'Tambah Produk Baru' }}</h1>
    <p class="muted">{{ $product->exists ? 'ID: #'.$product->id.' · Terakhir diubah '.$product->updated_at?->diffForHumans() : 'Buat item merchandise baru untuk etalase Goddess Club Store.' }}</p>
  </div>
  <div class="admin-actions">
    @if ($product->exists)
      <a class="admin-btn ghost small" href="{{ route('product.show', $product->slug) }}" target="_blank">LIHAT DI TOKO &nearr;</a>
    @endif
    <a class="admin-btn ghost" href="{{ route('admin.products.index') }}">&larr; KEMBALI KE DAFTAR</a>
  </div>
</div>

<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data">
  @csrf
  @if ($product->exists)
    @method('PUT')
  @endif

  <div class="admin-form-grid">
    <!-- Kolom Kiri: Detail Informasi & Varian -->
    <div class="admin-form-main">
      
      <!-- Panel 1: Detail Produk -->
      <div class="admin-panel">
        <h2>
          <span>Informasi Dasar</span>
          <span style="font-size:11px;color:#a79a82;font-family:sans-serif;font-weight:400;">WAJIB DIISI</span>
        </h2>

        <label class="field">
          <span>NAMA PRODUK <b style="color:#d92323">*</b></span>
          <input type="text" id="product_name" name="name" value="{{ old('name', $product->name) }}" placeholder="Contoh: The Goddess Tour Oversized Tee" required autofocus>
          <span class="hint">Nama resmi produk yang akan ditampilkan kepada pembeli.</span>
        </label>

        <div class="admin-grid-2">
          <label class="field">
            <span>KATEGORI <b style="color:#d92323">*</b></span>
            <select name="category_id" required>
              <option value="">Pilih Kategori…</option>
              @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
            <span class="hint">Kategori etalase merchandise.</span>
          </label>

          <label class="field">
            <span>SLUG URL <b style="color:#d92323">*</b></span>
            <input type="text" id="product_slug" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="the-goddess-tour-tee" required>
            <span class="hint">URL unik halaman produk (huruf kecil, angka, tanda strip).</span>
          </label>
        </div>

        <label class="field">
          <span>DESKRIPSI PRODUK</span>
          <textarea name="description" rows="5" placeholder="Ceritakan detail bahan, fitting, detail grafis, atau panduan perawatan...">{{ old('description', $product->description) }}</textarea>
          <span class="hint">Penjelasan produk yang muncul di tab detail.</span>
        </label>

        <div class="admin-grid-2">
          <label class="field">
            <span>STATUS PUBLIKASI <b style="color:#d92323">*</b></span>
            <select name="status" required>
              <option value="active" @selected(old('status', $product->status) === 'active')>Active (Tampil di Toko)</option>
              <option value="draft" @selected(old('status', $product->status) === 'draft')>Draft (Disimpan Sementara)</option>
              <option value="archived" @selected(old('status', $product->status) === 'archived')>Archived (Arsip / Tidak Dijual)</option>
            </select>
          </label>

          <label class="field">
            <span>BADGE / LABEL KHUSUS</span>
            <input type="text" name="tag" value="{{ old('tag', $product->tag) }}" placeholder="Bestseller / Limited Edition / Tour Exclusive">
            <span class="hint">Label sorotan di atas kartu produk (opsional).</span>
          </label>
        </div>

        <div class="admin-grid-2">
          <label class="field">
            <span>VISUAL KIND (TEMA TAMPILAN)</span>
            <input type="text" name="kind" value="{{ old('kind', $product->kind) }}" placeholder="tee / vinyl / accessory">
            <span class="hint">Gaya frame visual kartu produk pada storefront.</span>
          </label>

          <label class="field">
            <span>ESTIMASI BERAT (GRAM)</span>
            <input type="number" min="1" max="100000" name="weight_gram" value="{{ old('weight_gram', $product->variants->first()?->weight_gram ?? 300) }}" placeholder="300">
            <span class="hint">Digunakan untuk kalkulasi ongkos kirim.</span>
          </label>
        </div>
      </div>

      <!-- Panel 2: Harga & Stok Utama -->
      <div class="admin-panel">
        <h2>
          <span>Harga &amp; Stok Utama</span>
        </h2>

        <div class="admin-grid-2">
          <label class="field">
            <span>HARGA DASAR ($ USD) <b style="color:#d92323">*</b></span>
            <input type="number" step="0.01" min="0" id="base_price" name="price" value="{{ old('price', $product->price) }}" placeholder="0.00" required>
            <span class="hint">Harga default produk jika tanpa varian khusus.</span>
          </label>

          <label class="field">
            <span>TOTAL STOK FISIK <b style="color:#d92323">*</b></span>
            <input type="number" min="0" id="base_stock" name="stock" value="{{ old('stock', $product->stock) }}" placeholder="0" required>
            <span class="hint">Jika menggunakan varian di bawah, stok otomatis dihitung dari varian.</span>
          </label>
        </div>
      </div>

      <!-- Panel 3: Ukuran & Varian -->
      <div class="admin-panel">
        <h2>
          <span>Ukuran &amp; Varian Produk</span>
          <button type="button" class="admin-btn ghost small" id="btn-add-variant">+ TAMBAH BARIS</button>
        </h2>
        <p class="muted" style="margin-bottom:16px;font-size:11px;color:#a79a82;">
          Tentukan varian ukuran jika produk memiliki opsi (misal: S, M, L, XL atau Vinyl). Baris dengan ukuran kosong akan otomatis diabaikan.
        </p>

        <div class="variant-header-row">
          <div>UKURAN (SIZE)</div>
          <div>HARGA ($ USD)</div>
          <div>JUMLAH STOK</div>
          <div style="text-align:center;">AKSI</div>
        </div>

        <div id="variant-rows-container">
          @php
            $existing = $product->exists ? $product->variants : collect();
            $rows = max(3, $existing->count());
          @endphp

          @for ($i = 0; $i < $rows; $i++)
            @php
              $variant = $existing->get($i);
              $sizeValue = old("sizes.$i", $variant?->size());
            @endphp
            <div class="variant-item-row">
              <div class="field">
                <input type="text" name="sizes[]" value="{{ $sizeValue === 'One Size' && ! $variant ? '' : $sizeValue }}" placeholder="Contoh: S / M / L / All Size">
              </div>
              <div class="field">
                <input type="number" step="0.01" min="0" class="variant-price-input" name="variant_prices[]" value="{{ old("variant_prices.$i", $variant?->price ?? $product->price) }}" placeholder="0.00">
              </div>
              <div class="field">
                <input type="number" min="0" name="variant_stocks[]" value="{{ old("variant_stocks.$i", $variant?->stock ?? $product->stock) }}" placeholder="0">
              </div>
              <div>
                <button type="button" class="variant-del-btn" title="Hapus baris ini" onclick="removeVariantRow(this)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                </button>
              </div>
            </div>
          @endfor
        </div>
      </div>

    </div>

    <!-- Kolom Kanan: Gambar Produk & Publikasi -->
    <div class="admin-form-sidebar">

      <!-- Panel Media Gambar -->
      <div class="admin-panel media-box">
        <h2>
          <span>Gambar Produk</span>
        </h2>

        <!-- Interactive Preview Box -->
        <div class="image-preview-wrapper" id="preview-container">
          @if ($product->image)
            <img id="image-preview" src="{{ $product->image }}" alt="{{ $product->name }}">
            <div class="preview-empty-state" id="empty-preview" style="display:none;">
              <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span>BELUM ADA GAMBAR</span>
              <small>Pilih file dari device atau masukkan URL</small>
            </div>
          @else
            <img id="image-preview" src="" alt="Preview" style="display:none;">
            <div class="preview-empty-state" id="empty-preview">
              <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span>BELUM ADA GAMBAR</span>
              <small>Pilih file dari device atau masukkan URL</small>
            </div>
          @endif
        </div>

        <!-- Opsi 1: Upload dari Device -->
        <div class="upload-dropzone" id="dropzone" onclick="document.getElementById('image_file_input').click()">
          <input type="file" id="image_file_input" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" style="display:none;">
          <div class="upload-dropzone-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e5a93b" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          </div>
          <div class="upload-dropzone-title">📁 PILIH DARI DEVICE (KOMPUTER / HP)</div>
          <p class="upload-dropzone-subtitle">Klik di sini atau seret file gambar langsung ke kotak ini</p>
        </div>

        <!-- Indikator File Terpilih -->
        <div class="file-selected-bar" id="file-info-bar">
          <div>
            <strong style="color:#f4ebd0;display:block;font-size:10px;">FILE TERPILIH DARI PERANGKAT:</strong>
            <span id="file-name-text">filename.png</span>
          </div>
          <button type="button" id="btn-cancel-file" title="Batalkan pilihan file">&times;</button>
        </div>

        <!-- Pemisah ATAU -->
        <div style="display:flex;align-items:center;gap:12px;margin:20px 0;color:#574930;font-size:10px;font-weight:700;letter-spacing:1px;">
          <div style="flex:1;height:1px;background:#332d22;"></div>
          <span>ATAU MASUKKAN URL</span>
          <div style="flex:1;height:1px;background:#332d22;"></div>
        </div>

        <!-- Opsi 2: Image URL -->
        <label class="field" style="margin-bottom:0;">
          <span>URL GAMBAR EXTERNAL</span>
          <input type="text" id="image_url_input" name="image" value="{{ old('image', $product->image) }}" placeholder="https://images.unsplash.com/…">
          <span class="hint">Jika tidak mengunggah file dari perangkat, Anda dapat menempelkan URL gambar langsung.</span>
        </label>
      </div>

      <!-- Panel Simpan & Info -->
      <div class="publish-card">
        <h2 style="font-size:18px;margin-bottom:14px;">Simpan &amp; Terbitkan</h2>

        <button class="admin-btn btn-submit" type="submit">
          {{ $product->exists ? 'SIMPAN PERUBAHAN' : 'TERBITKAN PRODUK' }}
        </button>

        <a class="admin-btn ghost btn-cancel" href="{{ route('admin.products.index') }}">
          BATAL
        </a>

        @if ($product->exists)
          <div style="margin-top:20px;padding-top:14px;border-top:1px solid #332d22;">
            <div class="meta-detail-row">
              <span>Status</span>
              <b>{{ strtoupper($product->status) }}</b>
            </div>
            <div class="meta-detail-row">
              <span>Dibuat</span>
              <b>{{ $product->created_at?->format('d M Y, H:i') }}</b>
            </div>
            <div class="meta-detail-row">
              <span>Terakhir Diedit</span>
              <b>{{ $product->updated_at?->format('d M Y, H:i') }}</b>
            </div>
            <div class="meta-detail-row">
              <span>Total Varian</span>
              <b>{{ $product->variants->count() }} Ukuran</b>
            </div>
          </div>
        @endif
      </div>

    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
  // 1. Auto-generate Slug dari Nama Produk jika membuat produk baru atau slug belum diubah manual
  const nameInput = document.getElementById('product_name');
  const slugInput = document.getElementById('product_slug');
  let slugManuallyEdited = {{ $product->exists ? 'true' : 'false' }};

  if (slugInput) {
    slugInput.addEventListener('input', () => {
      slugManuallyEdited = true;
    });
  }

  if (nameInput && slugInput) {
    nameInput.addEventListener('input', () => {
      if (!slugManuallyEdited) {
        slugInput.value = nameInput.value
          .toLowerCase()
          .trim()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/[\s_-]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }
    });
  }

  // 2. Image Upload from Device & Live Preview
  const fileInput = document.getElementById('image_file_input');
  const dropzone = document.getElementById('dropzone');
  const previewImg = document.getElementById('image-preview');
  const emptyPreview = document.getElementById('empty-preview');
  const fileInfoBar = document.getElementById('file-info-bar');
  const fileNameText = document.getElementById('file-name-text');
  const btnCancelFile = document.getElementById('btn-cancel-file');
  const urlInput = document.getElementById('image_url_input');

  const originalImageUrl = '{{ $product->image ?? '' }}';

  function displayPreview(src) {
    if (src && src.trim() !== '') {
      previewImg.src = src;
      previewImg.style.display = 'block';
      if (emptyPreview) emptyPreview.style.display = 'none';
    } else {
      previewImg.src = '';
      previewImg.style.display = 'none';
      if (emptyPreview) emptyPreview.style.display = 'flex';
    }
  }

  function handleFileSelected(file) {
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      alert('File harus berupa gambar (JPG, PNG, WEBP, GIF, atau SVG).');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      alert('Ukuran file maksimal adalah 5MB.');
      return;
    }

    const sizeStr = (file.size / 1024 > 1024) 
      ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' 
      : (file.size / 1024).toFixed(0) + ' KB';

    fileNameText.textContent = `${file.name} (${sizeStr})`;
    fileInfoBar.style.display = 'flex';

    const reader = new FileReader();
    reader.onload = (e) => {
      displayPreview(e.target.result);
    };
    reader.readAsDataURL(file);
  }

  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        handleFileSelected(file);
      }
    });
  }

  if (btnCancelFile) {
    btnCancelFile.addEventListener('click', () => {
      fileInput.value = '';
      fileInfoBar.style.display = 'none';
      if (urlInput && urlInput.value.trim() !== '') {
        displayPreview(urlInput.value);
      } else {
        displayPreview(originalImageUrl);
      }
    });
  }

  // Drag and drop handling
  if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('dragover');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');
      }, false);
    });

    dropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const file = dt.files[0];
      if (file) {
        fileInput.files = dt.files;
        handleFileSelected(file);
      }
    }, false);
  }

  // URL input preview fallback
  if (urlInput) {
    urlInput.addEventListener('input', () => {
      if (!fileInput.files || fileInput.files.length === 0) {
        displayPreview(urlInput.value);
      }
    });
  }

  // 3. Dynamic Variants Management
  const btnAddVariant = document.getElementById('btn-add-variant');
  const variantsContainer = document.getElementById('variant-rows-container');
  const basePriceInput = document.getElementById('base_price');

  if (btnAddVariant && variantsContainer) {
    btnAddVariant.addEventListener('click', () => {
      const defaultPrice = basePriceInput ? basePriceInput.value : '';
      const rowDiv = document.createElement('div');
      rowDiv.className = 'variant-item-row';
      rowDiv.innerHTML = `
        <div class="field">
          <input type="text" name="sizes[]" placeholder="Contoh: S / M / L / XL">
        </div>
        <div class="field">
          <input type="number" step="0.01" min="0" class="variant-price-input" name="variant_prices[]" value="${defaultPrice}" placeholder="0.00">
        </div>
        <div class="field">
          <input type="number" min="0" name="variant_stocks[]" value="0" placeholder="0">
        </div>
        <div>
          <button type="button" class="variant-del-btn" title="Hapus baris ini" onclick="removeVariantRow(this)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
          </button>
        </div>
      `;
      variantsContainer.appendChild(rowDiv);
    });
  }

  function removeVariantRow(btn) {
    const row = btn.closest('.variant-item-row');
    if (row) {
      const allRows = variantsContainer.querySelectorAll('.variant-item-row');
      if (allRows.length > 1) {
        row.remove();
      } else {
        // Jika hanya ada 1 baris, kosongkan isiannya
        row.querySelectorAll('input').forEach(input => input.value = '');
      }
    }
  }
</script>
@endpush
