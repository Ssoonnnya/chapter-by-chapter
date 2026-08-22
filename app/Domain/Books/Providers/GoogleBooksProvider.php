<?php

namespace App\Domain\Books\Providers;

use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Data\BookCard;
use App\Domain\Books\Language\BlockedBookLanguages;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleBooksProvider implements BookProvider
{
    /**
     * @return list<BookCard>
     */
    public function search(string $query, int $limit): array
    {
        $response = $this->request('/books/v1/volumes', [
            'q' => $query,
            'maxResults' => min($limit, 40),
        ]);

        $items = $response->json('items');

        if (! is_array($items)) {
            return [];
        }

        $cards = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            /** @var array<string, mixed> $item */
            $card = $this->mapVolume($item);

            if ($card === null || BlockedBookLanguages::contains($card->language)) {
                continue;
            }

            $cards[] = $card;
        }

        return $cards;
    }

    public function find(string $googleVolumeId): ?BookCard
    {
        $response = Http::baseUrl(rtrim((string) config('books.google_api_base_url'), '/'))
            ->acceptJson()
            ->get('/books/v1/volumes/'.rawurlencode($googleVolumeId), $this->keyQuery());

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException('Google Books lookup failed: '.$response->body());
        }

        /** @var array<string, mixed> $volume */
        $volume = $response->json() ?? [];

        return $this->mapVolume($volume);
    }

    /**
     * @param  array<string, int|string>  $query
     */
    private function request(string $path, array $query): Response
    {
        $response = Http::baseUrl(rtrim((string) config('books.google_api_base_url'), '/'))
            ->acceptJson()
            ->get($path, [...$query, ...$this->keyQuery()]);

        if ($response->failed()) {
            throw new RuntimeException('Google Books search failed: '.$response->body());
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function keyQuery(): array
    {
        $key = config('books.google_api_key');

        return is_string($key) && $key !== '' ? ['key' => $key] : [];
    }

    /**
     * @param  array<string, mixed>  $volume
     */
    private function mapVolume(array $volume): ?BookCard
    {
        $id = $volume['id'] ?? null;
        $info = $volume['volumeInfo'] ?? null;

        if (! is_string($id) || $id === '' || ! is_array($info)) {
            return null;
        }

        $title = $info['title'] ?? null;

        if (! is_string($title) || $title === '') {
            return null;
        }

        return new BookCard(
            googleVolumeId: $id,
            title: $title,
            authors: $this->authors($info['authors'] ?? null),
            description: $this->description($info['description'] ?? null),
            coverUrl: $this->coverUrl($info['imageLinks'] ?? null),
            language: $this->optionalString($info['language'] ?? null),
            isbn13: $this->isbn13($info['industryIdentifiers'] ?? null),
            pageCount: $this->optionalInt($info['pageCount'] ?? null),
            publishedYear: $this->publishedYear($info['publishedDate'] ?? null),
        );
    }

    /**
     * @return list<string>
     */
    private function authors(mixed $authors): array
    {
        if (! is_array($authors)) {
            return [];
        }

        $names = [];

        foreach ($authors as $author) {
            if (is_string($author) && $author !== '') {
                $names[] = $author;
            }
        }

        return $names;
    }

    private function description(mixed $description): ?string
    {
        if (! is_string($description) || $description === '') {
            return null;
        }

        $plain = trim(html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($plain === '') {
            return null;
        }

        if (mb_strlen($plain) <= 500) {
            return $plain;
        }

        return mb_substr($plain, 0, 497).'...';
    }

    private function coverUrl(mixed $links): ?string
    {
        if (! is_array($links)) {
            return null;
        }

        foreach (['thumbnail', 'smallThumbnail'] as $key) {
            $url = $links[$key] ?? null;

            if (is_string($url) && $url !== '') {
                return preg_replace('/^http:/', 'https:', $url) ?? $url;
            }
        }

        return null;
    }

    private function isbn13(mixed $identifiers): ?string
    {
        if (! is_array($identifiers)) {
            return null;
        }

        foreach ($identifiers as $identifier) {
            if (! is_array($identifier)) {
                continue;
            }

            if (($identifier['type'] ?? null) === 'ISBN_13' && is_string($identifier['identifier'] ?? null)) {
                return $identifier['identifier'];
            }
        }

        return null;
    }

    private function publishedYear(mixed $publishedDate): ?int
    {
        if (! is_string($publishedDate) || ! preg_match('/^(\d{4})/', $publishedDate, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function optionalInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
