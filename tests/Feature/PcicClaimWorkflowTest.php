<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Farmer;
use App\Models\PcicDamageIncident;
use App\Models\PcicInsuranceCoverage;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PcicClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function setupClaim(string $verificationStatus = 'verified'): array
    {
        Storage::fake('private');

        $user = User::factory()->create(['role' => 'farmer']);

        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'PCIC Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_status' => true,
            'rsbsa_number' => 'RSBSA-PCIC-' . Str::random(8),
            'qr_code_token' => (string) Str::uuid(),
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'PCIC Crop Insurance',
            'description' => 'PCIC test program',
            'is_active' => false,
            'start_at' => now()->subDays(30),
            'end_at' => now()->subDay(),
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Claim for Indemnity',
            'description' => 'PCIC claim',
            'always_active' => true,

        ]);

        $benefit->eligibility_configured_at = now();
        $benefit->save();

        $coverage = PcicInsuranceCoverage::create([
            'farmer_id' => $farmer->id,
            'policy_number' => 'PCIC-TEST-' . Str::random(8),
            'crop_type' => 'Rice',
            'area_insured' => 1.5,
            'coverage_start_date' => now()->subMonths(3)->toDateString(),
            'coverage_end_date' => now()->addMonth()->toDateString(),
            'verification_status' => $verificationStatus,
            'verified_by' => $verificationStatus === 'verified' ? $admin->id : null,
            'verified_at' => $verificationStatus === 'verified' ? now() : null,
            'verification_evidence_reference' => 'PCIC-EVIDENCE-001',
            'verification_notes' => 'Verified test evidence',
        ]);

        return [$user, $farmer, $program, $benefit, $coverage];
    }

    public function test_pcic_new_application_enforces_the_combined_lot_area_limit(): void
    {
        [$user, $farmer, $program] = $this->setupClaim();
        $farmer->update(['land_size' => 2.25]);
        $program->update(['is_active' => true, 'end_at' => now()->addDay()]);

        $benefit = $program->benefits()->create(['name' => 'New Application']);
        $benefit->eligibility_configured_at = now();
        $benefit->save();

        $data = [
            'program_benefit_id' => $benefit->id,
            'variety_planted' => 'Inbred',
            'actual_planting_date' => now()->subDays(30)->toDateString(),
            'expected_harvest_date' => now()->addDays(30)->toDateString(),
        ];

        $lot = [
            'area_to_insure' => 1.75,
            'adjacent_north' => 'Farm A',
            'adjacent_south' => 'Farm B',
            'adjacent_east' => 'Farm C',
            'adjacent_west' => 'Farm D',
        ];
        $secondLot = array_replace($lot, ['area_to_insure' => 0.5, 'adjacent_north' => 'Irrigation canal']);

        $this->actingAs($user)
            ->postJson(route('farmer.applications.store', $program), $data + ['lots' => [$lot, []]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lots.1.area_to_insure');

        $this->postJson(route('farmer.applications.store', $program), $data + ['lots' => array_fill(0, 4, $lot)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lots');

        $excessLot = array_replace($secondLot, ['area_to_insure' => 0.51]);
        $this->postJson(route('farmer.applications.store', $program), $data + ['lots' => [$lot, $excessLot]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lots');

        $this->assertDatabaseCount('applications', 0);

        $this->post(route('farmer.applications.store', $program), $data + ['lots' => [$lot, $secondLot]])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('applications', 1);
        $this->assertEquals([$lot, $secondLot], \App\Models\Application::sole()->detail->details['lots']);
    }

    public function test_claim_date_of_loss_must_be_within_seven_days(): void
    {
        [$user, , $program, $benefit] = $this->setupClaim();
        $data = $this->claimData($benefit);
        $route = route('farmer.applications.store', $program);
        $today = today('Asia/Manila');

        $this->actingAs($user);
        foreach ([$today->copy()->subDays(8), $today->copy()->addDay()] as $invalidDate) {
            $this->post($route, array_replace($data, ['date_of_loss' => $invalidDate->toDateString()]))
                ->assertSessionHasErrors('date_of_loss');
        }
        $this->assertDatabaseCount('applications', 0);

        $this->post($route, array_replace($data, ['date_of_loss' => $today->subDays(7)->toDateString()]))
            ->assertSessionHas('success');
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_indemnity_claim_rejects_damaged_area_above_profile_land_size(): void
    {
        [$user, $farmer, $program, $benefit] = $this->setupClaim();
        $this->actingAs($user)->post(route('farmer.applications.store', $program), [
            'program_benefit_id' => $benefit->id,
            'date_of_loss' => now()->subDay()->toDateString(),
            'cause_of_loss' => 'Flood',
            'area_damaged' => 1.51,
            'damage_photo' => UploadedFile::fake()->createWithContent(
                'damage.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jR1sAAAAASUVORK5CYII=')
            ),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('area_damaged');

        $this->assertDatabaseCount('applications', 0);
    }

    private function claimData($benefit): array
    {
        return [
            'program_benefit_id' => $benefit->id,
            'variety_planted' => 'Inbred',
            'actual_planting_date' => now()->subDays(60)->toDateString(),
            'expected_harvest_date' => now()->addDays(30)->toDateString(),
            'adjacent_north' => 'Farm A',
            'adjacent_south' => 'Farm B',
            'adjacent_east' => 'Farm C',
            'adjacent_west' => 'Farm D',
            'date_of_loss' => now()->subDays(5)->toDateString(),
            'cause_of_loss' => 'Flood',
            'area_damaged' => 0.5,
            'damage_photo' => UploadedFile::fake()->createWithContent(
                'damage.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jR1sAAAAASUVORK5CYII=')
            ),
        ];
    }

    public function test_verified_farmer_can_submit_claim_when_program_is_inactive(): void
    {
        [$user, $farmer, $program, $benefit, $coverage] = $this->setupClaim();

        $this->actingAs($user)
            ->post(route('farmer.applications.store', $program), $this->claimData($benefit))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'farmer_id' => $farmer->id,
            'program_benefit_id' => $benefit->id,
        ]);

        $this->assertDatabaseHas('pcic_damage_incidents', [
            'farmer_id' => $farmer->id,
            'pcic_insurance_coverage_id' => $coverage->id,
            'cause_of_loss' => 'Flood',
        ]);

        $this->assertSame(1, PcicDamageIncident::count());
    }

    public function test_claim_form_omits_removed_fields_and_rejects_ambiguous_coverage(): void
    {
        [$user, , $program, $benefit, $coverage] = $this->setupClaim();
        $secondPolicy = $coverage->replicate();
        $secondPolicy->policy_number = 'SECOND-POLICY';
        $secondPolicy->save();

        $this->actingAs($user)->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertSee('File Claim')
            ->assertDontSee('Verified insurance coverage')
            ->assertDontSee('Coverage details')
            ->assertDontSee('Degree of Damage (%)');

        $this->post(route('farmer.applications.store', $program), $this->claimData($benefit))
            ->assertSessionHas('error', 'More than one verified policy matches this claim. Please contact the administrator to confirm which policy applies.');

        $this->assertDatabaseCount('pcic_damage_incidents', 0);
    }

    public function test_farmer_can_submit_before_insurance_is_verified(): void
    {
        [$user, , $program, $benefit, $coverage] = $this->setupClaim('pending');

        $this->actingAs($user)
            ->post(route('farmer.applications.store', $program), $this->claimData($benefit))
            ->assertSessionHas('success');

        $incident = PcicDamageIncident::sole();
        $this->assertNull($incident->pcic_insurance_coverage_id);
        $this->assertSame('pending', $incident->status);
        $this->assertSame('pending', $incident->application->status);
        $this->post(route('farmer.applications.store', $program), $this->claimData($benefit))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('pcic_damage_incidents', 1);

        $admin = $this->makeAdmin();
        $verify = route('admin.pcic-incidents.verify', $incident);
        $request = ['verification_confirmed' => '1', 'review_notes' => 'Insurance and incident details reviewed.'];
        $this->actingAs($admin)->patch($verify, $request)->assertUnprocessable();

        $coverage->update(['verification_status' => 'verified', 'verified_by' => $admin->id, 'verified_at' => now()]);
        $this->patch($verify, $request)->assertSessionHas('success');
        $this->assertDatabaseHas('pcic_damage_incidents', [
            'id' => $incident->id, 'pcic_insurance_coverage_id' => $coverage->id, 'status' => 'verified',
        ]);
    }

    public function test_duplicate_claim_is_rejected(): void
    {
        [$user, , $program, $benefit] = $this->setupClaim();

        $this->actingAs($user)
            ->post(route('farmer.applications.store', $program), $this->claimData($benefit))
            ->assertSessionHas('success');

        $this->post(route('farmer.applications.store', $program), $this->claimData($benefit))
            ->assertSessionHas('error');

        $this->assertSame(1, Application::count());
        $this->assertSame(1, PcicDamageIncident::count());
    }

    public function test_claim_without_damage_photo_fails_validation(): void
    {
        [$user, , $program, $benefit] = $this->setupClaim();

        $data = $this->claimData($benefit);
        unset($data['damage_photo']);

        $this->actingAs($user)
            ->post(route('farmer.applications.store', $program), $data)
            ->assertSessionHasErrors('damage_photo');

        $this->assertSame(0, Application::count());
    }
    private function submitClaimAndGetIncident(): array
    {
        [$user, $farmer, $program, $benefit, $coverage] = $this->setupClaim();

        $this->actingAs($user)
            ->post(
                route('farmer.applications.store', $program),
                $this->claimData($benefit)
            )
            ->assertSessionHas('success');

        return [$user, $farmer, PcicDamageIncident::firstOrFail()];
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
    }

    public function test_admin_can_verify_pending_pcic_incident(): void
    {
        [$user, $farmer, $incident] = $this->submitClaimAndGetIncident();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.pcic-incidents.verify', $incident), [
                'verification_confirmed' => '1',
                'review_notes' => 'Insurance and incident details reviewed.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pcic_damage_incidents', [
            'id' => $incident->id,
            'status' => 'verified',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_release_update_is_visible_only_to_the_claim_owner(): void
    {
        [$user, $farmer, $incident] = $this->submitClaimAndGetIncident();
        $admin = $this->makeAdmin();
        $application = $incident->application;

        $this->actingAs($admin)
            ->patch(route('admin.pcic-incidents.verify', $incident), [
                'verification_confirmed' => '1',
                'review_notes' => 'Internal review notes for the administrator.',
            ])
            ->assertSessionHas('success');

        $update = [
            'status' => 'release_scheduled',
            'pcic_reference' => 'PCIC-DECISION-001',
            'remarks' => 'Bring a valid ID when collecting the indemnity.',
            'expected_release_date' => now()->addWeek()->toDateString(),
        ];

        $url = route('admin.applications.pcic-tracking', $application);
        $this->post($url, $update)->assertSessionHas('success');
        $this->post($url, $update)->assertSessionHas('success');
        $this->assertDatabaseCount('pcic_tracking_updates', 2);

        $this->actingAs($user)
            ->get(route('farmer.applications.index'))
            ->assertOk()
            ->assertSee('Release scheduled')
            ->assertSee($update['remarks'])
            ->assertDontSee('Internal review notes');

        $this->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertSee($update['remarks'])
            ->assertSee('#application-'.$application->id);

        $other = User::factory()->create(['role' => 'farmer']);
        $otherFarmer = $farmer->replicate(['rsbsa_number', 'qr_code_token']);
        $otherFarmer->user_id = $other->id;
        $otherFarmer->rsbsa_number = 'RSBSA-PCIC-OTHER';
        $otherFarmer->save();

        $this->actingAs($other)->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertDontSee($update['remarks']);
    }

    public function test_admin_can_reject_pending_pcic_incident(): void
    {
        [$user, $farmer, $incident] = $this->submitClaimAndGetIncident();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.pcic-incidents.reject', $incident), [
                'rejection_reason' => 'Incident evidence does not support verification.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pcic_damage_incidents', [
            'id' => $incident->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_farmer_cannot_verify_pcic_incident(): void
    {
        [$user, $farmer, $incident] = $this->submitClaimAndGetIncident();

        $this->actingAs($user)
            ->patch(route('admin.pcic-incidents.verify', $incident), [
                'verification_confirmed' => '1',
                'review_notes' => 'Unauthorized verification attempt.',
            ])
            ->assertForbidden();

        $this->assertSame('pending', $incident->fresh()->status);
    }

    public function test_verified_pcic_incident_cannot_be_verified_again(): void
    {
        [$user, $farmer, $incident] = $this->submitClaimAndGetIncident();
        $admin = $this->makeAdmin();

        $data = [
            'verification_confirmed' => '1',
            'review_notes' => 'Insurance and incident details reviewed.',
        ];

        $this->actingAs($admin)
            ->patch(route('admin.pcic-incidents.verify', $incident), $data)
            ->assertSessionHas('success');

        $this->patch(route('admin.pcic-incidents.verify', $incident), $data)
            ->assertStatus(409);

        $this->assertSame('verified', $incident->fresh()->status);
    }

    public function test_claim_accepts_three_private_photos_and_rejects_four(): void
    {
        [$user, $farmer, $program, $benefit] = $this->setupClaim();
        $data = $this->claimData($benefit);
        $photo = $data['damage_photo'];
        $route = route('farmer.applications.store', $program);

        $this->actingAs($user)->post($route, array_replace($data, [
            'damage_photo' => array_fill(0, 4, $photo),
        ]))->assertSessionHasErrors('damage_photo');
        $this->assertDatabaseCount('applications', 0);

        $this->post($route, array_replace($data, [
            'damage_photo' => array_fill(0, 3, $photo),
        ]))->assertSessionHas('success');

        $incident = \App\Models\PcicDamageIncident::sole();
        $this->assertCount(3, $incident->damagePhotoPaths());
        Storage::disk('private')->assertExists($incident->damagePhotoPaths());

        $this->actingAs($this->makeAdmin());
        foreach ([1, 2, 3] as $index) {
            $this->get(route('admin.pcic-incidents.damage-photo', [$incident, $index]))->assertOk();
        }
        $this->get(route('admin.pcic-incidents.damage-photo', [$incident, 4]))->assertNotFound();
    }

    public function test_private_damage_photo_is_admin_only(): void
    {
        [$user, $farmer, $incident] = $this->submitClaimAndGetIncident();

        $this->actingAs($user)
            ->get(route('admin.pcic-incidents.damage-photo', $incident))
            ->assertForbidden();

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.pcic-incidents.damage-photo', $incident));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['private', 'no-store', 'max-age=0'], array_map('trim', explode(',', $response->headers->get('Cache-Control'))));
    }
}
