<?php

namespace App\Services;

use App\Models\Application;
use App\Models\TrainingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FarmerDecisionMailer
{
    public function application(Application $application): void
    {
        $application->loadMissing('farmer.user', 'program', 'benefit');
        $program = $application->program?->name ?? 'FAMS program';
        $benefit = $application->benefit ? "\nBenefit: {$application->benefit->name}" : '';

        $this->send(
            $application->farmer?->user,
            'FAMS | Application '.ucfirst($application->status),
            "The MAO has {$application->status} your application.\n\nProgram: {$program}{$benefit}\n\nView your decision details in FAMS: "
                .route('farmer.applications.index').'#application-'.$application->id,
            $application
        );
    }

    public function training(TrainingRegistration $registration): void
    {
        $registration->loadMissing('farmer.user', 'trainingSession');
        $session = $registration->trainingSession;
        $details = "Training: {$session->title}";

        if ($registration->status === 'confirmed') {
            $start = substr($session->start_time, 0, 5);
            $end = substr($session->end_time, 0, 5);
            $details .= "\nDate: ".$session->scheduled_date->format('F j, Y')
                ."\nTime: {$start}–{$end} (Philippine time)\nVenue: {$session->venue}";
        }

        $this->send(
            $registration->farmer?->user,
            'FAMS | Training registration '.ucfirst($registration->status),
            "The MAO has {$registration->status} your registration.\n\n{$details}\n\nView details in FAMS: "
                .route('farmer.trainings.registration', $registration),
            $registration
        );
    }

    private function send(?User $recipient, string $subject, string $text, Application|TrainingRegistration $record): void
    {
        if (! $recipient?->hasVerifiedEmail() || in_array(config('mail.default'), ['log', 'array'], true)) {
            return;
        }

        try {
            Mail::raw($text."\n\nFarmer Assistance Management System (FAMS)", function ($message) use ($recipient, $subject) {
                $message->to($recipient->email)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::warning('FAMS decision email could not be sent.', [
                'record' => class_basename($record),
                'id' => $record->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }
}
