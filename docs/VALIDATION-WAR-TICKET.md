# Validation War Ticket

Status dokumen: validasi sebelum production untuk branch `fix/war-ticket-concurrency`.

Jangan merge ke `main` dan jangan deploy production sebelum semua checklist staging selesai. Status yang benar saat dokumen ini dibuat adalah **READY FOR STAGING**, bukan **READY FOR PRODUCTION**.

## 1. Target Validasi

Validasi harus dilakukan di environment lokal atau staging yang memakai MySQL/MariaDB. Jangan menjalankan load test ke domain production.

Syarat staging:

- Database MySQL/MariaDB, bukan SQLite.
- Data event/tiket khusus testing, bukan event production.
- Stok kecil dulu untuk validasi, lalu stok mendekati skenario war.
- Callback Midtrans memakai sandbox/staging bila diuji end-to-end.
- Queue memakai `database`.
- Tidak memakai Redis/Supervisor sebagai dependency wajib.

## 2. Automated Test Lokal

Jalankan:

```bash
php artisan test
```

Hasil lokal terakhir:

```text
Tests: 14 passed (58 assertions)
```

Rincian test yang lulus:

- `Tests\Unit\ExampleTest`
- `Tests\Feature\ExampleTest`
- `Tests\Feature\UserProfileDefaultsTest`
- `Tests\Feature\WarTicketFlowTest`
  - harga request tersembunyi tidak memengaruhi harga database
  - `harga_id` event berbeda ditolak
  - quantity invalid dan total >5 ditolak
  - reservation tidak membuat stok negatif
  - reservation expired mengembalikan stok
  - user tidak bisa membayar cart user lain
  - cart expired tidak bisa membuat Snap baru
  - paynow mengabaikan event/user/invoice dari request
  - late payment masuk `PAYMENT_REVIEW` tanpa oversell
  - settlement callback idempotent untuk stok/voucher/email
  - `SUCCESS` tidak turun menjadi status lain
  - gross amount dan signature callback invalid ditolak

Belum dibuktikan oleh test lokal:

- True concurrency MySQL/MariaDB dengan row-level lock.
- Deadlock retry di database engine nyata.
- Throughput shared-hosting-like saat 10/25/50/100 VU.

## 3. Migration Safety

Migration `up()` hanya menambah:

- kolom baru non-destruktif pada `hargas`, `carts`, `transactions`
- index baru
- tabel baru `voucher_usages`

Tidak ada `drop`, `rename`, `truncate`, atau penghapusan data pada jalur deploy `php artisan migrate --force`.

Larangan penting:

- Jangan menjalankan `php artisan migrate:rollback` di production untuk migration ini.
- Method `down()` akan menghapus kolom/tabel baru sebagaimana pola Laravel rollback, sehingga tidak boleh dipakai sebagai rollback production setelah data reservation berjalan.
- Rollback production harus dilakukan dengan rollback kode, bukan menghapus schema/data transaksi.

## 4. Command Audit dan Stock

Lihat command:

```bash
php artisan list tickets
```

Command tersedia:

```bash
php artisan tickets:audit-integrity
php artisan tickets:backfill-stock --dry-run
php artisan tickets:backfill-stock
php artisan tickets:release-expired
```

Audit read-only:

```bash
php artisan tickets:audit-integrity --limit=20
```

Dry-run backfill:

```bash
php artisan tickets:backfill-stock --dry-run --batch=100
```

Backfill sebenarnya setelah dry-run diperiksa:

```bash
php artisan tickets:backfill-stock --batch=100
```

Release expired manual:

```bash
php artisan tickets:release-expired --batch=100
```

Catatan memory/batch:

- `tickets:backfill-stock` memakai `chunkById($batch)` pada tabel `hargas`.
- `tickets:release-expired` mengambil maksimal batch cart ID, default 100, lalu memproses tiap cart dalam transaction pendek.
- `tickets:audit-integrity` read-only dan membatasi sample output dengan `--limit`; agregasi stok dilakukan di database, bukan memuat seluruh row transaksi ke memory PHP.

## 5. Scheduler dan Queue Shared Hosting

Konfigurasi yang divalidasi:

- `QUEUE_CONNECTION=database`
- database queue `after_commit=true`
- scheduler menjalankan release expired tiap menit
- scheduler menjalankan queue worker database dengan `--stop-when-empty`
- `withoutOverlapping()` dipakai agar cron tidak menumpuk

Cron cPanel:

```cron
* * * * * cd /home/USER/path-project && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Alternatif queue worker bila dipisah dari scheduler:

```cron
* * * * * cd /home/USER/path-project && /usr/local/bin/php artisan queue:work database --stop-when-empty --tries=3 --timeout=60 >> /dev/null 2>&1
```

Untuk hosting 2 CPU, RAM 3 GB, 60 PHP worker:

- Jangan menjalankan banyak queue worker paralel.
- Jangan memakai Redis/Supervisor sebagai syarat.
- Jangan memakai `QUEUE_CONNECTION=sync` untuk production war ticket.
- Pastikan cron hanya satu entri aktif per command.

## 6. Route Cache

Jangan mewajibkan:

```bash
php artisan route:cache
```

Alasan: project masih memiliki Closure route, termasuk `/logout` dan `/out` di `routes/web.php`, serta closure route/group callbacks lain. Lewati `route:cache` sampai semua Closure route diganti controller.

Command cache yang boleh dipakai:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

## 7. k6 Load Test

Script:

```text
tests/load/war-ticket.js
```

Environment variable yang benar-benar dibaca script:

- `BASE_URL`, default `http://127.0.0.1:8000`
- `EVENT_SLUG`, default `demo`
- `AUTH_COOKIE`, default kosong
- `VUS`, default `10`
- `DURATION`, default `30s`
- `ALLOW_NON_LOCAL`, default kosong

Safety guard script:

- Script hanya otomatis menerima `BASE_URL` yang mengandung `localhost`, `127.0.0.1`, `.test`, atau `staging`.
- Jika target staging tidak cocok pola itu, script akan berhenti kecuali `ALLOW_NON_LOCAL=1`.
- Jangan set `ALLOW_NON_LOCAL=1` untuk domain production.

Persiapan:

1. Login sebagai user test pada localhost/staging.
2. Ambil cookie session login dari browser.
3. Pastikan event slug test memiliki tiket active dan event active/confirmed.
4. Pastikan stok event test disiapkan sesuai tujuan load test.
5. Jangan gunakan event/domain production.
6. Jalankan inspect sebelum load test:

```bash
k6 inspect tests/load/war-ticket.js
```

Catatan: `AUTH_COOKIE` tunggal masih didukung hanya untuk smoke-test authenticated flow. Ini bukan simulasi banyak pembeli unik. Untuk uji war yang lebih realistis, siapkan cookie/session user test berbeda dan perluasan script terpisah.

PowerShell contoh localhost:

```powershell
$env:BASE_URL="http://127.0.0.1:8000"
$env:EVENT_SLUG="demo"
$env:AUTH_COOKIE="laravel_session=ISI_COOKIE_TEST; XSRF-TOKEN=ISI_XSRF_TEST"
$env:DURATION="30s"
```

Tahap 10 VU:

```powershell
$env:VUS="10"; k6 run tests/load/war-ticket.js
```

Tahap 25 VU:

```powershell
$env:VUS="25"; k6 run tests/load/war-ticket.js
```

Tahap 50 VU:

```powershell
$env:VUS="50"; k6 run tests/load/war-ticket.js
```

Tahap 100 VU:

```powershell
$env:VUS="100"; k6 run tests/load/war-ticket.js
```

Bash contoh staging aman:

```bash
BASE_URL="https://war-ticket.staging.example.test" \
EVENT_SLUG="demo" \
AUTH_COOKIE="laravel_session=ISI_COOKIE_TEST; XSRF-TOKEN=ISI_XSRF_TEST" \
DURATION="30s" \
VUS="10" \
k6 run tests/load/war-ticket.js
```

Jika staging URL tidak mengandung `.test` atau `staging`, hanya gunakan ini bila benar-benar staging:

```bash
ALLOW_NON_LOCAL=1 BASE_URL="https://staging-domain-yang-terverifikasi" EVENT_SLUG="demo" AUTH_COOKIE="..." VUS="10" DURATION="30s" k6 run tests/load/war-ticket.js
```

## 8. Cara Membaca Hasil k6

Metric penting:

- `http_req_failed`: threshold script `< 2%`.
- `checks`: threshold script `> 95%`.
- `http_req_duration`: threshold script `p(95)<2000ms` dan `p(99)<5000ms`.
- `checkout_accepted`: jumlah redirect 302/303 ke `/detail-ticket/`.
- `checkout_sold_out`: graceful sold-out rejection yang terdeteksi setelah redirect.
- `csrf_error`: status 419; ini kegagalan, bukan keberhasilan.
- `validation_error`: status 422; ini kegagalan, bukan keberhasilan.
- `rate_limited`: status 429; ini kegagalan untuk kapasitas target.
- `server_error`: status `>=500`, threshold script `< 1%`.
- `unexpected_response`: response di luar klasifikasi, threshold script `< 2%`.

Tidak ada klaim duplicate invoice dari JavaScript runtime k6. Duplicate invoice wajib diverifikasi dari database dengan:

```bash
php artisan tickets:audit-integrity --limit=50
```

Status checkout:

- 302/303 ke `/detail-ticket/` = accepted.
- sold-out valid = graceful rejection.
- 419 = `csrf_error` dan gagal.
- 422 = `validation_error` dan gagal.
- 429 = `rate_limited` dan gagal.
- `>=500` = `server_error` dan gagal.

Jika `AUTH_COOKIE`, CSRF token, `event_uid`, atau `harga_id` tidak tersedia, script abort/fail. Test tidak boleh dianggap lulus bila tidak ada checkout yang benar-benar dikirim.

Setelah tiap tahap VU, jalankan audit DB:

```bash
php artisan tickets:audit-integrity --limit=50
```

Query sanity MySQL/MariaDB:

```sql
select id, kategori, qty, sold_qty, reserved_qty
from hargas
where sold_qty > qty
   or reserved_qty < 0
   or sold_qty < 0
   or (sold_qty + reserved_qty) > qty;
```

```sql
select invoice, count(*) duplicate_count
from carts
where invoice is not null and invoice <> ''
group by invoice
having count(*) > 1;
```

## 9. Checklist MySQL/MariaDB

Gunakan database staging MySQL/MariaDB untuk checklist ini.

- `lockForUpdate`
  - Jalankan 2 request checkout bersamaan pada kategori stok kecil.
  - Pastikan salah satu gagal gracefully ketika stok habis.
  - Pastikan tidak ada `sold_qty + reserved_qty > qty`.

- Deadlock retry
  - Buat checkout multi-kategori dengan urutan kategori berbeda dari beberapa client.
  - Pastikan log tidak menunjukkan failure permanen akibat deadlock normal.
  - Pastikan retry transaction maksimal 3 kali menyelesaikan request atau gagal dengan pesan aman.

- `reserved_qty`
  - Setelah checkout `RESERVED`, `reserved_qty` naik sesuai quantity.
  - Detail `harga_carts` tersimpan dengan harga/kategori dari DB.

- `sold_qty`
  - Setelah callback settlement valid, `reserved_qty` turun dan `sold_qty` naik.
  - `sold_qty` tidak pernah lebih besar dari `qty`.

- Expired reservation
  - Buat reservation dengan `expires_at` lewat.
  - Jalankan `php artisan tickets:release-expired --batch=100`.
  - Pastikan cart menjadi `EXPIRED` dan `reserved_qty` turun satu kali.

- Duplicate callback
  - Kirim payload settlement valid yang sama dua kali.
  - Pastikan callback kedua HTTP 200 dan no-op.
  - Pastikan `sold_qty`, voucher usage, dan email tidak bertambah dua kali.

- Duplicate email
  - Dengan queue fake/staging log, pastikan hanya satu job email per invoice.
  - Pastikan `sendEmailETransaksi` unique berdasarkan invoice/order id.

- Duplicate voucher usage
  - Gunakan voucher pada cart.
  - Kirim settlement duplikat.
  - Pastikan `voucher_usages.cart_uid` hanya satu row dan `vouchers.digunakan` naik satu kali.

- Duplicate invoice
  - Jalankan audit:
    ```bash
    php artisan tickets:audit-integrity --limit=50
    ```
  - Query manual:
    ```sql
    select invoice, count(*) from carts group by invoice having count(*) > 1;
    ```

## 10. Metrik Kelulusan

Fase 10/25/50/100 VU dinyatakan lulus bila:

- Tidak ada HTTP 500 pada checkout.
- `http_req_failed < 2%` sesuai threshold script.
- `checks > 95%`.
- `server_error < 1%`.
- `unexpected_response < 2%`.
- `http_req_duration p95 < 2000ms` dan `p99 < 5000ms`.
- Jumlah successful reservation tidak melebihi stok test.
- `sold_qty >= 0`, `reserved_qty >= 0`.
- `sold_qty <= qty`.
- `sold_qty + reserved_qty <= qty` setelah reservation aktif dihitung.
- Tidak ada duplicate invoice.
- Callback settlement duplikat idempotent.
- Voucher usage tidak duplikat.
- Email tidak duplikat.
- Release expired mengembalikan stok satu kali.
- Queue database tidak menumpuk tanpa worker.

## 11. Kondisi yang Melarang Deployment

Jangan deploy production bila salah satu terjadi:

- Load test belum dijalankan di MySQL/MariaDB staging.
- Ada `sold_qty > qty`.
- Ada `reserved_qty < 0` atau `sold_qty < 0`.
- Ada `sold_qty + reserved_qty > qty` untuk reservation aktif.
- Ada duplicate invoice.
- Callback settlement duplikat menggandakan stok/voucher/email.
- Callback invalid signature/gross amount diterima.
- `tickets:audit-integrity` melaporkan data kritis yang belum dipahami.
- Queue database tidak berjalan via cron.
- Cron `schedule:run` belum terpasang atau menumpuk.
- Error 500 muncul saat checkout/paynow/callback.
- Test lokal `php artisan test` gagal.

## 12. Status Akhir

- **READY FOR STAGING**: Ya, automated test lokal lulus dan dokumen validasi siap.
- **READY FOR PRODUCTION**: Belum.
- **NOT READY**: Gunakan status ini bila salah satu kondisi pelarangan deployment terjadi.

Production baru boleh dipertimbangkan setelah true concurrency MySQL/MariaDB staging dan load test 10/25/50/100 VU berhasil.
