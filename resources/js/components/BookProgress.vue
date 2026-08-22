<script setup lang="ts">
type ShelfStatus = 'want' | 'reading' | 'finished';

const props = defineProps<{
    status: ShelfStatus;
    currentPage: number;
    pageCount: number | null;
}>();

const percent = (): number => {
    if (props.pageCount === null || props.pageCount === 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((props.currentPage / props.pageCount) * 100),
    );
};
</script>

<template>
    <div v-if="status !== 'want'" class="space-y-1" data-test="book-progress">
        <template v-if="status === 'finished'">
            <p class="text-sm font-medium" data-test="book-finished">
                100% · Finished
            </p>
        </template>
        <template v-else-if="pageCount">
            <div
                class="h-2 overflow-hidden rounded-full bg-secondary"
                role="progressbar"
                :aria-valuenow="percent()"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="h-full rounded-full bg-primary"
                    :style="{ width: `${percent()}%` }"
                />
            </div>
            <p class="text-xs text-muted-foreground">
                {{ currentPage }} / {{ pageCount }} · {{ percent() }}%
            </p>
        </template>
    </div>
</template>
