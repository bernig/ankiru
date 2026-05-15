<?php

use App\Livewire\Contact;
use App\Mail\ContactFormMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Mail::fake();
    RateLimiter::clear('contact-form:127.0.0.1');
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

/**
 * Mounts the Contact component and advances time 2s past valid_from so the
 * honeypot timing check treats the submission as a legitimate human interaction.
 */
function livewireContact(): Testable
{
    $component = Livewire::test(Contact::class);
    Carbon::setTestNow(now()->addSeconds(2));

    return $component;
}

test('contact page renders the livewire component', function (): void {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSeeLivewire(Contact::class);
});

test('contact form can be submitted successfully', function (): void {
    livewireContact()
        ->set('name', 'Jean Dupont')
        ->set('email', 'jean@example.com')
        ->set('subject', 'Question sur le service')
        ->set('message', 'Bonjour, j\'ai une question concernant votre service.')
        ->call('submit')
        ->assertSet('sent', true)
        ->assertHasNoErrors();

    Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail): bool {
        return $mail->senderName === 'Jean Dupont'
            && $mail->senderEmail === 'jean@example.com'
            && $mail->emailSubject === 'Question sur le service';
    });
});

test('contact form mail is sent to the configured reception email', function (): void {
    config(['contact.reception_email' => 'admin@example.com']);

    livewireContact()
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('subject', 'Test sujet')
        ->set('message', 'Ceci est un message de test.')
        ->call('submit');

    Mail::assertSent(ContactFormMail::class, fn (ContactFormMail $mail): bool => $mail->hasTo('admin@example.com'));
});

test('contact form validates required fields', function (): void {
    livewireContact()
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'subject', 'message'])
        ->assertSet('sent', false);

    Mail::assertNothingSent();
});

test('contact form validates email format', function (): void {
    livewireContact()
        ->set('name', 'Jean Dupont')
        ->set('email', 'pas-un-email')
        ->set('subject', 'Sujet')
        ->set('message', 'Message valide de test.')
        ->call('submit')
        ->assertHasErrors(['email']);
});

test('contact form validates message minimum length', function (): void {
    livewireContact()
        ->set('name', 'Jean Dupont')
        ->set('email', 'jean@example.com')
        ->set('subject', 'Sujet')
        ->set('message', 'Court')
        ->call('submit')
        ->assertHasErrors(['message']);
});

test('contact form is rate limited after 3 attempts', function (): void {
    $formData = [
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'subject' => 'Sujet',
        'message' => 'Message assez long pour passer la validation.',
    ];

    $component = livewireContact();

    foreach (range(1, 3) as $i) {
        $component->set($formData)->call('submit');
        $component->set('sent', false);
    }

    $component->set($formData)->call('submit')
        ->assertHasErrors(['rate_limit'])
        ->assertSet('sent', false);

    Mail::assertSentCount(3);
});

test('contact form resets fields after successful submission', function (): void {
    livewireContact()
        ->set('name', 'Jean Dupont')
        ->set('email', 'jean@example.com')
        ->set('subject', 'Sujet test')
        ->set('message', 'Voici mon message complet.')
        ->call('submit')
        ->assertSet('name', '')
        ->assertSet('email', '')
        ->assertSet('subject', '')
        ->assertSet('message', '');
});

test('le formulaire de contact bloque le spam quand le honeypot est déclenché', function (): void {
    // Geler le temps : valid_from = now() + 1s sera toujours dans le futur
    // quand submit() est appelé (now() reste identique), ce que SpamProtection
    // interprète comme une soumission de bot.
    Carbon::setTestNow(now());

    Livewire::test(Contact::class)
        ->set('name', 'Spam Bot')
        ->set('email', 'bot@example.com')
        ->set('subject', 'Sujet spam')
        ->set('message', 'Achetez mes produits maintenant !')
        ->call('submit')
        ->assertStatus(403);

    Mail::assertNothingSent();
});

test('contact form mail subject is translated in russian', function (): void {
    App::setLocale('ru');

    $mail = new ContactFormMail(
        senderName: 'Ivan Ivanov',
        senderEmail: 'ivan@example.com',
        emailSubject: 'Вопрос',
        messageBody: 'Здравствуйте!',
    );

    expect($mail->envelope()->subject)->toBe('[Контакт] Вопрос');
});
