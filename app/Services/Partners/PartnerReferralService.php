<?php

namespace App\Services\Partners;

use App\Models\Event;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerReferralService
{
    public const SESSION_KEY = 'partner_referrals';

    public function captureFromRequest(Request $request, Event $event): ?Partner
    {
        if (! Schema::hasTable('partners')) {
            return null;
        }

        $ref = Str::upper(trim((string) $request->query('ref', '')));

        if ($ref !== '') {
            $partner = $this->findActivePartnerByReference($ref, $event);

            if ($partner && $request->hasSession()) {
                $referrals = (array) $request->session()->get(self::SESSION_KEY, []);
                $referrals[$event->uid] = $partner->uid;
                $request->session()->put(self::SESSION_KEY, $referrals);
            }

            if ($partner) {
                return $partner;
            }
        }

        return $this->resolveForEvent($request, $event);
    }

    public function resolveForEvent(Request $request, Event $event): ?Partner
    {
        if (! Schema::hasTable('partners') || ! $request->hasSession()) {
            return null;
        }

        $referrals = (array) $request->session()->get(self::SESSION_KEY, []);
        $partnerUid = $referrals[$event->uid] ?? null;

        if (! is_string($partnerUid) || $partnerUid === '') {
            return null;
        }

        return $this->findActivePartnerByUid($partnerUid, $event);
    }

    public function findActivePartnerByReference(string $reference, Event $event): ?Partner
    {
        $normalized = Str::upper(trim($reference));
        if ($normalized === '') {
            return null;
        }

        return Partner::query()
            ->where('referensi', $normalized)
            ->where('status', 'active')
            ->where(function ($query) use ($event) {
                $query->where('event_uid', $event->uid)
                    ->orWhere(function ($q) use ($event) {
                        $q->whereNull('event_uid')
                            ->where('user_uid', $event->user_uid);
                    });
            })
            ->first();
    }

    public function findActivePartnerByUid(string $partnerUid, Event $event): ?Partner
    {
        return Partner::query()
            ->where('uid', $partnerUid)
            ->where('status', 'active')
            ->where(function ($query) use ($event) {
                $query->where('event_uid', $event->uid)
                    ->orWhere(function ($q) use ($event) {
                        $q->whereNull('event_uid')
                            ->where('user_uid', $event->user_uid);
                    });
            })
            ->first();
    }
}
