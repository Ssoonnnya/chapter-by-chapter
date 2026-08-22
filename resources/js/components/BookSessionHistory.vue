<script setup lang="ts">
type Session = {
    id: number;
    started_at: string;
    ended_at: string;
    start_page: number;
    end_page: number;
    pages: number;
    seconds: number;
    minutes: number;
    pages_per_hour: number | null;
};

const props = defineProps<{
    sessions: Session[];
}>();

function dateLabel(iso: string): string {
    return new Date(iso).toLocaleString('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function durationLabel(session: Session): string {
    if (session.minutes > 0) {
        return `${session.minutes} min`;
    }

    if (session.seconds > 0) {
        return '< 1 min';
    }

    return '0 min';
}
</script>

<template>
    <section
        v-if="props.sessions.length > 0"
        class="space-y-3"
        data-test="session-history"
    >
        <h2 class="font-serif text-xl">Reading sessions</h2>

        <ul
            class="divide-y divide-border overflow-hidden rounded-xl border bg-card"
        >
            <li
                v-for="session in props.sessions"
                :key="session.id"
                class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-baseline sm:justify-between"
                :data-test="`session-history-${session.id}`"
            >
                <p class="text-sm">
                    {{ dateLabel(session.ended_at) }}
                    <span class="text-muted-foreground">
                        · pages {{ session.start_page }}–{{ session.end_page }}
                    </span>
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ session.pages }} pages · {{ durationLabel(session) }}
                    <template v-if="session.pages_per_hour !== null">
                        · {{ session.pages_per_hour }} pages/hour
                    </template>
                </p>
            </li>
        </ul>
    </section>
</template>
