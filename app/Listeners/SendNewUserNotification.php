<?php

namespace App\Listeners;

use App\Mail\NewUserNotificationMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendNewUserNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Registered $event): void
    {
        Mail::to(config('contact.reception_email'))
            ->send(new NewUserNotificationMail($event->user));
    }
}
