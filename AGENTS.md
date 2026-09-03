# AGENTS.md

## Project

Gotik Ticketing Platform.

Stack utama:

- Laravel
- Livewire
- Alpine.js
- Vue.js pada area yang memang sudah menggunakan Vue
- MySQL
- PHPUnit

Ikuti pola dan arsitektur existing repository.
Jangan mengganti stack atau menambah package tanpa instruksi eksplisit.

---

## Working Style

- Fokus hanya pada task yang diberikan.
- Jangan memperluas scope.
- Jangan audit atau refactor area lain.
- Jangan memperbaiki warning/error unrelated kecuali terbukti disebabkan task saat ini.
- Jangan membaca ulang file yang sudah dibaca jika konteks masih cukup.
- Jangan memberikan penjelasan setiap langkah pengerjaan.
- Jangan berhenti hanya untuk memberikan progress update.
- Selesaikan task dan verification terlebih dahulu sebelum memberikan output akhir.
- Jangan mengatakan akan melanjutkan otomatis setelah turn berakhir.
- Jika proses test masih berjalan, monitor sampai mendapat hasil dan exit code final.

Jika menemukan masalah di luar scope:

- jangan diperbaiki secara otomatis;
- catat sebagai blocker atau temuan.

---

## Code Changes

- Pertahankan backward compatibility.
- Jangan mengubah business logic existing tanpa kebutuhan task.
- Gunakan implementation paling kecil yang memenuhi requirement.
- Jangan membuat abstraction baru jika existing pattern sudah cukup.
- Jangan install package baru tanpa instruksi eksplisit.
- Jangan regenerate asset/build file jika source asset tidak berubah.
- Jangan mengubah migration lama yang sudah digunakan.
- Perubahan database harus memakai migration baru.
- Jangan hardcode kebutuhan satu event/game/organizer jika fitur bersifat generic.

Untuk security/business rule:

- frontend validation tidak cukup;
- server-side validation wajib authoritative;
- jangan percaya ID, mode, ownership, status, atau relational state dari client;
- baca state penting dari database saat melakukan mutation;
- query child resource harus scoped ke parent/resource yang sudah authorized.

---

## Ownership & Security

Semua mutation tenant/staff harus menjaga ownership existing.

Resource child harus diverifikasi terhadap parent authoritative.

Contoh prinsip:

requested child
AND
child belongs to authorized event/user/parent

Jangan mengandalkan hidden input, disabled field, Livewire state,
route parameter, atau payload client sebagai authority.

Manipulasi Livewire/client tidak boleh dapat bypass guard server-side.

---

## Git Workflow

Sebelum mengerjakan task:

1. Checkout base branch yang disebutkan di prompt.
2. Pull/fetch base terbaru.
3. Buat working branch baru dari base tersebut kecuali prompt mengatakan lanjutkan branch existing.

Untuk roadmap Event Registration:

```text
main
└── feature/event-registration
    └── feat/<milestone>
```

Lanjutkan branch existing hanya bila prompt menyatakannya dengan eksplisit.

Sebelum push:

1. Pastikan perubahan hanya mencakup scope task.
2. Jangan menambah file generated atau perubahan lokal milik user tanpa instruksi.
3. Jangan merge branch atau PR tanpa instruksi eksplisit.
4. Push hanya setelah verification yang diminta selesai.

---

## Scope Discipline

- Kerjakan hanya milestone dan file/area yang diperlukan oleh prompt.
- Jangan mengambil scope milestone berikutnya, termasuk dashboard, export, checkout redesign, atau refactor pembayaran kecuali disebutkan.
- Jangan mengubah schema existing selain melalui migration baru yang memang diperlukan oleh task.
- Jangan mengubah behavior ticketing existing saat menambah behavior registration kecuali requirement menyatakan sebaliknya.
- Jika requirement bertentangan atau perubahan di luar scope diperlukan, berhenti dan laporkan blocker sebelum mengubah area tersebut.

---

## Testing

- Tambahkan behavioral test untuk bug fix, security guard, dan flow baru yang diminta.
- Gunakan test feature/service existing sebagai pola; jangan melemahkan assertion untuk membuat test lulus.
- Jalankan test secara sequential. Jangan menjalankan PHPUnit paralel.
- Jalankan targeted test untuk area yang berubah dan regression test terkait.
- Jalankan existing test payment/OTP/checkout bila perubahan menyentuh checkout atau payment.

---

## Verification

Setelah implementasi, jalankan dalam urutan yang sesuai risiko task:

1. Pint untuk seluruh file PHP yang berubah.
2. Targeted PHPUnit dan regression suite terkait.
3. Full suite sequential: `php -d memory_limit=512M vendor/bin/phpunit`.
4. `git diff --check`.
5. Periksa `git status --short` sebelum commit dan push.

Jika full suite atau verification wajib gagal, jangan menyatakan siap merge. Jelaskan kegagalan yang masih tersisa secara ringkas.

---

## Existing Tests

- Existing test adalah kontrak behavior. Jangan menghapus atau menulis ulang assertion unrelated hanya untuk menyesuaikan perubahan baru.
- Untuk checkout/payment, jaga test OTP, Midtrans callback, recipient snapshot, quantity, dan ticketing tetap hijau.
- Untuk registration, jaga test mode event, dynamic fields, dan team roster tetap hijau.

---

## Final Output

Output akhir default menggunakan tepat 5 poin singkat:

1. File yang berubah.
2. Perbaikan atau behavior utama.
3. Test behavioral yang ditambahkan.
4. Hasil verification, termasuk Pint dan `git diff --check`.
5. HEAD branch/PR dan status siap atau tidak siap merge.

Jangan menyatakan merge telah dilakukan kecuali prompt meminta merge secara eksplisit.
