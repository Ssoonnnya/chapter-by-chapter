<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref } from 'vue';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';

export type BookSuggestion = {
    google_volume_id: string;
    title: string;
    authors: string[];
    description: string | null;
    cover_url: string | null;
    language: string | null;
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
            Suggestions appear after two characters. Your account language does
            not limit results.
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

        <InputError v-if="!compact" :message="addError" />
    </div>
</template>
