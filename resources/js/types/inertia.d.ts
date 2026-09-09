import type { PageProps as InertiaPageProps } from '@inertiajs/core';

declare module '@inertiajs/core' {
    interface PageProps {
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
                roles: string[];
                permissions: string[];
                school?: App.School | null;
            } | null;
        };
        school?: App.School | null;
        appearance?: App.Appearance;
        themeConfig?: App.ThemeConfig;
        navLabels?: Record<string, { en?: string | null; ar?: string | null }>;
        locale: string;
        flash: {
            success?: string;
            error?: string;
            warning?: string;
            info?: string;
        };
        errors: Record<string, string>;
    }
}

export {};