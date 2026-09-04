<?php

namespace App\Http\Controllers;

use App\Models\MarketingGuideAccess;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use Carbon\CarbonInterface;
use Symfony\Component\HttpFoundation\Response;

class MarketingGuideController extends Controller
{
    public function __construct(
        private MarketingGuideAccessService $accessService
    ) {}

    public function show(string $token): Response
    {
        $access = $this->accessService->findByToken($token);
        $status = $this->accessService->resolveStatus($access);

        if ($status === MarketingGuideAccessService::STATUS_INVALID
            || $status === MarketingGuideAccessService::STATUS_REVOKED) {
            abort(404);
        }

        if ($status === MarketingGuideAccessService::STATUS_EXPIRED) {
            return $this->secureGuideResponse(
                response()->view('marketing-guide.expired', [], 410)
            );
        }

        // Re-load under lock inside recordAccess; never trust the pre-lock snapshot for render.
        $access = $this->accessService->recordAccess($access);
        $status = $this->accessService->resolveStatus($access);

        if ($status === MarketingGuideAccessService::STATUS_INVALID
            || $status === MarketingGuideAccessService::STATUS_REVOKED) {
            abort(404);
        }

        if ($status === MarketingGuideAccessService::STATUS_EXPIRED) {
            return $this->secureGuideResponse(
                response()->view('marketing-guide.expired', [], 410)
            );
        }

        return $this->secureGuideResponse(
            response()->view('marketing-guide.index', $this->safeViewData($access))
        );
    }

    /**
     * @return array{recipientName: ?string, expiresAt: CarbonInterface|\DateTimeInterface|null}
     */
    private function safeViewData(MarketingGuideAccess $access): array
    {
        $recipientName = filled($access->recipient_name)
            ? trim((string) $access->recipient_name)
            : null;

        if ($recipientName === '') {
            $recipientName = null;
        }

        return [
            'recipientName' => $recipientName,
            'expiresAt' => $access->expires_at,
        ];
    }

    private function secureGuideResponse(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'DENY')
            ->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
