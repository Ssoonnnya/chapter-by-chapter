<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import LibraryController from '@/actions/App/Http/Controllers/LibraryController';
import BookProgress from '@/components/BookProgress.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ShelfStatus = 'want' | 'reading' | 'finished';

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
    libraryItem: LibraryItem;
    openSession: OpenSession | null;
}>();

const page = usePage();
const sessionError = computed(
    () =>
        (page.props.errors.session as string | undefined) ??
        (page.props.errors.start_page as string | undefined) ??
        (page.props.errors.end_page as string | undefined),
);

const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (timer !== undefined) {
        clearInterval(timer);
    }
});

const isOpen = computed(
    () => props.openSession?.library_item_id === props.libraryItem.id,
);
const isPaused = computed(
    () => isOpen.value && props.openSession?.paused_at !== null,
);

const startOpen = ref(false);
const stopOpen = ref(false);
const startPage = ref(props.libraryItem.current_page);
const endPage = ref(props.libraryItem.current_page);

function elapsed(): string {
    const session = props.openSession;

    if (!session) {
        return '0:00';
    }

    const until = session.paused_at ? Date.parse(session.paused_at) : now.value;
    const seconds = Math.max(
        0,
        Math.floor((until - Date.parse(session.started_at)) / 1000) -
            session.paused_seconds,
    );
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;

    return `${minutes}:${String(rest).padStart(2, '0')}`;
}

function openStart(): void {
    startPage.value = props.libraryItem.current_page;
    startOpen.value = true;
}

function openStop(): void {
    endPage.value =
        props.openSession?.start_page ?? props.libraryItem.current_page;
    stopOpen.value = true;
}

function confirmStart(): void {
    router.post(
        LibraryController.startSession.url(props.libraryItem.id),
        { start_page: Number(startPage.value) },
        {
            preserveScroll: true,
            onSuccess: () => {
                startOpen.value = false;
            },
        },
    );
}

function confirmStop(): void {
    router.post(
        LibraryController.stopSession.url(props.libraryItem.id),
        { end_page: Number(endPage.value) },
        {
            preserveScroll: true,
            onSuccess: () => {
                stopOpen.value = false;
            },
        },
    );
}

function pauseSession(): void {
    router.post(
        LibraryController.pauseSession.url(props.libraryItem.id),
        {},
        { preserveScroll: true },
    );
}

function resumeSession(): void {
    router.post(
        LibraryController.resumeSession.url(props.libraryItem.id),
        {},
        { preserveScroll: true },
    );
}

function cancelSession(): void {
    router.delete(LibraryController.cancelSession.url(props.libraryItem.id), {
        preserveScroll: true,
    });
}

const statuses: { value: ShelfStatus; label: string }[] = [
    { value: 'want', label: 'Want to read' },
    { value: 'reading', label: 'Reading' },
    { value: 'finished', label: 'Finished' },
];

function updateStatus(status: ShelfStatus): void {
    router.patch(
        LibraryController.updateStatus.url(props.libraryItem.id),
        { status },
        { preserveScroll: true },
    );
}
</script>

<template>
    <div class="space-y-4" data-test="reading-session">
        <BookProgress
            :status="libraryItem.status"
            :current-page="libraryItem.current_page"
            :page-count="libraryItem.page_count"
        />

        <div class="flex flex-wrap gap-2">
            <Button
                v-for="item in statuses"
                :key="item.value"
                size="sm"
                :variant="
                    libraryItem.status === item.value ? 'default' : 'outline'
                "
                @click="updateStatus(item.value)"
            >
                {{ item.label }}
            </Button>
        </div>

        <InputError :message="sessionError" />

        <div
            v-if="libraryItem.status === 'reading' || isOpen"
            class="space-y-2"
        >
            <p
                v-if="isOpen && openSession"
                class="text-sm font-medium"
                data-test="session-timer"
            >
                {{ isPaused ? 'Paused' : 'Reading' }} · {{ elapsed() }}
            </p>
            <p
                v-else-if="libraryItem.status === 'reading' && openSession"
                class="text-xs text-muted-foreground"
            >
                A session is already open on another book.
            </p>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="
                        libraryItem.status === 'reading' && openSession === null
                    "
                    size="sm"
                    data-test="start-session"
                    @click="openStart"
                >
                    Start reading
                </Button>
                <template v-if="isOpen">
                    <Button
                        v-if="isPaused"
                        size="sm"
                        data-test="resume-session"
                        @click="resumeSession"
                    >
                        Resume
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        variant="outline"
                        data-test="pause-session"
                        @click="pauseSession"
                    >
                        Pause
                    </Button>
                    <Button
                        size="sm"
                        data-test="stop-session"
                        @click="openStop"
                    >
                        Stop
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        data-test="cancel-session"
                        @click="cancelSession"
                    >
                        Cancel
                    </Button>
                </template>
            </div>
        </div>

        <Dialog v-model:open="startOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Start reading</DialogTitle>
                    <DialogDescription>
                        Which page are you starting from?
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="start-page">Start page</Label>
                    <Input
                        id="start-page"
                        v-model="startPage"
                        type="number"
                        min="0"
                        :max="libraryItem.page_count ?? undefined"
                        data-test="start-page"
                    />
                </div>
                <DialogFooter>
                    <Button
                        data-test="confirm-start-session"
                        @click="confirmStart"
                    >
                        Start
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="stopOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Stop reading</DialogTitle>
                    <DialogDescription>
                        Which page did you stop on?
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="end-page">End page</Label>
                    <Input
                        id="end-page"
                        v-model="endPage"
                        type="number"
                        min="0"
                        :max="libraryItem.page_count ?? undefined"
                        data-test="end-page"
                    />
                </div>
                <DialogFooter>
                    <Button
                        data-test="confirm-stop-session"
                        @click="confirmStop"
                    >
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
