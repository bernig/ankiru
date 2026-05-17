<?php

namespace App\Observers;

use App\Mail\NewFileMail;
use App\Models\CsvDraft;
use Illuminate\Support\Facades\Mail;

class CsvDraftObserver
{
    public function created(CsvDraft $csvDraft): void
    {
        Mail::to(config('contact.reception_email'))
            ->queue(new NewFileMail($csvDraft->load('user')));
    }
}
