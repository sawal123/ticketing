<?php

namespace App\Http\Controllers;

use App\Models\MarketingGuideAccess;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Carbon\CarbonInterface;
use Symfony\Component\HttpFoundation\Response;

class MarketingGuideController extends Controller
{
    public function __construct(
        private MarketingGuideAccessService $accessService,
        private MarketingGuideContentService $contentService
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

        $data = $this->safeViewData($access);
        $version = $this->contentService->currentVersion();

        // Fail-safe: if no published version exists, keep the static view as a
        // temporary fallback so the public guide never 500s.
        if ($version === null) {
            return $this->secureGuideResponse(
                response()->view('marketing-guide.index', $data)
            );
        }

        $data['sections'] = $this->contentService->activeSectionsForVersion($version)
            ->load('activeBlocks');

        return $this->secureGuideResponse(
            response()->view('marketing-guide.dynamic', $data)
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
