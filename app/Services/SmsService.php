<?php

namespace App\Services;

use App\Models\Farmer;
use App\Models\SmsNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $recipient, string $message, ?int $announcementId = null, ?int $farmerId = null): SmsNotification
    {
        $notification = SmsNotification::create([
            'recipient' => $this->formatNumber($recipient),
            'message' => $message,
            'status' => 'pending',
            'announcement_id' => $announcementId,
            'farmer_id' => $farmerId,
        ]);

        $apiKey = config('semaphore.api_key');

        if (! $apiKey) {
            $notification->update([
                'status' => 'failed',
                'response_data' => ['error' => 'Semaphore API key not configured'],
            ]);

            return $notification;
        }

        try {
            $response = Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $notification->recipient,
                'message' => $message,
                'sendername' => config('semaphore.sender_name'),
            ]);

            $notification->update([
                'status' => $response->successful() ? 'sent' : 'failed',
                'response_data' => $response->json(),
                'sent_at' => $response->successful() ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('SMS send failed: '.$e->getMessage());
            $notification->update([
                'status' => 'failed',
                'response_data' => ['error' => $e->getMessage()],
            ]);
        }

        return $notification;
    }

    public function sendAnnouncementSms(int $announcementId, string $title, string $content): void
    {
        $message = "FAMS Alert: {$title} - ".str($content)->limit(120);

        Farmer::where('profile_completed', true)->chunk(50, function ($farmers) use ($announcementId, $message) {
            foreach ($farmers as $farmer) {
                $this->send($farmer->contact_number, $message, $announcementId, $farmer->id);
            }
        });
    }

    protected function formatNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (str_starts_with($number, '09')) {
            return '63'.substr($number, 1);
        }

        if (str_starts_with($number, '9') && strlen($number) === 10) {
            return '63'.$number;
        }

        return $number;
    }
}
