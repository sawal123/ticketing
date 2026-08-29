# MOU V2 — UAT Checklist Manual

Checklist manual ini digunakan sebagai final QA untuk template `mou-v2` sebelum rilis.
**Jangan mengisi PASS secara otomatis** — setiap item wajib diverifikasi secara manual dan
diisi oleh tester.

> Catatan V2-R0: Checklist ini mengikuti scope kontrak terbaru setelah UAT — Buyer/Event Fee dan
> payment gateway adalah konfigurasi operasional (bukan isi kontraktual MOU), lihat
> `docs/mou-template-v2-spec.md` bagian "Revisi Scope V2-R0 (Post-UAT)".

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
| Payment gateway event dikonfigurasi (minimal 1 efektif aktif) | [ ] PASS / [ ] FAIL | | |
| Buyer / event fee terkonfigurasi (Rp / persen) | [ ] PASS / [ ] FAIL | | |

## B. Draft Preview

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| MOU v2 tampil pada tab review (draft) | [ ] PASS / [ ] FAIL | | |
| PIHAK PERTAMA benar (dari frozen platform profile) | [ ] PASS / [ ] FAIL | | |
| PIHAK KEDUA benar (dari frozen organizer) | [ ] PASS / [ ] FAIL | | |
| Pasal 1–21 lengkap | [ ] PASS / [ ] FAIL | | |
| LAMPIRAN I benar (Data Event & Informasi Pencairan — tanpa fee/gateway) | [ ] PASS / [ ] FAIL | | |
| LAMPIRAN II benar (readiness + kontak resmi) | [ ] PASS / [ ] FAIL | | |
| Preview read-only dan tidak mengubah file historis | [ ] PASS / [ ] FAIL | | |

## C. Finalisasi

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Finalisasi menghasilkan `template_version = mou-v2` | [ ] PASS / [ ] FAIL | | |
| Unsigned PDF dapat didownload | [ ] PASS / [ ] FAIL | | |
| PDF A4 portrait | [ ] PASS / [ ] FAIL | | |
| Cover / layout konsisten | [ ] PASS / [ ] FAIL | | |
| Heading tidak terpotong buruk | [ ] PASS / [ ] FAIL | | |
| Tabel gateway terbaca | [ ] PASS / [ ] FAIL | | |
| Signature page terbaca | [ ] PASS / [ ] FAIL | | |
| Page break Lampiran I / II wajar | [ ] PASS / [ ] FAIL | | |
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

## E. Activation

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Event diblok aktivasi sebelum MOU COMPLETED | [ ] PASS / [ ] FAIL | | |
| Event dapat diaktifkan setelah MOU COMPLETED (sesuai guard) | [ ] PASS / [ ] FAIL | | |
| Addendum pending memblokir aktivasi (M12) | [ ] PASS / [ ] FAIL | | |
| Addendum COMPLETED mengembalikan eligibility aktivasi | [ ] PASS / [ ] FAIL | | |

## F. Addendum

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Perubahan contractual data Event memunculkan Addendum v2 (parent v2) | [ ] PASS / [ ] FAIL | | |
| Diff kontraktual benar dan tampil | [ ] PASS / [ ] FAIL | | |
| Addendum v2 memakai frozen PIHAK PERTAMA (bukan live profile terbaru) | [ ] PASS / [ ] FAIL | | |
| Tidak ada hardcode nama platform ("Tim Gotik Indonesia") | [ ] PASS / [ ] FAIL | | |
| Finalisasi + signing Addendum bekerja | [ ] PASS / [ ] FAIL | | |
| Perubahan ticket-only (kategori/nama/harga/kuota/benefit/syarat) tidak membuat Addendum | [ ] PASS / [ ] FAIL | | |
| Data ticket tidak muncul pada MOU / Lampiran | [ ] PASS / [ ] FAIL | | |
| Perubahan Buyer/Event Fee tidak membuat Addendum | [ ] PASS / [ ] FAIL | | |
| Perubahan payment gateway (active/inactive, fee, fee_mode) tidak membuat Addendum | [ ] PASS / [ ] FAIL | | |

## G. Historical

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| MOU v1 lama tetap dapat didownload | [ ] PASS / [ ] FAIL | | |
| Addendum v1 lama tetap dapat didownload | [ ] PASS / [ ] FAIL | | |
| File historical tidak berubah oleh cutover v2 | [ ] PASS / [ ] FAIL | | |
| Parent lineage v1 tetap addendum-v1; parent v2 tetap addendum-v2 | [ ] PASS / [ ] FAIL | | |

## H. Lampiran I — Scope V2-R0

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Lampiran I berjudul "Data Event & Informasi Pencairan" | [ ] PASS / [ ] FAIL | | |
| Memuat minimal: nama event, penyelenggara, tanggal/waktu, venue, alamat venue, kota, provinsi, mulai penjualan, rekening pencairan, status verifikasi rekening | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat ticket category / nama tiket / harga / kuota / benefit | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat Buyer/Event Fee (nilai Rp/% tidak muncul sebagai kontrak) | [ ] PASS / [ ] FAIL | | |
| TIDAK memuat daftar payment gateway / gateway fee | [ ] PASS / [ ] FAIL | | |
| Buyer/Event Fee hanya disebut umum ("Biaya Pembeli / Event Fee") tanpa nilai | [ ] PASS / [ ] FAIL | | |

## I. PDF Pagination (V2-R0)

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Section panjang boleh split antar halaman | [ ] PASS / [ ] FAIL | | |
| Heading tidak orphan dari konten pertama | [ ] PASS / [ ] FAIL | | |
| Table row individual tidak terpotong jika memungkinkan | [ ] PASS / [ ] FAIL | | |
| Table header boleh repeat | [ ] PASS / [ ] FAIL | | |
| Cover stabil satu halaman | [ ] PASS / [ ] FAIL | | |
| Signature block tidak terpotong | [ ] PASS / [ ] FAIL | | |
| Lampiran boleh lanjut ke halaman berikutnya | [ ] PASS / [ ] FAIL | | |
| Tidak ada asumsi exact page count | [ ] PASS / [ ] FAIL | | |

## J. Lampiran II

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Missing organizer → `BELUM LENGKAP` | [ ] PASS / [ ] FAIL | | |
| Missing rekening → `BELUM LENGKAP` | [ ] PASS / [ ] FAIL | | |
| Missing surat penyelenggara → `BELUM LENGKAP` | [ ] PASS / [ ] FAIL | | |
| Buku Rekening Fisik tetap `BELUM TERSEDIA` (snapshot belum simpan availability file) | [ ] PASS / [ ] FAIL | | |
| Path private tidak dirender | [ ] PASS / [ ] FAIL | | |

## K. KTP Penanggung Jawab (V2-R0)

| Item | Hasil | Evidence | Catatan |
| --- | --- | --- | --- |
| Upload KTP/dokumen identitas penanggung jawab oleh penyewa (file private) | [ ] PASS / [ ] FAIL | | |
| Admin dapat approve / reject | [ ] PASS / [ ] FAIL | | |
| Status VERIFIED wajib sebelum MOU difinalisasi | [ ] PASS / [ ] FAIL | | |
| MOU/Lampiran hanya menampilkan status (TERVERIFIKASI / MENUNGGU VERIFIKASI / DITOLAK) | [ ] PASS / [ ] FAIL | | |
| Foto KTP / NIK / private path / signed URL / storage path tidak tampil | [ ] PASS / [ ] FAIL | | |

---

## Final Gate — Production Readiness

| Gate | Hasil |
| --- | --- |
| Automated Regression | [ ] PASS |
| Manual UAT | [ ] PASS |
| Legal Review | [ ] APPROVED |
| **Production Ready** | [ ] YES |

**Production Ready hanya boleh `YES` jika ketiga gate di atas sudah selesai dan lulus.**

> Catatan: lulusnya automated test dan manual UAT TIDAK otomatis menjadikan template
> legal-approved. Legal gate hanya selesai jika reviewer hukum/counsel mengisi
> sign-off secara eksplisit (lihat `docs/mou-v2-legal-review-checklist.md`).
