<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

class GmailApiTransport extends AbstractTransport
{
    public function __toString(): string
    {
        return 'gmail-api';
    }

    protected function doSend(SentMessage $message): void
    {
        $config = config('services.distribution_gmail');
        foreach (['sender', 'client_id', 'client_secret', 'refresh_token'] as $key) {
            if (blank($config[$key] ?? null)) {
                throw new TransportException('Gmail API credentials are incomplete.');
            }
        }

        $email = $message->getOriginalMessage();
        if (! $email instanceof Email) {
            throw new TransportException('Gmail API requires a MIME email.');
        }

        $email = clone $email;
        $email->from($config['sender']);
        $raw = rtrim(strtr(base64_encode($email->toString()), '+/', '-_'), '=');

        try {
            $oauth = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $config['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);
            $token = $oauth->json('access_token');

            if (! $oauth->successful() || ! is_string($token) || $token === '') {
                throw new TransportException('Gmail API authorization failed.');
            }

            $response = Http::withToken($token)->timeout(8)
                ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw]);
        } catch (ConnectionException) {
            throw new TransportException('Gmail API could not be reached.');
        }

        $messageId = $response->json('id');
        if (! $response->successful() || ! is_string($messageId) || $messageId === '') {
            throw new TransportException('Gmail API did not confirm acceptance (HTTP '.$response->status().').');
        }
    }
}
