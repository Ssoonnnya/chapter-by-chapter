<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTelegramWebhookSecret
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('telegram.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            abort(403);
        }

        $header = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! hash_equals($secret, $header)) {
            abort(403);
        }

        return $next($request);
    }
}
