<?php

namespace App\Domain\Books\Data;

final readonly class BookCard
{
    /**
     * @param  list<string>  $authors
     */
    public function __construct(
        public string $googleVolumeId,
        public string $title,
        public array $authors,
        public ?string $description,
        public ?string $coverUrl,
        public ?string $language,
        public ?string $isbn13,
        public ?int $pageCount,
        public ?int $publishedYear,
    ) {}

    /**
     * @return array{
     *     google_volume_id: string,
     *     title: string,
     *     authors: list<string>,
     *     description: string|null,
     *     cover_url: string|null,
     *     language: string|null,
     *     isbn_13: string|null,
     *     page_count: int|null,
     *     published_year: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'google_volume_id' => $this->googleVolumeId,
            'title' => $this->title,
            'authors' => $this->authors,
            'description' => $this->description,
            'cover_url' => $this->coverUrl,
            'language' => $this->language,
            'isbn_13' => $this->isbn13,
            'page_count' => $this->pageCount,
            'published_year' => $this->publishedYear,
        ];
    }
}
