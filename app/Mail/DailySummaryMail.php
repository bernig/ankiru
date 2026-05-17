<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{new_users: int, new_files: int, translations: int, stress_corrections: int, tts_generations: int}  $stats
     */
    public function __construct(
        public readonly array $stats,
        public readonly string $date,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(__('mail.daily_summary.subject'), config('app.name'), $this->date),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-summary',
        );
    }
}
