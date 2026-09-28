import { useForm } from '@inertiajs/react';
import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TranslatePair } from '@/components/ui/translate-pair';
import { ColorField } from '@/components/ui/color-field';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2 } from 'lucide-react';

type SchoolRecord = {
    id: number;
    organization_id: number;
    name_en: string | null;
    name_ar: string | null;
    slug: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    country: string;
    timezone: string;
    locale: string;
    currency: string;
    primary_color: string | null;
    secondary_color: string | null;
    accent_color: string | null;
};

type Option = { id: number; name: string };

type SchoolFormProps = {
    school: SchoolRecord | null;
    organizations: Option[];
};

const TIMEZONES = ['Asia/Riyadh', 'Asia/Dubai', 'Asia/Amman', 'Africa/Cairo', 'Europe/London', 'UTC'];
const LOCALES = [
    { value: 'en', label: 'English' },
    { value: 'ar', label: 'العربية' },
];
const CURRENCIES = ['SAR', 'AED', 'USD', 'EGP', 'JOD', 'GBP', 'EUR'];

export default function SchoolForm({ school, organizations }: SchoolFormProps) {
    const isEdit = school !== null;

    const form = useForm({
        organization_id: school?.organization_id ?? organizations[0]?.id ?? '',
        name_en: school?.name_en ?? '',
        name_ar: school?.name_ar ?? '',
        slug: school?.slug ?? '',
        email: school?.email ?? '',
        phone: school?.phone ?? '',
        address: school?.address ?? '',
        city: school?.city ?? '',
        country: school?.country ?? 'SA',
        timezone: school?.timezone ?? 'Asia/Riyadh',
        locale: school?.locale ?? 'en',
        currency: school?.currency ?? 'SAR',
        primary_color: school?.primary_color ?? '#065f46',
        secondary_color: school?.secondary_color ?? '#d97706',
        accent_color: school?.accent_color ?? '#065f46',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (isEdit && school) {
            form.put(`/schools/${school.id}`);
        } else {
            form.post('/schools');
        }
    };

    return (
        <AppShell
            title={isEdit ? 'Edit school' : 'New school'}
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Schools & branches', href: '/schools' },
                { label: isEdit ? 'Edit' : 'New' },
            ]}
        >
            <PageHeader
                title={isEdit ? `Edit ${school?.name_en ?? 'school'}` : 'New school or branch'}
                description="Branches are schools inside the same organization — give each one its own name, locale and branding."
            />

            <form onSubmit={submit} className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-4 w-4 text-muted-foreground" />
                            Identity
                        </CardTitle>
                        <CardDescription>How this school presents itself across the product.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="organization_id">Organization</Label>
                            <select
                                id="organization_id"
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={form.data.organization_id}
                                onChange={(e) => form.setData('organization_id', Number(e.target.value))}
                                required
                            >
                                <option value="">Select an organization</option>
                                {organizations.map((organization) => (
                                    <option key={organization.id} value={organization.id}>
                                        {organization.name}
                                    </option>
                                ))}
                            </select>
                            {form.errors.organization_id && (
                                <p className="text-sm text-destructive">{form.errors.organization_id}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name_en">Name (English)</Label>
                            <Input
                                id="name_en"
                                value={form.data.name_en}
                                onChange={(e) => form.setData('name_en', e.target.value)}
                                required
                            />
                            {form.errors.name_en && <p className="text-sm text-destructive">{form.errors.name_en}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name_ar">Name (Arabic)</Label>
                            <Input
                                id="name_ar"
                                dir="rtl"
                                value={form.data.name_ar}
                                onChange={(e) => form.setData('name_ar', e.target.value)}
                            />
                            {form.errors.name_ar && <p className="text-sm text-destructive">{form.errors.name_ar}</p>}
                            <TranslatePair enId="name_en" arId="name_ar" />
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={form.data.slug}
                                onChange={(e) => form.setData('slug', e.target.value)}
                                placeholder="Generated from the English name when left blank"
                                spellCheck={false}
                            />
                            {form.errors.slug && <p className="text-sm text-destructive">{form.errors.slug}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                            />
                            {form.errors.email && <p className="text-sm text-destructive">{form.errors.email}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="phone">Phone</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                            />
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="address">Address</Label>
                            <Input
                                id="address"
                                value={form.data.address}
                                onChange={(e) => form.setData('address', e.target.value)}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="city">City</Label>
                            <Input
                                id="city"
                                value={form.data.city}
                                onChange={(e) => form.setData('city', e.target.value)}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="country">Country code</Label>
                            <Input
                                id="country"
                                maxLength={2}
                                value={form.data.country}
                                onChange={(e) => form.setData('country', e.target.value.toUpperCase())}
                            />
                            {form.errors.country && <p className="text-sm text-destructive">{form.errors.country}</p>}
                        </div>
                    </CardContent>
                </Card>

                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Locale & branding</CardTitle>
                        <CardDescription>Defaults for dates, currency and the accent colour.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="locale">Default language</Label>
                            <select
                                id="locale"
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={form.data.locale}
                                onChange={(e) => form.setData('locale', e.target.value)}
                            >
                                {LOCALES.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="timezone">Timezone</Label>
                            <select
                                id="timezone"
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={form.data.timezone}
                                onChange={(e) => form.setData('timezone', e.target.value)}
                            >
                                {TIMEZONES.map((zone) => (
                                    <option key={zone} value={zone}>
                                        {zone}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="currency">Currency</Label>
                            <select
                                id="currency"
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={form.data.currency}
                                onChange={(e) => form.setData('currency', e.target.value)}
                            >
                                {CURRENCIES.map((currency) => (
                                    <option key={currency} value={currency}>
                                        {currency}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-2">
                            <ColorField
                                label="Primary colour"
                                value={form.data.primary_color}
                                onChange={(value) => form.setData('primary_color', value)}
                                error={form.errors.primary_color}
                            />
                        </div>

                        <div className="flex flex-wrap gap-3 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving...' : isEdit ? 'Save changes' : 'Create school'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <a href="/schools">Cancel</a>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AppShell>
    );
}
