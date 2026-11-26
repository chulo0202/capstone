<?php

namespace App\Services;

use App\Models\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class FarmerEmailVerification
{
    public function send(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            return 'email-unchanged';
        }

        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            return 'verification-delivery-failed';
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return 'verification-delivery-failed';
        }

        return 'verification-link-sent';
    }
}
