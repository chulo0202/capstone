<?php

namespace Tests\Feature\Farmer;

use App\Models\Announcement;
use App\Models\Farmer;
use App\Models\SmsNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_badge_counts_visible_updates_and_remembers_which_were_opened(): void
    {
        $farmer = $this->createFarmer('RSBSA-NOTIFICATIONS-001');
        $otherFarmer = $this->createFarmer('RSBSA-NOTIFICATIONS-002');
        $admin = User::factory()->create(['role' => 'admin']);

        Announcement::create([
            'title' => 'Published update',
            'content' => 'Announcement for all farmers.',
            'publish_date' => now()->subHour(),
            'is_published' => true,
            'created_by' => $admin->id,
        ]);
        Announcement::create([
            'title' => 'Scheduled update',
            'content' => 'This announcement is not yet public.',
            'publish_date' => now()->addDay(),
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        $this->addSms($farmer);
        $this->addSms($otherFarmer);

        $this->actingAs($farmer->user)->get(route('farmer.dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notifications, 2 unread"', false);

        $this->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertSee('Published update')
            ->assertDontSee('Scheduled update')
            ->assertSee('aria-label="Notifications"', false);

        $this->get(route('farmer.dashboard'))
            ->assertSee('aria-label="Notifications"', false);

        $this->addSms($farmer);

        $this->get(route('farmer.dashboard'))
            ->assertSee('aria-label="Notifications, 1 unread"', false);

        $this->actingAs($otherFarmer->user)->get(route('farmer.dashboard'))
            ->assertSee('aria-label="Notifications, 2 unread"', false);
    }

    private function createFarmer(string $number): Farmer
    {
        $user = User::factory()->create(['role' => 'farmer']);

        return Farmer::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'address' => 'Sample Address',
            'barangay' => 'Gate',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1,
            'rsbsa_number' => $number,
            'profile_completed' => true,
            'is_active' => true,
        ]);
    }

    private function addSms(Farmer $farmer): void
    {
        SmsNotification::create([
            'farmer_id' => $farmer->id,
            'recipient' => $farmer->contact_number,
            'message' => 'A farmer-specific update.',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
