<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Jobs\SendStaffInvitationJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = User::where('role', User::STAFF_ROLE)
            ->where('parent_uid', Auth::user()->uid)
            ->latest()
            ->get();

        return view('penyewa.page.staff', compact('staffs'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nomor' => 'nullable|string|max:30',
        ]);

        $ownerUid = Auth::user()->uid;
        $email = Str::lower(trim((string) $validated['email']));
        $existingUser = User::where('email', $email)->first();

        if ($existingUser?->role === User::STAFF_ROLE && $existingUser->parent_uid === $ownerUid) {
            return redirect()->back()
                ->withErrors(['email' => 'Staff dengan email ini sudah terdaftar.'])
                ->withInput();
        }

        if ($existingUser?->role === User::STAFF_ROLE) {
            return redirect()->back()
                ->withErrors(['email' => 'Email tidak dapat digunakan sebagai staff.'])
                ->withInput();
        }

        if ($existingUser) {
            return redirect()->back()
                ->withErrors(['email' => 'Email sudah terdaftar. Pemilik akun harus menerima undangan staff terlebih dahulu.'])
                ->withInput();
        }

        $staff = User::create([
            'uid' => (string) Str::uuid(),
            'parent_uid' => $ownerUid,
            'name' => $validated['name'],
            'email' => $email,
            'role' => User::STAFF_ROLE,
            'password' => Hash::make(Str::random(40)),
            'birthday' => '2000-01-01',
            'nomor' => $validated['nomor'] ?? '-',
            'alamat' => '-',
            'kota' => '-',
            'gender' => 'pria',
            'gambar' => 'default.png',
        ]);

        $verifyUrl = URL::temporarySignedRoute(
            'staff.verify',
            now()->addHours(24),
            ['uid' => $staff->uid]
        );

        dispatch(new SendStaffInvitationJob($staff->email, $staff->name, $verifyUrl));

        return redirect()->back()->with('success', 'Undangan berhasil dikirim ke '.$staff->email);
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $uid)
    {
        $staff = User::where('uid', $uid)
            ->where('role', User::STAFF_ROLE)
            ->where('parent_uid', Auth::user()->uid)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nomor' => 'nullable|string|max:30',
        ], [
            'name.required' => 'Nama staff wajib diisi.',
        ]);

        $staff->update([
            'name' => $validated['name'],
            'nomor' => $validated['nomor'] ?? $staff->nomor,
        ]);

        return redirect()->back()->with('success', 'Data staff '.$staff->name.' berhasil diperbarui!');
    }

    public function destroy(string $uid)
    {
        $staff = User::where('uid', $uid)
            ->where('role', User::STAFF_ROLE)
            ->where('parent_uid', Auth::user()->uid)
            ->firstOrFail();

        $staff->delete();

        return redirect()->back()->with('success', 'Akses staff '.$staff->name.' berhasil dihapus.');
    }

    public function verify(Request $request, $uid)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'Link verifikasi sudah kadaluarsa atau tidak valid.');
        }

        $staff = User::where('uid', $uid)
            ->where('role', User::STAFF_ROLE)
            ->whereNotNull('parent_uid')
            ->firstOrFail();

        if ($staff->email_verified_at) {
            return redirect('/login')->with('success', 'Akun sudah diverifikasi. Silakan login.');
        }

        return view('penyewa.page.verify', compact('staff'));
    }

    public function completeProfile(Request $request, $uid)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
            'nomor' => 'required',
        ]);

        $staff = User::where('uid', $uid)
            ->where('role', User::STAFF_ROLE)
            ->whereNotNull('parent_uid')
            ->firstOrFail();

        $staff->password = Hash::make($request->password);
        $staff->nomor = $request->nomor;
        $staff->alamat = $request->alamat;
        $staff->email_verified_at = now();
        $staff->save();

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/login')->with('success', 'Akun berhasil diverifikasi. Silakan login menggunakan email dan password baru Anda.');
    }
}
