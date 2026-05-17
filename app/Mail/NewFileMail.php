<?php

namespace App\Mail;

use App\Models\CsvDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewFileMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly CsvDraft $draft) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] Nouveau fichier créé',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-file',
        );
    }
}
