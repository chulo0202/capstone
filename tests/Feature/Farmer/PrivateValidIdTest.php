<?php

namespace Tests\Feature\Farmer;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateValidIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_farmer_can_access_their_own_valid_id(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $farmer = $this->createFarmer($user, 'valid-ids/farmer-one.pdf');
        Storage::disk('private')->put($farmer->valid_id_path, 'test-valid-id-content');

        $response = $this->actingAs($user)
            ->get(route('farmer.profile.valid-id'));

        $response->assertOk();
        $response->assertStreamedContent('test-valid-id-content');
    }

    public function test_authenticated_farmer_without_valid_id_receives_not_found(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $this->createFarmer($user, null);

        $this->actingAs($user)
            ->get(route('farmer.profile.valid-id'))
            ->assertNotFound();
    }

    public function test_farmer_with_missing_private_file_receives_not_found(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $this->createFarmer($user, 'valid-ids/missing-file.pdf');

        $this->actingAs($user)
            ->get(route('farmer.profile.valid-id'))
            ->assertNotFound();
    }

    public function test_guest_cannot_access_farmer_valid_id_route(): void
    {
        $this->get(route('farmer.profile.valid-id'))
            ->assertRedirect(route('login'));
    }

    public function test_farmer_cannot_access_another_farmers_valid_id(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $farmer = $this->createFarmer($user, 'valid-ids/farmer-one.pdf');
        Storage::disk('private')->put($farmer->valid_id_path, 'authenticated-farmer-valid-id');

        $otherUser = User::factory()->create([
            'role' => 'farmer',
        ]);
        $otherFarmer = $this->createFarmer($otherUser, 'valid-ids/farmer-two.pdf');
        Storage::disk('private')->put($otherFarmer->valid_id_path, 'other-farmer-valid-id');

        $response = $this->actingAs($user)
            ->get(route('farmer.profile.valid-id'));

        $response->assertOk();
        $response->assertStreamedContent('authenticated-farmer-valid-id');
        $this->assertNotSame('other-farmer-valid-id', $response->streamedContent());
    }

    public function test_authorized_admin_can_access_farmer_valid_id(): void
    {
        Storage::fake('private');

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
        $farmerUser = User::factory()->create([
            'role' => 'farmer',
        ]);
        $farmer = $this->createFarmer($farmerUser, 'valid-ids/farmer-admin-view.pdf');
        Storage::disk('private')->put($farmer->valid_id_path, 'admin-visible-valid-id');

        $response = $this->actingAs($admin)
            ->get(route('admin.farmers.valid-id', $farmer));

        $response->assertOk();
        $response->assertStreamedContent('admin-visible-valid-id');
    }

    public function test_farmer_cannot_access_admin_valid_id_route(): void
    {
        Storage::fake('private');

        $farmerUser = User::factory()->create([
            'role' => 'farmer',
        ]);
        $farmer = $this->createFarmer($farmerUser, 'valid-ids/farmer.pdf');

        $this->actingAs($farmerUser)
            ->get(route('admin.farmers.valid-id', $farmer))
            ->assertForbidden();
    }

    private function createFarmer(User $user, ?string $validIdPath): Farmer
    {
        return Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-TEST-'.fake()->unique()->numerify('######'),
            'valid_id_path' => $validIdPath,
            'profile_completed' => true,
        ]);
    }
}
