<?php

namespace Tests\Unit;

use App\Models\SmsNotification;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_semaphore_response_is_saved_as_queued(): void
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

        $notification = app(SmsService::class)->send(
            '09171234567',
            'Test message only'
        );

        $this->assertSame('queued', $notification->status);
        $this->assertSame('639171234567', $notification->recipient);
        $this->assertNull($notification->sent_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages')
                && $request['apikey'] === 'fake-test-key'
                && $request['number'] === '639171234567'
                && $request['message'] === 'Test message only';
        });
    }

    public function test_failed_semaphore_response_is_saved_as_failed(): void
    {
        config([
            'semaphore.api_key' => 'fake-test-key',
            'semaphore.base_url' => 'https://api.semaphore.co/api/v4',
        ]);

        Http::fake([
            'api.semaphore.co/*' => Http::response([
                'error' => 'Test failure',
            ], 400),
        ]);

        $notification = app(SmsService::class)->send(
            '09171234567',
            'Test failure message'
        );

        $this->assertSame('failed', $notification->status);
        $this->assertNull($notification->sent_at);

        Http::assertSentCount(1);
    }
}