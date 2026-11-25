<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartOwnershipService
{
    public const SESSION_KEY = 'guest_checkout.session_key';

    public const SESSION_TOKENS_KEY = 'guest_checkout.tokens';

    public function guestSessionKeyHash(Request $request): string
    {
        $rawKey = $request->session()->get(self::SESSION_KEY);

        if (! is_string($rawKey) || strlen($rawKey) < 32) {
            $rawKey = bin2hex(random_bytes(32));
            $request->session()->put(self::SESSION_KEY, $rawKey);
        }

        return hash('sha256', $rawKey);
    }

    public function hasGuestSession(Request $request): bool
    {
        $rawKey = $request->session()->get(self::SESSION_KEY);

        return is_string($rawKey) && strlen($rawKey) >= 32;
    }

    public function issueGuestToken(): array
    {
        $plain = bin2hex(random_bytes(32));

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain),
        ];
    }

    public function storeGuestAccess(Request $request, Cart $cart, string $plainToken): void
    {
        $tokens = (array) $request->session()->get(self::SESSION_TOKENS_KEY, []);
        $tokens[$cart->uid] = $plainToken;
        $request->session()->put(self::SESSION_TOKENS_KEY, $tokens);
    }

    public function guestAccessToken(Request $request, string $cartUid): ?string
    {
        $tokens = (array) $request->session()->get(self::SESSION_TOKENS_KEY, []);
        $plain = $tokens[$cartUid] ?? null;

        if (! is_string($plain) || $plain === '') {
            return null;
        }

        return $plain;
    }

    public function guestTokenHashForCart(Request $request, string $cartUid): ?string
    {
        $plain = $this->guestAccessToken($request, $cartUid);

        if ($plain === null) {
            return null;
        }

        return hash('sha256', $plain);
    }

    public function requestHasValidGuestToken(Request $request, Cart $cart): bool
    {
        if (! $cart->isGuest() || ! filled($cart->guest_token_hash)) {
            return false;
        }

        $token = $request->query('token');
        if (! is_string($token) || strlen($token) < 32) {
            return false;
        }

        return hash_equals((string) $cart->guest_token_hash, hash('sha256', $token));
    }

    public function findOwnedCartOrByToken(Request $request, string $cartUid): ?Cart
    {
        $owned = $this->findOwnedCart($request, $cartUid);
        if ($owned !== null) {
            return $owned;
        }

        $cart = Cart::where('uid', $cartUid)->first();
        if (! $cart || ! $this->requestHasValidGuestToken($request, $cart)) {
            return null;
        }

        $this->storeGuestAccess($request, $cart, (string) $request->query('token'));

        return $cart;
    }

    public function findOwnedCart(Request $request, string $cartUid, bool $forUpdate = false): ?Cart
    {
        $query = Cart::where('uid', $cartUid);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $cart = $query->first();

        if (! $cart) {
            return null;
        }

        if ($this->requestOwnsCart($request, $cart)) {
            return $cart;
        }

        return null;
    }

    public function requestOwnsCart(Request $request, Cart $cart): bool
    {
        if (Auth::check() && ! $cart->isGuest()) {
            return (string) $cart->user_uid === (string) Auth::user()->uid;
        }

        if ($cart->isGuest()) {
            $tokenHash = $this->guestTokenHashForCart($request, $cart->uid);

            return $tokenHash !== null
                && filled($cart->guest_token_hash)
                && hash_equals((string) $cart->guest_token_hash, $tokenHash);
        }

        return false;
    }

    public function activeGuestCart(Request $request, ?string $eventUid = null): ?Cart
    {
        if (! $this->hasGuestSession($request)) {
            return null;
        }

        $tokens = (array) $request->session()->get(self::SESSION_TOKENS_KEY, []);
        if ($tokens === []) {
            return null;
        }

        $query = Cart::where('is_guest', true)
            ->where('guest_session_key', $this->guestSessionKeyHash($request))
            ->whereIn('uid', array_keys($tokens))
            ->whereIn('status', [Cart::STATUS_UNPAID, 'unpaid', Cart::STATUS_RESERVED, Cart::STATUS_PENDING, 'pending'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($eventUid !== null) {
            $query->where('event_uid', $eventUid);
        }

        $cart = $query->latest('id')->first();

        if (! $cart || ! $this->requestOwnsCart($request, $cart)) {
            return null;
        }

        return $cart;
    }

    public function detailUrl(Cart $cart): string
    {
        if ($cart->isGuest()) {
            return route('checkout.guest.show', ['uid' => $cart->uid]);
        }

        return url('/detail-ticket/'.$cart->uid.'/'.$cart->user_uid);
    }

    public function finishUrl(Request $request, Cart $cart): string
    {
        if ($cart->isGuest()) {
            $params = ['uid' => $cart->uid];
            $plainToken = $this->guestAccessToken($request, $cart->uid);
            if ($plainToken !== null) {
                $params['token'] = $plainToken;
            }

            return route('checkout.guest.finish', $params);
        }

        return url('/transaksi');
    }
}
