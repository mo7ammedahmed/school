import { type ReactNode, useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { t, type CopyKey } from '@/lib/i18n/copy';
import { useLocale } from '@/lib/i18n/locale-context';
import { LanguageSwitcher } from '@/components/ui/language-switcher';
import { cn } from '@/lib/utils';
import {
    Users,
    UserCheck,
    BookOpen,
    CalendarCheck,
    ClipboardCheck,
    DollarSign,
    Settings,
    GraduationCap,
    DoorOpen,
    ClipboardList,
    FileText,
    CalendarDays,
    Bell,
    MessageSquare,
    Users2,
    Award,
    Upload,
    Calendar,
    PenTool,
    BarChart3,
    UserPlus,
    LayoutGrid,
    LogOut,
    Menu,
    X,
    Sparkles,
    TrendingUp,
} from 'lucide-react';

interface AppShellProps {
    children: ReactNode;
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

interface NavItem {
    key: CopyKey;
    href: string;
    icon: typeof BarChart3;
    roles: string[];
}

interface NavGroup {
    labelKey?: CopyKey;
    items: NavItem[];
}

// Role names match the seeded Spatie roles exactly.
const STAFF = ['school_admin', 'super_admin', 'principal', 'registrar', 'teacher', 'accountant'];
const ADMINS = ['school_admin', 'super_admin'];

export const NAV_GROUPS: NavGroup[] = [
    {
        labelKey: 'shell.group.studentPortal',
        items: [
            { key: 'nav.studentDashboard', href: '/student/dashboard', icon: GraduationCap, roles: ['student'] },
            { key: 'nav.studentSchedule', href: '/student/schedule', icon: Calendar, roles: ['student'] },
            { key: 'nav.studentAttendance', href: '/student/attendance', icon: CalendarCheck, roles: ['student'] },
            { key: 'nav.studentGrades', href: '/student/grades', icon: TrendingUp, roles: ['student'] },
            { key: 'nav.studentAssignments', href: '/student/assignments', icon: ClipboardList, roles: ['student'] },
            { key: 'nav.studentFees', href: '/student/fees', icon: DollarSign, roles: ['student'] },
        ],
    },
    {
        labelKey: 'shell.group.guardianPortal',
        items: [
            { key: 'nav.guardianDashboard', href: '/guardian/dashboard', icon: Users2, roles: ['guardian'] },
            { key: 'nav.guardianChildren', href: '/guardian/children', icon: Users, roles: ['guardian'] },
        ],
    },
    {
        items: [{ key: 'nav.dashboard', href: '/dashboard', icon: BarChart3, roles: STAFF }],
    },
    {
        labelKey: 'shell.group.people',
        items: [
            { key: 'nav.students', href: '/students', icon: Users, roles: [...ADMINS, 'registrar', 'teacher'] },
            { key: 'nav.guardians', href: '/guardians', icon: Users2, roles: [...ADMINS, 'registrar'] },
            { key: 'nav.teachers', href: '/teachers', icon: UserCheck, roles: [...ADMINS, 'principal'] },
        ],
    },
    {
        labelKey: 'shell.group.academics',
        items: [
            { key: 'nav.academicYears', href: '/academic-years', icon: BookOpen, roles: [...ADMINS, 'registrar', 'principal'] },
            { key: 'nav.gradeLevels', href: '/grade-levels', icon: GraduationCap, roles: [...ADMINS, 'registrar'] },
            { key: 'nav.sections', href: '/sections', icon: LayoutGrid, roles: [...ADMINS, 'registrar', 'principal'] },
            { key: 'nav.rooms', href: '/rooms', icon: DoorOpen, roles: [...ADMINS, 'registrar'] },
            { key: 'nav.timetable', href: '/timetable', icon: CalendarDays, roles: [...ADMINS, 'teacher'] },
        ],
    },
    {
        labelKey: 'shell.group.teaching',
        items: [
            { key: 'nav.attendance', href: '/attendance', icon: CalendarCheck, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.attendanceSessions', href: '/attendance-sessions', icon: Calendar, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.exams', href: '/exams', icon: ClipboardList, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.examResults', href: '/exam-results', icon: FileText, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.assignments', href: '/assignments', icon: PenTool, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.quizzes', href: '/quizzes', icon: ClipboardCheck, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.submissions', href: '/submissions', icon: Upload, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.reportCards', href: '/report-cards', icon: Award, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.materials', href: '/materials', icon: Upload, roles: [...ADMINS, 'teacher'] },
        ],
    },
    {
        labelKey: 'shell.group.communication',
        items: [
            { key: 'nav.announcements', href: '/announcements', icon: Bell, roles: [...ADMINS, 'teacher'] },
            { key: 'nav.news', href: '/content/news', icon: Bell, roles: ADMINS },
            { key: 'nav.events', href: '/content/events', icon: Calendar, roles: ADMINS },
            { key: 'nav.messages', href: '/messages', icon: MessageSquare, roles: [...ADMINS, 'teacher', 'registrar'] },
            { key: 'nav.documents', href: '/documents', icon: FileText, roles: [...ADMINS, 'teacher'] },
        ],
    },
    {
        labelKey: 'shell.group.operations',
        items: [
            { key: 'nav.admissions', href: '/admissions/applications', icon: UserPlus, roles: [...ADMINS, 'registrar'] },
            { key: 'nav.finance', href: '/finance/invoices', icon: DollarSign, roles: [...ADMINS, 'accountant'] },
        ],
    },
    {
        labelKey: 'shell.group.system',
        items: [{ key: 'nav.settings', href: '/settings/school', icon: Settings, roles: ADMINS }],
    },
];

function isActive(href: string, pathname: string): boolean {
    if (href === '/dashboard') return pathname === '/dashboard' || pathname === '/';
    return pathname === href || pathname.startsWith(`${href}/`);
}

function BrandLockup({ compact = false }: { compact?: boolean }) {
    const { locale } = useLocale();
    const { appearance } = usePage<App.PageProps>().props;

    return (
        <Link href="/dashboard" className="group flex items-center gap-2.5" aria-label={t(locale, 'brand.aether')}>
            <span
                className="relative flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_2px_6px_rgba(6,40,30,0.35)]"
                style={{ backgroundColor: appearance?.primary_color ?? 'var(--color-primary)' }}
            >
                {appearance?.logo_path ? (
                    <img src={`/storage/${appearance.logo_path}`} alt="" className="size-full object-cover" />
                ) : (
                    <span className="font-display text-[1.05rem] font-semibold leading-none text-white">A</span>
                )}
            </span>
            {!compact && (
                <span className="flex min-w-0 flex-col leading-none">
                    <span className="truncate text-sm font-medium text-foreground">
                        {t(locale, 'brand.aether')}
                    </span>
                    <span className="mt-0.5 text-xs font-medium uppercase tracking-[0.14em] text-muted-foreground">
                        {locale === 'ar' ? 'نظام إدارة المدارس' : 'School OS'}
                    </span>
                </span>
            )}
        </Link>
    );
}

function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
    const { locale } = useLocale();
    const page = usePage<App.PageProps>();
    const { auth } = page.props;
    const userRoles = auth.user?.roles || [];
    const pathname = page.url.split('?')[0];

    const visibleGroups = NAV_GROUPS.map((group) => ({
        ...group,
        items: group.items.filter((item) => item.roles.some((role) => userRoles.includes(role))),
    })).filter((group) => group.items.length > 0);

    return (
        <div className="flex h-full flex-col">
            <div className="flex h-16 shrink-0 items-center border-b border-border/80 px-4">
                <BrandLockup />
            </div>

            <nav
                className="sidebar-scroll flex-1 space-y-5 overflow-y-auto px-3 py-4"
                aria-label={locale === 'ar' ? 'التنقل الجانبي' : 'Sidebar navigation'}
            >
                {visibleGroups.map((group, gi) => (
                    <div key={gi} className="space-y-0.5">
                        {group.labelKey && (
                            <p className="px-3 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground/60">{t(locale, group.labelKey)}</p>
                        )}
                        <ul className="space-y-0.5">
                            {group.items.map((item) => {
                                const Icon = item.icon;
                                const active = isActive(item.href, pathname);
                                return (
                                    <li key={item.key}>
                                        <Link
                                            href={item.href}
                                            onClick={onNavigate}
                                            aria-current={active ? 'page' : undefined}
                                            className={cn(
                                                'group flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',
                                                active
                                                    ? 'bg-primary/10 text-primary shadow-[inset_0_0_0_1px_rgba(10,92,66,0.04)]'
                                                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                                            )}
                                        >
                                            <Icon
                                                className={cn(
                                                    'size-[1.05rem] shrink-0 transition-colors',
                                                    active ? 'text-primary' : 'text-muted-foreground/70 group-hover:text-foreground'
                                                )}
                                                aria-hidden="true"
                                            />
                                            <span className="truncate">{t(locale, item.key)}</span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                ))}
            </nav>

            <div className="shrink-0 border-t border-border/70 p-2">
                <div className="flex items-center gap-2 rounded-xl bg-muted/50 p-2">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pine-500 to-pine-700 text-sm font-semibold text-white shadow-sm">
                        {auth.user?.name?.charAt(0).toUpperCase() || 'U'}
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-foreground">
                            {auth.user?.name}
                        </p>
                        <p className="truncate text-xs text-muted-foreground">
                            {userRoles[0]?.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') ||
                                auth.user?.email}
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        onClick={() => router.post('/logout')}
                        aria-label={t(locale, 'shell.logout')}
                        title={t(locale, 'shell.logout')}
                    >
                        <LogOut className="size-4" aria-hidden="true" />
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default function AppShell({ children, title, breadcrumbs }: AppShellProps) {
    const { locale } = useLocale();
    const page = usePage<App.PageProps>();
    const { auth } = page.props;
    const url = page.url;
    const userRoles = auth.user?.roles || [];
    const canNotify = userRoles.some((r) => ['school_admin', 'super_admin', 'teacher'].includes(r));
    const canSettings = userRoles.some((r) => ['school_admin', 'super_admin'].includes(r));
    const [drawerOpen, setDrawerOpen] = useState(false);

    useEffect(() => {
        setDrawerOpen(false);
    }, [url]);

    useEffect(() => {
        document.body.style.overflow = drawerOpen ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [drawerOpen]);

    return (
        <div className="min-h-screen bg-background">
            <div className="flex min-h-screen">
                {/* Desktop sidebar */}
                <aside className="sticky top-0 hidden h-screen w-[16rem] shrink-0 border-e border-border/80 bg-card/60 backdrop-blur md:block">
                    <SidebarContent />
                </aside>

                {/* Mobile drawer */}
                {drawerOpen && (
                    <div className="fixed inset-0 z-[60] md:hidden" role="dialog" aria-modal="true">
                        <button
                            type="button"
                            aria-label="Close menu"
                            className="absolute inset-0 bg-foreground/30 backdrop-blur-[2px]"
                            onClick={() => setDrawerOpen(false)}
                        />
                        <div className="absolute inset-y-0 start-0 w-[80vw] max-w-[24rem] bg-card shadow-[var(--shadow-panel)]">
                            <button
                                type="button"
                                aria-label="Close menu"
                                onClick={() => setDrawerOpen(false)}
                                className="absolute end-3 top-4 flex size-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                            >
                                <X className="size-4" aria-hidden="true" />
                            </button>
                            <SidebarContent onNavigate={() => setDrawerOpen(false)} />
                        </div>
                    </div>
                )}

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-border/80 bg-background/90 px-4 backdrop-blur-md md:px-8">
                        <div className="flex min-w-0 items-center gap-2">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                className="md:hidden"
                                onClick={() => setDrawerOpen(true)}
                                aria-label={t(locale, 'shell.menu')}
                            >
                                <Menu className="size-5" aria-hidden="true" />
                            </Button>
                            {title && (
                                <h2 className="truncate text-sm font-medium md:hidden">
                                    {title}
                                </h2>
                            )}
                            <span className="hidden items-center gap-2 rounded-full border border-border/70 bg-card px-3 py-1 text-xs text-muted-foreground md:inline-flex">
                                <Sparkles className="size-3 text-gold-400" aria-hidden="true" />
                                <span>{locale === 'ar' ? 'العام الدراسي ٢٠٢٦–٢٠٢٧' : 'Academic Year 2026–2027'}</span>
                            </span>
                        </div>

                        <div className="flex items-center gap-1.5">
                            {canNotify && (
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    asChild
                                    aria-label={t(locale, 'shell.notifications')}
                                    title={t(locale, 'shell.notifications')}
                                    className="relative"
                                >
                                    <Link href="/announcements">
                                        <Bell className="size-[1.1rem]" aria-hidden="true" />
                                        <span className="pointer-events-none absolute -0.5 -0.5 size-2 rounded-full bg-gold-400 ring-2 ring-background" aria-hidden="true" />
                                    </Link>
                                </Button>
                            )}
                            <LanguageSwitcher variant="ghost" />
                            {canSettings && (
                                <>
                                    <div className="mx-1 hidden h-5 w-px bg-border sm:block" aria-hidden="true" />
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        asChild
                                        aria-label={t(locale, 'shell.settings')}
                                        title={t(locale, 'shell.settings')}
                                    >
                                        <Link href="/settings/school">
                                            <Settings className="size-[1.1rem]" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </header>

                    <main className="flex-1 px-3 py-4 md:px-4 md:py-6">
                        <div className="mx-auto w-full max-w-[96rem]">
                            {breadcrumbs && breadcrumbs.length > 0 && (
                                <nav
                                    className="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground"
                                    aria-label="Breadcrumb"
                                >
                                    {breadcrumbs.map((crumb, index) => (
                                        <span key={index} className="flex items-center gap-1.5">
                                            {index > 0 && (
                                                <span aria-hidden="true" className="text-muted-foreground/50">
                                                    /
                                                </span>
                                            )}
                                            {crumb.href ? (
                                                <a href={crumb.href} className="transition-colors hover:text-foreground">
                                                    {crumb.label}
                                                </a>
                                            ) : (
                                                <span className="font-medium text-foreground/80">{crumb.label}</span>
                                            )}
                                        </span>
                                    ))}
                                </nav>
                            )}
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
