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

    public static function searchUsage(): string
    {
        return 'Search with /search and a title, for example: /search Лісова пісня';
    }

    public static function searchEmpty(): string
    {
        return 'No books found. Try another title. Russian editions are not listed.';
    }

    public static function added(string $title): string
    {
        return "Added: {$title}";
    }

    public static function alreadyInLibrary(string $title): string
    {
        return "Already in your library: {$title}";
    }

    public static function libraryEmpty(): string
    {
        return 'Your library is empty. Use /search to add a book.';
    }

    public static function bookNotFound(): string
    {
        return 'That book could not be added.';
    }

    public static function unsupportedLanguage(): string
    {
        return 'Russian-language books are not supported.';
    }

    public static function statusUpdated(string $title, string $status): string
    {
        return "{$title} is now marked as {$status}.";
    }

    public static function libraryItemMissing(): string
    {
        return 'That book is not in your library.';
    }
}
