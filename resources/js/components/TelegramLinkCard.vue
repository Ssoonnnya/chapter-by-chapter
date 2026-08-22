<script setup lang="ts">
import QRCode from 'qrcode';
import { onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    url: string;
    expiresAt: string | null;
}>();

const qrSrc = ref<string | null>(null);
const copied = ref(false);

async function renderQr(url: string): Promise<void> {
    qrSrc.value = await QRCode.toDataURL(url, {
        margin: 1,
        width: 192,
        color: {
            dark: '#4a3428',
            light: '#faf6f0',
        },
    });
}

async function copyLink(): Promise<void> {
    await navigator.clipboard.writeText(props.url);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 2000);
}

onMounted(() => {
    void renderQr(props.url);
});

watch(
    () => props.url,
    (url) => {
        copied.value = false;
        void renderQr(url);
    },
);
</script>

<template>
    <div class="space-y-4" data-test="telegram-deep-link">
        <p class="text-sm break-all" data-test="telegram-link-url">
            {{ url }}
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <Button
                type="button"
                variant="outline"
                data-test="copy-telegram-link"
                @click="copyLink"
            >
                {{ copied ? 'Copied' : 'Copy link' }}
            </Button>
            <Button as="a" :href="url" target="_blank" rel="noreferrer">
                Open Telegram
            </Button>
        </div>

        <img
            v-if="qrSrc"
            :src="qrSrc"
            alt="QR code for the Telegram link"
            class="h-48 w-48 rounded-md border bg-card p-2"
            data-test="telegram-link-qr"
        />

        <p v-if="expiresAt" class="text-xs text-muted-foreground">
            Expires at {{ expiresAt }}
        </p>
        <p class="text-sm text-muted-foreground">
            After you tap Start in the bot, refresh this page.
        </p>
    </div>
</template>
