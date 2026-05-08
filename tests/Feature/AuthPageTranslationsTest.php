<?php

test('login page renders translations for configured default locale', function () {
    $configuredLocale = config('app.locale');

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee(__('auth.login', [], $configuredLocale));
    $response->assertSee(__('auth.login_subtitle', [], $configuredLocale));
    $response->assertSee(__('auth.sign_in', [], $configuredLocale));
});

test('login and register pages render french translations from session locale', function () {
    $loginResponse = $this->withSession(['locale' => 'fr'])->get(route('login'));

    $loginResponse->assertOk();
    $loginResponse->assertSee(__('auth.login', [], 'fr'));
    $loginResponse->assertSee(__('auth.login_subtitle', [], 'fr'));
    $loginResponse->assertSee(__('auth.no_account_yet', [], 'fr'));

    $registerResponse = $this->withSession(['locale' => 'fr'])->get(route('register'));

    $registerResponse->assertOk();
    $registerResponse->assertSee(__('auth.register', [], 'fr'));
    $registerResponse->assertSee(__('auth.register_subtitle', [], 'fr'));
    $registerResponse->assertSee(__('auth.create_account', [], 'fr'));
});
