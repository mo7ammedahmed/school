import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Pagination } from '@/components/ui/pagination';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@/lib/table';

interface Student {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    student_id_number: string;
    status: string;
}

/**
 * `StudentController::index` paginates, so the rows arrive as a paginator rather
 * than as the school's whole roll. `DataTable` renders a paginator's `data` and
 * nothing else, which is how fifteen of two hundred students became the list.
 */
interface StudentPage {
    data: Student[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export default function StudentsIndex({ students }: { students: StudentPage }) {
    /**
     * Paging is a server round trip, not a client slice.
     *
     * The rows the browser holds are one page of the roll. Slicing them would
     * page through the same fifteen students five times over, so the request
     * asks the server for the page it wants.
     */
    const goToPage = (page: number) => {
        router.get('/students', { page }, { preserveScroll: true, preserveState: true });
    };

    const columns: ColumnDef<any>[] = [
        {
            accessorKey: 'student_id_number',
            header: 'Enrollment #',
        },
        {
            accessorKey: 'first_name',
            header: 'First Name',
        },
        {
            accessorKey: 'last_name',
            header: 'Last Name',
        },
        {
            accessorKey: 'email',
            header: 'Email',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => {
                const status = row.original.status;
                const variant = status === 'active' ? 'default' : status === 'graduated' ? 'secondary' : 'destructive';
                return <Badge variant={variant}>{status}</Badge>;
            },
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/students/${row.original.id}`}>View</Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/students/${row.original.id}/edit`}>Edit</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppShell
            title="Students"
            breadcrumbs={[
                { label: 'Dashboard', href: '/dashboard' },
                { label: 'Students' },
            ]}
        >
            <PageHeader
                title="Students"
                description="Manage student records"
                actions={
                    <Button asChild>
                        <Link href="/students/create"><Plus className="mr-2 h-4 w-4" />New Student</Link>
                    </Button>
                }
            />

            <DataTable columns={columns} data={students.data} />

            <Pagination
                className="mt-4"
                pageCount={students.last_page}
                currentPage={students.current_page}
                onPageChange={goToPage}
            />

            {students.total > 0 && (
                <p className="mt-2 text-sm text-muted-foreground">
                    {`Showing ${students.from ?? 0}–${students.to ?? 0} of ${students.total}`}
                </p>
            )}
        </AppShell>
    );
}
