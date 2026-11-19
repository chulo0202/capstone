<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BenefitConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_configure_benefit_with_active_configuration(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'Equipment Support Duplicate Test',
            'description' => 'Original description',
            'is_active' => true,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Tractor Duplicate Test',
            'description' => 'Test benefit',
        ]);

        DB::table('benefit_configurations')->insert([
            'program_benefit_id' => $benefit->id,
            'description' => 'Existing active configuration',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'eligibility_snapshot' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'program_id' => $program->id,
                'program_benefit_id' => $benefit->id,
                'description' => 'Duplicate configuration',
                'start_at' => now()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            ]);

        $response->assertSessionHasErrors('program_benefit_id');

        $this->assertDatabaseCount('benefit_configurations', 1);

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'description' => 'Original description',
        ]);
    }

    public function test_admin_cannot_configure_benefit_with_upcoming_configuration(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'Upcoming Equipment Support Test',
            'description' => 'Original description',
            'is_active' => true,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Upcoming Tractor Test',
            'description' => 'Test benefit',
        ]);

        DB::table('benefit_configurations')->insert([
            'program_benefit_id' => $benefit->id,
            'description' => 'Existing upcoming configuration',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(10),
            'eligibility_snapshot' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'program_id' => $program->id,
                'program_benefit_id' => $benefit->id,
                'description' => 'Duplicate upcoming configuration',
                'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            ]);

        $response->assertSessionHasErrors('program_benefit_id');

        $this->assertDatabaseCount('benefit_configurations', 1);

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'description' => 'Original description',
        ]);
    }

    public function test_admin_can_reconfigure_expired_benefit(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'Expired Equipment Support Test',
            'description' => 'Original description',
            'is_active' => true,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Expired Tractor Test',
            'description' => 'Test benefit',
        ]);

        DB::table('benefit_configurations')->insert([
            'program_benefit_id' => $benefit->id,
            'description' => 'Previous configuration',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDay(),
            'eligibility_snapshot' => json_encode([]),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $newStart = now()->addDay()->format('Y-m-d H:i:s');
        $newEnd = now()->addDays(10)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'program_id' => $program->id,
                'program_benefit_id' => $benefit->id,
                'description' => 'New configuration after expiration',
                'start_at' => $newStart,
                'end_at' => $newEnd,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.programs.index'));

        $this->assertDatabaseCount('benefit_configurations', 2);

        $this->assertDatabaseHas('benefit_configurations', [
            'program_benefit_id' => $benefit->id,
            'description' => 'Previous configuration',
        ]);

        $this->assertDatabaseHas('benefit_configurations', [
            'program_benefit_id' => $benefit->id,
            'description' => 'New configuration after expiration',
            'start_at' => $newStart,
            'end_at' => $newEnd,
        ]);
    }

    public function test_admin_can_configure_never_configured_benefit(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'New Equipment Support Test',
            'description' => 'Original description',
            'is_active' => false,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'New Tractor Test',
            'description' => 'Test benefit',
        ]);

        $this->assertDatabaseCount('benefit_configurations', 0);

        $newStart = now()->addDay()->format('Y-m-d H:i:s');
        $newEnd = now()->addDays(10)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'program_id' => $program->id,
                'program_benefit_id' => $benefit->id,
                'description' => 'First benefit configuration',
                'start_at' => $newStart,
                'end_at' => $newEnd,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.programs.index'));

        $this->assertTrue($program->refresh()->is_active);
        $this->assertDatabaseCount('benefit_configurations', 1);

        $this->assertDatabaseHas('benefit_configurations', [
            'program_benefit_id' => $benefit->id,
            'description' => 'First benefit configuration',
            'start_at' => $newStart,
            'end_at' => $newEnd,
        ]);
    }

    public function test_inactive_program_shows_all_benefits_and_only_allows_available_choices(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'Dropdown Filtering Test Program',
            'description' => 'Test dropdown availability',
            'is_active' => false,
        ]);

        $active = $program->benefits()->create([
            'name' => 'Active Test Benefit',
            'description' => 'Active',
        ]);

        $upcoming = $program->benefits()->create([
            'name' => 'Upcoming Test Benefit',
            'description' => 'Upcoming',
        ]);

        $expired = $program->benefits()->create([
            'name' => 'Expired Test Benefit',
            'description' => 'Expired',
        ]);

        $neverConfigured = $program->benefits()->create([
            'name' => 'Never Configured Test Benefit',
            'description' => 'Never configured',
        ]);

        foreach ([
            [$active, now()->subDay(), now()->addDay()],
            [$upcoming, now()->addDay(), now()->addDays(10)],
            [$expired, now()->subDays(10), now()->subDay()],
        ] as [$benefit, $start, $end]) {
            DB::table('benefit_configurations')->insert([
                'program_benefit_id' => $benefit->id,
                'description' => 'Test configuration',
                'start_at' => $start,
                'end_at' => $end,
                'eligibility_snapshot' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.programs.create'));

        $response->assertOk();

        $entries = $response->viewData('programBenefits')->get($program->id)->keyBy('id');

        foreach ([
            [$active, 'Inactive', false],
            [$upcoming, 'Upcoming', false],
            [$expired, 'Expired', true],
            [$neverConfigured, 'Available', true],
        ] as [$benefit, $status, $selectable]) {
            $this->assertSame($status, $entries[$benefit->id]['status']);
            $this->assertSame($selectable, $entries[$benefit->id]['selectable']);
        }

        $this->assertDatabaseCount('benefit_configurations', 3);
    }
}
