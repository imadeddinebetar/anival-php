<?php

namespace App\Jobs;

class SendEmailJob
{
    public function handle(array $data): void
    {
        $to = $data['to'] ?? null;
        $subject = $data['subject'] ?? 'No Subject';
        $message = $data['message'] ?? '';

        if (!$to) {
            throw new \InvalidArgumentException('Email recipient is required');
        }

        logger()->info('Sending email', ['to' => $to, 'subject' => $subject]);

        // TODO: Implement actual mail dispatch (e.g. via SMTP or a mail service)

        logger()->info('Email sent successfully', ['to' => $to]);
    }
}
