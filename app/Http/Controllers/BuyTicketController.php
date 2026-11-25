<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationField;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\Voucher;
use App\Services\Tickets\CartOwnershipService;
use App\Services\Tickets\GateTokenService;
use App\Services\Tickets\TicketPricingService;
use App\Services\Tickets\TicketReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BuyTicketController extends Controller
{
    protected $data_pay = 1;

    protected function payment(Event $event)
    {
        $query = PaymentGateway::where('payment_gateways.is_active', true);

        $query->whereHas('eventPaymentGateways', function ($builder) use ($event) {
            $builder->where('event_id', $event->id)
                ->where('is_active', true);
        });

        $this->data_pay = $query->get();
    }

    public function showGuest(Request $request, string $uid, CartOwnershipService $ownershipService)
    {
        return $this->renderCheckout($request, $uid, $ownershipService);
    }

    public function showGuestFinish(Request $request, string $uid, CartOwnershipService $ownershipService)
    {
        $cart = $ownershipService->findOwnedCartOrByToken($request, $uid);
        if (! $cart) {
            return redirect('/')->with('error', 'Transaksi tidak ditemukan atau akses tidak valid.');
        }

        if (! $cart->isGuest()) {
            return redirect('/transaksi');
        }

        $event = Event::where('uid', $cart->event_uid)->first();
        if (! $event) {
            return redirect('/')->with('error', 'Event tidak tersedia.');
        }

        $harga = HargaCart::where('uid', $cart->uid)->get();
        $pricing = app(TicketPricingService::class)->calculateCart($cart);
        $grandTotal = $cart->gross_amount !== null
            ? (int) $cart->gross_amount
            : (int) $pricing['gross_amount'];

        $isPaid = $cart->status === Cart::STATUS_SUCCESS;
        $isPending = in_array($cart->status, [
            Cart::STATUS_PENDING,
            Cart::STATUS_RESERVED,
            Cart::STATUS_UNPAID,
        ], true) && ! $cart->isReservationExpired();

        return view('frontend.page.guest-transaction-finish', [
            'title' => $isPaid ? 'Pembayaran Berhasil' : 'Status Pembayaran',
            'event' => $event,
            'cart' => $cart,
            'harga' => $harga,
            'pricing' => $pricing,
            'grandTotal' => $grandTotal,
            'isPaid' => $isPaid,
            'isPending' => $isPending,
            'guestTicketAccessUrl' => $this->buildGuestTicketAccessUrl($cart, $event),
            'checkoutUrl' => $ownershipService->detailUrl($cart),
        ]);
    }

    public function index(Request $request, $uid, $user = null)
    {
        return $this->renderCheckout($request, (string) $uid, app(CartOwnershipService::class));
    }

    protected function renderCheckout(Request $request, string $uid, CartOwnershipService $ownershipService)
    {
        error_reporting(0);
        $cart = $ownershipService->findOwnedCart($request, $uid);
        if ($cart == null) {
            return redirect('/');
        }
        $event = Event::where('uid', $cart->event_uid)->first();
        if (! $event) {
            return redirect('/')->with('error', 'Event tidak tersedia.');
        }
        $pricingService = app(TicketPricingService::class);
        $this->payment($event);
        $harga = HargaCart::where('uid', $cart->uid)->get();
        $cartV = CartVoucher::where('uid', $cart->uid)
            ->where('event_uid', $cart->event_uid)
            ->first();
        $voucher = $cartV && $cartV->code ? Voucher::where('code', $cartV->code)
            ->where('event_uid', $cart->event_uid)
            ->first() : null;
        $pricing = $pricingService->calculateCart($cart);
        $registrationFields = collect();
        $memberFields = collect();
        $registration = null;

        if ($event->registration_mode !== Event::REGISTRATION_MODE_TICKETING
            && Schema::hasTable('event_registration_fields')) {
            $registrationFields = EventRegistrationField::query()
                ->where('event_uid', $event->uid)
                ->where('scope', 'registration')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            $memberFields = $event->registration_mode === Event::REGISTRATION_MODE_TEAM
                ? EventRegistrationField::query()
                    ->where('event_uid', $event->uid)
                    ->where('scope', 'member')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                : collect();
            $registration = Schema::hasTable('event_registrations')
                ? EventRegistration::query()->where('cart_uid', $cart->uid)->with('members')->first()
                : null;
        }

        $this->data_pay = $this->data_pay->map(function (PaymentGateway $gateway) use ($pricingService, $cart) {
            $resolvedPricing = $pricingService->calculateCart($cart, $gateway);
            $gateway->setAttribute('resolved_internet_fee', $resolvedPricing['internet_fee']);

            return $gateway;
        });

        $iFee = $cart->payment_gateway_id
            ? PaymentGateway::where('id', $cart->payment_gateway_id)->first()
            : null;

        if (! $iFee && $cart->payment_type) {
            $iFee = PaymentGateway::where('slug', $cart->payment_type)->first();
        }

        $selectedPaymentGatewayId = $iFee && $this->data_pay->contains('id', $iFee->id)
            ? $iFee->id
            : null;

        if ($selectedPaymentGatewayId === null && $cart->status === Cart::STATUS_RESERVED && $cart->gross_amount === null) {
            $iFee = null;
        }

        $selectedGatewayPricing = $cart->status === Cart::STATUS_RESERVED && $iFee
            ? $pricingService->calculateCart($cart, $iFee)
            : null;
        $selectInternetFee = $selectedGatewayPricing['internet_fee'] ?? $pricing['internet_fee'];
        $grandTotal = $cart->gross_amount !== null
            ? $pricing['gross_amount']
            : max(0, $pricing['subtotal'] + $pricing['tax_amount'] + $selectInternetFee);

        $guestTicketAccessUrl = $this->buildGuestTicketAccessUrl($cart, $event);

        return view('frontend.page.bayartiket', [
            'title' => 'Detail Ticket',
            'event' => $event,
            'harga' => $harga,
            'cart' => $cart,
            'isGuestCart' => $cart->isGuest(),
            'guestTicketAccessUrl' => $guestTicketAccessUrl,
            'total' => $pricing['ticket_total'],
            'uid' => $uid,
            'voucher' => $voucher,
            'payment' => $this->data_pay,
            'hasAvailablePaymentGateways' => $this->data_pay->isNotEmpty(),
            'canChangePaymentMethod' => in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)
                && ! $cart->isReservationExpired(),
            'selectInternetFee' => $selectInternetFee,
            'selectedPaymentGatewayId' => $selectedPaymentGatewayId,
            'iFee' => $iFee,
            'diskon' => $pricing['discount'],
            'pajakPersen' => $pricing['tax_percent'],
            'nilaiPajak' => $pricing['tax_amount'],
            'subtotal' => $pricing['subtotal'],
            'grandTotal' => $grandTotal,
            'registrationFields' => $registrationFields,
            'memberFields' => $memberFields,
            'registration' => $registration,
        ]);
    }

    public function checkVoucher(Request $request, CartOwnershipService $ownershipService)
    {
        $request->validate([
            'code' => 'required|string|max:100',
            'cartUid' => 'required',
        ]);
        $code = Str::upper(trim((string) $request->code));
        $cartUid = (string) $request->cartUid;
        $cart = $ownershipService->findOwnedCart($request, $cartUid);

        if (! $cart) {
            return redirect()->back()->with('vError', 'Reservation sudah expired atau cart tidak valid.');
        }

        try {
            $reservationService = app(TicketReservationService::class);
            if ($cart->isGuest()) {
                $tokenHash = $ownershipService->guestTokenHashForCart($request, $cart->uid);
                $reservationService->reserveVoucherForGuestCart($cart->uid, (string) $tokenHash, $code);
            } else {
                $reservationService->reserveVoucherForCart($cart->uid, Auth::user()->uid, $code);
            }
        } catch (ValidationException $exception) {
            return redirect()->back()->with('vError', collect($exception->errors())->flatten()->first());
        }

        return redirect()->back()->with('voucher', 'Voucher berhasil digunakan');
    }

    public function closeVoucher(Request $request, CartOwnershipService $ownershipService)
    {
        $request->validate([
            'cartUid' => 'required',
        ]);

        $cartUid = (string) $request->cartUid;
        $cart = $ownershipService->findOwnedCart($request, $cartUid);

        if (! $cart) {
            return redirect()->back()->with('vError', 'Reservation sudah expired atau cart tidak valid.');
        }

        try {
            $reservationService = app(TicketReservationService::class);
            if ($cart->isGuest()) {
                $tokenHash = $ownershipService->guestTokenHashForCart($request, $cart->uid);
                $reservationService->releaseVoucherForGuestCart($cart->uid, (string) $tokenHash);
            } else {
                $reservationService->releaseVoucherForCart($cart->uid, Auth::user()->uid);
            }
        } catch (ValidationException $exception) {
            return redirect()->back()->with('vError', collect($exception->errors())->flatten()->first());
        }

        return redirect()->back()->with('voucher', 'Voucher berhasil dihapus!');
    }

    public function checkout(
        Request $request,
        TicketReservationService $reservationService,
        CartOwnershipService $ownershipService
    ) {
        $eventUid = $request->input('event_uid', $request->input('eventUid'));

        $request->merge(['event_uid' => $eventUid]);
        $request->validate([
            'event_uid' => 'required|exists:events,uid',
        ]);

        $items = $this->parseCheckoutItems($request);
        $totalRequestedQty = collect($items)->sum('quantity');

        if ($totalRequestedQty <= 0) {
            return redirect()->back()->with('error', 'Harap pilih minimal 1 tiket!');
        }

        $event = Event::where('uid', $eventUid)->firstOrFail();

        if ($event->registration_mode !== Event::REGISTRATION_MODE_TICKETING && $totalRequestedQty !== 1) {
            return redirect()->back()->with('error', 'Pendaftaran individual atau tim hanya dapat checkout satu tiket.');
        }

        try {
            if (Auth::check()) {
                $reservation = $reservationService->reserveForUserEvent(
                    $event,
                    Auth::user()->uid,
                    $items
                );
            } else {
                $guestSessionKeyHash = $ownershipService->guestSessionKeyHash($request);
                $issuedToken = $ownershipService->issueGuestToken();

                $reservation = $reservationService->reserveForGuestEvent(
                    $event,
                    $guestSessionKeyHash,
                    $issuedToken['hash'],
                    $items
                );

                if ($reservation['created']) {
                    $ownershipService->storeGuestAccess($request, $reservation['cart'], $issuedToken['plain']);
                }
            }
        } catch (ValidationException $exception) {
            return redirect()->back()->with('error', collect($exception->errors())->flatten()->first());
        }

        $cart = $reservation['cart'];

        if (! $reservation['created']) {
            return redirect($ownershipService->detailUrl($cart))
                ->with('error', 'Anda masih memiliki reservation aktif untuk event ini.');
        }

        return redirect($ownershipService->detailUrl($cart));
    }

    protected function parseCheckoutItems(Request $request): array
    {
        $items = [];

        foreach ((array) $request->input('tickets', []) as $index => $ticket) {
            $quantity = (int) ($ticket['quantity'] ?? 0);
            $hargaId = (int) ($ticket['harga_id'] ?? 0);

            if ($quantity > 0 && $hargaId > 0) {
                $items[] = [
                    'harga_id' => $hargaId,
                    'quantity' => $quantity,
                    'order_by' => (int) ($ticket['orderBy'] ?? $ticket['order_by'] ?? ($index + 1)),
                ];
            }
        }

        foreach ((array) $request->input('harga_id', []) as $index => $hargaId) {
            $quantity = (int) data_get((array) $request->input('quantity', []), $index, 0);

            if ($quantity > 0 && (int) $hargaId > 0) {
                $items[] = [
                    'harga_id' => (int) $hargaId,
                    'quantity' => $quantity,
                    'order_by' => $index + 1,
                ];
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $quantity = (int) $request->input("ticket$i", 0);
            $hargaId = (int) $request->input("harga_id$i", 0);

            if ($quantity > 0 && $hargaId > 0) {
                $items[] = [
                    'harga_id' => $hargaId,
                    'quantity' => $quantity,
                    'order_by' => (int) $request->input("orderBy$i", $i + 1),
                ];
            }
        }

        return collect($items)
            ->groupBy('harga_id')
            ->map(function ($group, $hargaId) {
                return [
                    'harga_id' => (int) $hargaId,
                    'quantity' => (int) $group->sum('quantity'),
                    'order_by' => (int) $group->min('order_by'),
                ];
            })
            ->sortBy('harga_id')
            ->values()
            ->all();
    }

    protected function buildGuestTicketAccessUrl(Cart $cart, Event $event): ?string
    {
        if (! $cart->isGuest() || $cart->status !== Cart::STATUS_SUCCESS || ! filled($cart->gate_token_hash)) {
            return null;
        }

        try {
            $expiresAt = Carbon::parse($event->tanggal)->addDay();
            if (! $expiresAt->isFuture()) {
                $expiresAt = now()->addDays(7);
            }
        } catch (Throwable) {
            $expiresAt = now()->addDays(30);
        }

        return URL::temporarySignedRoute(
            'online.ticket.show',
            $expiresAt,
            [
                'uid' => $cart->uid,
                'gate_access' => app(GateTokenService::class)->ticketAccessProof($cart),
            ],
        );
    }
}
