# Product Images

Simpan gambar produk di folder ini dengan nama file berikut:

| Produk | Nama File |
|--------|-----------|
| The Goddess Tour Tee | `goddess-tour-tee.jpg` |
| Reality Club Presents... | `reality-club-vinyl.jpg` |
| All Night Long Tote | `all-night-long-tote.jpg` |
| The After Hours Cap | `after-hours-cap.jpg` |

## Format & Ukuran Rekomendasi

- **Format**: JPG atau WebP
- **Resolusi**: 1200×1200px (rasio 1:1)
- **Ukuran file**: Maksimal 500KB per gambar
- **Kualitas**: 85%
- **Color profile**: sRGB

Setelah menambahkan gambar, jalankan:

```bash
docker compose exec app php artisan migrate:refresh --seed
```

Atau update langsung di database tanpa migrate:

```sql
UPDATE products SET image = '/images/goddess-tour-tee.jpg' WHERE slug = 'the-goddess-tour-tee';
```