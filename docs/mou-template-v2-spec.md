# MOU Template v2 Specification & Source Mapping

## Tujuan

Dokumen ini mendefinisikan spesifikasi template baru `mou-v2` sebelum implementasi Blade/PDF. Fokus V2-0 hanya pada struktur dokumen, keputusan arsitektur, dan pemetaan source data agar implementasi berikutnya tidak menebak field yang belum memiliki source authoritative.

## Scope V2-0

- Menetapkan struktur Perjanjian Kerja Sama `mou-v2`.
- Menentukan bagian yang bersifat teks hukum statis vs data dinamis.
- Memetakan field dinamis ke source saat ini dan bentuk snapshot final.
- Menetapkan boundary contractual data vs non-contractual data.
- Mendokumentasikan gap source authoritative, terutama untuk PIHAK PERTAMA.

## Non-Goal V2-0

- Tidak mengubah `mou-v1`.
- Tidak membuat Blade/PDF `mou-v2`.
- Tidak membuat migration, model, controller, admin UI, atau settings baru.
- Tidak mengubah lifecycle private PDF, signing, verification, activation, M0-M12, payment semantics, atau addendum flow.

## Keputusan Arsitektur

- Template baru untuk implementasi berikutnya adalah `mou-v2`.
- `mou-v1` existing tetap immutable.
- PDF `READY` dan `COMPLETED` lama tidak boleh diregenerate.
- Preview `DRAFT` boleh memakai live data.
- Final PDF hanya boleh memakai frozen snapshot yang tersimpan di `agreements.event_snapshot`, `agreements.party_snapshot`, `agreements.bank_snapshot`, `agreements.document_snapshot`, `agreements.commercial_snapshot`, dan dedicated snapshot PIHAK PERTAMA `platform_party_snapshot` saat V2-1/V2-2 tersedia.
- `party_snapshot` existing tetap khusus PIHAK KEDUA/penyelenggara.
- Data PIHAK PERTAMA nantinya harus berasal dari database/admin settings pada V2-1, lalu dibekukan ke `platform_party_snapshot` untuk final `mou-v2`, bukan hardcode.
- Nilai yang belum memiliki source authoritative tidak boleh ditebak.
- Data tiket bukan contractual data untuk `mou-v2`.
- Perubahan contractual setelah agreement `COMPLETED` tetap mengikuti mekanisme addendum M12.
- Data PIHAK PERTAMA menjadi contractual setelah dibekukan ke `platform_party_snapshot`.
- Edit profil global Gotik tidak otomatis membuat addendum untuk agreement/event lama yang sudah memakai snapshot sebelumnya.
- Agreement `READY`/`COMPLETED` lama tetap immutable; MOU baru memakai profil Gotik terbaru yang berlaku saat finalisasi.
- Jika perubahan profil PIHAK PERTAMA perlu berlaku ke agreement yang sudah ada, addendum harus dibuat secara eksplisit.
- Private PDF, signing, verification, dan activation lifecycle yang ada saat ini tidak berubah.

## Batasan Data Kontraktual

Data berikut eksplisit di luar kontrak dan tidak boleh disnapshot sebagai contractual data pada `mou-v2`:

- kategori tiket
- nama tiket
- harga tiket
- kuota
- benefit tiket
- syarat per kategori tiket

Perubahan data di atas tidak boleh menjadi trigger addendum.

## Source Runtime Saat Ini

- Preview live saat `DRAFT` dibentuk oleh `AgreementPreviewService`.
- Finalisasi membekukan data live ke snapshot melalui `AgreementFinalizationService`.
- Addendum/diff kontraktual saat ini dihitung oleh `AgreementVersioningService`.
- Source utama organizer berasal dari `event_organizers`.
- Source utama rekening berasal dari `event_bank_accounts`.
- Source utama dokumen penyelenggara berasal dari `event_documents` dengan `document_type = ORGANIZER_LETTER`.
- Source utama konfigurasi komersial berasal dari `events`, `event_payment_gateways`, dan `payment_gateways`.
- `agreements.document_number` sudah tersedia sebagai field dokumen agreement, tetapi policy/generator nomor dokumen authoritative masih TBD.

## Terminologi Fee

- `buyer/event fee`: fee yang berasal dari `events.fee` dan direpresentasikan sebagai `buyer_fee`.
- `payment channel fee`: fee kanal pembayaran per gateway yang direpresentasikan sebagai `resolved_fee_fixed` dan `resolved_fee_percent`.
- `platform/system fee`: BELUM ADA SOURCE AUTHORITATIVE bila belum ada field khusus; jangan disamakan dengan `buyer_fee`.

## Struktur Dokumen `mou-v2`

| No | Bagian | Sifat | Tujuan | Data dinamis yang dibutuhkan | Source data | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Cover | STATIC + DATABASE/SNAPSHOT | Menampilkan identitas ringkas dokumen perjanjian. | Judul dokumen, nomor dokumen, nama event, nama penyelenggara, versi template. | `agreements.document_number`, `agreements.template_version`, `agreements.event_snapshot.event_name`, `agreements.party_snapshot.organizer_name` | Final PDF wajib dari snapshot; `document_number` tidak boleh diasumsikan selalu terisi sebelum policy/generator authoritative ditetapkan. |
| 2 | Pembukaan Perjanjian | STATIC + DATABASE/SNAPSHOT | Menyatakan para pihak, konteks kerja sama, dan referensi dokumen. | Nomor dokumen, nama PIHAK PERTAMA, nama PIHAK KEDUA, referensi event. | `agreements.document_number`, `platform_party_snapshot.*` target V2-1/V2-2, `agreements.party_snapshot.organizer_name`, `agreements.event_snapshot.event_name` | Tanggal efektif/perumusan pembukaan jangan diasumsikan bila belum diputuskan legal. |
| 3 | Identitas PIHAK PERTAMA / Gotik | CONFIG + SNAPSHOT | Menetapkan identitas hukum platform. | Nama badan usaha/pengelola, legalitas/NIB, alamat, wakil, jabatan, email, telepon/WhatsApp, situs/platform. | BELUM ADA authoritative source saat ini; target V2-1 admin settings/database lalu final snapshot `platform_party_snapshot`. | `party_snapshot` existing tidak dipakai untuk PIHAK PERTAMA. |
| 4 | Identitas PIHAK KEDUA / Penyelenggara | DATABASE + SNAPSHOT | Menetapkan identitas penyelenggara event. | Nama penyelenggara, penanggung jawab, jabatan, email, telepon, alamat. | `event_organizers.*` lalu dibekukan ke `agreements.party_snapshot.*` | Source saat ini tersedia. |
| 5 | PASAL 1 - Definisi | STATIC | Menentukan definisi istilah hukum dan operasional utama. | Tidak ada. | Teks hukum statis. | Jangan mengambil data runtime. |
| 6 | PASAL 2 - Maksud, Tujuan dan Ruang Lingkup | STATIC + SNAPSHOT | Menjelaskan kerja sama penyelenggaraan dan penjualan tiket event tertentu. | Nama event, peran platform, ruang lingkup layanan. | `agreements.event_snapshot.event_name`, `platform_party_snapshot.*` target V2-1/V2-2 | Isi hukum statis, event menjadi referensi dinamis. |
| 7 | PASAL 3 - Pendaftaran, Verifikasi dan Listing Event | STATIC + SNAPSHOT | Menjelaskan proses onboarding event dan prasyarat listing. | Nama event, status/verifikasi dokumen bila ingin disebut. | `agreements.event_snapshot.event_name`, `agreements.document_snapshot.*`, `agreements.bank_snapshot.*` | Jika status verifikasi hanya informasional, jangan dijadikan trigger addendum. |
| 8 | PASAL 4 - Penjualan dan Pengelolaan Tiket | STATIC + SNAPSHOT | Menjelaskan peran platform atas penjualan tiket. | Periode mulai penjualan. | `agreements.event_snapshot.start_sale` | Detail kategori/harga/kuota tiket tidak boleh masuk; daftar payment gateway adalah konfigurasi operasional (V2-R0). |
| 9 | PASAL 5 - Biaya, Pajak dan Ketentuan Komersial | STATIC + SNAPSHOT | Menjelaskan secara umum ketentuan biaya, pajak dan komersial. | Periode penjualan; deskripsi umum kewajiban biaya kepada Pembeli tanpa nilai. | `agreements.event_snapshot.start_sale` | V2-R0: Buyer/Event Fee dan payment channel fee adalah konfigurasi operasional; nilai tidak menjadi isi kontraktual dan tidak memicu addendum. Jangan gunakan istilah `buyer/platform fee` sebagai satu field. |
| 10 | PASAL 6 - Rekonsiliasi dan Pencairan Dana | STATIC + SNAPSHOT | Menetapkan rekening tujuan dan proses settlement. | Rekening pencairan, pemilik rekening, bank. | `agreements.bank_snapshot.bank_name`, `account_number`, `account_holder_name` | Status verifikasi rekening dapat disebut sebagai info, bukan trigger addendum saat ini. |
| 11 | PASAL 7 - Pembatalan, Penjadwalan Ulang dan Refund | STATIC + SNAPSHOT | Mengatur konsekuensi operasional saat event batal/ditunda. | Nama event, tanggal event bila perlu konteks. | `agreements.event_snapshot.event_name`, `start`, `end` | Isi hukum dominan statis. |
| 12 | PASAL 8 - Hak dan Kewajiban PIHAK PERTAMA | STATIC + CONFIG | Menetapkan kewajiban dan hak platform. | Identitas PIHAK PERTAMA jika disebut. | `platform_party_snapshot.*` target V2-1/V2-2 | Jangan hardcode identitas sekarang. |
| 13 | PASAL 9 - Hak dan Kewajiban PIHAK KEDUA | STATIC + SNAPSHOT | Menetapkan kewajiban dan hak penyelenggara. | Nama penyelenggara, penanggung jawab jika perlu rujukan. | `agreements.party_snapshot.*` | Isi hukum statis, identitas dinamis. |
| 14 | PASAL 10 - Gate System dan Dukungan Event | STATIC + SNAPSHOT | Menjelaskan dukungan platform saat pelaksanaan event. | Nama event, venue, tanggal event. | `agreements.event_snapshot.event_name`, `venue_name`, `start`, `end` | Tidak memerlukan data tiket per kategori. |
| 15 | PASAL 11 - Publikasi dan Merek | STATIC + SNAPSHOT | Menetapkan penggunaan nama, logo, dan materi promosi. | Nama event dan nama para pihak. | `agreements.event_snapshot.event_name`, `agreements.party_snapshot.organizer_name`, `platform_party_snapshot.*` target V2-1/V2-2 | Merek/logotype actual tidak perlu jadi field kontraktual baru pada V2-0. |
| 16 | PASAL 12 - Data Pribadi, Keamanan dan Kerahasiaan | STATIC | Menetapkan kewajiban perlindungan data dan kerahasiaan. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 17 | PASAL 13 - Kekayaan Intelektual | STATIC | Menetapkan kepemilikan dan lisensi kekayaan intelektual. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 18 | PASAL 14 - Fraud, Chargeback dan Penahanan Dana | STATIC | Menjelaskan risiko transaksi dan hak platform menahan dana, termasuk payment provider secara umum. | Tidak ada / teks hukum statis. | Tidak ada | V2-R0: pasal tidak memakai daftar payment gateway maupun fee live/frozen sebagai isi kontraktual; hanya menjelaskan secara umum. |
| 19 | PASAL 15 - Ketersediaan Layanan | STATIC | Menjelaskan best effort / availability layanan. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 20 | PASAL 16 - Jangka Waktu dan Pengakhiran | STATIC + SNAPSHOT | Menentukan masa berlaku kerja sama dan pengakhiran. | Tanggal event atau periode event bila dijadikan batas. | `agreements.event_snapshot.start`, `end` | Tanggal efektif perjanjian tetap perlu formula legal yang konsisten. |
| 21 | PASAL 17 - Keadaan Kahar | STATIC | Menetapkan force majeure. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 22 | PASAL 18 - Tanggung Jawab dan Ganti Rugi | STATIC | Menetapkan alokasi tanggung jawab. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 23 | PASAL 19 - Hukum dan Penyelesaian Sengketa | STATIC | Menetapkan governing law dan forum sengketa. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 24 | PASAL 20 - Ketentuan Lain | STATIC | Menampung klausul miscellaneous. | Tidak ada. | Teks hukum statis. | Tidak memerlukan source runtime. |
| 25 | PASAL 21 - Penutup | STATIC + SNAPSHOT | Menutup dokumen dan menegaskan keberlakuan perjanjian. | Nama para pihak, nomor dokumen. | `agreements.document_number`, `agreements.party_snapshot.organizer_name`, `platform_party_snapshot.*` target V2-1/V2-2 | Teks hukum statis, identitas dinamis; `document_number` tetap TBD bila policy/generator belum ada. |
| 26 | Halaman Penandatanganan | CONFIG + SNAPSHOT | Menyediakan blok tanda tangan para pihak. | Nama badan usaha, wakil, jabatan PIHAK PERTAMA; nama penyelenggara, penanggung jawab, jabatan PIHAK KEDUA. | `platform_party_snapshot.*` target V2-1/V2-2; `agreements.party_snapshot.*` | PIHAK PERTAMA belum punya source authoritative saat ini. |
| 27 | Lampiran I - Data Event & Informasi Pencairan | SNAPSHOT | Merangkum data event dan informasi pencairan yang bersifat contractual. | Nama event, penyelenggara, tanggal/waktu event, venue, alamat venue, kota/kabupaten, provinsi, periode penjualan, rekening pencairan, status verifikasi rekening. | `agreements.event_snapshot.*`, `agreements.party_snapshot.organizer_name`, `agreements.bank_snapshot.*` | V2-R0: TIDAK memuat ticket category/name, harga tiket, kuota, benefit, Buyer/Event Fee, payment gateway list, atau gateway fee. |
| 28 | Lampiran II - Dokumen/Kesiapan & Kontak Resmi | SNAPSHOT + CONFIG | Merangkum data dokumen pendukung dan kontak resmi yang benar-benar tersedia. | PIC penyelenggara, rekening/buku rekening, surat penyelenggara, kontak resmi para pihak, status verifikasi relevan bila ditampilkan. | `agreements.party_snapshot.*`, `agreements.bank_snapshot.*`, `agreements.document_snapshot.*`, `platform_party_snapshot.*` target V2-1/V2-2 | `bank_book_path`/private storage path dilarang masuk snapshot/PDF; jika checklist tertentu belum dikelola sistem, tulis `BELUM TERSEDIA - jangan diasumsikan`. |

## Field Source Matrix

| Bagian | Field | Jenis | Source/model/field | Sudah tersedia? | Masuk contractual diff? | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| PIHAK PERTAMA | nama badan usaha/pengelola | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2: admin settings/database -> frozen snapshot `platform_party_snapshot.name` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Saat ini tidak ada source database yang sah; jangan hardcode. |
| PIHAK PERTAMA | legalitas/NIB | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.legal_id` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Harus berasal dari profil legal platform resmi. |
| PIHAK PERTAMA | alamat | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.address` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Jangan ambil dari footer website atau teks bebas. |
| PIHAK PERTAMA | pemilik/wakil/penanggung jawab aplikasi | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.representative_name` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Dibutuhkan untuk identitas dan halaman tanda tangan. |
| PIHAK PERTAMA | jabatan | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.representative_position` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Belum ada field resmi saat ini. |
| PIHAK PERTAMA | email | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.email` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Email seed/admin sekarang bukan source kontraktual. |
| PIHAK PERTAMA | telepon/WhatsApp | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.phone` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Wajib ditandai gap. |
| PIHAK PERTAMA | situs/platform | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.website` atau ekuivalen | Belum | Ya, target V2-1/V2-2 | Domain/platform name harus berasal dari setting resmi. |
| PIHAK KEDUA | nama penyelenggara | DATABASE -> SNAPSHOT | Preview: `event_organizers.organizer_name`; Final: `agreements.party_snapshot.organizer_name` | Ya | Ya | Sudah dimonitor addendum. |
| PIHAK KEDUA | penanggung jawab | DATABASE -> SNAPSHOT | Preview: `event_organizers.responsible_name`; Final: `agreements.party_snapshot.responsible_name` | Ya | Ya | Sudah dimonitor addendum. |
| PIHAK KEDUA | jabatan | DATABASE -> SNAPSHOT | Preview: `event_organizers.responsible_position`; Final: `agreements.party_snapshot.responsible_position` | Ya | Ya | Sudah dimonitor addendum. |
| PIHAK KEDUA | email | DATABASE -> SNAPSHOT | Preview: `event_organizers.email`; Final: `agreements.party_snapshot.email` | Ya | Ya | Sudah dimonitor addendum. |
| PIHAK KEDUA | telepon | DATABASE -> SNAPSHOT | Preview: `event_organizers.phone`; Final: `agreements.party_snapshot.phone` | Ya | Ya | Sudah dimonitor addendum. |
| PIHAK KEDUA | alamat | DATABASE -> SNAPSHOT | Preview: `event_organizers.address`; Final: `agreements.party_snapshot.address` | Ya | Ya | Sudah dimonitor addendum. |
| EVENT | nama event | DATABASE -> SNAPSHOT | Preview: `events.event`; Final: `agreements.event_snapshot.event_name` | Ya | Ya | Sudah dimonitor addendum. |
| EVENT | tanggal mulai | DATABASE -> SNAPSHOT | Preview: `events.tanggal`; Final: `agreements.event_snapshot.start` | Ya | Ya | Diformat `d-m-Y H:i` saat snapshot. |
| EVENT | tanggal selesai | DATABASE -> SNAPSHOT | Preview: `events.event_end`; Final: `agreements.event_snapshot.end` | Ya | Ya | Diformat `d-m-Y H:i` saat snapshot. |
| EVENT | venue | DATABASE -> SNAPSHOT | Preview: `events.venue_name`; Final: `agreements.event_snapshot.venue_name` | Ya | Ya | Sudah dimonitor addendum. |
| EVENT | alamat venue | DATABASE -> SNAPSHOT | Preview: `events.venue_address` fallback `events.alamat`; Final: `agreements.event_snapshot.venue_address` | Ya | Ya | Fallback lama perlu dipertahankan bila implementasi v2 mengikuti source saat ini. |
| EVENT | kota | DATABASE -> SNAPSHOT | Preview: `events.venue_city`; Final: `agreements.event_snapshot.venue_city` | Ya | Ya | Sudah dimonitor addendum. |
| EVENT | provinsi | DATABASE -> SNAPSHOT | Preview: `events.venue_province`; Final: `agreements.event_snapshot.venue_province` | Ya | Ya | Sudah dimonitor addendum. |
| EVENT | periode mulai penjualan | DATABASE -> SNAPSHOT | Preview: `events.start_sale`; Final: `agreements.event_snapshot.start_sale` | Ya | Ya | Saat ini hanya start sale yang authoritative, belum ada end sale khusus. |
| REKENING | bank | DATABASE -> SNAPSHOT | Preview: `event_bank_accounts.bank_name`; Final: `agreements.bank_snapshot.bank_name` | Ya | Ya | Sudah dimonitor addendum. |
| REKENING | nomor rekening | DATABASE -> SNAPSHOT | Preview: `event_bank_accounts.account_number`; Final: `agreements.bank_snapshot.account_number` | Ya | Ya | Sudah dimonitor addendum. |
| REKENING | pemilik rekening | DATABASE -> SNAPSHOT | Preview: `event_bank_accounts.account_holder_name`; Final: `agreements.bank_snapshot.account_holder_name` | Ya | Ya | Sudah dimonitor addendum. |
| REKENING | status verifikasi rekening | DATABASE -> SNAPSHOT | Preview: `event_bank_accounts.status`; Final: `agreements.bank_snapshot.verification_status` | Ya | Tidak | Tersedia untuk Lampiran II/informational, tetapi perubahan status belum memicu addendum. |
| REKENING | buku rekening | DATABASE | Source aman jika diperlukan: `event_bank_accounts.bank_book_original_name`, `bank_book_mime`, status/verifikasi terkait. `bank_book_path` private storage path dilarang masuk snapshot/PDF | Sebagian | Tidak | Lampiran II hanya boleh memakai status keberadaan/verifikasi atau metadata aman; jangan menyimpan path privat ke contractual snapshot atau PDF. |
| DOKUMEN | nomor surat penyelenggara | DATABASE -> SNAPSHOT | Preview: `event_documents.document_number` pada `document_type = ORGANIZER_LETTER`; Final: `agreements.document_snapshot.document_number` | Ya | Ya | Sudah dimonitor addendum. |
| DOKUMEN | tanggal surat penyelenggara | DATABASE -> SNAPSHOT | Preview: `event_documents.document_date`; Final: `agreements.document_snapshot.document_date` | Ya | Ya | Sudah dimonitor addendum. |
| DOKUMEN | status dokumen penyelenggara | DATABASE -> SNAPSHOT | Preview: `event_documents.status`; Final: `agreements.document_snapshot.verification_status` | Ya | Tidak | Tersedia untuk Lampiran II/informational, tetapi belum memicu addendum. |
| DOKUMEN | jenis dokumen penyelenggara | DATABASE -> SNAPSHOT | Preview/final: `event_documents.document_type` -> `agreements.document_snapshot.document_type` | Ya | Ya | Saat ini default ke `ORGANIZER_LETTER`. |
| DOKUMEN | nama file surat penyelenggara | DATABASE -> SNAPSHOT | Preview: `event_documents.original_name`; Final: `agreements.document_snapshot.original_name` | Ya | Ya | Saat ini termasuk contractual diff. |
| DOKUMEN | nomor dokumen agreement | DATABASE | `agreements.document_number` | Ya, field tersedia | TBD | Field tersedia, tetapi policy/generator numbering authoritative belum didefinisikan; jangan asumsikan selalu terisi. |
| KOMERSIAL | buyer/event fee | DATABASE -> SNAPSHOT | Sumber dasar: `events.fee` via `TicketPricingService::tax($event, 0)`; Final: `agreements.event_snapshot.buyer_fee` dan `agreements.commercial_snapshot.buyer_fee` | Ya | Tidak | V2-R0: konfigurasi operasional; boleh disnapshot untuk audit tetapi nilai tidak kontraktual dan tidak memicu addendum. Semantik saat ini: `percent`, `fixed`, atau `none`. |
| KOMERSIAL | payment gateway yang digunakan | DATABASE -> SNAPSHOT | Preview: `event_payment_gateways` join `payment_gateways`; Final: `agreements.commercial_snapshot.payment_gateways[*].payment` | Ya | Tidak | V2-R0: konfigurasi operasional; boleh disnapshot untuk audit tetapi tidak kontraktual. Hanya gateway yang punya relasi `paymentGateway` valid. |
| KOMERSIAL | active/inactive payment method | DATABASE -> SNAPSHOT | Preview/final: `event_payment_gateways.is_active`, `payment_gateways.is_active`, `effective_is_active` | Ya | Tidak | V2-R0: konfigurasi operasional; tidak memicu addendum. |
| KOMERSIAL | payment channel fee fixed | DATABASE -> SNAPSHOT | Sumber dasar: `event_payment_gateways.fee_fixed` atau fallback `payment_gateways.default_fee_fixed` / `payment_gateways.biaya`; Final: `agreements.commercial_snapshot.payment_gateways[*].resolved_fee_fixed` | Ya | Tidak | V2-R0: konfigurasi operasional; tidak kontraktual. Ini payment channel fee, bukan platform/system fee. |
| KOMERSIAL | payment channel fee percent | DATABASE -> SNAPSHOT | Sumber dasar: `event_payment_gateways.fee_percent` atau fallback `payment_gateways.default_fee_percent` / `payment_gateways.biaya`; Final: `agreements.commercial_snapshot.payment_gateways[*].resolved_fee_percent` | Ya | Tidak | V2-R0: konfigurasi operasional; tidak kontraktual. Ini payment channel fee, bukan platform/system fee. |
| KOMERSIAL | fee mode gateway | DATABASE -> SNAPSHOT | `event_payment_gateways.fee_mode`; Final: `agreements.commercial_snapshot.payment_gateways[*].fee_mode` | Ya | Tidak | V2-R0: konfigurasi operasional; tidak memicu addendum. `global` atau `manual`. |
| KOMERSIAL | platform/system fee | CONFIG | BELUM ADA SOURCE AUTHORITATIVE jika belum ada field khusus | Belum | TBD | Jangan memakai istilah ini sebagai alias `buyer_fee` atau `payment channel fee`. |
| KOMERSIAL | payment OTP | DATABASE -> SNAPSHOT | Preview: `events.payment_otp_enabled`; Final: `agreements.commercial_snapshot.payment_otp_enabled` | Ya | Ya | Cantumkan hanya jika pasal/lampiran memang membutuhkan. |
| LAMPIRAN I | nama event | SNAPSHOT | `agreements.event_snapshot.event_name` | Ya | Ya | Termasuk data contractual. |
| LAMPIRAN I | penyelenggara | SNAPSHOT | `agreements.party_snapshot.organizer_name` | Ya | Ya | Termasuk data contractual. |
| LAMPIRAN I | tanggal/waktu event | SNAPSHOT | `agreements.event_snapshot.start`, `agreements.event_snapshot.end` | Ya | Ya | Termasuk data contractual. |
| LAMPIRAN I | venue | SNAPSHOT | `agreements.event_snapshot.venue_name` | Ya | Ya | Termasuk data contractual. |
| LAMPIRAN I | alamat venue | SNAPSHOT | `agreements.event_snapshot.venue_address`, `venue_city`, `venue_province` | Ya | Ya | Termasuk data contractual. |
| LAMPIRAN I | periode penjualan | SNAPSHOT | `agreements.event_snapshot.start_sale` | Ya | Ya | End of sales belum punya field khusus authoritative. |
| LAMPIRAN I | buyer/event fee | SNAPSHOT | `agreements.commercial_snapshot.buyer_fee` | Ya | Tidak | V2-R0: TIDAK termasuk Lampiran I; hanya disnapshot untuk audit, nilai tidak kontraktual. |
| LAMPIRAN I | payment method | SNAPSHOT | `agreements.commercial_snapshot.payment_gateways[*].payment` | Ya | Tidak | V2-R0: TIDAK termasuk Lampiran I. |
| LAMPIRAN I | payment channel fee | SNAPSHOT | `agreements.commercial_snapshot.payment_gateways[*].resolved_fee_fixed`, `resolved_fee_percent` | Ya | Tidak | V2-R0: TIDAK termasuk Lampiran I. |
| LAMPIRAN I | rekening pencairan | SNAPSHOT | `agreements.bank_snapshot.bank_name`, `account_number`, `account_holder_name` | Ya | Ya | Termasuk data contractual. |
| LAMPIRAN II | identitas penanggung jawab penyelenggara | SNAPSHOT | `agreements.party_snapshot.responsible_name`, `responsible_position`, `email`, `phone` | Ya | Ya | Data kontak penyelenggara tersedia. |
| LAMPIRAN II | rekening/buku rekening | SNAPSHOT + DATABASE | Snapshot rekening: `agreements.bank_snapshot.*`; metadata aman bila diperlukan dari source live `event_bank_accounts.bank_book_original_name`, `bank_book_mime`, status/verifikasi | Sebagian | Rekening: Ya, buku rekening file: Tidak | `bank_book_path` atau private storage path tidak boleh masuk snapshot/PDF. |
| LAMPIRAN II | surat penyelenggara | SNAPSHOT | `agreements.document_snapshot.document_number`, `document_date`, `original_name`, `verification_status` | Ya | Nomor/tanggal/file: Ya, status: Tidak | Pisahkan antara data contractual dan status informasional. |
| LAMPIRAN II | kontak resmi PIHAK PERTAMA | CONFIG | BELUM ADA SOURCE AUTHORITATIVE. Target V2-1/V2-2 admin settings/database -> `platform_party_snapshot.*` | Belum | Ya, target V2-1/V2-2 | Tulis `BELUM TERSEDIA - jangan diasumsikan` sampai source authoritative tersedia. |
| LAMPIRAN II | checklist kesiapan lain di luar sistem | STATIC | BELUM TERSEDIA - jangan diasumsikan | Belum | Tidak | Jangan buat checklist fiktif tanpa source. |
| OUT OF CONTRACT | kategori tiket | DATABASE | `hargas` / data tiket lain | Ya | Tidak | Tidak boleh masuk template `mou-v2` dan tidak boleh trigger addendum. |
| OUT OF CONTRACT | nama tiket | DATABASE | `hargas` / data tiket lain | Ya | Tidak | Tidak boleh masuk template `mou-v2` dan tidak boleh trigger addendum. |
| OUT OF CONTRACT | harga tiket | DATABASE | `hargas` / data tiket lain | Ya | Tidak | Tidak boleh masuk template `mou-v2` dan tidak boleh trigger addendum. |
| OUT OF CONTRACT | kuota | DATABASE | `hargas` / data tiket lain | Ya | Tidak | Tidak boleh masuk template `mou-v2` dan tidak boleh trigger addendum. |
| OUT OF CONTRACT | benefit tiket | DATABASE | Tidak dipetakan untuk kontrak | Tidak diketahui | Tidak | Eksplisit di luar kontrak. |
| OUT OF CONTRACT | syarat per kategori tiket | DATABASE | Tidak dipetakan untuk kontrak | Tidak diketahui | Tidak | Eksplisit di luar kontrak. |
| KTP PENANGGUNG JAWAB | dokumen identitas penanggung jawab | DATABASE (target) | Upload penyewa per Event; file private; admin approve/reject; status VERIFIED wajib sebelum finalisasi | Target | Tidak | V2-R0: KTP adalah prerequisite/readiness finalisasi dan bukti administratif, BUKAN contractual diff; perubahan status/verifikasi KTP TIDAK membuat Addendum; boleh dibekukan metadata/status aman untuk audit; MOU/Lampiran hanya menampilkan status TERVERIFIKASI / MENUNGGU VERIFIKASI / DITOLAK; jangan tampilkan foto KTP, NIK, private/signed/storage path. Fase awal tanpa field DB khusus NIK. |

## Bagian yang Bersifat Teks Hukum Tetap

Bagian berikut sebaiknya diperlakukan sebagai static legal text pada implementasi `mou-v2`:

- Cover title dan label dokumen.
- Pembukaan perjanjian selain placeholder identitas/nomor dokumen.
- PASAL 1 sampai PASAL 21 sebagai teks hukum inti.
- Label dan heading halaman penandatanganan.
- Heading Lampiran I dan Lampiran II.

Pendekatan implementasi yang diharapkan:

- Hanya placeholder data yang bersifat dinamis.
- Narasi hukum tidak dihasilkan dari database.
- Jika suatu nilai belum memiliki source authoritative, placeholder-nya tidak boleh diisi otomatis.

## Catatan Implementasi Lanjutan

- V2-1/V2-2 perlu membuat source authoritative untuk seluruh identitas PIHAK PERTAMA melalui database/admin settings dan dedicated frozen snapshot `platform_party_snapshot`.
- Saat implementasi `mou-v2`, preview dapat memakai live query seperti pola `AgreementPreviewService`, tetapi final render harus memakai payload yang dibangun dari snapshot agreement.
- Snapshot `mou-v2` harus menjaga prinsip yang sudah berlaku saat ini: completed agreement immutable dan perubahan contractual setelah completed diproses lewat addendum, bukan regenerate PDF lama.
- Perubahan profil global Gotik setelah agreement dibekukan tidak boleh otomatis mengubah agreement lama atau memicu addendum; addendum untuk profil PIHAK PERTAMA harus eksplisit bila memang dibutuhkan.
- `agreements.document_number` tetap perlu policy/generator authoritative sebelum implementasi mengandalkan field tersebut sebagai nomor dokumen final.
- Jika Lampiran II ingin menampilkan bukti file buku rekening, hanya metadata aman atau status verifikasi yang boleh dipakai; `bank_book_path` privat tidak boleh ikut dibekukan ke snapshot final maupun dirender ke PDF.

## Revisi Scope V2-R0 (Post-UAT)

Revisi ini ditetapkan setelah UAT dan menjadi acuan corrective phase. Revisi hanya berlaku untuk dokumen baru yang difinalisasi setelah implementasi corrective phase; MOU/Addendum yang sudah `READY`/`COMPLETED` tetap immutable, tidak diregenerate, tidak dimigrasikan massal, dan tetap memakai file historical existing.

### 1. Buyer/Event Fee — Konfigurasi Operasional, Bukan Kontraktual

- `events.fee` (Buyer/Event Fee) adalah konfigurasi transaksi operasional.
- Nilainya dapat berubah sesuai kewenangan aplikasi.
- Boleh tetap disnapshot untuk audit/transaction history.
- Nominal/persentasenya TIDAK menjadi isi kontraktual MOU.
- Perubahan nilainya TIDAK memicu Addendum.

MOU hanya boleh menjelaskan secara umum bahwa biaya tambahan kepada Pembeli harus diinformasikan sebelum transaksi; nilai spesifik (`Rp X` / `X%`) tidak disebut sebagai bagian kontrak.

### 2. Payment Gateway — Konfigurasi Operasional, Bukan Kontraktual

Berikut adalah konfigurasi operasional:

- daftar payment gateway
- status active/inactive
- fixed fee
- percentage fee
- fee_mode global/manual

Data boleh tetap tersimpan pada `commercial_snapshot` untuk audit, tetapi:

- tidak dirender sebagai nilai kontraktual MOU
- tidak memicu Addendum
- tidak perlu dicantumkan di Lampiran I

MOU boleh menjelaskan secara umum bahwa Platform menyediakan kanal pembayaran yang tersedia dan biaya kanal, jika ada, ditampilkan kepada Pembeli sebelum transaksi.

### 3. Lampiran I — Scope Direvisi

Scope Lampiran I direvisi menjadi **DATA EVENT DAN INFORMASI PENCAIRAN**.

Minimal contractual data:

- Nama Event
- Nama Penyelenggara
- Tanggal/Waktu Mulai
- Tanggal/Waktu Selesai
- Venue
- Alamat Venue
- Kota/Kabupaten
- Provinsi
- Mulai Penjualan
- Rekening pencairan
- Status verifikasi rekening

TIDAK BOLEH memuat:

- ticket category
- ticket name
- harga tiket
- kuota
- benefit
- Buyer/Event Fee
- payment gateway list
- gateway fee

### 4. KTP Penanggung Jawab

Requirement:

- KTP/dokumen identitas penanggung jawab disimpan per Event.
- File bersifat private.
- Upload oleh penyewa.
- Admin approve/reject.
- Status `VERIFIED` wajib sebelum MOU dapat difinalisasi.

MOU/Lampiran hanya boleh menampilkan status:

- Identitas Penanggung Jawab: `TERVERIFIKASI` / `MENUNGGU VERIFIKASI` / `DITOLAK`

KTP adalah prerequisite/readiness finalisasi dan bukti administratif, BUKAN contractual diff. Perubahan status/verifikasi KTP TIDAK membuat Addendum; metadata/status aman boleh dibekukan untuk audit.

JANGAN tampilkan:

- foto KTP
- NIK
- private path
- signed URL
- storage path

Pada fase awal jangan membuat field database khusus NIK.

### 5. PDF Pagination

Aturan rendering PDF:

- Section panjang boleh split antar halaman.
- Jangan `page-break-inside: avoid` pada seluruh section.
- Heading tidak boleh orphan dari konten pertama.
- Table row individual tidak boleh terpotong jika memungkinkan.
- Table header boleh repeat.
- Cover harus stabil satu halaman.
- Signature block tidak boleh terpotong.
- Lampiran boleh berlanjut ke halaman berikutnya.

Tidak ada penguncian exact page count.

### 6. Addendum — Diff yang Tidak Kontraktual

Perubahan berikut TIDAK merupakan contractual diff:

- Buyer/Event Fee
- payment gateway active/inactive
- payment gateway fee
- fee_mode

Perubahan contractual Event/Organizer/Bank existing tetap mengikuti versioning existing. Data tiket tetap OUT OF CONTRACT.

### 7. Historical — Immutable

Aturan immutability berlaku untuk seluruh agreement non-DRAFT, tidak terbatas pada `READY`/`COMPLETED`:

- Hanya Agreement `DRAFT` yang belum memiliki file historical yang boleh menghasilkan preview/final PDF baru sesuai lifecycle existing.
- Agreement non-DRAFT (`READY`, `SENT_TO_PRIVY`, `SIGNING`, `COMPLETED`, `REJECTED`, `CANCELLED`) tidak boleh diregenerate.
- File `unsigned`/`signed` existing tidak boleh dioverwrite.
- Agreement non-DRAFT tetap mengikuti file/history existing bila tersedia.

Tidak ada backfill, regenerate, atau migrasi historical. Revisi hanya berlaku untuk dokumen baru setelah implementasi corrective phase.
