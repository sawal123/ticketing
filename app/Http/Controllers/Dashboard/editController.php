<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Mail\ProfileEmailChangeOtpMail;
use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Landing;
use App\Models\Penarikan;
use App\Models\ProfileEmailChangeOtp;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Term;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SecureImageStorage;
use App\Services\Tickets\GateTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class editController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function editEvent(Request $request)
    {
        $request->validate([
            'cover' => SecureImageStorage::rules(),
        ]);

        $event = Event::where('uid', $request->uid)->first(); // Mengambil instance model yang akan diupdate

        $tanggal = date('Y-m-d H:i', strtotime($request->tanggal));
        $event->event = $request->event;
        $event->alamat = $request->alamat;
        $event->tanggal = $tanggal;
        $event->status = $request->status;
        $event->fee = $request->fee;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;

        $oldCover = null;
        if ($request->hasFile('cover')) {
            $oldCover = $event->cover;
            $event->cover = $this->images->storeBasename($request->file('cover'), 'cover');
        }

        $event->save();
        $this->images->delete('cover', $oldCover);

        return redirect('/admin/event/eventDetail/'.$request->uid)->with('success', 'Berhasil di Update');
    }

    public function editTalent(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

        $uid = $request->uid;
        $talent = $request->talent;

        $talents = Talent::where('id', $uid)->first();
        $talents->talent = $talent;

        $oldImage = null;
        if ($request->hasFile('gambar')) {
            $oldImage = $talents->gambar;
            $talents->gambar = $this->images->storeBasename($request->file('gambar'), 'talent');
        }
        $talents->save();
        $this->images->delete('talent', $oldImage);

        return redirect()->back()->with('success', 'Berhasil di Update');
    }

    public function editHarga(Request $request)
    {
        $id = $request->id;
        $harga = Harga::where('id', $id)->first();
        // dd($request->kategori);
        $harga->update([
            'kategori' => $request->kategori,
            'qty' => $request->qty,
            'harga' => $request->harga,
        ]);

        return redirect()->back()->with('editHarga', 'Harga Berhasil Di Ubah');
    }

    public function editSlide(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

        $slide = Slider::where('uid', $request->uid)->first();
        $slide->uid = $request->uid;
        $slide->title = $request->title;
        $slide->url = $request->url;
        $slide->sort = $request->sort;
        $oldImage = null;
        if ($request->hasFile('gambar')) {
            $oldImage = $slide->gambar;
            $slide->gambar = $this->images->storeBasename($request->file('gambar'), 'slide');
        }
        $slide->save();
        $this->images->delete('slide', $oldImage);

        return redirect()->back()->with('editSlide', 'Slide Berhasil Diubah');
    }

    public function profile()
    {
        $final = [];
        //  dd($data);
        $valueUser = [Auth::user()->name, Auth::user()->email, Auth::user()->nomor, Auth::user()->gambar];
        $dataUser = User::where('uid', Auth::user()->uid)->first();
        $provinsi = [];
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        if ($http->successful()) {
            $provinsi = $http->json();
            $com = compact('provinsi');
        }

        return view(
            'frontend.page.editProfile',
            [
                'title' => 'Edit Profile',
                'dataUser' => $dataUser,
                'provinsi' => $provinsi,
            ]
        );
    }

    public function editProfile(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nomor' => 'nullable|numeric',
            'gender' => 'required|string|max:10',
            'birthday' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'gambar' => SecureImageStorage::rules(),
        ]);
        $validate->validate();
        $user = User::where('uid', Auth::user()->uid)->first();
        $user->name = $request->input('name');
        $user->nomor = $request->input('nomor') ?? '';
        $user->gender = $request->input('gender');
        $user->birthday = $request->input('birthday');
        $user->kota = $request->input('kota');
        $user->alamat = $request->input('alamat');
        $oldImage = null;
        if ($request->hasFile('gambar')) {
            $oldImage = $user->gambar;
            $user->gambar = $this->images->storeBasename($request->file('gambar'), 'user');
        }

        $user->save();
        $this->images->delete('user', $oldImage);

        return redirect()->back()->with('editProfile', 'Profile Berhasil Diubah');
    }

    public function updateProfilePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
        ], [
            'password.regex' => 'Password harus mengandung huruf dan angka.',
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();

        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();
        $request->session()->regenerate();

        return redirect()->back()->with('editProfile', 'Password berhasil diubah.');
    }

    public function requestEmailChangeOtp(Request $request)
    {
        $request->validate([
            'new_email' => 'required|email',
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();
        $newEmail = Str::lower(trim((string) $request->new_email));

        if ($user->google_id) {
            return redirect()->back()->withErrors([
                'new_email' => 'Akun Google tidak dapat mengganti email dari profile. Silakan gunakan akun Google yang sesuai.',
            ]);
        }

        if (Str::lower($user->email) === $newEmail) {
            return redirect()->back()->with('editProfile', 'Email baru sama dengan email saat ini.');
        }

        Validator::make(['new_email' => $newEmail], [
            'new_email' => ['required', 'email', Rule::unique('users', 'email')],
        ])->validate();

        $rateLimitKey = $this->emailOtpRateLimitKey($user, $newEmail, $request);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return redirect()->back()->withErrors([
                'new_email' => 'Terlalu banyak permintaan OTP. Silakan coba lagi beberapa saat.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, 600);

        $recentOtp = ProfileEmailChangeOtp::query()
            ->where('user_uid', $user->uid)
            ->where('new_email', $newEmail)
            ->active()
            ->latest()
            ->first();

        if ($recentOtp && $recentOtp->last_sent_at?->greaterThan(now()->subSeconds(60))) {
            return redirect()->back()
                ->with('editProfile', 'Jika email valid, kode OTP akan dikirim.')
                ->withInput(['new_email' => $newEmail])
                ->with('pending_email_change', $newEmail);
        }

        ProfileEmailChangeOtp::query()
            ->where('user_uid', $user->uid)
            ->active()
            ->update(['used_at' => now()]);

        $otp = (string) random_int(100000, 999999);

        ProfileEmailChangeOtp::create([
            'user_uid' => $user->uid,
            'current_email' => $user->email,
            'new_email' => $newEmail,
            'otp_hash' => hash('sha256', $otp),
            'purpose' => ProfileEmailChangeOtp::PURPOSE,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        Mail::to($newEmail)->send(new ProfileEmailChangeOtpMail($user, $otp));

        return redirect()->back()
            ->with('editProfile', 'Jika email valid, kode OTP akan dikirim.')
            ->withInput(['new_email' => $newEmail])
            ->with('pending_email_change', $newEmail);
    }

    public function verifyEmailChangeOtp(Request $request)
    {
        $request->validate([
            'new_email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();
        $newEmail = Str::lower(trim((string) $request->new_email));

        $result = DB::transaction(function () use ($request, $user, $newEmail): true|string {
            $otp = ProfileEmailChangeOtp::query()
                ->where('user_uid', $user->uid)
                ->where('new_email', $newEmail)
                ->active()
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $otp || ! $otp->isUsable()) {
                return 'Kode OTP tidak valid atau sudah kedaluwarsa.';
            }

            if (! $otp->verifyOtp((string) $request->otp)) {
                $otp->increment('attempts');

                return 'Kode OTP tidak valid atau sudah kedaluwarsa.';
            }

            if (User::where('email', $newEmail)->where('uid', '!=', $user->uid)->exists()) {
                return 'Email baru sudah digunakan.';
            }

            $lockedUser = User::where('uid', $user->uid)->lockForUpdate()->firstOrFail();
            $oldEmail = $lockedUser->email;

            $lockedUser->forceFill([
                'email' => $newEmail,
                'email_verified_at' => now(),
            ])->save();

            $otp->forceFill(['used_at' => now()])->save();

            ActivityLog::safeCreate([
                'user_uid' => $lockedUser->uid,
                'activity' => 'Profile Email Changed',
                'description' => 'Email profile diubah dari '.$oldEmail.' ke '.$newEmail,
                'impact_level' => 'Medium',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => request()->session()->getId(),
            ]);

            return true;
        });

        if ($result !== true) {
            return redirect()->back()->withErrors([
                'otp' => $result,
            ])->withInput(['new_email' => $newEmail]);
        }

        return redirect()->back()->with('editProfile', 'Email berhasil diverifikasi dan diubah.');
    }

    private function emailOtpRateLimitKey(User $user, string $newEmail, Request $request): string
    {
        return 'profile-email-otp:'.sha1($user->uid.'|'.$newEmail.'|'.$request->ip());
    }

    public function editLogo(Request $request)
    {
        $request->validate([
            'logo' => SecureImageStorage::rules(),
        ]);

        // dd($data);
        $logo = Landing::where('id', $request->id)->first();
        // dd($logo);
        if ($logo === null) {
            $save = new Landing;
            $save->description = '';
            $save->keyword = '';
            if ($request->hasFile('logo')) {
                $save->logo = $this->images->storeBasename($request->file('logo'), 'logo');
            }
            $save->save();
        } else {
            $oldLogo = null;
            if ($request->hasFile('logo')) {
                $oldLogo = $logo->logo;
                $logo->logo = $this->images->storeBasename($request->file('logo'), 'logo');
            }
            $logo->save();
            $this->images->delete('logo', $oldLogo);
        }

        return redirect()->back()->with('editLogo', 'Logo Berhasil Diubah');
    }

    public function editIcon(Request $request)
    {
        $request->validate([
            'icon' => SecureImageStorage::rules(),
        ]);

        // dd($data);
        $logo = Landing::where('id', $request->id)->first();
        // dd($logo);
        if ($logo === null) {
            $save = new Landing;
            $save->description = '';
            $save->keyword = '';
            if ($request->hasFile('icon')) {
                $save->icon = $this->images->storeBasename($request->file('icon'), 'logo');
            }
            $save->save();
        } else {
            $oldIcon = null;
            if ($request->hasFile('icon')) {
                $oldIcon = $logo->icon;
                $logo->icon = $this->images->storeBasename($request->file('icon'), 'logo');
            }
            $logo->save();
            $this->images->delete('logo', $oldIcon);
        }

        return redirect()->back()->with('editLogo', 'Logo Berhasil Diubah');
    }

    public function editDeskripis(Request $request)
    {
        $id = $request->id;
        $des = $request->description;
        $deskripis = Landing::where('id', $id)->first();

        $deskripis->description = $des;
        $deskripis->save();

        return redirect()->back()->with('success', 'Deskripsi Meta Berhasil di Ubah');
    }

    public function editKeyword(Request $request)
    {
        $id = $request->id;
        $key = $request->keyword;
        $keyword = Landing::where('id', $id)->first();

        $keyword->keyword = $key;
        $keyword->save();

        return redirect()->back()->with('success', 'Keyword Meta Berhasil di Ubah');
    }

    public function editTerm(Request $request)
    {
        $term = Term::where('uid', $request->uid)->first();

        $term->title = $request->title;
        $term->term = $request->term;
        $term->save();

        return redirect()->back()->with('editTerm', 'Term Berhasil Diubah');
    }

    public function editUser(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email',
            'tanggal' => 'date',
            'kota' => 'string|max:50',
            'alamat' => 'required|string|max:255',
            'nomor' => 'required|numeric',
            'gender' => 'required|string|max:20',
            'poto' => SecureImageStorage::rules(),
        ]);
        $validate->validate();

        $user = User::where('uid', $request->uid)->first();
        $user->name = $request->nama;
        $user->email = $request->email;
        $user->birthday = $request->tanggal;
        $user->kota = $request->kota;
        $user->alamat = $request->alamat;
        $user->nomor = $request->nomor;
        $user->gender = $request->gender;

        $oldImage = null;
        if ($request->hasFile('poto')) {
            $oldImage = $user->gambar;
            $user->gambar = $this->images->storeBasename($request->file('poto'), 'user');
        }

        if ($request->password !== null) {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        $this->images->delete('user', $oldImage);

        if ($user->role === 'penyewa') {
            return redirect()->back()->with('editUser', 'Penyewa Berhasil Diubah');
        } else {
            return redirect()->back()->with('editUser', 'Admin Berhasil Diubah');
        }
        // dd($request->poto);

    }

    public function editCashes(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'uid' => 'required|string',
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email',
            'lahir' => 'date',
            'alamat' => 'required|string|max:255',
            'nomor' => 'required|numeric',
            'gender' => 'required|string|max:20',
        ]);
        $validate->validate();

        $cashes = Cash::where('uid', $request->uid)->first();
        $cashes->name = $request->nama;
        $cashes->email = $request->email;
        $cashes->lahir = $request->lahir;
        $cashes->alamat = $request->alamat;
        $cashes->nomor = $request->nomor;
        $cashes->gender = $request->gender;
        $cashes->save();

        return redirect()->back()->with('success', 'Cashes Berhasil Diubah');
    }

    public function setujuiEvent($data)
    {
        $event = Event::where('uid', $data)->first();
        $event->konfirmasi = '1';
        $event->save();

        return redirect()->back()->with('konfirmasi', 'Event Berhasil di Setujui dan di publish');
    }

    public function editStatusInvoice(Request $request)
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $request->validate([
            'uid' => 'required|string',
        ]);

        DB::transaction(function () use ($request) {
            $penarikan = Penarikan::where('uid', $request->uid)->lockForUpdate()->firstOrFail();

            if (! in_array(strtoupper((string) $penarikan->status), [
                Penarikan::STATUS_PENDING,
                Penarikan::STATUS_PROCESSING,
            ], true)) {
                return back()
                    ->with('error', 'Penarikan hanya dapat disetujui jika masih pending atau processing.')
                    ->throwResponse();
            }

            $penarikan->status = Penarikan::STATUS_SUCCESS;
            $penarikan->approved_at = now();
            $penarikan->save();
        }, 3);

        return redirect()->back()->with('success', 'Konfirmasi Berhasil');
    }

    public function editTransaksi(Request $request)
    {
        $uid = $request->uid;

        $transaksis = Transaction::where('uid', $request->uid)->first();
        $carts = Cart::where('uid', $request->uid)->first();

        if (! $carts) {
            return redirect()->back()->with('error', 'Transaksi tidak ditemukan.');
        }

        $carts->status = $request->status;
        if ($transaksis) {
            $transaksis->status_transaksi = $request->status;
            $transaksis->save();
        }
        $carts->save();

        if ($request->status === 'SUCCESS') {
            app(GateTokenService::class)->issueIfEnabled($carts);

            try {
                if ($carts->payment_type === 'cash') {
                    $cash = Cash::where('uid', $uid)->first();
                    if (! $cash || ! filter_var($cash->email, FILTER_VALIDATE_EMAIL)) {
                        return redirect()->back()->with('success', 'Transaksi Berhasil di Ubah. Email pembeli cash perlu diperiksa sebelum dikirim ulang.');
                    }

                    dispatch(new sendEmailTrnsaksi($cash->email, $cash->name, $carts->uid));
                } else {
                    $user = User::where('uid', $carts->user_uid)->first();
                    if (! $user) {
                        return redirect()->back()->with('success', 'Transaksi Berhasil di Ubah. Data pembeli tidak ditemukan untuk pengiriman email.');
                    }

                    dispatch(new sendEmailETransaksi($user, $carts));
                }
            } catch (\Throwable $e) {
                Log::error('Gagal menjadwalkan email setelah edit transaksi.', [
                    'cart_uid' => $carts->uid,
                    'payment_type' => $carts->payment_type,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->back()->with('success', 'Transaksi Berhasil di Ubah. Email barcode perlu dikirim ulang.');
            }
        }

        return redirect()->back()->with('success', 'Transaksi Berhasil di Ubah');
    }

    public function editPro(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'nama' => 'required|string',
            'nomor' => 'required|numeric',
            'email' => 'required|email',
            'date' => 'required|string',
            'gender' => 'required|string',
            'provinsi' => 'required|string',
            'alamat' => 'required|string',
            'img' => SecureImageStorage::rules(),
        ]);

        $validate->validate();
        $user = User::where('uid', Auth::user()->uid)->first();
        // dd($user);
        $user->name = $request->nama;
        $user->nomor = $request->nomor;
        $user->email = $request->email;
        $user->birthday = $request->date;
        $user->gender = $request->gender;
        $user->kota = $request->provinsi;
        $user->alamat = $request->alamat;

        $oldImage = null;
        if ($request->hasFile('img')) {
            $oldImage = $user->gambar;
            $user->gambar = $this->images->storeBasename($request->file('img'), 'user');
        }

        try {
            $user->save();
            $this->images->delete('user', $oldImage);

            return redirect()->back()->with('editProfile', 'Informasi Berhasil Di Update');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'Gagal Update. Silahkan coba lagi.');
        }
    }

    public function editRekening(Request $request)
    {

        $rek = Bank::where('uid', Auth::user()->uid)->first();
        if ($rek) {
            $rek->nama = $request->nama;
            $rek->bank = $request->bank;
            $rek->norek = $request->norek;
            // $rek->save();
        }
        try {
            $rek->save();

            return redirect()->back()->with('editRek', 'Rekening Berhasil Di Update');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'Gagal Update. Silahkan coba lagi.');
        }
    }

    public function editContact(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'sosmed' => 'string',
            'nama' => 'string',
            'link' => 'string|max:255|nullable',
            'icon' => SecureImageStorage::rules(),
        ]);
        $validate->validate();
        $con = Contact::where('id', $request->id)->first();
        $con->sosmed = $request->sosmed;
        $con->name = $request->nama;
        $con->link = $request->link == null ? '' : $request->link;
        $oldIcon = null;
        if ($request->hasFile('icon')) {
            $oldIcon = $con->icon;
            $con->icon = $this->images->storeBasename($request->file('icon'), 'sosmed');
        }
        $con->save();
        $this->images->delete('sosmed', $oldIcon);

        return redirect()->back()->with('success', 'Contact Berhasil Di Ubah');
    }
}
