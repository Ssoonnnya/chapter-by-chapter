<?php

namespace Tests\Feature\Telegram;

use App\Telegram\BotCommands;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetTelegramCommandsCommandTest extends TestCase
{
    public function test_it_registers_the_command_menu(): void
    {
        Http::fake([
            '*setMyCommands*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('telegram:set-commands')->assertSuccessful();

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_contains($request->url(), 'setMyCommands')
                && ($payload['commands'] ?? null) === BotCommands::definitions();
        });
    }
}
