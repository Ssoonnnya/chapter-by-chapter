<?php

namespace App\Telegram;

final class BotReplies
{
    public static function guest(): string
    {
        return 'To use this bot, create an account on the website and connect Telegram from Settings: '.config('app.url');
    }

    public static function welcome(string $name): string
    {
        return "Welcome back, {$name}!";
    }

    public static function linked(string $name): string
    {
        return "Your account is connected. Welcome, {$name}!";
    }

    public static function invalidToken(): string
    {
        return 'This link is invalid or has expired. Generate a new one from Settings.';
    }

    public static function telegramAlreadyLinked(): string
    {
        return 'This Telegram account is already connected to another user.';
    }

    public static function userAlreadyHasTelegram(): string
    {
        return 'This website account already has a Telegram connection.';
    }
}
