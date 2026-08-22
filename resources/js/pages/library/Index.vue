<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import BookProgress from '@/components/BookProgress.vue';
import BookSearch from '@/components/BookSearch.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { show as showBook } from '@/routes/books';
import { index as library } from '@/routes/library';

type ShelfStatus = 'want' | 'reading' | 'finished';

type LibraryBook = {
    id: number;
    google_volume_id: string;
    title: string;
    authors: string[];
    cover_url: string | null;
    language: string | null;
    status: ShelfStatus;
    current_page: number;
    page_count: number | null;
};

type OpenSession = {
    id: number;
    library_item_id: number;
    started_at: string;
    start_page: number;
};

const props = defineProps<{
    books: LibraryBook[];
    filter: ShelfStatus | null;
    openSession: OpenSession | null;
}>();

const statuses: { value: ShelfStatus; label: string }[] = [
    { value: 'want', label: 'Want to read' },
    { value: 'reading', label: 'Reading' },
    { value: 'finished', label: 'Finished' },
];

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

function filterBy(status: ShelfStatus | null): void {
    router.get(library.url(), status ? { status } : {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

function updateStatus(id: number, status: ShelfStatus): void {
    router.patch(
        LibraryController.updateStatus.url(id),
        { status },
        { preserveScroll: true },
    );
}

function isOpen(book: LibraryBook): boolean {
    return props.openSession?.library_item_id === book.id;
}
</script>

<template>
    <Head title="Library" />

    <div class="flex flex-1 flex-col gap-8 p-4">
        <Heading
            title="Library"
            description="Search by title in any language. Russian editions are not listed."
        />

        <div class="max-w-xl">
            <BookSearch />
        </div>

        <div class="flex flex-wrap gap-2" data-test="shelf-filters">
            <Button
                size="sm"
                :variant="props.filter === null ? 'default' : 'outline'"
                @click="filterBy(null)"
            >
                All
            </Button>
            <Button
                v-for="item in statuses"
                :key="item.value"
                size="sm"
                :variant="props.filter === item.value ? 'default' : 'outline'"
                @click="filterBy(item.value)"
            >
                {{ item.label }}
            </Button>
        </div>

        <div
            v-if="books.length === 0"
            class="text-sm text-muted-foreground"
            data-test="library-empty"
        >
            No books yet. Search above to add one.
        </div>

        <ul v-else class="grid gap-4 md:grid-cols-2">
            <li
                v-for="book in books"
                :key="book.id"
                class="flex gap-4 rounded-xl border p-4"
            >
                <Link :href="showBook(book.google_volume_id)" class="shrink-0">
                    <img
                        v-if="book.cover_url"
                        :src="book.cover_url"
                        :alt="book.title"
                        class="h-24 w-16 rounded object-cover"
                    />
                </Link>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="space-y-1">
                        <Link
                            :href="showBook(book.google_volume_id)"
                            class="font-medium hover:underline"
                        >
                            {{ book.title }}
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            {{
                                book.authors.length
                                    ? book.authors.join(', ')
                                    : 'Unknown author'
                            }}
                            <span v-if="book.language">
                                · {{ book.language }}
                            </span>
                        </p>
                        <p
                            v-if="isOpen(book)"
                            class="text-xs font-medium text-accent-foreground"
                            data-test="reading-now"
                        >
                            Reading now
                        </p>
                        <BookProgress
                            :status="book.status"
                            :current-page="book.current_page"
                            :page-count="book.page_count"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="item in statuses"
                            :key="item.value"
                            size="sm"
                            :variant="
                                book.status === item.value
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="updateStatus(book.id, item.value)"
                        >
                            {{ item.label }}
                        </Button>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
