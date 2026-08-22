<?php

namespace App\Telegram\Handlers;

use App\Domain\Identity\Actions\LinkTelegramIdentity;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Domain\Identity\Exceptions\TelegramAlreadyLinked;
use App\Domain\Identity\Exceptions\TelegramLinkTokenInvalid;
use App\Domain\Identity\Exceptions\UserAlreadyHasTelegram;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;

class StartHandler
{
    public function __construct(
        private readonly LinkTelegramIdentity $linkTelegramIdentity,
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramMessage $message): void
    {
        $token = $message->startToken();

        if ($token !== null) {
            $this->link($message, $token);

            return;
        }

        $user = $this->resolveUserByTelegramId->handle($message->from->telegramId);

        $this->messenger->sendMessage(
            $message->chatId,
            $user === null ? BotReplies::guest() : BotReplies::welcome($user->name),
        );
    }

    private function link(IncomingTelegramMessage $message, string $token): void
    {
        try {
            $user = $this->linkTelegramIdentity->handle($token, $message->from);
        } catch (TelegramLinkTokenInvalid) {
            $this->messenger->sendMessage($message->chatId, BotReplies::invalidToken());

            return;
        } catch (TelegramAlreadyLinked) {
            $this->messenger->sendMessage($message->chatId, BotReplies::telegramAlreadyLinked());

            return;
        } catch (UserAlreadyHasTelegram) {
            $this->messenger->sendMessage($message->chatId, BotReplies::userAlreadyHasTelegram());

            return;
        }

        $this->messenger->sendMessage($message->chatId, BotReplies::linked($user->name));
    }
}
