<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { login } from '@/routes';
import { register } from '@/routes';

const name = usePage().props.name;
</script>

<template>
    <Head :title="name" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-6"
        >
            <p class="font-serif text-xl tracking-tight">{{ name }}</p>
            <nav class="flex items-center gap-3">
                <Link
                    :href="login()"
                    class="rounded-full px-4 py-1.5 text-sm text-foreground hover:bg-secondary"
                >
                    Log in
                </Link>
                <Link
                    :href="register()"
                    class="rounded-full border border-border bg-card px-4 py-1.5 text-sm text-foreground shadow-sm hover:bg-accent"
                >
                    Register
                </Link>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-6 pb-20"
        >
            <p
                class="mb-3 text-sm tracking-wide text-muted-foreground uppercase"
            >
                A reading companion
            </p>
            <h1 class="font-serif text-4xl leading-tight sm:text-5xl">
                Keep the books you are reading, chapter by chapter.
            </h1>
            <p
                class="mt-5 max-w-lg text-base leading-relaxed text-muted-foreground"
            >
                Search for a title, put it on your shelf, and continue from the
                website or Telegram. The same library, the same page.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <Link
                    :href="register()"
                    class="rounded-full bg-primary px-5 py-2 text-sm text-primary-foreground"
                >
                    Create an account
                </Link>
                <Link
                    :href="login()"
                    class="rounded-full border border-border bg-card px-5 py-2 text-sm"
                >
                    I already have one
                </Link>
            </div>
        </main>
    </div>
</template>
