# MOU V2 - UAT Checklist Manual

Checklist manual ini digunakan sebagai final QA untuk template `mou-v2` sebelum rilis.
**Jangan mengisi PASS secara otomatis** - setiap item wajib diverifikasi secara manual dan
diisi oleh tester.

> Catatan per 30 Agustus 2026: Checklist ini mengikuti implementasi final V2-R0 s.d. V2-R5.
> Buyer/Event Fee dan payment gateway tetap boleh dikonfigurasi sebagai data operasional
> untuk audit/UAT negatif, tetapi bukan isi kontraktual MOU.

Format kolom:

- `[ ] PASS` / `[ ] FAIL`
- **Evidence**: tautan / file / screenshot / nomor run yang membuktikan verifikasi.
- **Catatan**: temuan, kendala, atau konteks tambahan.

---

## A. Data Awal

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Platform Legal Profile terisi (nama badan usaha, legalitas, alamat, wakil, kontak) | [ ] PASS / [ ] FAIL | | |
| Organizer Event terisi (nama, penanggung jawab, jabatan, telepon, email, alamat) | [ ] PASS / [ ] FAIL | | |
| Rekening Event terisi + terverifikasi + file buku rekening fisik tersedia | [ ] PASS / [ ] FAIL | | |
| Surat penyelenggara terisi + terverifikasi + file fisik tersedia | [ ] PASS / [ ] FAIL | | |
| KTP/dokumen identitas penanggung jawab terupload (file private) + status VERIFIED | [ ] PASS / [ ] FAIL | | |
| Payment gateway event dikonfigurasi (minimal 1 efektif aktif) | [ ] PASS / [ ] FAIL | | Untuk membuktikan daftar/detail gateway tidak bocor ke kontrak dan perubahan gateway tidak memicu Addendum. |
| Buyer / event fee terkonfigurasi (Rp / persen) | [ ] PASS / [ ] FAIL | | Untuk membuktikan nilai fee tetap operasional dan tidak bocor ke kontrak atau diff Addendum. |

## B. Draft Preview

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| MOU v2 tampil pada tab review (draft) | [ ] PASS / [ ] FAIL | | |
| PIHAK PERTAMA benar (dari frozen platform profile) | [ ] PASS / [ ] FAIL | | |
| PIHAK KEDUA benar (dari frozen organizer) | [ ] PASS / [ ] FAIL | | |
| Pasal 1-21 lengkap | [ ] PASS / [ ] FAIL | | |
| LAMPIRAN I benar (Data Event & Informasi Pencairan - tanpa fee/gateway) | [ ] PASS / [ ] FAIL | | |
| LAMPIRAN II benar (readiness + kontak resmi) | [ ] PASS / [ ] FAIL | | |
| Preview read-only dan tidak mengubah file historis | [ ] PASS / [ ] FAIL | | |

## C. Finalisasi

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Finalisasi menghasilkan `template_version = mou-v2` | [ ] PASS / [ ] FAIL | | |
| Unsigned PDF dapat didownload | [ ] PASS / [ ] FAIL | | |
| PDF A4 portrait | [ ] PASS / [ ] FAIL | | |
| Cover stabil satu halaman dan layout konsisten | [ ] PASS / [ ] FAIL | | |
| Event box pada cover tidak pecah | [ ] PASS / [ ] FAIL | | |
| Heading tidak orphan / tidak terpotong buruk | [ ] PASS / [ ] FAIL | | |
| Section panjang boleh split alami antar halaman | [ ] PASS / [ ] FAIL | | |
| Table row tidak pecah buruk; thead repeat bila applicable | [ ] PASS / [ ] FAIL | | |
| PDF tidak menampilkan tabel/daftar payment gateway, status active/inactive gateway, atau exact fixed/percentage gateway fee | [ ] PASS / [ ] FAIL | | |
| Signature block / signature page terbaca stabil | [ ] PASS / [ ] FAIL | | |
| Page break Lampiran I / II wajar | [ ] PASS / [ ] FAIL | | Jangan menilai berdasarkan exact jumlah halaman. |
| Finalisasi ulang tidak menimpa file unsigned existing (guard `historical_file_exists`) | [ ] PASS / [ ] FAIL | | |

## D. Manual Signing

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Download unsigned (owner / penyewa) | [ ] PASS / [ ] FAIL | | |
| Signing dilakukan independen (tanpa Privy / OAuth / webhook / callback) | [ ] PASS / [ ] FAIL | | |
| Signed PDF dapat diupload | [ ] PASS / [ ] FAIL | | |
| Admin dapat mereview dokumen signed | [ ] PASS / [ ] FAIL | | |
| Status menjadi COMPLETED setelah approve | [ ] PASS / [ ] FAIL | | |
| Tenant lain tidak dapat mengakses file (isolasi) | [ ] PASS / [ ] FAIL | | |
| Path private storage tidak diekspos sebagai public URL | [ ] PASS / [ ] FAIL | | |

## E. Full Lifecycle

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Urutan DRAFT -> Preview -> Finalize -> unsigned PDF -> signing independen -> upload signed PDF -> admin approve -> COMPLETED -> activation allowed tervalidasi end-to-end | [ ] PASS / [ ] FAIL | | |
| Tidak ada Privy API / OAuth / webhook / callback pada lifecycle MOU v2 | [ ] PASS / [ ] FAIL | | |

## F. Activation

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Event diblok aktivasi sebelum MOU COMPLETED | [ ] PASS / [ ] FAIL | | |
| Event dapat diaktifkan setelah MOU COMPLETED (sesuai guard) | [ ] PASS / [ ] FAIL | | |
| Addendum pending memblokir aktivasi (M12) | [ ] PASS / [ ] FAIL | | |
| Addendum COMPLETED mengembalikan eligibility aktivasi | [ ] PASS / [ ] FAIL | | |

## G. Addendum

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Perubahan contractual data Event memunculkan Addendum v2 (parent v2) | [ ] PASS / [ ] FAIL | | |
| Perubahan contractual organizer / responsible data memunculkan Addendum | [ ] PASS / [ ] FAIL | | |
| Perubahan contractual rekening bank memunculkan Addendum | [ ] PASS / [ ] FAIL | | |
| Perubahan contractual surat penyelenggara memunculkan Addendum | [ ] PASS / [ ] FAIL | | |
| Perubahan `payment_otp_enabled` memunculkan Addendum | [ ] PASS / [ ] FAIL | | |
| Diff kontraktual benar dan tampil | [ ] PASS / [ ] FAIL | | |
| Addendum v2 memakai frozen PIHAK PERTAMA (bukan live profile terbaru) | [ ] PASS / [ ] FAIL | | |
| Tidak ada hardcode nama platform ("Tim Gotik Indonesia") | [ ] PASS / [ ] FAIL | | |
| Finalisasi + signing Addendum bekerja | [ ] PASS / [ ] FAIL | | |
| Perubahan ticket-only (kategori/nama/harga/kuota/benefit/syarat) tidak membuat Addendum | [ ] PASS / [ ] FAIL | | |
| Data ticket tidak muncul pada MOU / Lampiran | [ ] PASS / [ ] FAIL | | |
| Perubahan Buyer/Event Fee tidak membuat Addendum | [ ] PASS / [ ] FAIL | | |
| Perubahan payment gateway (global/event active/inactive, fee_mode, fixed fee, percentage fee, add/remove) tidak membuat Addendum | [ ] PASS / [ ] FAIL | | |
| Replacement/review status KTP penanggung jawab tidak membuat Addendum jika responsible person tidak berubah | [ ] PASS / [ ] FAIL | | |
| Perubahan `responsible_name` / `responsible_position` tetap membuat Addendum | [ ] PASS / [ ] FAIL | | |

## H. Historical

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| MOU v1 lama tetap dapat didownload | [ ] PASS / [ ] FAIL | | |
| Addendum v1 lama tetap dapat didownload | [ ] PASS / [ ] FAIL | | |
| MOU v2 COMPLETED tetap dapat didownload dari file lama | [ ] PASS / [ ] FAIL | | |
| Addendum v2 COMPLETED tetap dapat didownload dari file lama | [ ] PASS / [ ] FAIL | | |
| `unsigned_pdf_path` historical tidak overwrite | [ ] PASS / [ ] FAIL | | |
| `signed_pdf_path` historical tidak overwrite | [ ] PASS / [ ] FAIL | | |
| `template_version` historical tidak berubah | [ ] PASS / [ ] FAIL | | |
| File historical tidak berubah oleh cutover / live change baru | [ ] PASS / [ ] FAIL | | |
| Parent lineage v1 tetap addendum-v1; parent v2 tetap addendum-v2 | [ ] PASS / [ ] FAIL | | |

## I. Lampiran I - Scope V2-R3

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Lampiran I berjudul "Data Event & Informasi Pencairan" | [ ] PASS / [ ] FAIL | | |
| Memuat minimal: nama event, penyelenggara, tanggal/waktu, venue, alamat venue, kota, provinsi, mulai penjualan, rekening pencairan, status verifikasi rekening | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat ticket category / nama tiket / harga / kuota / benefit | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat label atau nilai Buyer/Event Fee sebagai kontrak | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat daftar/detail payment gateway | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat status active/inactive gateway atau exact fixed/percentage gateway fee | [ ] PASS / [ ] FAIL | | |
| Body kontrak hanya boleh menjelaskan biaya tambahan / kanal-metode pembayaran secara umum tanpa membekukan konfigurasi mutable | [ ] PASS / [ ] FAIL | | |

## J. PDF Pagination (V2-R4)

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Cover stabil satu halaman | [ ] PASS / [ ] FAIL | | |
| Cover event box tidak pecah | [ ] PASS / [ ] FAIL | | |
| Heading tidak orphan dari konten pertama | [ ] PASS / [ ] FAIL | | |
| Section panjang boleh split antar halaman | [ ] PASS / [ ] FAIL | | |
| Table row individual tidak terpotong jika memungkinkan | [ ] PASS / [ ] FAIL | | |
| Table header boleh repeat bila applicable | [ ] PASS / [ ] FAIL | | |
| Signature block tidak terpotong | [ ] PASS / [ ] FAIL | | |
| Lampiran I dan II page break wajar | [ ] PASS / [ ] FAIL | | |
| Tidak ada asumsi exact page count | [ ] PASS / [ ] FAIL | | |

## K. Lampiran II

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Missing organizer -> `BELUM LENGKAP` | [ ] PASS / [ ] FAIL | | |
| Missing rekening -> `BELUM LENGKAP` | [ ] PASS / [ ] FAIL | | |
| Missing surat penyelenggara -> `BELUM LENGKAP` | [ ] PASS / [ ] FAIL | | |
| Buku Rekening Fisik tetap `BELUM TERSEDIA` (snapshot belum simpan availability file) | [ ] PASS / [ ] FAIL | | |
| Status identitas penanggung jawab tampil aman pada Lampiran II | [ ] PASS / [ ] FAIL | | |
| Path private tidak dirender | [ ] PASS / [ ] FAIL | | |

## L. KTP Penanggung Jawab (V2-R5)

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Upload KTP/dokumen identitas penanggung jawab oleh penyewa (file private) | [ ] PASS / [ ] FAIL | | |
| Admin dapat preview dokumen private dengan aman | [ ] PASS / [ ] FAIL | | |
| Admin dapat approve / reject | [ ] PASS / [ ] FAIL | | |
| Status VERIFIED wajib sebelum MOU difinalisasi | [ ] PASS / [ ] FAIL | | |
| MOU/Lampiran hanya menampilkan status (TERVERIFIKASI / MENUNGGU VERIFIKASI / DITOLAK) | [ ] PASS / [ ] FAIL | | |
| Foto KTP / NIK / file_path / storage path / signed URL / private URL tidak tampil | [ ] PASS / [ ] FAIL | | |
| Setelah MOU COMPLETED, replacement/review status KTP sendiri tidak membuat Addendum jika responsible person sama | [ ] PASS / [ ] FAIL | | |
| Perubahan `responsible_name` / `responsible_position` tetap membuat Addendum | [ ] PASS / [ ] FAIL | | |

## M. Automated Regression Evidence

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Command / workflow CI yang dijalankan dicatat | [ ] PASS / [ ] FAIL | | |
| Jumlah test / assertion dicatat bila diketahui | [ ] PASS / [ ] FAIL | | |
| GitHub Actions run / URL / nomor run dicatat | [ ] PASS / [ ] FAIL | | |
| Hasil aktual PASS / FAIL diisi setelah run selesai | [ ] PASS / [ ] FAIL | | Jangan isi otomatis sebelum run aktual selesai. |

---

## Final Gate - Production Readiness

| Gate | Hasil |
| --- | --- |
| Automated Regression | [ ] PASS |
| Manual UAT | [ ] PASS |
| Legal Review | [ ] APPROVED |
| **Production Ready** | [ ] YES |

**Production Ready hanya boleh `YES` jika Automated Regression = PASS, Manual UAT = PASS, dan Legal Review = APPROVED.**

> Catatan: lulusnya automated test dan manual UAT TIDAK otomatis menjadikan template
> legal-approved. Legal gate hanya selesai jika reviewer hukum/counsel mengisi
> sign-off secara eksplisit (lihat `docs/mou-v2-legal-review-checklist.md`).
