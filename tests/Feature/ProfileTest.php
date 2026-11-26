<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);

        Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Farmer',
            'address' => 'Bulan, Sorsogon',
            'barangay' => 'Zone I Pob.',
            'contact_number' => '09123456789',
            'birthdate' => '2000-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_status' => true,
            'rsbsa_number' => 'RSBSA-TEST-PROFILE-001',
            'profile_completed' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/farmer/profile');

        $response->assertOk();
    }

    public function test_farmer_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);

        Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Old Farmer Name',
            'address' => 'Old Address',
            'barangay' => 'Zone I Pob.',
            'contact_number' => '09123456789',
            'birthdate' => '2000-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_status' => true,
            'rsbsa_number' => 'RSBSA-TEST-PROFILE-002',
            'profile_completed' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/farmer/profile', [
                'full_name' => 'Test Farmer',
                'address' => 'Bulan, Sorsogon',
                'barangay' => 'Zone I Pob.',
                'contact_number' => '09123456789',
                'birthdate' => '2000-01-01',
                'crop_type' => 'Rice',
                'land_size' => 1.5,
                'rsbsa_status' => true,
                'rsbsa_number' => 'RSBSA-TEST-PROFILE-002',
            ]);

        $response->assertSessionHasNoErrors();

        $response->assertRedirect();
    }

    public function test_farmer_downloads_a_centered_png_from_an_existing_svg_qr_code(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'farmer']);
        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Farmer',
            'address' => 'Bulan, Sorsogon',
            'barangay' => 'Zone I Pob.',
            'contact_number' => '09123456789',
            'birthdate' => '2000-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-TEST-QR-DOWNLOAD',
            'profile_completed' => true,
        ]);
        app(QrCodeService::class)->generateForFarmer($farmer);
        $token = $farmer->qr_code_token;

        $response = $this->actingAs($user)->get(route('farmer.qr-code.download'))
            ->assertDownload("fams-qr-{$farmer->id}.png")
            ->assertHeader('Content-Type', 'image/png');
        $image = imagecreatefromstring($response->streamedContent());

        $this->assertSame(720, imagesx($image));
        $this->assertSame(720, imagesy($image));

        $margin = 0;
        while ($margin < 360 && imagecolorat($image, $margin, $margin) === 0xffffff) {
            $margin++;
        }

        $this->assertGreaterThan(0, $margin);
        $this->assertLessThan(360, $margin);
        $this->assertSame(0, imagecolorat($image, 719 - $margin, $margin));
        $this->assertSame(0, imagecolorat($image, $margin, 719 - $margin));
        $this->assertSame(0xffffff, imagecolorat($image, $margin - 1, $margin));
        $this->assertSame($token, $farmer->fresh()->qr_code_token);
    }
}
