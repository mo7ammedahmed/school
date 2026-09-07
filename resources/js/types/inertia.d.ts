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
                school?: {
                    id: number;
                    name: string;
                    locale: string;
                    timezone: string;
                };
            };
        };
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
