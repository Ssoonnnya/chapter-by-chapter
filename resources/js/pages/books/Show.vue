<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import ReadingSessionPanel from '@/components/ReadingSessionPanel.vue';
import { Button } from '@/components/ui/button';
import { index as library } from '@/routes/library';

type ShelfStatus = 'want' | 'reading' | 'finished';

type Book = {
    id: number;
    google_volume_id: string;
    title: string;
    authors: string[];
    description: string | null;
    cover_url: string | null;
    language: string | null;
    isbn_13: string | null;
    page_count: number | null;
    published_year: number | null;
};

type LibraryItem = {
    id: number;
    status: ShelfStatus;
    current_page: number;
    page_count: number | null;
};

type OpenSession = {
    id: number;
    library_item_id: number;
    started_at: string;
    start_page: number;
    paused_at: string | null;
    paused_seconds: number;
};

const props = defineProps<{
    book: Book;
    libraryItem: LibraryItem | null;
    openSession: OpenSession | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Library',
                href: library(),
            },
        ],
    },
});

function addToLibrary(): void {
    router.post(
        LibraryController.store.url(),
        { google_volume_id: props.book.google_volume_id },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="book.title" />

    <div class="flex flex-1 flex-col gap-8 p-4">
        <Link
            :href="library()"
            class="text-sm text-muted-foreground hover:text-foreground"
        >
            ← Library
        </Link>

        <div class="flex flex-col gap-8 md:flex-row">
            <img
                v-if="book.cover_url"
                :src="book.cover_url"
                :alt="book.title"
                class="h-72 w-48 shrink-0 rounded-lg object-cover shadow-sm"
            />

            <div class="min-w-0 flex-1 space-y-5">
                <div class="space-y-2">
                    <h1 class="font-serif text-3xl leading-tight">
                        {{ book.title }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{
                            book.authors.length
                                ? book.authors.join(', ')
                                : 'Unknown author'
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="book.published_year">{{
                            book.published_year
                        }}</span>
                        <span v-if="book.language">
                            <span v-if="book.published_year"> · </span>
                            {{ book.language }}
                        </span>
                        <span v-if="book.page_count">
                            · {{ book.page_count }} pages
                        </span>
                        <span v-if="book.isbn_13">
                            · ISBN {{ book.isbn_13 }}</span
                        >
                    </p>
                </div>

                <p
                    v-if="book.description"
                    class="max-w-2xl text-sm leading-relaxed whitespace-pre-line"
                    data-test="book-description"
                >
                    {{ book.description }}
                </p>

                <Button
                    v-if="libraryItem === null"
                    variant="outline"
                    data-test="add-to-library"
                    @click="addToLibrary"
                >
                    <Heart class="size-4" />
                    Add to library
                </Button>
                <p
                    v-else
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground"
                    data-test="on-shelf"
                >
                    <Heart class="size-4 fill-current text-accent-foreground" />
                    On your shelf
                </p>

                <ReadingSessionPanel
                    v-if="libraryItem"
                    :library-item="libraryItem"
                    :open-session="openSession"
                />
            </div>
        </div>
    </div>
</template>
