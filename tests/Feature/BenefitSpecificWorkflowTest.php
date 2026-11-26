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
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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

        // Recurring applications no longer require a unique legacy null-benefit scope.
        $this->assertSame(1, Application::whereNull('program_benefit_id')->count());
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
        [$user, $farmer] = $this->createFarmer();
        $this->fakeDecisionGmail();
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
            ->assertSessionHasErrorsIn('applicationRejection', 'remarks');
        $this->patch(route('admin.applications.reject', $rejected), ['remarks' => 'Application documents were incomplete.'])
            ->assertSessionHas('success');
        $this->assertSame('Application documents were incomplete.', $rejected->fresh()->remarks);

        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertSame(2, AuditLog::whereIn('action', [
            'APPLICATION_APPROVED',
            'APPLICATION_REJECTED',
        ])->count());

        $this->patch(route('admin.applications.approve', $approved))->assertSessionHas('error');
        [$unverifiedUser, $unverifiedFarmer] = $this->createFarmer();
        $unverifiedUser->forceFill(['email_verified_at' => null])->save();
        $unverifiedApplication = $this->makeBenefitApplication($unverifiedFarmer, $program, $approvedBenefit);
        $this->patch(route('admin.applications.approve', $unverifiedApplication))->assertSessionHas('success');

        $this->assertGmailDecisions([
            ['Application Approved', $user->email, 'Approved benefit'],
            ['Application Rejected', $user->email, 'Rejected benefit'],
        ]);
    }

    public function test_admin_can_approve_pcic_claim_in_fams_before_official_pcic_approval(): void
    {
        Storage::fake('private');
        [$user, $farmer] = $this->createFarmer();
        $program = $this->createProgram(Program::PCIC_NAME);
        $benefit = $this->createBenefit($program, 'Claim for Indemnity');
        $claim = $this->makeBenefitApplication($farmer, $program, $benefit);

        $this->actingAs($this->createAdmin())
            ->patch(route('admin.applications.approve', $claim))
            ->assertSessionHasErrorsIn('applicationRejection', 'decision_attachment');
        $this->assertSame('pending', $claim->fresh()->status);

        $this->patch(route('admin.applications.approve', $claim), [
            'decision_attachment' => UploadedFile::fake()->create('decision.pdf', 12, 'application/pdf'),
        ])->assertSessionHas('success');
        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertSame('claim_approved_by_fams', $claim->latestPcicTrackingUpdate()->firstOrFail()->status);

        $this->post(route('admin.distributions.release'), ['application_id' => $claim->id])
            ->assertSessionHas('error', 'Official PCIC approval is required before release.');
        $this->assertDatabaseCount('distributions', 0);

        $update = $claim->latestPcicTrackingUpdate()->firstOrFail();
        Storage::disk('private')->assertExists($update->attachment_path);
        $download = route('pcic.decision-attachment', $update);
        $this->get(route('admin.applications.show', $claim))->assertOk()->assertSee($download, false);
        $this->actingAs($user)->get(route('farmer.notifications.index'))
            ->assertOk()->assertSee($download, false);
        $this->get(route('farmer.applications.index'))->assertOk()->assertSee($download, false);
        $this->get($download)->assertDownload('PCIC-decision.pdf');
        [$otherFarmer] = $this->createFarmer();
        $this->actingAs($otherFarmer)->get($download)->assertNotFound();
    }

    public function test_pcic_new_application_rejection_requires_an_attachment_and_notifies_the_farmer(): void
    {
        Storage::fake('private');
        [$user, $farmer] = $this->createFarmer();
        $program = $this->createProgram(Program::PCIC_NAME);
        $benefit = $this->createBenefit($program, 'New Application');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit);
        $route = route('admin.applications.reject', $application);
        $data = ['remarks' => 'The insured area could not be verified.'];

        $this->actingAs($this->createAdmin())->patch($route, $data)
            ->assertSessionHasErrorsIn('applicationRejection', 'decision_attachment');
        $this->assertSame('pending', $application->fresh()->status);

        $this->patch($route, $data + [
            'decision_attachment' => UploadedFile::fake()->create('decision.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('success');
        $update = $application->latestPcicTrackingUpdate()->firstOrFail();
        $this->assertSame('rejected', $application->fresh()->status);
        $this->actingAs($user)->get(route('farmer.notifications.index'))->assertOk()
            ->assertSeeText('The insured area could not be verified.')
            ->assertSee(route('pcic.decision-attachment', $update), false);
    }

    public function test_agricultural_loan_decisions_require_an_attachment_and_notify_the_owner(): void
    {
        Storage::fake('private');
        [$owner, $farmer] = $this->createFarmer();
        [$otherFarmer] = $this->createFarmer();
        $admin = $this->createAdmin();
        $program = $this->createProgram('Agricultural Loans');

        foreach (['approve' => 'approved', 'reject' => 'rejected'] as $action => $status) {
            $benefit = $this->createBenefit($program, 'Loan benefit for '.$action);
            $application = $this->makeBenefitApplication($farmer, $program, $benefit);
            $route = route('admin.applications.'.$action, $application);
            $data = $action === 'reject' ? ['remarks' => 'Incomplete supporting documents.'] : [];

            $this->actingAs($admin)->patch($route, $data)
                ->assertSessionHasErrorsIn('applicationRejection', 'decision_attachment');
            $this->assertSame('pending', $application->fresh()->status);

            $this->patch($route, $data + [
                'decision_attachment' => UploadedFile::fake()->create('review.pdf', 12, 'application/pdf'),
            ])->assertSessionHas('success');
            $this->assertSame($status, $application->fresh()->status);
            Storage::disk('private')->assertExists($application->fresh()->loan_decision_attachment_path);

            $download = route('loans.decision-attachment', $application);
            $this->actingAs($owner)->get(route('farmer.notifications.index'))->assertOk()
                ->assertSee($benefit->name)->assertSee($download, false);
            $this->get(route('farmer.applications.index'))->assertOk()->assertSee($download, false);
            $this->get($download)->assertDownload('Loan-decision.pdf');
            $this->actingAs($otherFarmer)->get($download)->assertNotFound();
        }
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

        DB::statement('SAVEPOINT program_delete_constraint');

        try {
            $program->delete();

            DB::statement('RELEASE SAVEPOINT program_delete_constraint');
            $this->fail('A program with historical applications should not be physically deletable.');
        } catch (QueryException) {
            DB::statement('ROLLBACK TO SAVEPOINT program_delete_constraint');
            DB::statement('RELEASE SAVEPOINT program_delete_constraint');

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

        $this->actingAs($admin);

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

        DB::statement('SAVEPOINT benefit_delete_constraint');

        try {
            $archivedBenefit->delete();

            DB::statement('RELEASE SAVEPOINT benefit_delete_constraint');
            $this->fail('A benefit referenced by an eligibility rule should be protected from deletion.');
        } catch (QueryException) {
            DB::statement('ROLLBACK TO SAVEPOINT benefit_delete_constraint');
            DB::statement('RELEASE SAVEPOINT benefit_delete_constraint');

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

        $this->get(route('farmer.eligibility'))->assertRedirect(route('farmer.recommendations'));

        $applicationsResponse = $this->get(route('farmer.applications.index'));
        $this->assertSame(200, $applicationsResponse->status(), $applicationsResponse->exceptions->last()?->getMessage() ?? 'Applications page did not render.');
        $applicationsResponse->assertSee('Application History');

        $dashboardResponse = $this->get(route('farmer.dashboard'));
        $this->assertSame(200, $dashboardResponse->status(), $dashboardResponse->exceptions->last()?->getMessage() ?? 'Dashboard page did not render.');

        $notificationsResponse = $this->get(route('farmer.notifications.index'));
        $this->assertSame(200, $notificationsResponse->status(), $notificationsResponse->exceptions->last()?->getMessage() ?? 'Notifications page did not render.');
        $notificationsResponse->assertSee('Distribution Updates');
    }

    public function test_distribution_form_only_loads_benefits_and_applications_for_the_selected_program(): void
    {
        [, $farmer] = $this->createFarmer();
        $fertilizer = $this->createProgram('Fertilizer Subsidies');
        $urea = $this->createBenefit($fertilizer, 'Urea');
        $seed = $this->createProgram('Seed Subsidies');
        $certified = $this->createBenefit($seed, 'Certified Seeds');
        $legacy = $this->createBenefit($seed, 'Legacy Seed Grant');
        $legacy->forceFill(['archived_at' => now()])->save();
        $this->makeBenefitApplication($farmer, $seed, $legacy, 'approved');
        $application = $this->makeBenefitApplication($farmer, $seed, $certified, 'approved');
        $this->actingAs($this->createAdmin());

        $this->get(route('admin.distribution-batches.create'))
            ->assertOk()
            ->assertSee('Select a program first');

        $this->get(route('admin.distribution-batches.create', [
            'program' => $seed->id,
            'benefit' => $urea->id,
        ]))->assertOk()
            ->assertSee('Certified Seeds')
            ->assertViewHas('benefit', null)
            ->assertViewHas('beneficiaries', fn ($rows) => $rows->isEmpty());

        $this->get(route('admin.distribution-batches.create', [
            'program' => $seed->id,
            'benefit' => $legacy->id,
        ]))->assertOk()
            ->assertSee('Certified Seeds')
            ->assertDontSee('Legacy Seed Grant')
            ->assertViewHas('benefit', null);

        $this->get(route('admin.distribution-batches.create', [
            'program' => $seed->id,
            'benefit' => $certified->id,
        ]))->assertOk()
            ->assertViewHas('benefit', fn ($selected) => $selected->is($certified))
            ->assertSee('Create Draft');

        $this->getJson(route('admin.distribution-batches.create', [
            'program' => $seed->id,
            'benefit' => $certified->id,
        ]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $application->id);

        $this->getJson(route('admin.distribution-batches.create', [
            'program' => $seed->id,
            'benefit' => $urea->id,
        ]))->assertUnprocessable();
    }

    public function test_training_uses_the_shared_schedules_page_and_confirms_only_available_slots(): void
    {
        $admin = $this->createAdmin();
        [$first, $firstFarmer] = $this->createFarmer();
        [$second, $secondFarmer] = $this->createFarmer();
        $title = 'Rice field demonstration';
        $this->fakeDecisionGmail();
        $program = $this->createProgram('Training and Technology Demonstrations');
        $benefit = $this->createBenefit($program, 'Field Demonstration');
        $this->makeBenefitApplication($firstFarmer, $program, $benefit, 'approved');
        $secondApplication = $this->makeBenefitApplication($secondFarmer, $program, $benefit);


        $this->actingAs($admin)->get(route('admin.schedules.options'))
            ->assertOk()->assertSee('Seed Subsidies')->assertSee('Fertilizer Subsidies')
            ->assertSee('Training and Technology Demonstrations')->assertDontSee('Philippine Crop Insurance Corporation (PCIC)');
        $this->get(route('admin.trainings.create'))->assertOk()
            ->assertSee('Save Training Draft')
            ->assertSee('training-farmer-'.$firstFarmer->id)
            ->assertDontSee('training-farmer-'.$secondFarmer->id);
        $schedule = [
            'farmer_ids' => [$firstFarmer->id],
            'title' => $title,
            'topic' => 'Rice planting',
            'objectives' => 'Demonstrate correct spacing.',
            'scheduled_date' => today('Asia/Manila')->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:30',
            'venue' => 'Municipal Demonstration Farm',
            'trainer' => 'Municipal Agriculture Office',
            'slots' => 1,
            'instructions' => 'Bring a notebook.',
        ];
        $store = route('admin.trainings.store');
        $bothFarmers = [$firstFarmer->id, $secondFarmer->id];
        $this->post($store, array_replace($schedule, ['farmer_ids' => $bothFarmers]))
            ->assertSessionHasErrors('farmer_ids');
        $this->post($store, $schedule)->assertSessionHas('success');

        $training = \App\Models\TrainingSession::sole();
        $register = '/farmer/trainings/'.$training->id.'/register';
        $this->actingAs($first)->get(route('farmer.trainings.index'))->assertOk()->assertDontSee($title);
        $this->post($register)->assertNotFound();

        $this->actingAs($admin)->post(route('admin.trainings.publish', $training))->assertSessionHas('success');
        $this->get(route('admin.distribution-batches.index'))->assertOk()->assertSee($title);
        $this->get(route('admin.distribution-batches.index', ['type' => 'distribution']))->assertOk()->assertDontSee($title);

        $firstEntry = $training->registrations()->where('farmer_id', $firstFarmer->id)->sole();
        $view = route('farmer.trainings.registration', $firstEntry);
        $this->actingAs($first)->get(route('farmer.trainings.index', ['status' => 'pending']))
            ->assertOk()->assertSee($title)->assertSee($view)->assertDontSee('Bring a notebook.');
        $this->get(route('farmer.trainings.index', ['status' => 'confirmed']))
            ->assertOk()->assertDontSee($title);

        $this->actingAs($second)->post($register)->assertNotFound();
        $this->assertDatabaseCount('training_registrations', 1);
        $secondEntry = $training->registrations()->create(['farmer_id' => $secondFarmer->id]);
        $this->get(route('farmer.trainings.index'))->assertOk()->assertDontSee($title);
        $this->get($view)->assertNotFound();

        $this->actingAs($admin)->get(route('admin.trainings.show', $training))->assertOk()->assertSee('Confirm Slot');
        $this->post(route('admin.trainings.review', [$training, $firstEntry]), ['status' => 'confirmed'])
            ->assertSessionHas('success');
        $this->post(route('admin.trainings.review', [$training, $secondEntry]), ['status' => 'confirmed'])
            ->assertSessionHasErrors('status');
        $this->post(route('admin.trainings.review', [$training, $secondEntry]), ['status' => 'rejected', 'reason' => 'All slots filled.'])
            ->assertSessionHas('success');

        $this->actingAs($first)->get(route('farmer.notifications.index'))->assertOk()->assertSee($title)->assertSee('Confirmed');
        $this->get(route('farmer.trainings.registration', $firstEntry))->assertOk()->assertSee('Bring a notebook.');
        $this->get(route('farmer.trainings.registration', $secondEntry))->assertNotFound();
        $this->actingAs($second)->get(route('farmer.notifications.index'))->assertOk()->assertSee('All slots filled.');
        $this->assertDatabaseCount('distribution_batches', 0);
        $this->assertDatabaseCount('distributions', 0);

        $this->assertGmailDecisions([
            ['Training registration Confirmed', $first->email, 'Municipal Demonstration Farm'],
            ['Training registration Rejected', $second->email, $title],
        ]);

        $secondApplication->update(['status' => 'approved']);
        $this->actingAs($admin)->post($store, array_replace($schedule, ['farmer_ids' => $bothFarmers]))
            ->assertSessionHasErrors('slots');
        $this->post($store, array_replace($schedule, ['farmer_ids' => $bothFarmers, 'slots' => 2]))
            ->assertSessionHas('success');

        $draft = \App\Models\TrainingSession::where('status', 'draft')->sole();
        $this->assertEqualsCanonicalizing($bothFarmers, $draft->registrations()->pluck('farmer_id')->all());
    }

    public function test_non_distribution_programs_are_excluded_from_new_schedules(): void
    {
        [, $farmer] = $this->createFarmer();
        $seed = $this->createProgram('Seed Subsidies');
        $fertilizer = $this->createProgram('Fertilizer Subsidies');
        $this->createBenefit($seed, 'Seed benefit');
        $this->createBenefit($fertilizer, 'Fertilizer benefit');
        $this->actingAs($this->createAdmin());

        foreach (['Agricultural Loans', 'Training and Technology Demonstrations', Program::PCIC_NAME] as $name) {
            $program = $this->createProgram($name);
            $benefit = $this->createBenefit($program, 'Excluded benefit');
            $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');

            $this->getJson(route('admin.distribution-batches.create', ['program' => $program->id, 'benefit' => $benefit->id]))
                ->assertUnprocessable();
            $this->post(route('admin.distribution-batches.store'), $this->distributionBatchData($application))
                ->assertSessionHasErrors('program_id');
        }

        $this->get(route('admin.distribution-batches.create'))->assertOk()
            ->assertViewHas('programs', fn ($programs) => $programs->pluck('id')->sort()->values()->all() === [$seed->id, $fertilizer->id]);
        $this->assertDatabaseCount('distribution_batches', 0);
    }

    public function test_distribution_draft_restores_wizard_inputs_and_redirects_after_success(): void
    {
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Schedule benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $this->actingAs($this->createAdmin());
        $create = route('admin.distribution-batches.create');
        $store = route('admin.distribution-batches.store');
        $data = $this->distributionBatchData($application);

        $this->from($create)->post($store, array_replace($data, ['end_time' => '08:00']))
            ->assertRedirect($create)
            ->assertSessionHasErrors('end_time')
            ->assertSessionHasInput('application_ids', [$application->id]);

        $this->get($create)->assertOk()
            ->assertViewHas('benefit', fn ($selected) => $selected->is($benefit))
            ->assertSee('value="'.$data['name'].'"', false)
            ->assertSee('End time must be later than start time.');

        $response = $this->post($store, $data);
        $this->assertDatabaseCount('distribution_batches', 1);
        $this->assertDatabaseCount('distribution_assignments', 1);
        $this->assertDatabaseCount('distributions', 0);
        $batch = \App\Models\DistributionBatch::sole();
        $response->assertRedirect(route('admin.distribution-batches.show', $batch))
            ->assertSessionHas('success', 'Distribution draft created.');
        $this->assertSame('draft', $batch->status);
        $this->assertSame('approved', $application->fresh()->status);

        $this->get(route('admin.distribution-batches.edit', $batch))
            ->assertOk()->assertSee('Save Schedule')->assertDontSee('distribution-wizard');
    }

    public function test_distribution_gmail_only_sends_to_verified_beneficiaries_and_retries_failures(): void
    {
        config([
            'services.distribution_gmail.enabled' => true,
            'services.distribution_gmail.sender' => 'fams@example.com',
        ]);

        $gmail = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token']),
            $gmail => Http::sequence()->pushStatus(429)->push(['id' => 'test-message-id']),
        ]);

        [$user, $farmer] = $this->createFarmer();
        [$unverified, $unverifiedFarmer] = $this->createFarmer();
        $unverified->forceFill(['email_verified_at' => null])->save();
        [, $otherFarmer] = $this->createFarmer();

        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Email test benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $other = $this->makeBenefitApplication($unverifiedFarmer, $program, $benefit, 'approved');
        $this->makeBenefitApplication($otherFarmer, $program, $benefit, 'approved');

        $this->actingAs($this->createAdmin())->post(route('admin.distribution-batches.store'), array_replace(
            $this->distributionBatchData($application),
            ['application_ids' => [$application->id, $other->id]]
        ))->assertSessionHas('success');
        $batch = \App\Models\DistributionBatch::sole();
        Http::assertNothingSent();

        $this->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertSessionHas('success');
        $this->assertDatabaseCount('distribution_schedule_emails', 2);
        $this->assertDatabaseHas('distribution_schedule_emails', ['farmer_id' => $unverifiedFarmer->id, 'status' => 'unverified']);

        $email = $batch->scheduleEmails()->where('farmer_id', $farmer->id)->sole();
        $this->assertSame('failed', $email->status);
        $retry = route('admin.distribution-batches.emails.retry', [$batch, $email]);
        $this->post($retry)->assertSessionHas('success');
        $this->assertSame('test-message-id', $email->refresh()->gmail_message_id);
        $this->post($retry)->assertSessionHas('success');

        Http::assertSentCount(4);
        Http::assertSent(function ($request) use ($gmail, $user) {
            if ($request->url() !== $gmail) {
                return false;
            }

            $message = base64_decode(strtr($request['raw'], '-_', '+/'));

            return str_contains($message, 'To: '.$user->email)
                && str_contains($message, 'Municipal Hall');
        });
        $this->get(route('admin.distribution-batches.show', $batch))
            ->assertOk()->assertSee('Accepted by Gmail')->assertSee('Skipped (email not verified)');
    }

    public function test_publishing_distribution_submits_sms_only_for_assigned_farmers(): void
    {
        config(['services.distribution_sms.enabled' => true]);
        [, $farmer] = $this->createFarmer();
        [, $otherFarmer] = $this->createFarmer();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Batch benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $this->makeBenefitApplication($otherFarmer, $program, $benefit, 'approved');
        $this->actingAs($this->createAdmin());

        $sns = \Mockery::mock(\Aws\Sns\SnsClient::class);
        $sns->shouldReceive('publish')->once()->withArgs(function (array $payload) {
            return $payload['PhoneNumber'] === '+639123456789'
                && str_contains($payload['Message'], 'Batch benefit')
                && str_contains($payload['Message'], 'See FAMS Notifications');
        })->andReturn(new \Aws\Result(['MessageId' => 'test-sms-id']));
        $this->app->instance(\Aws\Sns\SnsClient::class, $sns);

        $batch = $this->createDistributionBatch($application);
        $this->assertDatabaseCount('sms_notifications', 0);
        $this->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('sms_notifications', 1);
        $this->assertDatabaseHas('sms_notifications', [
            'distribution_batch_id' => $batch->id,
            'farmer_id' => $farmer->id,
            'recipient' => '+639123456789',
            'status' => 'submitted',
        ]);

        $this->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertConflict();
        $this->assertDatabaseCount('sms_notifications', 1);
    }

    public function test_distribution_batch_enforces_assignment_on_web_and_scanner_and_preserves_direct_release(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        [, $farmer] = $this->createFarmer();
        [, $otherFarmer] = $this->createFarmer();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Batch benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $other = $this->makeBenefitApplication($otherFarmer, $program, $benefit, 'approved');
        $this->actingAs($this->createAdmin());

        $batch = $this->createDistributionBatch($application);
        $otherBatch = $this->createDistributionBatch($other);
        $this->get(route('admin.distribution-batches.show', $batch))->assertOk();
        $this->get(route('admin.distribution-batches.create', [
            'program' => $program->id,
            'benefit' => $benefit->id,
        ]))->assertOk();

        $scan = ['token' => $farmer->qr_code_token, 'application_id' => $application->id];
        $this->postJson('/api/qr/release', $scan)->assertConflict();
        $this->post(route('admin.distributions.release'), ['application_id' => $application->id])
            ->assertSessionHas('error');
        $this->postJson('/api/qr/release', $scan + ['distribution_batch_id' => $otherBatch->id])
            ->assertConflict()
            ->assertJsonPath('message', 'This application is not assigned to the selected distribution batch.');
        $this->assertDatabaseCount('distributions', 0);

        $this->post(route('admin.distribution-batches.publish', $batch))->assertSessionHasErrors('confirm_publish');
        $this->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertSessionHas('success');
        $this->travelTo(now()->setTime(13, 0));
        $this->postJson('/api/qr/release', $scan)->assertConflict();
        $this->travelTo(now()->setTime(10, 0));
        $this->postJson('/api/qr/release', $scan)->assertOk()
            ->assertJsonPath('distribution.distribution_batch_id', $batch->id);
        $this->postJson('/api/qr/release', $scan)->assertConflict();
        $this->assertSame('completed', $batch->fresh()->status);

        $repeat = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $this->postJson('/api/qr/release', ['token' => $farmer->qr_code_token, 'application_id' => $repeat->id])
            ->assertConflict()
            ->assertJsonPath('message', 'Benefit already released for this application.');
        $this->post(route('admin.distribution-batches.store'), $this->distributionBatchData($repeat))
            ->assertSessionHasErrors('application_ids');
        $this->assertDatabaseCount('distributions', 1);

        $this->post(route('admin.distribution-batches.cancel', $otherBatch), ['confirm_cancel' => 'yes'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('distribution_assignments', [
            'distribution_batch_id' => $otherBatch->id,
            'application_id' => $other->id,
            'active_application_id' => null,
        ]);
        $this->post(route('admin.distributions.release'), ['application_id' => $other->id])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('distributions', ['application_id' => $other->id, 'distribution_batch_id' => null]);
    }

    public function test_distribution_preparation_rejects_invalid_schedule_and_beneficiaries_without_creating_records(): void
    {
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Batch benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit);
        $data = $this->distributionBatchData($application);
        $this->actingAs($this->createAdmin());

        foreach ([
            'name' => '   ',
            'location' => '',
            'scheduled_date' => today()->subDay()->toDateString(),
            'start_time' => 'invalid',
            'end_time' => '09:00',
            'application_ids' => [],
            'program_id' => 9999,
            'program_benefit_id' => 9999,
        ] as $field => $value) {
            $this->post(route('admin.distribution-batches.store'), array_replace($data, [$field => $value]))
                ->assertSessionHasErrors($field);
        }

        $this->post(route('admin.distribution-batches.store'), $data)->assertSessionHasErrors('application_ids');
        $application->update(['status' => 'rejected']);
        $this->post(route('admin.distribution-batches.store'), $data)->assertSessionHasErrors('application_ids');
        $application->update(['status' => 'approved', 'program_benefit_id' => null]);
        $this->post(route('admin.distribution-batches.store'), $data)->assertSessionHasErrors('application_ids');
        $application->update(['program_benefit_id' => $benefit->id]);

        $this->post(route('admin.distribution-batches.store'), array_replace($data, ['application_ids' => [9999]]))
            ->assertSessionHasErrors('application_ids.0');
        $this->post(route('admin.distribution-batches.store'), array_replace($data, ['application_ids' => [$application->id, $application->id]]))
            ->assertSessionHasErrors('application_ids.0');
        $wrongBenefit = $this->createBenefit($program, 'Different benefit');
        $this->post(route('admin.distribution-batches.store'), array_replace($data, ['program_benefit_id' => $wrongBenefit->id]))
            ->assertSessionHasErrors('application_ids');

        $this->assertDatabaseCount('distribution_batches', 0);
        $this->assertDatabaseCount('distribution_assignments', 0);
        $batch = $this->createDistributionBatch($application);
        $this->post(route('admin.distribution-batches.store'), $data)->assertSessionHasErrors('application_ids');

        $anotherBatch = $batch->replicate();
        $anotherBatch->save();
        $inserted = \Illuminate\Support\Facades\DB::table('distribution_assignments')->insertOrIgnore([
            'distribution_batch_id' => $anotherBatch->id,
            'application_id' => $application->id,
            'active_application_id' => $application->id,
        ]);
        $this->assertSame(0, $inserted);
        $this->assertDatabaseCount('distribution_assignments', 1);
    }

    public function test_archived_benefit_and_inactive_farmer_can_use_web_batch_release(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        [$farmerUser, $farmer] = $this->createFarmer();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Historical benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $benefit->forceFill(['archived_at' => now()])->save();
        $farmer->forceFill(['is_active' => false])->save();

        $this->actingAs($farmerUser)
            ->postJson(route('admin.distribution-batches.store'), $this->distributionBatchData($application))
            ->assertForbidden();
        $this->actingAs($this->createAdmin());
        $batch = $this->createDistributionBatch($application);
        $this->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertSessionHas('success');

        $release = ['application_id' => $application->id, 'distribution_batch_id' => $batch->id];
        $this->post(route('admin.distributions.release'), $release)->assertSessionHasErrors('confirm_release');
        $this->assertDatabaseCount('distributions', 0);
        $this->post(route('admin.distributions.release'), $release + ['confirm_release' => '1'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('distributions', $release);
    }

    public function test_batch_release_rolls_back_when_recording_the_audit_fails(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        [, $farmer] = $this->createFarmer();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Batch benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $batch = $this->createDistributionBatch($application);
        $this->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertSessionHas('success');

        $this->mock(\App\Services\AuditLogService::class)
            ->shouldReceive('log')->once()->andThrow(new \RuntimeException('Audit unavailable'));

        try {
            app(\App\Services\DistributionReleaseService::class)->release(
                admin: $admin,
                applicationId: $application->id,
                notes: null,
                batchId: $batch->id,
            );
            $this->fail('The audit failure should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
        }

        $this->assertDatabaseCount('distributions', 0);
        $this->assertNull($batch->assignments()->firstOrFail()->released_at);
        $this->assertSame('published', $batch->fresh()->status);
    }

    public function test_only_assigned_farmers_can_open_distribution_schedule_and_receipt_notifications(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        [$owner, $farmer] = $this->createFarmer();
        [$other] = $this->createFarmer();
        $admin = $this->createAdmin();
        $program = $this->createProgram('Seed Subsidies');
        $benefit = $this->createBenefit($program, 'Distribution notification benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $this->actingAs($admin);
        $batch = $this->createDistributionBatch($application);
        $batch->update(['instructions' => 'Bring your application reference.']);
        $assignment = $batch->assignments()->sole();
        $schedule = route('farmer.notifications.distribution-schedule', $assignment);

        $this->actingAs($owner)->get($schedule)->assertNotFound();
        $this->actingAs($admin)
            ->post(route('admin.distribution-batches.publish', $batch), ['confirm_publish' => 'yes'])
            ->assertSessionHas('success');

        $this->actingAs($other)->get($schedule)->assertNotFound();
        $this->actingAs($owner)->get(route('farmer.notifications.index'))
            ->assertOk()->assertSee($schedule, false);
        $this->get($schedule)->assertOk()->assertSee($batch->location)
            ->assertSee('Bring your application reference.')->assertSee('Scheduled');

        $this->actingAs($admin)->post(route('admin.distributions.release'), [
            'application_id' => $application->id,
            'distribution_batch_id' => $batch->id,
            'confirm_release' => '1',
        ])->assertSessionHas('success');

        $distribution = \App\Models\Distribution::sole();
        $receipt = route('farmer.notifications.distribution-receipt', $distribution);
        $this->actingAs($owner)->get($schedule)->assertRedirect($receipt);
        $this->get(route('farmer.notifications.index'))->assertOk()->assertSee($receipt, false);
        $this->get($receipt)->assertOk()->assertSee('Benefit Received')->assertSee($batch->location);
        $this->actingAs($other)->get($receipt)->assertNotFound();
    }

    public function test_analytics_and_exports_share_the_selected_academic_month(): void
    {
        [, $farmer] = $this->createFarmer();
        [, $outside] = $this->createFarmer();
        $farmer->forceFill(['full_name' => 'January Report Farmer', 'created_at' => '2027-01-31 23:59:59'])->save();
        $outside->forceFill(['full_name' => 'February Excluded Farmer', 'created_at' => '2027-02-01 00:00:00'])->save();

        $program = $this->createProgram();
        $benefit = $this->createBenefit($program, 'Report benefit');
        $application = $this->makeBenefitApplication($farmer, $program, $benefit, 'approved');
        $application->update(['applied_at' => '2027-01-10', 'remarks' => '=1+1']);
        $this->makeBenefitApplication($outside, $program, $benefit)->update(['applied_at' => '2027-02-01']);
        $admin = $this->createAdmin();
        \App\Models\Distribution::create([
            'farmer_id' => $farmer->id, 'program_id' => $program->id, 'program_benefit_id' => $benefit->id,
            'application_id' => $application->id, 'released_by' => $admin->id, 'distributed_at' => '2027-01-12',
        ])->forceFill(['created_at' => '2027-01-12'])->save();
        $this->actingAs($admin);
        $filters = ['academic_year' => '2026-2027', 'month' => '01'];

        $this->get(route('admin.analytics.index', $filters))->assertOk()
            ->assertViewHas('totalFarmers', 1)->assertViewHas('totalDistributions', 1)
            ->assertSee('January 2027')->assertSee('month=01');

        $pdf = $this->get(route('admin.reports.pdf', $filters))->assertOk()->assertDownload('fams-report-2027-01.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $excel = $this->get(route('admin.reports.excel', $filters))->assertOk()->assertDownload('fams-report-2027-01.xlsx');
        $path = $excel->baseResponse->getFile()->getPathname();
        try {
            $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $this->assertSame(['Summary', 'Farmers', 'Programs', 'Applications', 'Distributions'], $workbook->getSheetNames());
            $applicationSheet = $workbook->getSheetByName('Applications');
            $this->assertSame(2, $applicationSheet->getHighestDataRow());
            $this->assertSame('January Report Farmer', $applicationSheet->getCell('B2')->getValue());
            $this->assertSame('=1+1', $applicationSheet->getCell('I2')->getValue());
            $this->assertSame('s', $applicationSheet->getCell('I2')->getDataType());
            $this->assertSame(2, $workbook->getSheetByName('Farmers')->getHighestDataRow());
            $this->assertSame(2, $workbook->getSheetByName('Distributions')->getHighestDataRow());
        } finally {
            unlink($path);
        }

        $benefit->forceFill(['archived_at' => now()])->save();

        $this->get(route('admin.analytics.index', $filters))->assertOk()
            ->assertViewHas('totalDistributions', 0)
            ->assertViewHas('distributionLogs', fn ($logs) => $logs->isEmpty())
            ->assertSee($program->name);

        $program->forceFill(['archived_at' => now()])->save();

        $this->get(route('admin.analytics.index', $filters))->assertOk()
            ->assertDontSee($program->name);
    }

    private function createDistributionBatch(Application $application): \App\Models\DistributionBatch
    {
        $this->post(route('admin.distribution-batches.store'), $this->distributionBatchData($application))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        return \App\Models\DistributionBatch::latest('id')->firstOrFail();
    }

    private function distributionBatchData(Application $application): array
    {
        return [
            'name' => 'Municipal distribution',
            'program_id' => $application->program_id,
            'program_benefit_id' => $application->program_benefit_id,
            'application_ids' => [$application->id],
            'scheduled_date' => today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'location' => 'Municipal Hall',
        ];
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
    private function fakeDecisionGmail(): void
    {
        config([
            'mail.default' => 'gmail-api',
            'services.distribution_gmail.sender' => 'fams@example.com',
            'services.distribution_gmail.client_id' => 'test-client',
            'services.distribution_gmail.client_secret' => 'test-secret',
            'services.distribution_gmail.refresh_token' => 'test-refresh',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access']),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response(['id' => 'test-message']),
        ]);
    }

    private function assertGmailDecisions(array $expected): void
    {
        $requests = Http::recorded(fn ($request) => str_ends_with($request->url(), '/messages/send'))->values();
        $this->assertCount(count($expected), $requests);

        foreach ($expected as $index => [$subject, $email, $body]) {
            $raw = $requests[$index][0]['raw'];
            $message = quoted_printable_decode(base64_decode(strtr($raw, '-_', '+/')));
            $this->assertStringContainsString("Subject: FAMS | {$subject}", $message);
            $this->assertStringContainsString("To: {$email}", $message);
            $this->assertStringContainsString($body, $message);
        }
    }

}
