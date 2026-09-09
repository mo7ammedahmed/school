import type { PageProps as InertiaPageProps } from '@inertiajs/core';

declare global {
    namespace App {
        interface Appearance {
            /**
             * Only the light theme is supported. The app always renders
             * in light mode (no dark variant), regardless of the user's
             * OS/browser preference.
             */
            theme: 'light';
            primary_color?: string | null;
            secondary_color?: string | null;
            accent_color?: string | null;
            logo_path?: string | null;
            favicon_path?: string | null;
        }

        interface ThemeConfig {
            light: Record<string, string>;
            dark: Record<string, string>;
        }

        interface School {
            id: number;
            name?: string;
            slug?: string;
            locale?: string;
            timezone?: string;
            name_ar?: string | null;
            name_en?: string | null;
            logo_path?: string | null;
            favicon_path?: string | null;
            primary_color?: string | null;
            secondary_color?: string | null;
            accent_color?: string | null;
            theme_config?: ThemeConfig | null;
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
}

export {};