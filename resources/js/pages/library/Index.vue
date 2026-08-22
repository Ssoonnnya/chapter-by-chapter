<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import BookSearch from '@/components/BookSearch.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as library } from '@/routes/library';

type ShelfStatus = 'want' | 'reading' | 'finished';

type LibraryBook = {
    id: number;
    title: string;
    authors: string[];
    description: string | null;
    cover_url: string | null;
    language: string | null;
    status: ShelfStatus;
    current_page: number;
    page_count: number | null;
};

const props = defineProps<{
    books: LibraryBook[];
    filter: ShelfStatus | null;
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

function savePage(id: number, event: Event): void {
    const value = Number((event.target as HTMLInputElement).value);

    router.patch(
        LibraryController.updatePage.url(id),
        { current_page: value },
        { preserveScroll: true },
    );
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
                <img
                    v-if="book.cover_url"
                    :src="book.cover_url"
                    :alt="book.title"
                    class="h-24 w-16 shrink-0 rounded object-cover"
                />
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="space-y-1">
                        <h2 class="font-medium">{{ book.title }}</h2>
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
                            v-if="book.description"
                            class="line-clamp-3 text-sm text-muted-foreground"
                        >
                            {{ book.description }}
                        </p>
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

                    <div class="flex max-w-xs items-end gap-2">
                        <div class="grid flex-1 gap-1">
                            <Label :for="`page-${book.id}`">Page</Label>
                            <Input
                                :id="`page-${book.id}`"
                                type="number"
                                min="0"
                                :max="book.page_count ?? undefined"
                                :default-value="book.current_page"
                                @change="savePage(book.id, $event)"
                            />
                        </div>
                        <p
                            v-if="book.page_count"
                            class="pb-2 text-sm text-muted-foreground"
                        >
                            / {{ book.page_count }}
                        </p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
