<?php

namespace App\Http\Controllers;

use App\Services\MarketingGuide\MarketingGuideAccessService;
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
            return response()
                ->view('marketing-guide.expired', [], 410)
                ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        $this->accessService->recordAccess($access);

        return response()
            ->view('marketing-guide.index')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
