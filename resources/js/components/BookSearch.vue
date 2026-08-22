<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref } from 'vue';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { show as showBook } from '@/routes/books';

export type BookSuggestion = {
    google_volume_id: string;
    title: string;
    authors: string[];
    description: string | null;
    cover_url: string | null;
    language: string | null;
    in_library: boolean;
};

withDefaults(
    defineProps<{
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const page = usePage();
const addError = computed(
    () => page.props.errors.google_volume_id as string | undefined,
);
const query = ref('');
const suggestions = ref<BookSuggestion[]>([]);
const searching = ref(false);
const addingId = ref<string | null>(null);

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

    const payload = (await response.json()) as { data: BookSuggestion[] };
    suggestions.value = payload.data;
    searching.value = false;
}, 300);

function onQueryInput(value: string | number): void {
    void fetchSuggestions(String(value));
}

function openBook(volumeId: string): void {
    router.visit(showBook.url(volumeId));
}

function addBook(volumeId: string, event: Event): void {
    event.preventDefault();
    event.stopPropagation();

    if (
        suggestions.value.some(
            (suggestion) =>
                suggestion.google_volume_id === volumeId &&
                suggestion.in_library,
        )
    ) {
        return;
    }

    addingId.value = volumeId;

    router.post(
        LibraryController.store.url(),
        { google_volume_id: volumeId },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                suggestions.value = suggestions.value.map((suggestion) =>
                    suggestion.google_volume_id === volumeId
                        ? { ...suggestion, in_library: true }
                        : suggestion,
                );
            },
            onFinish: () => {
                addingId.value = null;
            },
        },
    );
}
</script>

<template>
    <div class="relative w-full space-y-2">
        <Input
            v-model="query"
            type="search"
            placeholder="Search for a book title…"
            autocomplete="off"
            :data-test="compact ? 'header-book-search' : 'book-search'"
            @update:model-value="onQueryInput"
        />
        <p v-if="!compact" class="text-xs text-muted-foreground">
            Open a title to read about it. The heart adds it to your library.
        </p>

        <div
            v-if="searching || suggestions.length > 0"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border bg-popover shadow-md"
            :data-test="
                compact ? 'header-book-suggestions' : 'book-suggestions'
            "
        >
            <p v-if="searching" class="px-3 py-2 text-sm text-muted-foreground">
                Searching…
            </p>
            <div
                v-for="suggestion in suggestions"
                :key="suggestion.google_volume_id"
                class="flex items-start gap-2"
            >
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-start gap-3 px-3 py-2 text-left hover:bg-muted"
                    @click="openBook(suggestion.google_volume_id)"
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
                <button
                    type="button"
                    class="mt-2 mr-2 rounded-full p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                    :class="{
                        'text-accent-foreground': suggestion.in_library,
                    }"
                    :disabled="addingId === suggestion.google_volume_id"
                    :aria-label="
                        suggestion.in_library
                            ? 'Already in your library'
                            : 'Add to library'
                    "
                    :data-test="
                        suggestion.in_library
                            ? 'suggestion-in-library'
                            : 'add-suggestion'
                    "
                    @click="addBook(suggestion.google_volume_id, $event)"
                >
                    <Heart
                        class="size-4"
                        :class="{
                            'fill-current text-accent-foreground':
                                suggestion.in_library,
                        }"
                    />
                </button>
            </div>
        </div>

        <InputError v-if="!compact" :message="addError" />
    </div>
</template>
