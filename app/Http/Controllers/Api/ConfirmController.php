<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GateTokenException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Event;
use App\Services\Tickets\GateCheckInService;
use App\Services\Tickets\GateTokenService;
use Illuminate\Http\Request;

class ConfirmController extends Controller
{
    public function __construct(private GateCheckInService $gateCheckIn) {}

    protected function ownerId(Request $request): ?string
    {
        $user = $request->user();

        return $user?->role === 'staff' ? $user->parent_uid : $user?->uid;
    }

    protected function userCanAccessEvent(Request $request, string $eventUid): bool
    {
        $ownerId = $this->ownerId($request);

        return $ownerId && Event::where('uid', $eventUid)
            ->where('user_uid', $ownerId)
            ->exists();
    }

    public function cekData(Request $request, $data = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'Endpoint berbasis invoice sudah dinonaktifkan. Perbarui aplikasi scanner.',
        ], 410);
    }

    public function upKonfirmasi(Request $request, $data)
    {
        return response()->json([
            'success' => false,
            'message' => 'Endpoint berbasis invoice sudah dinonaktifkan. Perbarui aplikasi scanner.',
        ], 410);
    }

    public function verfikasi(Request $request, $data = null)
    {
        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => 'Event harus diberikan.',
            ], 400);
        }

        if (! $this->userCanAccessEvent($request, $data)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke event ini.',
            ], 403);
        }

        $cart = Cart::select(['carts.uid', 'carts.konfirmasi', 'events.event', 'users.name', 'users.gambar'])
            ->where('event_uid', $data)
            ->where('carts.konfirmasi', '1')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->get();
        if ($data !== null) {
            return response()->json([
                'cart' => $cart,

            ], 200);
        }
    }

    public function checkTicketByGateToken(Request $request)
    {
        $validated = $request->validate([
            'gate_token' => ['required', 'string', 'size:43', 'regex:'.GateTokenService::TOKEN_PATTERN],
        ]);

        try {
            $ticket = $this->gateCheckIn->inspect($validated['gate_token'], (string) $this->ownerId($request));
        } catch (GateTokenException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status);
        }

        return $this->ticketResponse($ticket);
    }

    public function confirmTicketStatus(Request $request)
    {
        $validated = $request->validate([
            'gate_token' => ['required', 'string', 'size:43', 'regex:'.GateTokenService::TOKEN_PATTERN],
            'scan_device_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $ticket = $this->gateCheckIn->checkIn(
                $validated['gate_token'],
                (string) $this->ownerId($request),
                $request->user()->uid,
                $validated['scan_device_id'] ?? null,
            );
        } catch (GateTokenException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil.',
            'data' => [
                'uid' => $ticket->uid,
                'invoice' => $ticket->invoice,
                'scanned_at' => $ticket->scanned_at?->toIso8601String(),
                'scanned_by' => $ticket->scanned_by,
                'scan_device_id' => $ticket->scan_device_id,
            ],
        ], 200);
    }

    private function ticketResponse(Cart $ticket)
    {
        return response()->json([
            'success' => true,
            'message' => 'Tiket ditemukan.',
            'data' => [
                'uid' => $ticket->uid,
                'invoice' => $ticket->invoice,
                'event_name' => $ticket->event->event ?? '-',
                'cover' => $ticket->event->cover ?? null,
                'buyer_name' => $ticket->payment_type === 'cash'
                    ? ($ticket->cashBuyer->name ?? '-')
                    : ($ticket->users->name ?? '-'),
                'email' => $ticket->payment_type === 'cash'
                    ? ($ticket->cashBuyer->email ?? '-')
                    : ($ticket->users->email ?? '-'),
                'order_date' => $ticket->created_at?->format('d M Y H:i'),
                'konfirmasi' => $ticket->konfirmasi,
                'status_label' => 'Belum Terverifikasi',
                'ticket_items' => $ticket->hargaCarts->map(fn ($item) => [
                    'jenis_tiket' => $item->kategori_harga,
                    'qty' => $item->quantity,
                ]),
                'total_qty' => $ticket->hargaCarts->sum('quantity'),
            ],
        ], 200);
    }

    public function listEvent(Request $request)
    {
        // 1. Ambil data user yang sedang login dari token Sanctum
        $user = $request->user();

        // 2. Tentukan ID Pemilik Event (Penyewa)
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // 3. Ambil event + Hitung Total Tiket Terjual & Terverifikasi dalam 1 Kueri!
        $events = Event::where('user_uid', $ownerId)
            ->whereNotNull('konfirmasi')
            ->addSelect([
                // Menghitung TOTAL TIKET TERJUAL (Semua transaksi SUCCESS)
                'tiket_terjual' => Cart::selectRaw('COALESCE(SUM(harga_carts.quantity), 0)')
                    ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                    ->whereColumn('carts.event_uid', 'events.uid')
                    ->whereNull('harga_carts.deleted_at')
                    ->where('carts.status', 'SUCCESS'),

                // Menghitung TOTAL TIKET TERVERIFIKASI (Sudah di-scan/Hadir)
                'tiket_terverifikasi' => Cart::selectRaw('COALESCE(SUM(harga_carts.quantity), 0)')
                    ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                    ->whereColumn('carts.event_uid', 'events.uid')
                    ->whereNull('harga_carts.deleted_at')
                    ->where('carts.status', 'SUCCESS')
                    // PERHATIAN: Ganti 'Hadir' dengan string status yang kamu pakai saat tiket di-scan
                    // (misal: 'Terverifikasi', 'Scanned', atau cek di kolom 'status'/'konfirmasi' kamu)
                    ->where('carts.konfirmasi', 1),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Return Response
        if ($events->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar event',
                'data' => $events,
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada event yang dikonfirmasi',
                'data' => [],
            ], 200);
        }
    }
}
