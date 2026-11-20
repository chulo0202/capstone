<?php

namespace Tests\Feature\Admin;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Tests\TestCase;

class FarmerCreationRsbsaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_farmer_creation_rejects_a_missing_rsbsa_number(): void
    {
        $admin = $this->createAdmin();
        $data = $this->validFarmerData();
        unset($data['rsbsa_number']);

        $this->actingAs($admin)
            ->post(route('admin.farmers.store'), $data)
            ->assertSessionHasErrors('rsbsa_number');

        $this->assertDatabaseCount('farmers', 0);
    }

    public function test_admin_farmer_creation_rejects_a_duplicate_rsbsa_number(): void
    {
        $admin = $this->createAdmin();
        $existingUser = User::factory()->create(['role' => 'farmer']);
        $existingRsbsaNumber = 'RSBSA-ADMIN-DUPLICATE-001';

        Farmer::create([
            'user_id' => $existingUser->id,
            'full_name' => 'Existing Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Gate',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => $existingRsbsaNumber,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.farmers.store'), $this->validFarmerData([
                'rsbsa_number' => $existingRsbsaNumber,
            ]))
            ->assertSessionHasErrors('rsbsa_number');

        $this->assertDatabaseCount('farmers', 1);
    }

    public function test_admin_farmer_creation_accepts_a_valid_unique_rsbsa_number(): void
    {
        $admin = $this->createAdmin();
        $data = $this->validFarmerData();

        $this->actingAs($admin)
            ->post(route('admin.farmers.store'), $data)
            ->assertRedirect(route('admin.farmers.index'));

        $this->assertDatabaseHas('farmers', [
            'full_name' => $data['full_name'],
            'rsbsa_number' => $data['rsbsa_number'],
        ]);
    }

    public function test_admin_can_read_name_and_birthdate_from_national_id(): void
    {
        Process::fake(fn () => Process::result(output: <<<'OCR'
Apelyido / Last Name

DELA CRUZ
Mga Pangalan / Given Names

JUAN MIGUEL
Gitnang Apelyido / Middle Name

SANTOS
Petsa ng Kapanganakan / Date of Birth

JANUARY 02, 1990
OCR));

        $this->actingAs($this->createAdmin())
            ->postJson(route('admin.farmers.national-id.ocr'), [
                'valid_id' => UploadedFile::fake()->image('national-id.png'),
            ])
            ->assertOk()
            ->assertExactJson([
                'full_name' => 'JUAN MIGUEL SANTOS DELA CRUZ',
                'birthdate' => '1990-01-02',
            ]);

        $this->assertDatabaseCount('farmers', 0);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);
    }

    private function validFarmerData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Admin Created Farmer',
            'email' => 'farmer-' . Str::uuid() . '@example.test',
            'password' => 'Farmer123!',
            'full_name' => 'Test Admin Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Gate',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => '1.50',
            'rsbsa_number' => 'RSBSA-ADMIN-' . Str::uuid(),
        ], $overrides);
    }
}
