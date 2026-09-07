import type { PageProps as InertiaPageProps } from '@inertiajs/core';

declare global {
    namespace App {
        interface School {
            id: number;
            name: string;
            slug?: string;
            locale?: string;
        }

        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface PageProps<T extends Record<string, unknown> = Record<string, unknown>>
            extends InertiaPageProps,
                T {
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
}

export {};
