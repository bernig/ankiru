<div>
    @if ($sent)
        <flux:callout color="green" icon="check-circle">
            <flux:callout.heading>{{ __('contact.success_heading') }}</flux:callout.heading>
            <flux:callout.text>{{ __('contact.success_text') }}</flux:callout.text>
        </flux:callout>
    @else
        @error('rate_limit')
            <flux:callout class="mb-6" color="red" icon="exclamation-triangle">
                <flux:callout.text>{{ $message }}</flux:callout.text>
            </flux:callout>
        @enderror

        <form class="space-y-4" wire:submit="submit">
            <x-honeypot />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('contact.name') }}</flux:label>
                    <flux:input type="text" autocomplete="name" placeholder="{{ __('contact.name_placeholder') }}" required wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('contact.email') }}</flux:label>
                    <flux:input type="email" autocomplete="email" placeholder="{{ __('contact.email_placeholder') }}" required wire:model="email" />
                    <flux:error name="email" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('contact.subject') }}</flux:label>
                <flux:input type="text" placeholder="{{ __('contact.subject_placeholder') }}" required wire:model="subject" />
                <flux:error name="subject" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('contact.message') }}</flux:label>
                <flux:textarea placeholder="{{ __('contact.message_placeholder') }}" required rows="6" wire:model="message" />
                <flux:error name="message" />
            </flux:field>

            <flux:button type="submit" icon="paper-airplane" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('contact.send') }}</span>
                <span wire:loading>{{ __('contact.sending') }}</span>
            </flux:button>
        </form>
    @endif
</div>
