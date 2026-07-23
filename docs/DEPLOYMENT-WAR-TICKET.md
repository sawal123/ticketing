# Deployment War Ticket - Shared/Business Hosting

Dokumen ini untuk deployment perubahan reservation stok pada hosting cPanel/shared hosting tanpa asumsi Redis, Supervisor, Docker, akses root, atau daemon permanen.

## Prinsip Aman

- Jangan deploy langsung ke `main`.
- Jangan menghapus data transaksi production.
- Deploy kode yang kompatibel dengan schema lama, lalu jalankan migration penambahan kolom.
- Jalankan audit dan backfill sebelum membuka war ticket.
- Jangan menjalankan load test ke domain production.

## Backup

1. Backup database dari cPanel/phpMyAdmin atau command hosting yang tersedia.
2. Backup file `.env`.
3. Backup folder `storage/`, terutama file upload dan generated ticket.
4. Catat commit production saat ini:

```bash
git rev-parse HEAD
```

## Environment

Set production:

```env
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file
```

Jangan gunakan `QUEUE_CONNECTION=sync` untuk war ticket production.

## Deploy Bertahap

1. Pull kode branch/commit release ke hosting.
2. Aktifkan maintenance mode hanya bila diperlukan saat migration:

```bash
php artisan down --render="errors::503"
```

3. Jalankan migration non-destruktif:

```bash
php artisan migrate --force
```

4. Audit data:

```bash
php artisan tickets:audit-integrity
```

5. Dry-run backfill stok:

```bash
php artisan tickets:backfill-stock --dry-run
```

6. Jika hasil dry-run masuk akal, jalankan backfill sebenarnya:

```bash
php artisan tickets:backfill-stock
```

7. Bersihkan dan cache ulang:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

8. Jalankan `route:cache` hanya jika semua route di hosting kompatibel:

```bash
php artisan route:cache
```

9. Matikan maintenance mode:

```bash
php artisan up
```

## Cron cPanel

Sesuaikan path project dan binary PHP sesuai cPanel. Contoh:

```cron
* * * * * cd /home/USER/path-project && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Jika scheduler queue worker tidak dipakai, pasang cron queue worker database yang berhenti saat kosong:

```cron
* * * * * cd /home/USER/path-project && /usr/local/bin/php artisan queue:work database --stop-when-empty --tries=3 --timeout=60 >> /dev/null 2>&1
```

Hindari menjalankan banyak worker bersamaan pada shared hosting. Scheduler sudah memakai `withoutOverlapping()`.

## Verifikasi Production

1. Pastikan callback Midtrans mengarah ke:

```text
https://domain-anda.com/api/callback
```

2. Uji satu pembayaran nominal kecil.
3. Pastikan status `SUCCESS` hanya muncul setelah callback server Midtrans.
4. Cek queue:

```bash
php artisan queue:work database --stop-when-empty --tries=3 --timeout=60
```

5. Cek log:

```bash
tail -n 100 storage/logs/laravel.log
```

6. Jalankan audit ulang:

```bash
php artisan tickets:audit-integrity
```

## Operasional

Release reservation expired manual:

```bash
php artisan tickets:release-expired --batch=100
```

Audit integritas:

```bash
php artisan tickets:audit-integrity
```

Backfill stok:

```bash
php artisan tickets:backfill-stock --dry-run
php artisan tickets:backfill-stock
```

## Rollback

1. Aktifkan maintenance mode jika rollback kode berisiko:

```bash
php artisan down --render="errors::503"
```

2. Hentikan cron sementara dari cPanel:

- `php artisan schedule:run`
- `php artisan queue:work database --stop-when-empty ...`

3. Kembalikan kode ke commit sebelumnya:

```bash
git fetch origin
git checkout <commit-production-sebelumnya>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

4. Jangan rollback migration dengan menghapus kolom/tabel transaksi production. Kolom baru aman dibiarkan.
5. Jika perlu menonaktifkan reservation baru sementara, arahkan traffic ke maintenance mode atau revert kode checkout/paynow ke commit sebelumnya. Jangan menghapus `sold_qty`, `reserved_qty`, atau transaksi.
6. Nyalakan kembali aplikasi:

```bash
php artisan up
```

## Catatan Kapasitas

- Gunakan batch 50-100 untuk command stok.
- Jangan proses 6.000 transaksi dalam satu request web.
- Jangan memanggil API eksternal di checkout/paynow selain Midtrans setelah DB commit.
- Gunakan database queue pada shared hosting.
- Pantau CPU/RAM/process cPanel saat war.

