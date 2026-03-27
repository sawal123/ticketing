<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Jobs\SendStaffInvitationJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index()
    {
        $staffs = \App\Models\User::where('role', 'staff')
            ->where('parent_uid', auth()->user()->uid)
            ->latest()
            ->get();
        // dd(Auth::user()->uid);
        return view('penyewa.page.staff', compact('staffs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ], [
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
        ]);

        // 2. Simpan data staff ke database
        $staff = User::create([
            'uid' => Str::uuid(),
            'parent_uid' => Auth::user()->uid, // ID Penyewa yang mengundang
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'staff',
            'password' => bcrypt(Str::random(16)), // Password acak, nanti diubah staff
            // 👇 TAMBAHKAN NILAI DUMMY
            'birthday' => '2000-01-01', // Tanggal default sementara
            'nomor' => '-',             // Strip sementara
            'alamat' => '-',            // Strip sementara
            'kota' => '-',              // Strip sementara
            'gender' => 'pria',            // Asumsi default (sesuaikan dengan ENUM database kamu jika ada)
            'gambar' => 'default.png',
        ]);

        // 3. Buat URL Verifikasi yang aman (Valid 24 jam)
        $verifyUrl = URL::temporarySignedRoute(
            'staff.verify',
            now()->addHours(24),
            ['uid' => $staff->uid]
        );

        // 4. KIRIM EMAIL (MENGGUNAKAN QUEUE)
        // Kita panggil Job yang sudah kita buat sebelumnya
        $send = new \App\Jobs\SendStaffInvitationJob($staff->email, $staff->name, $verifyUrl);
        dispatch($send);

        // 5. Kembali ke halaman sebelumnya dengan pesan sukses
        return redirect()->back()->with('success', 'Undangan berhasil dikirim ke ' . $staff->email);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uid)
    {
        // 1. Cari data staff berdasarkan UID dan pastikan ini milik penyewa yang sedang login
        $staff = \App\Models\User::where('uid', $uid)
            ->where('role', 'staff')
            ->where('parent_uid', auth()->user()->uid)
            ->firstOrFail();

        // 2. Validasi input
        // Penting: Pengecualian unik (unique:users,email,id) ditambahkan 
        // agar jika staff tidak mengubah emailnya, Laravel tidak mengira email itu duplikat.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
        ], [
            'name.required' => 'Nama staff wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain. Silakan gunakan email berbeda.',
        ]);

        // 3. Simpan pembaruan ke database
        $staff->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // 4. Kembali ke halaman sebelumnya dengan memunculkan notifikasi/alert sukses
        return redirect()->back()->with('success', 'Data staff ' . $staff->name . ' berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uid)
    {
        // Cari data staff berdasarkan UID dan pastikan parent_uid adalah penyewa yang sedang login
        $staff = User::where('uid', $uid)
            ->where('role', 'staff')
            ->where('parent_uid', auth()->user()->uid)
            ->firstOrFail();

        // (Opsional) Jika kamu menyimpan file gambar dan ingin menghapusnya dari server
        // if ($staff->gambar && $staff->gambar !== 'default.png') {
        //     \Illuminate\Support\Facades\Storage::delete('public/' . $staff->gambar);
        // }

        // Hapus data dari database
        $staff->delete();

        // Redirect kembali dengan pesan sukses (agar SweetAlert atau Notifikasi sukses muncul)
        return redirect()->back()->with('success', 'Akses staff ' . $staff->name . ' berhasil dihapus.');
    }

    public function verify(Request $request, $uid)
    {
        // Cek apakah link URL valid dan belum expired (Keamanan Laravel)
        if (! $request->hasValidSignature()) {
            abort(401, 'Link verifikasi sudah kadaluarsa atau tidak valid.');
        }

        $staff = User::where('uid', $uid)->firstOrFail();
        if ($staff->email_verified_at) {
            return redirect('/login')->with('success', 'Akun sudah diverifikasi. Silakan login.');
        }

        // Tampilkan halaman form untuk isi password dan data tambahan
        return view('penyewa.page.verify', compact('staff'));
    }

    public function completeProfile(Request $request, $uid)
    {
        // 1. Validasi Input
        $request->validate([
            'password' => 'required|min:8|confirmed',
            'nomor' => 'required',
            // tambahkan validasi lain seperti alamat, dll
        ]);

        $staff = User::where('uid', $uid)->firstOrFail();

        // 2. Update data menggunakan properti langsung lalu save()
        // Menggunakan cara ini LEBIH AMAN dan tidak terpengaruh aturan $fillable
        $staff->password = bcrypt($request->password);
        $staff->nomor = $request->nomor;
        $staff->alamat = $request->alamat;
        $staff->email_verified_at = now(); // Sekarang pasti tersimpan ke database
        $staff->save();

        // 3. LOGOUT OTOMATIS JIKA ADA YANG SEDANG LOGIN
        if (Auth::check()) {
            Auth::logout();

            // Hapus sesi lama dan buat token baru untuk keamanan (Mencegah Session Fixation)
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // 4. Arahkan ke halaman login
        return redirect('/login')->with('success', 'Akun berhasil diverifikasi. Silakan login menggunakan email dan password baru Anda.');
    }
}
