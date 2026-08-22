<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Identity\Actions\GenerateTelegramLinkToken;
use App\Domain\Identity\Actions\UnlinkTelegramIdentity;
use App\Domain\Identity\Exceptions\TelegramBotUsernameMissing;
use App\Domain\Identity\Exceptions\UserAlreadyHasTelegram;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TelegramLinkController extends Controller
{
    /**
     * Show Telegram connection settings.
     */
    public function edit(Request $request): Response
    {
        $user = $this->user($request);
        $identity = $user->telegramIdentity;

        return Inertia::render('settings/Telegram', [
            'identity' => $identity === null ? null : [
                'telegram_id' => $identity->telegram_id,
                'username' => $identity->username,
            ],
            'linkUrl' => $request->session()->get('telegram_link_url'),
            'linkExpiresAt' => $request->session()->get('telegram_link_expires_at'),
        ]);
    }

    /**
     * Generate a one-time Telegram deep link.
     */
    public function store(Request $request, GenerateTelegramLinkToken $generate): RedirectResponse
    {
        try {
            $result = $generate->handle($this->user($request)->load('telegramIdentity'));
        } catch (UserAlreadyHasTelegram) {
            return to_route('telegram.edit');
        } catch (TelegramBotUsernameMissing) {
            return to_route('telegram.edit')->withErrors([
                'telegram' => __('Telegram bot is not configured.'),
            ]);
        }

        $request->session()->flash('telegram_link_url', $result->url);
        $request->session()->flash('telegram_link_expires_at', $result->expiresAt->toIso8601String());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Open Telegram to finish connecting your account.')]);

        return to_route('telegram.edit');
    }

    /**
     * Disconnect Telegram from the authenticated user.
     */
    public function destroy(Request $request, UnlinkTelegramIdentity $unlink): RedirectResponse
    {
        $unlink->handle($this->user($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Telegram disconnected.')]);

        return to_route('telegram.edit');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
