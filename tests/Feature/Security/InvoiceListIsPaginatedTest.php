<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * A tenant list that answers `->get()` returns the school's entire table to the
 * browser and holds it all in memory to build the response. A school with ten
 * years of invoices has tens of thousands of rows, so the screen is a slow page
 * for every accountant and an easy lever for anyone who can reach it.
 *
 * The bound matters as much as the count: an unpaginated list also cannot be
 * reasoned about, because nothing says which page a row belongs to.
 *
 * Filters have to survive the change. `status` and `search` are what make the
 * screen usable, and a paginated query that drops them on page two is a screen
 * that quietly lies — the accountant pages through looking for one invoice and
 * is shown a different list each time.
 */
class InvoiceListIsPaginatedTest extends TestCase
{
    use RefreshDatabase;

    private const PER_PAGE = 15;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        foreach (['manage-invoices', 'manage-settings'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_the_list_returns_one_page_of_rows_not_the_whole_table(): void
    {
        $this->withInvoices(60);

        $rows = $this->rowsOn(1);

        $this->assertLessThanOrEqual(
            self::PER_PAGE,
            $rows->count(),
            "The invoice list returned {$rows->count()} rows for a 60-row table. It is not paginated.",
        );
    }

    public function test_the_response_reports_how_many_rows_exist_in_total(): void
    {
        $this->withInvoices(60);

        $this->assertSame(
            60,
            $this->prop($this->page(1), 'total'),
            'The payload does not report a total, so a pager cannot know there is anything to page to.',
        );
    }

    public function test_a_second_page_holds_different_rows_from_the_first(): void
    {
        $this->withInvoices(60);

        $first = $this->rowsOn(1)->pluck('id')->all();
        $second = $this->rowsOn(2)->pluck('id')->all();

        $this->assertNotEmpty($second, 'Page two was empty, so the pager would be decoration.');
        $this->assertSame([], array_intersect($first, $second), 'Page two repeated rows from page one.');
    }

    public function test_the_total_counts_only_this_schools_invoices(): void
    {
        $this->withInvoices(60);

        $this->createInvoice(School::factory()->create());

        $this->assertSame(
            60,
            $this->prop($this->page(1), 'total'),
            'Another school\'s invoice inflated the total, so the pager describes a table the caller is '
            .'not allowed to see.',
        );
    }

    public function test_the_status_filter_still_applies_on_a_later_page(): void
    {
        $this->withInvoices(30, 'paid');
        $this->withInvoices(30, 'draft');

        $response = $this->page(2, ['status' => 'paid']);

        $this->assertSame(
            ['paid'],
            $this->rowsOn(2, ['status' => 'paid'])->pluck('status')->unique()->values()->all(),
            'Page two of a filtered list contains rows the filter excluded, so paging shows the user '
            .'something other than what they asked for.',
        );

        $this->assertSame(30, $this->prop($response, 'total'));
    }

    public function test_the_search_filter_still_applies_to_the_total(): void
    {
        $this->withInvoices(40, 'paid', 'Zahra');
        $this->withInvoices(40, 'paid', 'Someone Else');

        $this->assertSame(
            40,
            $this->prop($this->page(2, ['search' => 'Zahra']), 'total'),
            'The search filter was not applied to the paginated total, so the pager counts rows the user '
            .'never asked for.',
        );
    }

    public function test_a_page_beyond_the_end_is_empty_rather_than_an_error(): void
    {
        $this->withInvoices(5);

        $this->page(99)->assertSuccessful();

        $this->assertCount(0, $this->rowsOn(99));
    }

    public function test_another_schools_invoices_never_appear_on_any_page(): void
    {
        $this->withInvoices(20);

        $foreign = $this->createInvoice(School::factory()->create());

        foreach ([1, 2] as $pageNumber) {
            $this->assertNotContains(
                $foreign->id,
                $this->rowsOn($pageNumber)->pluck('id')->all(),
                "Page {$pageNumber} leaked an invoice from another school.",
            );
        }
    }

    // ------------------------------------------------------------------

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rowsOn(int $number, array $query = []): Collection
    {
        $rows = $this->prop($this->page($number, $query), 'data');

        $this->assertIsArray($rows, 'invoices.data is not an array, so the payload is not a paginator.');

        return collect($rows);
    }

    /**
     * A prop by dot path, read from the rendered page payload.
     *
     * `assertInertiaHas` can assert but not return, and these cases are about
     * the *shape* of the payload as much as a value in it. The payload is the
     * `<script type="application/json">` block Inertia ships the page in; the
     * `data-page` attribute on the root div is only the element id, so matching
     * on that finds nothing.
     */
    private function prop(TestResponse $response, string $key): mixed
    {
        $html = (string) $response->getContent();

        if (preg_match('#<script[^>]*type="application/json"[^>]*>(.*?)</script>#s', $html, $matches) !== 1) {
            $this->fail('The response carries no Inertia page payload, so no prop can be read from it.');
        }

        $page = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

        $this->assertIsArray($page, 'The page payload did not decode.');

        $value = data_get($page, 'props.invoices.'.$key);

        $this->assertNotNull(
            $value,
            "The response carries nothing at [props.invoices.{$key}]. The rows live at invoices.data "
            .'and the counts beside them; a bare array is the defect under test.',
        );

        return $value;
    }

    private function withInvoices(int $count, string $status = 'draft', ?string $firstName = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createInvoice($this->school, $status, $firstName);
        }
    }

    private function createInvoice(School $school, string $status = 'draft', ?string $firstName = null): Invoice
    {
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'first_name' => $firstName ?? 'Pupil',
        ]);

        return Invoice::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'invoice_number' => 'INV-'.uniqid(),
            'status' => $status,
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'total_amount' => 100,
            'balance_due' => 100,
            'currency' => 'SAR',
        ]);
    }

    private function page(int $number, array $query = []): TestResponse
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $user->givePermissionTo(['manage-invoices', 'manage-settings']);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $this->school->id);

        $response = $this->get('/finance/invoices?'.http_build_query(['page' => $number] + $query));

        $response->assertSuccessful();

        return $response;
    }
}
