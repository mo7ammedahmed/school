import * as React from 'react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PageHeaderProps {
    title: string;
    description?: string;
    actions?: React.ReactNode;
    breadcrumbs?: { label: string; href?: string }[];
    className?: string;
}

export function PageHeader({ title, description, actions, breadcrumbs, className }: PageHeaderProps) {
    return (
        <div className={cn('space-y-5', className)}>
            {breadcrumbs && breadcrumbs.length > 0 && (
                <nav className="flex items-center gap-1.5 text-[0.8125rem] text-muted-foreground">
                    {breadcrumbs.map((crumb, index) => (
                        <React.Fragment key={index}>
                            {index > 0 && (
                                <ChevronRight
                                    aria-hidden="true"
                                    className="size-3.5 text-muted-foreground/50 rtl:-scale-x-100"
                                />
                            )}
                            {crumb.href ? (
                                <a
                                    href={crumb.href}
                                    className="transition-colors hover:text-foreground"
                                >
                                    {crumb.label}
                                </a>
                            ) : (
                                <span className="font-medium text-foreground/80">{crumb.label}</span>
                            )}
                        </React.Fragment>
                    ))}
                </nav>
            )}
            <div className="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
                <div className="min-w-0">
                    <h1 className="text-2xl font-semibold tracking-[-0.02em] text-foreground md:text-[1.75rem] md:leading-tight">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-1.5 max-w-2xl text-[0.9375rem] leading-relaxed text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {actions && (
                    <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>
                )}
            </div>
        </div>
    );
}
