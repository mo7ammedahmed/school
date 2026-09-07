import AppShell from '@/layouts/app-shell';
import { useEffect, useRef } from 'react';
import gsap from 'gsap';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import {
    Users,
    GraduationCap,
    LayoutGrid,
    DollarSign,
    CalendarCheck,
    ReceiptText,
    ArrowUpRight,
    BookOpen,
} from 'lucide-react';

interface DashboardStats {
    total_students: number;
    total_teachers: number;
    total_classes: number;
    total_revenue: number;
    attendance_rate: number;
    pending_payments: number;
}

const ADMINS = ['school_admin', 'super_admin'];

const canEnroll = (roles: string[]) => roles.some((r) => [...ADMINS, 'registrar'].includes(r));
const canTeach = (roles: string[]) => roles.some((r) => [...ADMINS, 'teacher'].includes(r));

export default function Dashboard({ stats }: { stats: DashboardStats }) {
    const { auth } = usePage<App.PageProps>().props;
    const roles = auth.user?.roles || [];
    const grid = useRef<HTMLDivElement>(null);

    const tiles = [
        {
            title: 'Total Students',
            value: stats.total_students,
            href: '/students',
            icon: Users,
            accent: 'bg-pine-800/10 text-pine-800',
            visible: roles.some((r) => [...ADMINS, 'registrar', 'teacher'].includes(r)),
        },
        {
            title: 'Total Teachers',
            value: stats.total_teachers,
            href: '/teachers',
            icon: GraduationCap,
            accent: 'bg-gold-500/15 text-gold-700',
            visible: roles.some((r) => [...ADMINS, 'principal'].includes(r)),
        },
        {
            title: 'Total Classes',
            value: stats.total_classes,
            href: '/sections',
            icon: LayoutGrid,
            accent: 'bg-pine-800/10 text-pine-800',
            visible: roles.some((r) => [...ADMINS, 'registrar', 'principal'].includes(r)),
        },
        {
            title: 'Attendance Rate',
            value: `${Number(stats.attendance_rate).toFixed(1)}%`,
            href: '/attendance',
            icon: CalendarCheck,
            accent: 'bg-gold-500/15 text-gold-700',
            visible: roles.some((r) => [...ADMINS, 'teacher'].includes(r)),
        },
        {
            title: 'Revenue',
            value: new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                maximumFractionDigits: 0,
            }).format(Number(stats.total_revenue)),
            href: '/finance/payments',
            icon: DollarSign,
            accent: 'bg-pine-800/10 text-pine-800',
            visible: roles.some((r) => [...ADMINS, 'accountant'].includes(r)),
        },
        {
            title: 'Pending Payments',
            value: stats.pending_payments,
            href: '/finance/invoices',
            icon: ReceiptText,
            accent: 'bg-gold-500/15 text-gold-700',
            visible: roles.some((r) => [...ADMINS, 'accountant'].includes(r)),
        },
    ].filter((tile) => tile.visible);

    const roleLabel = auth.user?.roles?.[0]?.split('_').join(' ') ?? '';
    const primaryTile = tiles[0];
    const quickLinks = [
        {
            label: 'Attendance sessions',
            meta: 'Daily registers',
            href: '/attendance-sessions',
            visible: canTeach(roles),
        },
        { label: 'Announcements', meta: 'Share updates', href: '/announcements', visible: canTeach(roles) },
        { label: 'Events calendar', meta: 'Campus life', href: '/events', visible: canTeach(roles) },
        { label: 'Documents', meta: 'Policies & forms', href: '/documents', visible: canTeach(roles) },
    ].filter((item) => item.visible);

    useEffect(() => {
        if (!grid.current) return;
        const ctx = gsap.context(() => {
            gsap.from('[data-tile]', {
                y: 18,
                autoAlpha: 0,
                duration: 0.55,
                stagger: 0.07,
                ease: 'power3.out',
            });
        }, grid);
        return () => ctx.revert();
    }, []);

    return (
        <AppShell
            title="Dashboard"
            breadcrumbs={[{ label: 'Dashboard' }]}
        >
            <div className="space-y-8">
                {/* Greeting */}
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="mb-1.5 flex items-center gap-2 text-[0.8125rem] text-muted-foreground">
                            <Badge variant="neutral" className="normal-case tracking-normal">
                                {roleLabel || 'School admin'}
                            </Badge>
                        </p>
                        <h1 className="text-2xl font-semibold tracking-[-0.02em] text-foreground md:text-[1.75rem]">
                            {new Date().getHours() < 12
                                ? 'Good morning'
                                : new Date().getHours() < 18
                                  ? 'Good afternoon'
                                  : 'Good evening'}
                            {auth.user?.name ? `, ${auth.user.name.split(' ')[0]}` : ''}
                        </h1>
                        <p className="mt-1.5 max-w-xl text-[0.9375rem] leading-relaxed text-muted-foreground">
                            Here is what is happening across {auth.user?.school?.name ?? 'your school'} today.
                        </p>
                    </div>
                    {canEnroll(roles) && (
                        <Button asChild className="hidden sm:inline-flex">
                            <a href="/students/create">
                                <BookOpen className="size-4" aria-hidden="true" />
                                New enrollment
                            </a>
                        </Button>
                    )}
                </div>

                {/* Stat tiles */}
                <div ref={grid} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {tiles.map((tile) => {
                        const Icon = tile.icon;
                        return (
                            <a
                                key={tile.title}
                                href={tile.href}
                                data-tile
                                className="group relative overflow-hidden rounded-xl border border-border/80 bg-card p-5 shadow-[var(--shadow-sm)] transition-all duration-300 hover:-translate-y-0.5 hover:border-border hover:shadow-[var(--shadow-md)]"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-[0.8125rem] font-medium text-muted-foreground">
                                            {tile.title}
                                        </p>
                                        <p className="mt-2.5 text-[1.9rem] font-semibold leading-none tracking-[-0.02em] tabular-nums text-foreground">
                                            {tile.value}
                                        </p>
                                    </div>
                                    <span
                                        className={cn(
                                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-105',
                                            tile.accent
                                        )}
                                    >
                                        <Icon className="size-5" aria-hidden="true" />
                                    </span>
                                </div>
                                <span className="mt-4 inline-flex items-center gap-1 text-[0.8125rem] font-medium text-muted-foreground transition-colors group-hover:text-primary">
                                    Open module
                                    <ArrowUpRight
                                        className="size-3.5 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 rtl:-scale-x-100"
                                        aria-hidden="true"
                                    />
                                </span>
                            </a>
                        );
                    })}
                </div>

                {/* Assistant / quick panel */}
                <Card className="overflow-hidden border-border/70">
                    <div className="grid lg:grid-cols-[1fr_1.6fr]">
                        <div className="relative overflow-hidden bg-gradient-to-br from-pine-900 via-pine-950 to-[#04231b] p-8 text-white">
                            <div
                                aria-hidden="true"
                                className="pointer-events-none absolute -end-16 -top-16 size-48 rounded-full bg-gold-400/15 blur-[70px]"
                            />
                            <p className="font-display text-2xl leading-snug tracking-[-0.01em]">
                                Start the academic year with confidence.
                            </p>
                            <p className="mt-3 max-w-xs text-sm leading-relaxed text-white/65">
                                Set up classrooms, sections and timetables so every day runs smoothly from day one.
                            </p>
                            {primaryTile && (
                                <Button
                                    asChild
                                    className="mt-6 bg-white text-pine-950 hover:bg-white/90"
                                >
                                    <a href={primaryTile.href}>Open {primaryTile.title}</a>
                                </Button>
                            )}
                        </div>
                        <CardContent className="grid gap-1 p-2 sm:grid-cols-2">
                            {quickLinks.length > 0 ? (
                                quickLinks.map((item) => (
                                    <a
                                        key={item.label}
                                        href={item.href}
                                        className="group rounded-xl p-4 transition-colors hover:bg-muted/60"
                                    >
                                        <p className="flex items-center justify-between gap-2 text-[0.875rem] font-medium text-foreground">
                                            {item.label}
                                            <ArrowUpRight
                                                className="size-3.5 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 rtl:-scale-x-100"
                                                aria-hidden="true"
                                            />
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">{item.meta}</p>
                                    </a>
                                ))
                            ) : (
                                <p className="p-6 text-sm text-muted-foreground">
                                    No shortcuts available for this account type yet.
                                </p>
                            )}
                        </CardContent>
                    </div>
                </Card>
            </div>
        </AppShell>
    );
}
