/// <reference path="./global.d.ts" />

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = App.PageProps<T>;

export interface School {
    id: number;
    name?: string;
    name_ar?: string | null;
    name_en?: string | null;
    slug: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    country: string;
    timezone: string;
    locale: string;
    currency: string;
    logo_path?: string | null;
    favicon_path?: string | null;
    primary_color?: string | null;
    secondary_color?: string | null;
    accent_color?: string | null;
    theme_config?: App.ThemeConfig | null;
    metadata?: Record<string, unknown>;
}

export interface Organization {
    id: number;
    name: string;
    slug: string;
    email?: string;
    phone?: string;
    address?: string;
    logo_path?: string;
    metadata?: Record<string, unknown>;
}

export interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
    school?: {
        id: number;
        name: string;
        locale: string;
        timezone: string;
    };
}

export interface Flash {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}
