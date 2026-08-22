<?php

namespace Tests\Feature\Books;

use App\Domain\Books\Providers\GoogleBooksProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksProviderTest extends TestCase
{
    public function test_search_does_not_send_lang_restrict(): void
    {
        Http::fake([
            '*books/v1/volumes*' => Http::response([
                'items' => [
                    $this->volume('uk-1', 'Лісова пісня', 'uk'),
                    $this->volume('ru-1', 'Война и мир', 'ru'),
                    $this->volume('en-1', 'The Hobbit', 'en'),
                ],
            ]),
        ]);

        $cards = (new GoogleBooksProvider)->search('пісня', 8);

        $this->assertSame(['uk-1', 'en-1'], array_map(fn ($card) => $card->googleVolumeId, $cards));

        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ! array_key_exists('langRestrict', $query)
                && ($query['q'] ?? null) === 'пісня';
        });
    }

    public function test_find_maps_a_volume(): void
    {
        Http::fake([
            '*books/v1/volumes/abc*' => Http::response($this->volume('abc', 'The Hobbit', 'en')),
        ]);

        $card = (new GoogleBooksProvider)->find('abc');

        $this->assertNotNull($card);
        $this->assertSame('The Hobbit', $card->title);
        $this->assertSame('en', $card->language);
        $this->assertSame('https://example.com/cover.jpg', $card->coverUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function volume(string $id, string $title, string $language): array
    {
        return [
            'id' => $id,
            'volumeInfo' => [
                'title' => $title,
                'authors' => ['Author'],
                'description' => 'A short description.',
                'language' => $language,
                'pageCount' => 200,
                'publishedDate' => '1937-09-21',
                'imageLinks' => [
                    'thumbnail' => 'http://example.com/cover.jpg',
                ],
                'industryIdentifiers' => [
                    ['type' => 'ISBN_13', 'identifier' => '9780000000001'],
                ],
            ],
        ];
    }
}
