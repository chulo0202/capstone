<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Distribution;
use App\Models\EligibilityRule;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\ProgramBenefit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class FarmerDeactivationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_and_reactivate_farmer_with_audit_history(): void
    {
        $admin = $this->createAdmin();
        [$farmerUser, $farmer] = $this->createFarmer();

        $this->actingAs($admin)
            ->post(route('admin.farmers.deactivate', $farmer))
            ->assertRedirect(route('admin.farmers.index'));

        $this->assertFalse($farmer->fresh()->is_active);
        $this->assertDatabaseHas('farmers', ['id' => $farmer->id]);
        $this->assertDatabaseHas('users', ['id' => $farmerUser->id]);
        $this->assertAuditState($admin, $farmer, 'deactivated', true, false);

        $this->post(route('admin.farmers.deactivate', $farmer))
            ->assertSessionHas('info', 'Farmer is already inactive.');
        $this->assertSame(1, $this->auditCount($farmer, 'deactivated'));

        $this->post(route('admin.farmers.reactivate', $farmer))
            ->assertRedirect(route('admin.farmers.index'));

        $this->assertTrue($farmer->fresh()->is_active);
        $this->assertDatabaseHas('farmers', ['id' => $farmer->id]);
        $this->assertDatabaseHas('users', ['id' => $farmerUser->id]);
        $this->assertAuditState($admin, $farmer, 'reactivated', false, true);

        $this->post(route('admin.farmers.reactivate', $farmer))
            ->assertSessionHas('info', 'Farmer is already active.');
        $this->assertSame(1, $this->auditCount($farmer, 'reactivated'));
    }

    public function test_deactivation_revokes_existing_sanctum_tokens(): void
    {
        $admin = $this->createAdmin();
        [$farmerUser, $farmer] = $this->createFarmer();
        $plainTextToken = $farmerUser->createToken('regression-test')->plainTextToken;

        $this->withToken($plainTextToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $farmerUser->id);

        $this->actingAs($admin, 'web')
            ->post(route('admin.farmers.deactivate', $farmer))
            ->assertRedirect(route('admin.farmers.index'));

        Auth::guard('web')->logout();
        Auth::forgetGuards();

        $this->assertSame(0, $farmerUser->tokens()->count());

        $this->withToken($plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_inactive_farmer_with_previously_approved_application_can_be_verified_and_released_in_admin_flow(): void
    {
        [$admin, $farmer, $program, $application] = $this->createApprovedAssistance();

        $this->actingAs($admin)
            ->post(route('admin.farmers.deactivate', $farmer))
            ->assertRedirect(route('admin.farmers.index'));

        $verification = $this->post(route('admin.distributions.verify'), [
            'qr_data' => json_encode(['token' => $farmer->qr_code_token]),
        ]);

        $verification->assertOk()
            ->assertViewHas('farmer', fn (Farmer $verifiedFarmer) => $verifiedFarmer->is($farmer));

        $this->post(route('admin.distributions.release'), [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
        ])->assertRedirect(route('admin.distributions.index'));

        $this->assertDatabaseHas('distributions', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'released_by' => $admin->id,
        ]);
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_inactive_farmer_with_previously_approved_application_can_be_verified_and_released_through_qr_api(): void
    {
        [$admin, $farmer, $program, $application] = $this->createApprovedAssistance();
        $adminToken = $admin->createToken('regression-admin')->plainTextToken;

        $this->actingAs($admin, 'web')
            ->post(route('admin.farmers.deactivate', $farmer))
            ->assertRedirect(route('admin.farmers.index'));

        Auth::guard('web')->logout();
        Auth::forgetGuards();

        $this->withToken($adminToken)
            ->postJson('/api/qr/verify', [
                'token' => $farmer->qr_code_token,
            ])
            ->assertOk()
            ->assertJsonPath('approved_applications.0.application_id', $application->id);

        Auth::forgetGuards();

        $this->withToken($adminToken)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => $application->id,
            ])
            ->assertOk()
            ->assertJsonPath('distribution.farmer_id', $farmer->id)
            ->assertJsonPath('distribution.program_id', $program->id);

        $this->assertDatabaseHas('distributions', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'released_by' => $admin->id,
        ]);
        $this->assertSame('approved', $application->fresh()->status);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
    }

    private function createFarmer(): array
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Regression Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_status' => true,
            'rsbsa_number' => 'RSBSA-TEST-DEACT-' . fake()->unique()->numerify('######'),
            'qr_code_token' => (string) Str::uuid(),
            'profile_completed' => true,
        ]);

        return [$user, $farmer];
    }

    private function createApprovedAssistance(): array
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = Program::create([
            'name' => 'Regression Assistance Program',
            'description' => 'Program for deactivation regression coverage',
            'is_active' => true,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Regression Assistance Benefit',
            'description' => 'Benefit for deactivation regression coverage',
        ]);
        $benefit->eligibility_configured_at = now();
        $benefit->save();

        EligibilityRule::create([
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'crop_types' => ['Rice'],
            'min_land_size' => 1,
            'max_land_size' => 2,
            'requires_rsbsa' => true,
            'requires_association' => false,
            'requires_4ps' => false,
        ]);

        $application = Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => 'approved',
            'applied_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(12),
        ]);

        return [$admin, $farmer, $program, $application];
    }

    private function assertAuditState(
        User $admin,
        Farmer $farmer,
        string $action,
        bool $previousState,
        bool $newState
    ): void {
        $audit = AuditLog::query()
            ->where('action', $action)
            ->where('model', 'Farmer')
            ->where('model_id', $farmer->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame([
            'previous_is_active' => $previousState,
            'new_is_active' => $newState,
        ], $audit->details);
    }

    private function auditCount(Farmer $farmer, string $action): int
    {
        return AuditLog::query()
            ->where('action', $action)
            ->where('model', 'Farmer')
            ->where('model_id', $farmer->id)
            ->count();
    }
}
