<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BookSearch from '@/components/BookSearch.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import TelegramConnect from '@/components/TelegramConnect.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { index as library } from '@/routes/library';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const siteName = computed(() => page.props.name);
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80 bg-card/70">
            <div class="mx-auto flex h-16 items-center gap-3 px-4 md:max-w-7xl">
                <Link
                    :href="library()"
                    class="shrink-0 font-serif text-lg tracking-tight text-foreground"
                >
                    {{ siteName }}
                </Link>

                <div class="min-w-0 flex-1 md:mx-6 md:max-w-xl">
                    <BookSearch compact />
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <TelegramConnect />

                    <DropdownMenu v-if="auth.user">
                        <DropdownMenuTrigger :as-child="true">
                            <button
                                type="button"
                                class="cursor-pointer rounded-full border bg-background px-3 py-1.5 text-sm font-medium text-foreground shadow-sm hover:bg-secondary"
                                data-test="user-menu"
                            >
                                {{ auth.user.name }}
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-muted-foreground md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
