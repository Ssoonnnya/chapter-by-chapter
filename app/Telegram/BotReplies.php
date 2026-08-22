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

    public static function readingEmpty(): string
    {
        return 'No books are marked as reading. Use /library to move one there first.';
    }

    public static function sessionStarted(string $title): string
    {
        return "Started reading: {$title}";
    }

    public static function sessionAlreadyOpen(string $title): string
    {
        return "You already have an open session: {$title}. Stop or cancel it first.";
    }

    public static function askForStartPage(string $title): string
    {
        return "What page are you starting from in {$title}? Reply with a number.";
    }

    public static function askForPage(string $title): string
    {
        return "What page did you reach in {$title}? Reply with a number.";
    }

    public static function sessionPaused(string $title): string
    {
        return "Paused: {$title}";
    }

    public static function sessionResumed(string $title): string
    {
        return "Resumed: {$title}";
    }

    public static function sessionAlreadyPaused(): string
    {
        return 'This session is already paused.';
    }

    public static function sessionNotPaused(): string
    {
        return 'This session is not paused.';
    }

    public static function sendPageNumber(): string
    {
        return 'Send the page number, for example 42.';
    }

    public static function sessionStopped(string $title, int $page): string
    {
        return "Saved. You are on page {$page} of {$title}.";
    }

    public static function sessionCancelled(string $title): string
    {
        return "Cancelled the session for {$title}. The page was not changed.";
    }

    public static function bookNotReading(): string
    {
        return 'Start a session only on a book marked as reading.';
    }

    public static function invalidPage(): string
    {
        return 'That page number is not valid for this book.';
    }

    public static function noOpenSession(): string
    {
        return 'There is no open reading session.';
    }

    public static function nowReading(string $title, string $elapsed, int $page): string
    {
        return "Now reading: {$title}. {$elapsed}. Started on page {$page}.";
    }
}
