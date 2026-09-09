import PublicLayout from '@/layouts/public-layout';

interface PublicSitePreviewProps {
    tokens: Record<string, string>;
}

export default function PublicSitePreview({ tokens }: PublicSitePreviewProps) {
    // Apply the token set to CSS variables on a scoped container so the preview
    // renders with the same token-driven palette as the live public site.
    return (
        <div className="pub-preview">
            <style>{`
                .pub-preview {
                    --color-primary: ${tokens.colorPrimary ?? '#0a5c42'};
                    --color-primary-foreground: ${tokens.colorPrimaryForeground ?? '#ffffff'};
                    --color-secondary: ${tokens.colorSecondary ?? '#f2efe8'};
                    --color-secondary-foreground: ${tokens.colorSecondaryForeground ?? '#29261f'};
                    --color-accent: ${tokens.colorAccent ?? '#efecdf'};
                    --color-accent-foreground: ${tokens.colorAccentForeground ?? '#29261f'};
                    --color-foreground: ${tokens.colorForeground ?? '#1c1a16'};
                    --color-background: ${tokens.colorBackground ?? '#faf9f5'};
                    --color-card: ${tokens.colorCard ?? '#ffffff'};
                    --color-card-foreground: ${tokens.colorCardForeground ?? '#1c1a16'};
                    --color-muted: ${tokens.colorMuted ?? '#f2efe8'};
                    --color-muted-foreground: ${tokens.colorMutedForeground ?? '#74705f'};
                    --color-border: ${tokens.colorBorder ?? '#e6e1d3'};
                    --color-input: ${tokens.colorInput ?? '#ddd8c9'};
                    --color-ring: ${tokens.colorRing ?? '#0a5c42'};
                    --color-link: ${tokens.colorLink ?? '#0a5c42'};
                    --color-link-hover: ${tokens.colorLinkHover ?? '#0c3f30'};
                    --color-sidebar: ${tokens.colorSidebar ?? '#ffffff'};
                    --color-sidebar-foreground: ${tokens.colorSidebarForeground ?? '#1c1a16'};
                    --color-sidebar-primary: ${tokens.colorSidebarPrimary ?? '#0a5c42'};
                    --color-sidebar-primary-foreground: ${tokens.colorSidebarPrimaryForeground ?? '#ffffff'};
                    --color-sidebar-accent: ${tokens.colorSidebarAccent ?? '#f2efe8'};
                    --color-sidebar-accent-foreground: ${tokens.colorSidebarAccentForeground ?? '#29261f'};
                    --color-sidebar-border: ${tokens.colorSidebarBorder ?? '#e6e1d3'};
                    --color-sidebar-ring: ${tokens.colorSidebarRing ?? '#0a5c42'};
                    --color-header: ${tokens.colorHeader ?? '#ffffff'};
                    --color-header-foreground: ${tokens.colorHeaderForeground ?? '#1c1a16'};
                    --color-header-border: ${tokens.colorHeaderBorder ?? '#e6e1d3'};
                    --color-footer: ${tokens.colorFooter ?? '#06281e'};
                    --color-footer-foreground: ${tokens.colorFooterForeground ?? '#f4f5f2'};
                    --color-footer-border: ${tokens.colorFooterBorder ?? '#1c533e'};
                    --color-button-primary: ${tokens.colorButtonPrimary ?? '#0a5c42'};
                    --color-button-primary-foreground: ${tokens.colorButtonPrimaryForeground ?? '#ffffff'};
                    --color-button-secondary: ${tokens.colorButtonSecondary ?? '#f2efe8'};
                    --color-button-secondary-foreground: ${tokens.colorButtonSecondaryForeground ?? '#29261f'};
                    --color-input-background: ${tokens.colorInputBackground ?? '#ffffff'};
                    --color-input-foreground: ${tokens.colorInputForeground ?? '#1c1a16'};
                    --color-input-border: ${tokens.colorInputBorder ?? '#ddd8c9'};
                    --color-input-focus: ${tokens.colorInputFocus ?? '#0a5c42'};
                    --color-success: ${tokens.colorSuccess ?? '#15803d'};
                    --color-success-foreground: ${tokens.colorSuccessForeground ?? '#ffffff'};
                    --color-warning: ${tokens.colorWarning ?? '#b45309'};
                    --color-warning-foreground: ${tokens.colorWarningForeground ?? '#ffffff'};
                    --color-error: ${tokens.colorError ?? '#b42318'};
                    --color-error-foreground: ${tokens.colorErrorForeground ?? '#ffffff'};
                    --color-info: ${tokens.colorInfo ?? '#0e7490'};
                    --color-info-foreground: ${tokens.colorInfoForeground ?? '#ffffff'};
                    --color-pine-600: ${tokens.colorPrimary ?? '#0a5c42'};
                    --color-pine-950: ${tokens.colorPrimary ?? '#0a5c42'};
                    --color-paper: ${tokens.colorBackground ?? '#faf9f5'};
                    --color-ink: ${tokens.colorForeground ?? '#1c1a16'};
                    --color-gold-400: ${tokens.colorAccent ?? '#cda253'};
                    --color-gold-600: ${tokens.colorMutedForeground ?? '#74705f'};
                }
            `}</style>
            <PublicLayout>
                <section className="public-section py-16 sm:py-20">
                    <div className="mx-auto max-w-2xl text-center">
                        <span className="eyebrow mb-4">Welcome</span>
                        <h1 className="public-site display-serif text-3xl font-semibold tracking-tight text-ink sm:text-4xl">
                            Al Noor School
                        </h1>
                        <p className="mt-4 text-lg text-muted-foreground">
                            A bilingual, future-ready school where every student is known and supported.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3 justify-center">
                            <a
                                href="/apply"
                                className="btn-primary inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-[inset_0_0_0_1px_rgba(255,255,255,0.15),0_1px_2px_rgba(6,40,30,0.3)]"
                                style={{ backgroundColor: 'var(--color-primary)' }}
                            >
                                Apply now
                            </a>
                            <a
                                href="/about"
                                className="btn-secondary inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-secondary-foreground"
                                style={{ backgroundColor: 'var(--color-secondary)', color: 'var(--color-secondary-foreground)' }}
                            >
                                About the school
                            </a>
                        </div>
                    </div>
                </section>
                <section className="public-section pb-16 sm:pb-20" style={{ backgroundColor: 'var(--color-footer)', color: 'var(--color-footer-foreground)' }}>
                    <div className="mx-auto max-w-360 px-4 pb-10 pt-16 sm:px-6 lg:px-10 text-center">
                        <p className="text-base text-white/60">
                            Nurturing minds, building character, inspiring leaders since 1995.
                        </p>
                        <div className="mt-8 flex flex-wrap justify-center gap-3">
                            <a
                                href="/login"
                                className="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white border border-white/20 hover:bg-white/10"
                                style={{ color: 'var(--color-footer-foreground)', borderColor: 'var(--color-footer-foreground)' }}
                            >
                                Staff portal
                            </a>
                        </div>
                    </div>
                </section>
            </PublicLayout>
        </div>
    );
}
