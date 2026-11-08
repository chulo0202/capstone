<?php

namespace Tests\Feature\Admin;

use App\Exports\ReportsExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_reports_to_excel(): void
    {
        Excel::fake();

        $this->travelTo(now()->startOfSecond());

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.excel'));

        $response->assertOk();

        Excel::assertDownloaded(
            'fams-report-' . now()->format('YmdHis') . '.xlsx',
            function (ReportsExport $export) {
                return true;
            }
        );
    }

    public function test_farmer_cannot_export_reports_to_excel(): void
    {
        Excel::fake();

        $farmer = User::factory()->create([
            'role' => 'farmer',
        ]);

        $this->actingAs($farmer)
            ->get(route('admin.reports.excel'))
            ->assertForbidden();

    }

    public function test_guest_cannot_export_reports_to_excel(): void
    {
        Excel::fake();

        $this->get(route('admin.reports.excel'))
            ->assertRedirect(route('login'));

    }
}
