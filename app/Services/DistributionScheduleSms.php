<?php

namespace App\Services;

use App\Models\DistributionBatch;
use App\Models\SmsNotification;
use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\Sns\SnsClient;
use Illuminate\Support\Collection;
use Throwable;

class DistributionScheduleSms
{
    public function record(DistributionBatch $batch, Collection $assignments): Collection
    {
        if (! config('services.distribution_sms.enabled')) {
            return collect();
        }

        $date = $batch->scheduled_date->format('M j, Y');
        $start = substr($batch->start_time, 0, 5);
        $end = substr($batch->end_time, 0, 5);
        $message = "FAMS distribution: {$batch->benefit->name}. {$date}, {$start}-{$end}, at {$batch->location}. See FAMS Notifications for details.";

        return $assignments->pluck('application.farmer')->unique('id')->map(
            fn ($farmer) => SmsNotification::create([
                'distribution_batch_id' => $batch->id,
                'farmer_id' => $farmer->id,
                'recipient' => $this->mobileNumber($farmer->contact_number),
                'message' => $message,
                'status' => 'pending',
            ])
        );
    }

    public function send(SmsNotification $sms): void
    {
        if (! config('services.distribution_sms.enabled') || $sms->distributionBatch?->status !== 'published') {
            return;
        }

        $claimed = SmsNotification::whereKey($sms->id)
            ->whereIn('status', ['pending', 'failed'])
            ->update(['status' => 'sending']);

        if (! $claimed) {
            return;
        }

        $recipient = $this->mobileNumber($sms->farmer?->contact_number);

        if ($recipient === '') {
            $sms->update([
                'status' => 'failed',
                'response_data' => ['error' => 'A valid Philippine mobile number is required.'],
            ]);

            return;
        }

        $sms->update(['recipient' => $recipient]);

        try {
            $result = app(SnsClient::class)->publish([
                'PhoneNumber' => $recipient,
                'Message' => $sms->message,
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SMSType' => ['DataType' => 'String', 'StringValue' => 'Transactional'],
                ],
            ]);
        } catch (AwsException $exception) {
            // A timeout or server error can occur after AWS accepts the text.
            $status = $exception->getStatusCode();
            $sms->update([
                'status' => $status >= 400 && $status < 500 ? 'failed' : 'unknown',
                'response_data' => ['error' => $exception->getAwsErrorCode() ?? 'AWS response unavailable'],
            ]);

            return;
        } catch (CredentialsException) {
            $sms->update([
                'status' => 'failed',
                'response_data' => ['error' => 'AWS credentials are unavailable.'],
            ]);

            return;
        } catch (Throwable $exception) {
            report($exception);
            $sms->update([
                'status' => 'unknown',
                'response_data' => ['error' => 'SMS submission could not be confirmed.'],
            ]);

            return;
        }

        $sms->update([
            'status' => 'submitted',
            'response_data' => ['message_id' => $result->get('MessageId')],
            'sent_at' => now(),
        ]);
    }

    private function mobileNumber(?string $number): string
    {
        $digits = preg_replace('/\D/', '', $number ?? '');

        return preg_match('/^(?:63|0)?(9\d{9})$/D', $digits, $matches)
            ? '+63'.$matches[1]
            : '';
    }
}
