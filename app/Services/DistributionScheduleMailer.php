<?php

namespace App\Services;

use App\Models\DistributionBatch;
use App\Models\DistributionScheduleEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Email;
use Throwable;

class DistributionScheduleMailer
{
    private ?string $accessToken = null;

    public function record(DistributionBatch $batch, Collection $assignments): Collection
    {
        if (! config('services.distribution_gmail.enabled')) {
            return collect();
        }

        return $assignments->pluck('application.farmer')->unique('id')->map(
            fn ($farmer) => DistributionScheduleEmail::create([
                'distribution_batch_id' => $batch->id,
                'farmer_id' => $farmer->id,
                'status' => $farmer->user?->hasVerifiedEmail() ? 'pending' : 'unverified',
            ])
        );
    }

    public function send(DistributionScheduleEmail $delivery): void
    {
        if (! config('services.distribution_gmail.enabled') || $delivery->batch?->status !== 'published') {
            return;
        }

        $claimed = DistributionScheduleEmail::whereKey($delivery->id)
            ->whereIn('status', ['pending', 'failed'])
            ->update(['status' => 'sending']);

        if (! $claimed) {
            return;
        }

        $recipient = $delivery->farmer?->user;
        if (! $recipient?->hasVerifiedEmail()) {
            $delivery->update(['status' => 'unverified']);

            return;
        }

        try {
            $token = $this->accessToken ??= $this->refreshToken();
            $raw = $this->message($delivery->batch, $recipient->email);
        } catch (Throwable) {
            $delivery->update(['status' => 'failed']);

            return;
        }

        try {
            $response = Http::withToken($token)->timeout(8)
                ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw]);
        } catch (Throwable) {
            // A transport error can occur after Gmail has already accepted the email.
            $delivery->update(['status' => 'unknown']);

            return;
        }

        $messageId = $response->json('id');
        $accepted = $response->successful() && is_string($messageId) && $messageId !== '';

        if ($response->unauthorized()) {
            $this->accessToken = null;
        }

        $rejected = $response->clientError() && $response->status() !== 408;

        $delivery->update([
            'status' => $accepted ? 'accepted' : ($rejected ? 'failed' : 'unknown'),
            'gmail_message_id' => $accepted ? $messageId : null,
        ]);
    }

    private function refreshToken(): string
    {
        $config = config('services.distribution_gmail');

        return Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $config['refresh_token'],
            'grant_type' => 'refresh_token',
        ])->throw()->json('access_token');
    }

    private function message(DistributionBatch $batch, string $recipient): string
    {
        $date = $batch->scheduled_date->format('F j, Y');
        $start = substr($batch->start_time, 0, 5);
        $end = substr($batch->end_time, 0, 5);
        $text = implode("\n", [
            "Your benefit distribution has been scheduled.",
            '',
            "Benefit: {$batch->benefit->name}",
            "Date: {$date}",
            "Time: {$start}–{$end} (Philippine time)",
            "Location: {$batch->location}",
            "Instructions: ".($batch->instructions ?: 'See FAMS Notifications for updates.'),
            '',
            'FAMS Notifications: '.route('farmer.notifications.index'),
        ]);

        $email = (new Email())
            ->from(config('services.distribution_gmail.sender'))
            ->to($recipient)
            ->subject('FAMS Distribution Schedule – '.$batch->benefit->name)
            ->text($text);

        return rtrim(strtr(base64_encode($email->toString()), '+/', '-_'), '=');
    }
}
