import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/lib/i18n/locale-context';
import { t } from '@/lib/i18n/copy';

/**
 * The paper copy of the timetable.
 *
 * This is rendered by the browser rather than by dompdf so the school's name,
 * logo and — above all — correctly joined Arabic subject names appear on the
 * sheet. "Print" and "Save as PDF" are the same browser dialog.
 */

type Lesson = {
    subject: string;
    section?: string | null;
    teacher?: string | null;
    room?: string | null;
};

type PrintProps = {
    days: string[];
    slots: { slot: string; days: Record<string, Lesson[]> }[];
    entry_count: number;
    generated_at: string;
    school: { name?: string | null; logo_url?: string | null };
};

export default function TimetablePrint({ days, slots, entry_count, generated_at, school }: PrintProps) {
    const { locale } = useLocale();
    const page = usePage();
    const isArabic = locale === 'ar';
    // Carry the current filters back to the interactive timetable.
    const query = page.url.includes('?') ? page.url.slice(page.url.indexOf('?')) : '';

    const dayLabels: Record<string, string> = {
        sunday: isArabic ? 'الأحد' : 'Sunday',
        monday: isArabic ? 'الاثنين' : 'Monday',
        tuesday: isArabic ? 'الثلاثاء' : 'Tuesday',
        wednesday: isArabic ? 'الأربعاء' : 'Wednesday',
        thursday: isArabic ? 'الخميس' : 'Thursday',
    };

    const heading = isArabic ? 'الجدول الدراسي' : 'Timetable';
    const printedOn = isArabic ? 'تاريخ الطباعة' : 'Printed';
    const noSlots = isArabic ? 'لا توجد حصص في الجدول.' : 'No lessons have been scheduled yet.';

    return (
        <div className="min-h-screen bg-white text-black">
            {/* Toolbar — never printed. */}
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-black/10 px-4 py-3 print:hidden">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={`/timetable${query}`}>
                        <ArrowLeft className="me-2 size-4 rtl:-scale-x-100" aria-hidden="true" />
                        {isArabic ? 'عودة إلى الجدول' : 'Back to timetable'}
                    </Link>
                </Button>
                <Button size="sm" onClick={() => window.print()}>
                    <Printer className="me-2 size-4" aria-hidden="true" />
                    {isArabic ? 'طباعة / حفظ PDF' : 'Print / Save as PDF'}
                </Button>
            </div>

            <div className="mx-auto max-w-[1100px] px-6 py-8 print:max-w-none print:px-0 print:py-0">
                {/* Masthead: the school's own name and mark, not the app name. */}
                <header className="mb-5 flex items-center gap-4 border-b border-black/15 pb-4">
                    {school.logo_url ? (
                        <img src={school.logo_url} alt="" className="size-14 shrink-0 object-contain" />
                    ) : (
                        /* A logo is optional: until the school uploads one in
                           Settings → Appearance, the sheet is branded with the
                           school's initial instead of the app's name. */
                        <span className="flex size-14 shrink-0 items-center justify-center rounded-lg bg-[var(--color-primary)] text-2xl font-semibold text-[var(--color-primary-foreground)]">
                            {(school.name ?? t(locale, 'brand.alnoor')).trim().charAt(0)}
                        </span>
                    )}

                    <div className="min-w-0">
                        <h1 className="text-xl font-semibold leading-tight">
                            {school.name ?? t(locale, 'brand.alnoor')}
                        </h1>
                        <p className="mt-1 text-xs text-black/60">
                            {heading} · {printedOn} {generated_at}
                        </p>
                    </div>

                    <p className="ms-auto shrink-0 text-xs text-black/60">
                        {entry_count} {isArabic ? 'حصة' : 'lessons'}
                    </p>
                </header>

                {slots.length === 0 ? (
                    <p className="py-16 text-center text-sm text-black/60">{noSlots}</p>
                ) : (
                    <table className="w-full border-collapse text-start text-xs">
                        <thead>
                            <tr>
                                <th className="border border-black/15 bg-black/[0.04] px-2 py-2 text-start font-semibold">
                                    {isArabic ? 'الوقت' : 'Time'}
                                </th>
                                {days.map((day) => (
                                    <th
                                        key={day}
                                        className="border border-black/15 bg-black/[0.04] px-2 py-2 text-start font-semibold"
                                    >
                                        {dayLabels[day] ?? day}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {slots.map((slot) => (
                                <tr key={slot.slot} className="break-inside-avoid">
                                    <td className="border border-black/15 px-2 py-2 align-top font-medium tabular-nums">
                                        {slot.slot}
                                    </td>
                                    {days.map((day) => (
                                        <td key={day} className="border border-black/15 px-2 py-2 align-top">
                                            {(slot.days[day] ?? []).map((lesson, index) => (
                                                <div key={index} className="mb-1 last:mb-0">
                                                    <div className="font-medium">{lesson.subject}</div>
                                                    <div className="text-black/60">
                                                        {[lesson.section, lesson.room].filter(Boolean).join(' · ')}
                                                    </div>
                                                    {lesson.teacher ? (
                                                        <div className="text-black/50">{lesson.teacher}</div>
                                                    ) : null}
                                                </div>
                                            ))}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            {/* Landscape gives the seven columns room to breathe. */}
            <style>{'@media print { @page { size: A4 landscape; margin: 10mm; } }'}</style>
        </div>
    );
}
