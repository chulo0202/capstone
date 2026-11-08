<?php

namespace Tests\Feature;

use App\Models\SmsNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_retry_a_failed_sms_without_real_api_call(): void
    {
        config([
            'semaphore.api_key' => 'fake-test-key',
            'semaphore.base_url' => 'https://api.semaphore.co/api/v4',
            'semaphore.sender_name' => 'FAMS',
        ]);

        Http::fake([
            'api.semaphore.co/*' => Http::response([
                [
                    'message_id' => 12345,
                    'status' => 'Queued',
                ],
            ], 200),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $failedNotification = SmsNotification::create([
            'recipient' => '639171234567',
            'message' => 'Test retry message',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.sms.retry', $failedNotification));

        $response->assertRedirect();

        $this->assertDatabaseHas('sms_notifications', [
            'recipient' => '639171234567',
            'message' => 'Test retry message',
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('sms_notifications', [
            'id' => $failedNotification->id,
            'status' => 'failed',
        ]);

        Http::assertSentCount(1);
    }

    public function test_non_admin_cannot_retry_a_failed_sms(): void
    {
        $farmer = User::factory()->create([
            'role' => 'farmer',
        ]);

        $failedNotification = SmsNotification::create([
            'recipient' => '639171234567',
            'message' => 'Test retry message',
            'status' => 'failed',
        ]);

        Http::fake();

        $response = $this->actingAs($farmer)
            ->post(route('admin.sms.retry', $failedNotification));

        $response->assertForbidden();

        Http::assertNothingSent();
    }
}