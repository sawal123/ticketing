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
