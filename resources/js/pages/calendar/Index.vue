<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { index as calendar } from '@/routes/calendar';
import { index as library } from '@/routes/library';

type ReadingDay = {
    date: string;
    sessions: number;
    pages: number;
    seconds: number;
    minutes: number;
};

type ReadingSummary = {
    month: string | null;
    sessions: number;
    pages: number;
    seconds: number;
    minutes: number;
    pages_per_hour: number | null;
    days: ReadingDay[];
};

const props = defineProps<{
    month: string;
    summary: ReadingSummary;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Library',
                href: library(),
            },
            {
                title: 'Calendar',
                href: calendar(),
            },
        ],
    },
});

const localToday = new Date();
const today = [
    localToday.getFullYear(),
    String(localToday.getMonth() + 1).padStart(2, '0'),
    String(localToday.getDate()).padStart(2, '0'),
].join('-');
const selected = ref<string | null>(
    props.summary.days.find((day) => day.date === today && day.sessions > 0)
        ?.date ??
        props.summary.days.find((day) => day.sessions > 0)?.date ??
        null,
);

const monthLabel = computed(() => {
    const [year, month] = props.month.split('-').map(Number);

    return new Date(year, month - 1, 1).toLocaleString('en', {
        month: 'long',
        year: 'numeric',
    });
});

const weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const leadingBlanks = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    const weekday = new Date(year, month - 1, 1).getDay();

    return (weekday + 6) % 7;
});

const selectedDay = computed(
    () => props.summary.days.find((day) => day.date === selected.value) ?? null,
);

function shiftMonth(delta: number): string {
    const [year, month] = props.month.split('-').map(Number);
    const date = new Date(year, month - 1 + delta, 1);
    const nextYear = date.getFullYear();
    const nextMonth = String(date.getMonth() + 1).padStart(2, '0');

    return `${nextYear}-${nextMonth}`;
}

function monthHref(month: string): string {
    return calendar.url({ query: { month } });
}

function dayNumber(date: string): number {
    return Number(date.slice(8, 10));
}

function tooltip(day: ReadingDay): string {
    if (day.sessions === 0) {
        return dateLabel(day.date);
    }

    return `${day.pages} pages · ${day.minutes} min`;
}

function dateLabel(date: string): string {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleString('en', {
        day: 'numeric',
        month: 'short',
    });
}

function totalsLine(): string {
    const parts = [
        `${props.summary.sessions} sessions`,
        `${props.summary.pages} pages`,
        `${props.summary.minutes} min`,
    ];

    if (props.summary.pages_per_hour !== null) {
        parts.push(`${props.summary.pages_per_hour} pages/hour`);
    }

    return parts.join(' · ');
}
</script>

<template>
    <Head title="Calendar" />

    <div class="flex flex-1 flex-col gap-8 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading title="Calendar" :description="monthLabel" />

            <div class="flex gap-2">
                <Link
                    :href="monthHref(shiftMonth(-1))"
                    class="rounded-md border bg-card px-3 py-1.5 text-sm text-foreground hover:bg-secondary"
                    data-test="calendar-prev"
                >
                    Previous
                </Link>
                <Link
                    :href="monthHref(shiftMonth(1))"
                    class="rounded-md border bg-card px-3 py-1.5 text-sm text-foreground hover:bg-secondary"
                    data-test="calendar-next"
                >
                    Next
                </Link>
            </div>
        </div>

        <p class="text-sm text-muted-foreground" data-test="calendar-totals">
            {{ totalsLine() }}
        </p>

        <div class="max-w-xl space-y-3" data-test="calendar-grid">
            <div
                class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground"
            >
                <span v-for="label in weekdayLabels" :key="label">{{
                    label
                }}</span>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <span
                    v-for="blank in leadingBlanks"
                    :key="`blank-${blank}`"
                    class="aspect-square"
                />
                <button
                    v-for="day in summary.days"
                    :key="day.date"
                    type="button"
                    :title="tooltip(day)"
                    :data-test="`calendar-day-${day.date}`"
                    :class="[
                        'aspect-square rounded-lg border text-sm transition-colors',
                        day.sessions > 0
                            ? 'border-accent-foreground/20 bg-accent text-accent-foreground'
                            : 'border-transparent bg-card text-muted-foreground',
                        day.date === today ? 'ring-1 ring-primary/40' : '',
                        selected === day.date ? 'ring-2 ring-primary' : '',
                    ]"
                    @click="selected = day.date"
                >
                    {{ dayNumber(day.date) }}
                </button>
            </div>
        </div>

        <p
            v-if="selectedDay && selectedDay.sessions > 0"
            class="text-sm"
            data-test="calendar-day-detail"
        >
            {{ dateLabel(selectedDay.date) }} · {{ selectedDay.pages }} pages ·
            {{ selectedDay.minutes }} min
        </p>
        <p
            v-else-if="selectedDay"
            class="text-sm text-muted-foreground"
            data-test="calendar-day-empty"
        >
            {{ dateLabel(selectedDay.date) }} · no finished sessions
        </p>
    </div>
</template>
