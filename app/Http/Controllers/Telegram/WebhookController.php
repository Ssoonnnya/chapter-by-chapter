<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Telegram\TelegramWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(Request $request, TelegramWebhookProcessor $processor): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        $processor->handle($payload);

        return response()->json(['ok' => true]);
    }
}
