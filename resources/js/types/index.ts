/// <reference path="./global.d.ts" />

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = App.PageProps<T>;

export interface School {
    id: number;
    name: string;
    slug: string;
    email?: string;
    phone?: string;
    address?: string;
    city?: string;
    country: string;
    timezone: string;
    locale: string;
    currency: string;
    logo_path?: string;
    favicon_path?: string;
    primary_color: string;
    secondary_color: string;
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
