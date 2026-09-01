<?php

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class HelpCenter extends Component
{
    #[Layout('layouts.unified')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['penyewa', 'staff'], true), 403);
    }

    public function render()
    {
        $categories = [
            'Memulai' => [['id' => 'memulai-dashboard', 'title' => 'Memahami Dashboard', 'body' => 'Ringkasan event, transaksi, tiket terjual, omset, dan akses cepat ke event.'], ['id' => 'memulai-event', 'title' => 'Membuat dan Menyiapkan Event', 'body' => 'Lengkapi informasi, penyelenggara, jadwal, lokasi, rekening, dan dokumen sebelum melanjutkan ke tiket dan MOU.']],
            'Penjualan Tiket' => [['id' => 'penjualan-tiket', 'title' => 'Mengelola Tiket Event', 'body' => 'Buat kategori tiket, atur harga dan kuota atau stok, status aktif atau nonaktif, serta lihat jumlah terjual.']],
            'Operasional Event' => [['id' => 'operasional-event', 'title' => 'Mengelola Event Berjalan', 'body' => 'Kelola informasi event, talent, status event, serta akses tiket dan transaksi.'], ['id' => 'operasional-transaksi', 'title' => 'Transaksi dan Check-in', 'body' => 'Gunakan pencarian atau filter, buka detail transaksi, dan export laporan. Scanner membaca tiket dan menampilkan data; petugas menekan Verifikasi agar hasil check-in tercatat.']],
            'Keuangan' => [['id' => 'keuangan-penarikan', 'title' => 'Penarikan Saldo', 'body' => 'Ajukan penarikan dari saldo tersedia. Status PENDING, PROCESSING, lalu SUCCESS; invoice tersedia dan bukti transfer dapat dibuka jika tersedia.']],
            'Dokumen' => [['id' => 'dokumen-event', 'title' => 'Dokumen dan Rekening Event', 'body' => 'Rekening pencairan dan dokumen penyelenggara disimpan sesuai event.'], ['id' => 'dokumen-mou', 'title' => 'MOU Event', 'body' => 'Lihat status MOU, file unsigned atau signed bila tersedia, upload dokumen bertanda tangan, dan pantau review admin.']],
        ];
        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            foreach ($categories as $name => $articles) {
                $categories[$name] = array_values(array_filter($articles, fn ($article) => str_contains(mb_strtolower($name.' '.$article['title'].' '.$article['body']), $term)));
            }
        }

        return view('livewire.dashboard.help-center', compact('categories'));
    }
}
