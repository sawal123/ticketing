<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\User;
use App\Services\Tickets\GateTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarcodeController extends Controller
{
    public function __construct(private GateTokenService $gateTokens) {}

    public function showLogin($data)
    {
        if (Auth::check()) {
            return redirect()->route('barcode.generate', ['data' => $data]);
        }

        return view('barcode-login', [
            'data' => $data,
        ]);
    }

    public function login(Request $request, $data)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('barcode.generate', ['data' => $data]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Email atau password yang Anda masukkan salah.');
    }

    public function generateBarcode(Request $request, $data)
    {
        if (! $data) {
            abort(404, 'not found');
        }
        $cart = Cart::where('invoice', $data)->first();

        if (! $cart) {
            abort(404, 'not found');
        }

        if (strtolower($cart->status) !== 'success') {
            return response()->view('barcode-error', [
                'message' => 'Barcode hanya bisa diakses untuk invoice dengan status success.',
            ], 403);
        }

        if (! Auth::check()) {
            return redirect()->route('barcode.login', ['data' => $data]);
        }

        if (! $this->userCanViewTicket($cart)) {
            return response()->view('barcode-error', [
                'message' => 'Anda tidak memiliki akses ke invoice ini.',
            ], 403);
        }

        try {
            $gateCredential = $cart->gate_token_hash
                ? $this->gateTokens->tokenForQr($cart)
                : $this->legacyCredential($cart);
        } catch (\RuntimeException) {
            return $this->barcodeError('Tiket belum memiliki gate token yang valid.', 409);
        }

        $hargaC = HargaCart::where('uid', $cart->uid)->get();
        $event = Event::where('uid', $cart->event_uid)->first();
        $barcodeData = QrCode::size(250)->generate($gateCredential);

        if ($cart->payment_type === 'cash') {
            $user = Cash::where('uid', $cart->uid)->first();
        } else {
            $user = User::where('uid', $cart->user_uid)->first();
        }

        return response()->view('barcode', [
            'barcodeData' => $barcodeData,
            'invoice' => $cart->invoice,
            'event' => $event,
            'hargaC' => $hargaC,
            'userBarcode' => $user,
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function showCashTicket(Request $request, string $uid)
    {
        $cart = Cart::query()
            ->with(['cashBuyer', 'event', 'hargaCarts'])
            ->where('uid', $uid)
            ->where('payment_type', 'cash')
            ->where('status', Cart::STATUS_SUCCESS)
            ->first();

        if (! $cart || ! $cart->cashBuyer || ! $cart->event || $cart->hargaCarts->isEmpty()) {
            return $this->barcodeError('Tiket cash tidak ditemukan atau sudah tidak dapat diakses.', 404);
        }

        try {
            if ($cart->gate_token_hash
                && ! $this->gateTokens->validCashTicketProof($cart, $request->query('gate_access'))) {
                return $this->barcodeError('Tautan tiket cash tidak valid.', 403);
            }

            $gateCredential = $cart->gate_token_hash
                ? $this->gateTokens->tokenForQr($cart)
                : $this->legacyCredential($cart);
        } catch (\RuntimeException) {
            return $this->barcodeError('Tiket belum memiliki gate token yang valid.', 409);
        }

        return response()->view('barcode', [
            'barcodeData' => QrCode::size(250)->generate($gateCredential),
            'invoice' => $cart->invoice,
            'event' => $cart->event,
            'hargaC' => $cart->hargaCarts,
            'userBarcode' => $cart->cashBuyer,
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function userCanViewTicket(Cart $cart): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['penyewa', 'staff'], true)) {
            $ownerUid = $user->role === 'staff' ? $user->parent_uid : $user->uid;

            return Event::where('uid', $cart->event_uid)
                ->where('user_uid', $ownerUid)
                ->exists();
        }

        if ($cart->payment_type === 'cash') {
            return Cash::where('uid', $cart->uid)
                ->where('email', $user->email)
                ->exists();
        }

        return $user->uid === $cart->user_uid;
    }

    private function barcodeError(string $message, int $status)
    {
        return response()->view('barcode-error', [
            'message' => $message,
        ], $status);
    }

    private function legacyCredential(Cart $cart): string
    {
        if ($this->gateTokens->eventIsEnabled($cart->event_uid)) {
            throw new \RuntimeException('Gate token wajib untuk event aktif.');
        }

        return (string) $cart->invoice;
    }
}
