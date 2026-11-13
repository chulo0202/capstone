<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Distribution;
use App\Models\EligibilityRule;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\ProgramBenefit;
use App\Models\User;
use App\Services\EligibilityService;
use App\Services\RecommendationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class BenefitSpecificWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_verify_or_release_farmer_qr_codes(): void
    {
        [$farmerUser, $farmer] = $this->createFarmer();
        $admin = $this->createAdmin();

        $farmerToken = $farmerUser->createToken('farmer-qr-security-test')->plainTextToken;
        $adminToken = $admin->createToken('admin-qr-security-test')->plainTextToken;

        $this->withToken($farmerToken)
            ->postJson('/api/qr/verify', ['token' => $farmer->qr_code_token])
            ->assertForbidden();

        Auth::forgetGuards();

        $this->withToken($farmerToken)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => 1,
            ])
            ->assertForbidden();

        Auth::forgetGuards();

        $this->withToken($adminToken)
            ->postJson('/api/qr/verify', ['token' => $farmer->qr_code_token])
            ->assertOk();
    }
    public function test_benefit_eligibility_combines_program_rules_without_leaking_benefit_rules(): void
    {
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $riceBenefit = $this->createBenefit($program, 'Rice kit');
        $generalBenefit = $this->createBenefit($program, 'General grant');

        EligibilityRule::create([
            'program_id' => $program->id,
            'program_benefit_id' => $riceBenefit->id,
            'requires_4ps' => true,
        ]);

        EligibilityRule::create([
            'program_id' => $program->id,
            'crop_types' => ['Rice'],
            'requires_rsbsa' => true,
        ]);

        $service = app(EligibilityService::class);
        $riceResult = $service->evaluate($farmer, $program, $riceBenefit);
        $generalResult = $service->evaluate($farmer, $program, $generalBenefit);

        $this->assertSame('partially_eligible', $riceResult['status']);
        $this->assertContains('4Ps beneficiary status required', $riceResult['benefit']['missing_requirements']);
        $this->assertSame('eligible', $riceResult['global']['status']);
        $this->assertSame('eligible', $generalResult['status']);
        $this->assertSame([], $generalResult['benefit']['missing_requirements']);
    }

    public function test_farmer_can_apply_once_per_benefit_without_legacy_application_authorizing_another_benefit(): void
    {
        [$user, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $firstBenefit = $this->createBenefit($program, 'Seed package');
        $secondBenefit = $this->createBenefit($program, 'Fertilizer package');

        $legacy = Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'status' => 'approved',
            'applied_at' => now()->subDays(3),
        ]);

        try {
            Application::create([
                'farmer_id' => $farmer->id,
                'program_id' => $program->id,
                'status' => 'pending',
            ]);
            $this->fail('The legacy null-benefit scope must remain unique.');
        } catch (QueryException) {
            $this->assertSame(1, Application::whereNull('program_benefit_id')->count());
        }

        $this->actingAs($user)
            ->post(route('farmer.applications.store', $program), [
                'program_benefit_id' => $firstBenefit->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->post(route('farmer.applications.store', $program), [
            'program_benefit_id' => $firstBenefit->id,
        ])->assertSessionHas('info', 'You already applied for this benefit.');

        $this->post(route('farmer.applications.store', $program), [
            'program_benefit_id' => $secondBenefit->id,
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('applications', 3);
        $this->assertNull($legacy->fresh()->program_benefit_id);
        $this->assertDatabaseHas('applications', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $firstBenefit->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('applications', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $secondBenefit->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'APPLICATION_SUBMITTED',
            'model' => 'Application',
        ]);
    }

    public function test_unconfigured_benefit_and_out_of_period_program_cannot_accept_applications(): void
    {
        [$user, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $unconfigured = $this->createBenefit($program, 'Draft benefit', false);
        $farmer->profile_completed = false;
        $farmer->save();

        $this->actingAs($user)
            ->post(route('farmer.applications.store', $program), [
                'program_benefit_id' => $unconfigured->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('applications', 0);

        $farmer->profile_completed = true;
        $farmer->save();
        $user->unsetRelation('farmer');

        $this->post(route('farmer.applications.store', $program), [
            'program_benefit_id' => $unconfigured->id,
        ])->assertSessionHas('error');
        $this->assertDatabaseCount('applications', 0);

        $unconfigured->eligibility_configured_at = now();
        $unconfigured->save();
        $program->update(['end_at' => now()->subMinute()]);

        $this->post(route('farmer.applications.store', $program), [
            'program_benefit_id' => $unconfigured->id,
        ])->assertSessionHas('error');

        $farmer->is_active = false;
        $farmer->save();
        $user->unsetRelation('farmer');
        $program->update(['end_at' => now()->addDay()]);

        $this->post(route('farmer.applications.store', $program), [
            'program_benefit_id' => $unconfigured->id,
        ])->assertForbidden();
    }

    public function test_admin_review_approves_and_rejects_benefit_applications_with_audit_events(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $approvedBenefit = $this->createBenefit($program, 'Approved benefit');
        $rejectedBenefit = $this->createBenefit($program, 'Rejected benefit');
        $approved = $this->makeBenefitApplication($farmer, $program, $approvedBenefit);
        $rejected = $this->makeBenefitApplication($farmer, $program, $rejectedBenefit);

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('Benefit');
        $this->get(route('admin.recommendations.index'))
            ->assertOk()
            ->assertSee('Program / Benefit');

        $this->actingAs($admin)
            ->patch(route('admin.applications.approve', $approved))
            ->assertSessionHas('success');

        $this->patch(route('admin.applications.reject', $rejected))
            ->assertSessionHas('success');

        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertSame(2, AuditLog::whereIn('action', [
            'APPLICATION_APPROVED',
            'APPLICATION_REJECTED',
        ])->count());
    }

    public function test_web_distribution_releases_exact_approved_benefit_application_once(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $firstBenefit = $this->createBenefit($program, 'First benefit');
        $secondBenefit = $this->createBenefit($program, 'Second benefit');
        $firstApplication = $this->makeBenefitApplication($farmer, $program, $firstBenefit, 'approved');
        $secondApplication = $this->makeBenefitApplication($farmer, $program, $secondBenefit, 'approved');

        $this->actingAs($admin)
            ->post(route('admin.distributions.release'), [
                'application_id' => $firstApplication->id,
            ])
            ->assertRedirect(route('admin.distributions.index'));

        $this->post(route('admin.distributions.release'), [
            'application_id' => $firstApplication->id,
        ])->assertSessionHas('error', 'This application or benefit has already been released.');

        $this->post(route('admin.distributions.release'), [
            'application_id' => $secondApplication->id,
        ])->assertRedirect(route('admin.distributions.index'));

        $this->assertDatabaseCount('distributions', 2);
        $this->assertDatabaseHas('distributions', [
            'application_id' => $firstApplication->id,
            'program_benefit_id' => $firstBenefit->id,
        ]);
        $this->assertDatabaseHas('distributions', [
            'application_id' => $secondApplication->id,
            'program_benefit_id' => $secondBenefit->id,
        ]);
        $this->assertSame(
            2,
            AuditLog::where('action', 'BENEFIT_RELEASED')->where('model', 'Distribution')->count()
        );
        $this->get(route('admin.distributions.index'))->assertOk()->assertSee('Benefit');
    }

    public function test_qr_scanner_keeps_contract_fields_and_releases_inactive_farmer_benefits_by_application_id(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $farmer->is_active = false;
        $farmer->save();

        $program = $this->createProgram();
        $firstBenefit = $this->createBenefit($program, 'Rice kit');
        $secondBenefit = $this->createBenefit($program, 'Tool kit');
        $firstApplication = $this->makeBenefitApplication($farmer, $program, $firstBenefit, 'approved');
        $secondApplication = $this->makeBenefitApplication($farmer, $program, $secondBenefit, 'approved');
        $token = $admin->createToken('benefit-qr-test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/qr/verify', ['token' => $farmer->qr_code_token])
            ->assertOk()
            ->assertJsonFragment(['application_id' => $firstApplication->id])
            ->assertJsonFragment(['application_id' => $secondApplication->id])
            ->assertJsonStructure([
                'success',
                'message',
                'farmer' => ['id', 'full_name', 'barangay', 'crop_type', 'land_size'],
                'approved_applications' => [[
                    'application_id',
                    'program' => ['id', 'name', 'description'],
                    'status',
                    'applied_at',
                    'remarks',
                    'already_released',
                ]],
            ]);

        Auth::forgetGuards();
        $this->withToken($token)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => $firstApplication->id,
            ])
            ->assertOk()
            ->assertJsonPath('distribution.application_id', $firstApplication->id)
            ->assertJsonPath('distribution.program_id', $program->id)
            ->assertJsonPath('distribution.program_name', $program->name)
            ->assertJsonStructure([
                'success',
                'message',
                'distribution' => [
                    'id',
                    'farmer_id',
                    'program_id',
                    'program_name',
                    'distributed_at',
                    'notes',
                ],
            ]);

        Auth::forgetGuards();
        $this->withToken($token)
            ->postJson('/api/qr/verify', ['token' => $farmer->qr_code_token])
            ->assertOk()
            ->assertJsonFragment([
                'application_id' => $firstApplication->id,
                'already_released' => true,
            ]);

        Auth::forgetGuards();
        $this->withToken($token)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => $firstApplication->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Benefit already released for this application.');

        Auth::forgetGuards();
        $this->withToken($token)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => $secondApplication->id,
            ])
            ->assertOk();

        $this->assertDatabaseCount('distributions', 2);
        $this->assertDatabaseHas('distributions', [
            'application_id' => $firstApplication->id,
            'program_benefit_id' => $firstBenefit->id,
        ]);
        $this->assertDatabaseHas('distributions', [
            'application_id' => $secondApplication->id,
            'program_benefit_id' => $secondBenefit->id,
        ]);
        $this->assertSame(2, AuditLog::where('action', 'BENEFIT_RELEASED')->count());
    }

    public function test_legacy_approved_application_cannot_be_released_through_admin_web(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();

        $application = Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'status' => 'approved',
            'applied_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(12),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.distributions.release'), [
                'application_id' => $application->id,
            ])
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.'
            );

        $this->assertDatabaseCount('distributions', 0);
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_legacy_approved_application_cannot_be_released_through_qr_api(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();

        $application = Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'status' => 'approved',
            'applied_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(12),
        ]);

        $token = $admin->createToken('legacy-release-test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => $application->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.'
            );

        $this->assertDatabaseCount('distributions', 0);
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_previously_approved_benefit_applications_can_be_released_after_benefit_archival(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $webBenefit = $this->createBenefit($program, 'Archived web benefit');
        $qrBenefit = $this->createBenefit($program, 'Archived QR benefit');

        $webApplication = $this->makeBenefitApplication(
            $farmer,
            $program,
            $webBenefit,
            'approved'
        );
        $qrApplication = $this->makeBenefitApplication(
            $farmer,
            $program,
            $qrBenefit,
            'approved'
        );

        $webBenefit->archived_at = now();
        $webBenefit->save();

        $qrBenefit->archived_at = now();
        $qrBenefit->save();

        $this->actingAs($admin)
            ->post(route('admin.distributions.release'), [
                'application_id' => $webApplication->id,
            ])
            ->assertRedirect(route('admin.distributions.index'));

        $this->assertDatabaseHas('distributions', [
            'application_id' => $webApplication->id,
            'program_benefit_id' => $webBenefit->id,
        ]);

        $adminToken = $admin->createToken('archived-benefit-release-test')->plainTextToken;

        Auth::forgetGuards();

        $this->withToken($adminToken)
            ->postJson('/api/qr/release', [
                'token' => $farmer->qr_code_token,
                'application_id' => $qrApplication->id,
            ])
            ->assertOk()
            ->assertJsonPath('distribution.application_id', $qrApplication->id);

        $this->assertDatabaseHas('distributions', [
            'application_id' => $qrApplication->id,
            'program_benefit_id' => $qrBenefit->id,
        ]);

        $this->assertDatabaseCount('distributions', 2);
    }
    public function test_program_archive_is_durable_and_historical_program_rows_block_physical_deletion(): void
    {
        $admin = $this->createAdmin();
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram();
        $benefit = $this->createBenefit($program, 'Archive-safe benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit);

        $this->actingAs($admin)
            ->patch(route('admin.programs.archive', $program))
            ->assertRedirect();

        $this->assertNotNull($program->fresh()->archived_at);
        $this->assertFalse($program->fresh()->is_active);

        $this->patch(route('admin.programs.toggle', $program))
            ->assertSessionHas('error', 'Archived programs cannot be reactivated.');
        $this->assertFalse($program->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'archived',
            'model' => 'Program',
            'model_id' => $program->id,
        ]);
        $this->get(route('admin.programs.index', ['status' => 'archived']))
            ->assertOk()
            ->assertSee('Archived');

        try {
            $program->delete();
            $this->fail('A program with historical applications should not be physically deletable.');
        } catch (QueryException) {
            $this->assertDatabaseHas('applications', ['id' => $application->id]);
            $this->assertDatabaseHas('programs', ['id' => $program->id]);
        }
    }

    public function test_benefit_archive_preserves_its_id_rule_and_audit_history(): void
    {
        $admin = $this->createAdmin();
        $program = $this->createProgram();
        $archivedBenefit = $this->createBenefit($program, 'Historical benefit');
        $activeBenefit = $this->createBenefit($program, 'Current benefit');
        $rule = EligibilityRule::create([
            'program_id' => $program->id,
            'program_benefit_id' => $archivedBenefit->id,
            'requires_rsbsa' => true,
        ]);

        $editResponse = $this->actingAs($admin)
            ->get(route('admin.programs.edit', $program));
        $this->assertSame(200, $editResponse->status(), $editResponse->exceptions->last()?->getMessage() ?? 'Program edit page did not render.');
        $editResponse->assertSee('Benefit-specific eligibility');

        $this->actingAs($admin)
            ->put(route('admin.programs.update', $program), [
                'name' => $program->name,
                'description' => $program->description,
                'is_active' => '1',
                'benefits' => [
                    [
                        'id' => $archivedBenefit->id,
                        'name' => $archivedBenefit->name,
                        'description' => $archivedBenefit->description,
                        'remove' => '1',
                    ],
                    [
                        'id' => $activeBenefit->id,
                        'name' => $activeBenefit->name,
                        'description' => $activeBenefit->description,
                        'remove' => '0',
                    ],
                ],
                'benefit_rules' => [
                    ['program_benefit_id' => $archivedBenefit->id],
                    ['program_benefit_id' => $activeBenefit->id],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertNotNull($archivedBenefit->fresh()->archived_at);
        $this->assertNull($activeBenefit->fresh()->archived_at);
        $this->assertSame($archivedBenefit->id, $rule->fresh()->program_benefit_id);
        $archiveAudit = AuditLog::where('model', 'Program')
            ->where('model_id', $program->id)
            ->where('action', 'updated')
            ->firstOrFail();
        $this->assertContains($archivedBenefit->id, $archiveAudit->details['archived_benefit_ids']);
        $this->assertDatabaseHas('audit_logs', [
            'model' => 'Program',
            'model_id' => $program->id,
            'action' => 'updated',
        ]);

        try {
            $archivedBenefit->delete();
            $this->fail('A benefit referenced by an eligibility rule should be protected from deletion.');
        } catch (QueryException) {
            $this->assertDatabaseHas('program_benefits', ['id' => $archivedBenefit->id]);
            $this->assertSame($archivedBenefit->id, $rule->fresh()->program_benefit_id);
        }
    }

    public function test_recommendations_are_per_configured_active_benefit_and_hide_zero_benefit_programs(): void
    {
        [, $farmer] = $this->createFarmer();
        $user = $farmer->user;
        $program = $this->createProgram();
        $firstBenefit = $this->createBenefit($program, 'First');
        $secondBenefit = $this->createBenefit($program, 'Second');
        $draftBenefit = $this->createBenefit($program, 'Unconfigured', false);
        $draftProgram = $this->createProgram('No benefits');

        app(RecommendationService::class)->generateForFarmer($farmer);

        $this->assertDatabaseHas('recommendations', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $firstBenefit->id,
        ]);
        $this->assertDatabaseHas('recommendations', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $secondBenefit->id,
        ]);
        $this->assertDatabaseMissing('recommendations', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $draftBenefit->id,
        ]);
        $this->assertDatabaseMissing('recommendations', [
            'farmer_id' => $farmer->id,
            'program_id' => $draftProgram->id,
        ]);

        $this->actingAs($user)
            ->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertSee('First')
            ->assertSee('Second')
            ->assertDontSee('No benefits');

        $eligibilityResponse = $this->get(route('farmer.eligibility'));
        $this->assertSame(200, $eligibilityResponse->status(), $eligibilityResponse->exceptions->last()?->getMessage() ?? 'Eligibility page did not render.');
        $eligibilityResponse->assertSee('Benefit-specific rules');

        $applicationsResponse = $this->get(route('farmer.applications.index'));
        $this->assertSame(200, $applicationsResponse->status(), $applicationsResponse->exceptions->last()?->getMessage() ?? 'Applications page did not render.');
        $applicationsResponse->assertSee('Application History');

        $dashboardResponse = $this->get(route('farmer.dashboard'));
        $this->assertSame(200, $dashboardResponse->status(), $dashboardResponse->exceptions->last()?->getMessage() ?? 'Dashboard page did not render.');

        $notificationsResponse = $this->get(route('farmer.notifications.index'));
        $this->assertSame(200, $notificationsResponse->status(), $notificationsResponse->exceptions->last()?->getMessage() ?? 'Notifications page did not render.');
        $notificationsResponse->assertSee('Distribution Updates');
    }

    private function createFarmer(): array
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Benefit Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_status' => true,
            'rsbsa_number' => 'RSBSA-BENEFIT-' . fake()->unique()->numerify('######'),
            'qr_code_token' => (string) Str::uuid(),
            'profile_completed' => true,
        ]);

        return [$user, $farmer];
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
    }

    private function createProgram(string $name = 'Benefit Program'): Program
    {
        return Program::create([
            'name' => $name,
            'description' => 'Benefit workflow test program',
            'is_active' => true,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);
    }

    private function createBenefit(
        Program $program,
        string $name,
        bool $configured = true
    ): ProgramBenefit {
        $benefit = $program->benefits()->create([
            'name' => $name,
            'description' => $name,
        ]);

        if ($configured) {
            $benefit->eligibility_configured_at = now();
            $benefit->save();
        }

        return $benefit;
    }

    private function makeBenefitApplication(
        Farmer $farmer,
        Program $program,
        ProgramBenefit $benefit,
        string $status = 'pending'
    ): Application {
        return Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => $status,
            'applied_at' => now(),
        ]);
    }
}
