<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref } from 'vue';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { index as library } from '@/routes/library';

type LibraryBook = {
    id: number;
    title: string;
    authors: string[];
    description: string | null;
    cover_url: string | null;
    language: string | null;
};

type Suggestion = {
    google_volume_id: string;
    title: string;
    authors: string[];
    description: string | null;
    cover_url: string | null;
    language: string | null;
};

defineProps<{
    books: LibraryBook[];
}>();

const page = usePage();
const addError = computed(
    () => page.props.errors.google_volume_id as string | undefined,
);
const query = ref('');
const suggestions = ref<Suggestion[]>([]);
const searching = ref(false);
const addingId = ref<string | null>(null);

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

const fetchSuggestions = useDebounceFn(async (value: string) => {
    if (value.trim().length < 2) {
        suggestions.value = [];
        searching.value = false;

        return;
    }

    searching.value = true;

    const response = await fetch(
        `${LibraryController.suggestions.url()}?q=${encodeURIComponent(value)}`,
        { headers: { Accept: 'application/json' } },
    );

    if (!response.ok) {
        suggestions.value = [];
        searching.value = false;

        return;
    }

    const payload = (await response.json()) as { data: Suggestion[] };
    suggestions.value = payload.data;
    searching.value = false;
}, 300);

function onQueryInput(value: string | number): void {
    void fetchSuggestions(String(value));
}

function addBook(volumeId: string): void {
    addingId.value = volumeId;

    router.post(
        LibraryController.store.url(),
        { google_volume_id: volumeId },
        {
            preserveScroll: true,
            onFinish: () => {
                addingId.value = null;
                suggestions.value = [];
                query.value = '';
            },
        },
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

        <div class="relative max-w-xl space-y-2">
            <Input
                v-model="query"
                type="search"
                placeholder="Search for a book title…"
                autocomplete="off"
                data-test="book-search"
                @update:model-value="onQueryInput"
            />
            <p class="text-xs text-muted-foreground">
                Suggestions appear after two characters. Your account language
                does not limit results.
            </p>

            <div
                v-if="searching || suggestions.length > 0"
                class="absolute z-10 mt-1 w-full overflow-hidden rounded-md border bg-popover shadow-md"
                data-test="book-suggestions"
            >
                <p
                    v-if="searching"
                    class="px-3 py-2 text-sm text-muted-foreground"
                >
                    Searching…
                </p>
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion.google_volume_id"
                    type="button"
                    class="flex w-full items-start gap-3 px-3 py-2 text-left hover:bg-muted"
                    :disabled="addingId === suggestion.google_volume_id"
                    @click="addBook(suggestion.google_volume_id)"
                >
                    <img
                        v-if="suggestion.cover_url"
                        :src="suggestion.cover_url"
                        :alt="suggestion.title"
                        class="h-14 w-10 shrink-0 rounded object-cover"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ suggestion.title }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{
                                suggestion.authors.length
                                    ? suggestion.authors.join(', ')
                                    : 'Unknown author'
                            }}
                            <span v-if="suggestion.language">
                                · {{ suggestion.language }}
                            </span>
                        </p>
                    </div>
                </button>
            </div>
        </div>

        <InputError class="max-w-xl" :message="addError" />

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
                <div class="min-w-0 space-y-1">
                    <h2 class="font-medium">{{ book.title }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{
                            book.authors.length
                                ? book.authors.join(', ')
                                : 'Unknown author'
                        }}
                        <span v-if="book.language"> · {{ book.language }}</span>
                    </p>
                    <p
                        v-if="book.description"
                        class="line-clamp-3 text-sm text-muted-foreground"
                    >
                        {{ book.description }}
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>
