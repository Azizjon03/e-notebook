<?php

namespace App\Modules\Telegram\Webhook;

use App\Modules\Onboarding\OnboardingFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController
{
    public function __invoke(Request $request, OnboardingFlow $onboarding): JsonResponse
    {
        $secret = (string) config('telegram.webhook_secret');
        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($secret === '' || ! hash_equals($secret, $given)) {
            abort(403);
        }

        try {
            $onboarding->handle($request->all());
        } catch (Throwable $e) {
            // Telegram xato javobda update'ni qayta-qayta yuboradi; xatoni logga yozib, 200 qaytaramiz.
            Log::error('Telegram update xatosi', ['error' => $e->getMessage(), 'update_id' => $request->input('update_id')]);
        }

        return response()->json(['ok' => true]);
    }
}
