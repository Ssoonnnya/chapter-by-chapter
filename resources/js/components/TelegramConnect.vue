<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import TelegramLinkController from '@/actions/App/Http/Controllers/Settings/TelegramLinkController';
import TelegramLinkCard from '@/components/TelegramLinkCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const page = usePage();
const telegram = computed(() => page.props.auth.telegram);

const confirmOpen = ref(false);
const linkOpen = ref(false);
const generating = ref(false);
const error = ref<string | null>(null);
const linkUrl = ref<string | null>(null);
const expiresAt = ref<string | null>(null);

const connectedLabel = computed(() => {
    const username = telegram.value?.username;

    return username ? `Connected / @${username}` : 'Connected';
});

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function openConfirm(): void {
    if (telegram.value !== null) {
        return;
    }

    error.value = null;
    confirmOpen.value = true;
}

async function generateLink(): Promise<void> {
    generating.value = true;
    error.value = null;

    const response = await fetch(TelegramLinkController.store.url(), {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    generating.value = false;

    if (response.status === 409) {
        confirmOpen.value = false;
        window.location.reload();

        return;
    }

    if (!response.ok) {
        const payload = (await response.json().catch(() => null)) as {
            message?: string;
        } | null;
        error.value = payload?.message ?? 'Could not create a Telegram link.';

        return;
    }

    const payload = (await response.json()) as {
        url: string;
        expires_at: string;
    };

    linkUrl.value = payload.url;
    expiresAt.value = payload.expires_at;
    confirmOpen.value = false;
    linkOpen.value = true;
}
</script>

<template>
    <div>
        <p
            v-if="telegram"
            class="max-w-40 truncate text-xs text-muted-foreground sm:max-w-none sm:text-sm"
            data-test="telegram-connected"
            :title="connectedLabel"
        >
            {{ connectedLabel }}
        </p>
        <Button
            v-else
            variant="outline"
            size="sm"
            data-test="header-telegram"
            @click="openConfirm"
        >
            <Send class="size-4" />
            Telegram
        </Button>

        <Dialog v-model:open="confirmOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Connect Telegram?</DialogTitle>
                    <DialogDescription>
                        We will create a one-time link. Open it in Telegram to
                        connect this account. The link expires in 10 minutes.
                    </DialogDescription>
                </DialogHeader>
                <p v-if="error" class="text-sm text-destructive">
                    {{ error }}
                </p>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        :disabled="generating"
                        data-test="confirm-telegram-connect"
                        @click="generateLink"
                    >
                        {{ generating ? 'Creating link…' : 'Create link' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="linkOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Open Telegram</DialogTitle>
                    <DialogDescription>
                        Copy the link or scan the QR code, then tap Start in the
                        bot.
                    </DialogDescription>
                </DialogHeader>
                <TelegramLinkCard
                    v-if="linkUrl"
                    :url="linkUrl"
                    :expires-at="expiresAt"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
