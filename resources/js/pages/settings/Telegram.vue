<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TelegramLinkController from '@/actions/App/Http/Controllers/Settings/TelegramLinkController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { edit } from '@/routes/telegram';

type Props = {
    identity: {
        telegram_id: number;
        username: string | null;
    } | null;
    linkUrl: string | null;
    linkExpiresAt: string | null;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Telegram settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Telegram settings" />

    <h1 class="sr-only">Telegram settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Telegram"
            description="Connect the bot so your reading data is the same on the website and in Telegram"
        />

        <div v-if="props.identity" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Connected as
                <span class="font-medium text-foreground">
                    {{
                        props.identity.username
                            ? `@${props.identity.username}`
                            : `Telegram ID ${props.identity.telegram_id}`
                    }}
                </span>
            </p>

            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="unlink-telegram">
                        Disconnect Telegram
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="TelegramLinkController.destroy.form()"
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ processing }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                Disconnect Telegram from this account?
                            </DialogTitle>
                            <DialogDescription>
                                The bot will stop recognizing you until you
                                connect again.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-unlink-telegram"
                            >
                                Disconnect
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div v-else class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Generate a one-time link, then open it in Telegram. The link
                expires in 10 minutes.
            </p>

            <Form
                v-bind="TelegramLinkController.store.form()"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <InputError :message="errors.telegram" />
                <Button
                    :disabled="processing"
                    data-test="generate-telegram-link"
                >
                    Connect Telegram
                </Button>
            </Form>

            <div v-if="props.linkUrl" class="rounded-lg border p-4">
                <TelegramLinkCard
                    :url="props.linkUrl"
                    :expires-at="props.linkExpiresAt"
                />
            </div>
        </div>
    </div>
</template>
