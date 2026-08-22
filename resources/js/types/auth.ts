export type User = {
    id: number;
    name: string;
    email: string;
    locale: 'en' | 'uk';
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type TelegramConnection = {
    telegram_id: number;
    username: string | null;
};

export type Auth = {
    user: User | null;
    telegram: TelegramConnection | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
