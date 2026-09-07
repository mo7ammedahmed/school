import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Select } from '@/components/ui/select';
import { useLocale } from '@/lib/i18n/locale-context';
import PublicLayout from '@/layouts/public-layout';
import { Building2 } from 'lucide-react';

export default function SelectSchool({ schools }: { schools: App.School[] }) {
    const { locale } = useLocale();
    const ar = locale === 'ar';
    const { errors } = usePage<App.PageProps>().props;
    const { data, setData, post, processing } = useForm({
        school_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/select-school');
    };

    return (
        <PublicLayout>
            <div className="flex min-h-[calc(100vh-10rem)] items-center justify-center px-4 py-14">
                <div className="w-full max-w-md rounded-2xl border border-border/80 bg-card p-8 shadow-[var(--shadow-panel)] sm:p-9">
                    <div className="flex size-12 items-center justify-center rounded-2xl bg-pine-800/10 text-pine-800">
                        <Building2 className="size-6" aria-hidden="true" />
                    </div>
                    <h1 className="mt-5 font-display text-[1.7rem] font-semibold leading-tight tracking-[-0.015em] text-ink">
                        {ar ? 'اختر مدرستك' : 'Select your school'}
                    </h1>
                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                        {ar
                            ? 'اختر المدرسة التي تريد الوصول إليها'
                            : 'Choose the school you want to access'}
                    </p>

                    <form onSubmit={submit} className="mt-7 space-y-5">
                        <Select
                            id="school_id"
                            label={ar ? 'المدرسة' : 'School'}
                            value={data.school_id}
                            onChange={(e) => setData('school_id', e.target.value)}
                            error={errors.school_id}
                            required
                        >
                            <option value="">
                                {ar ? 'اختر مدرسة' : 'Select a school'}
                            </option>
                            {schools.map((school) => (
                                <option key={school.id} value={school.id}>
                                    {school.name}
                                </option>
                            ))}
                        </Select>

                        <Button
                            type="submit"
                            size="lg"
                            className="w-full"
                            disabled={processing || !data.school_id}
                        >
                            {processing ? (ar ? 'جاري التحميل...' : 'Loading...') : ar ? 'متابعة' : 'Continue'}
                        </Button>
                    </form>
                </div>
            </div>
        </PublicLayout>
    );
}
