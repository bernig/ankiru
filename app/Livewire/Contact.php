<?php

namespace App\Livewire;

use App\Mail\ContactFormMail;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class Contact extends Component
{
    use UsesSpamProtection;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:150')]
    public string $subject = '';

    #[Validate('required|string|min:10|max:2000')]
    public string $message = '';

    public bool $sent = false;

    public HoneypotData $extraFields;

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
    }

    /**
     * @throws Exception
     */
    public function submit(): void
    {
        $this->protectAgainstSpam();

        $key = 'contact-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('rate_limit', __('contact.rate_limit', ['seconds' => $seconds]));

            return;
        }

        RateLimiter::hit($key, decaySeconds: 300);

        $this->validate();

        $receptionEmail = config('contact.reception_email');

        Mail::to($receptionEmail)->send(new ContactFormMail(
            senderName: $this->name,
            senderEmail: $this->email,
            emailSubject: $this->subject,
            messageBody: $this->message,
        ));

        $this->reset(['name', 'email', 'subject', 'message']);
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.contact');
    }
}
