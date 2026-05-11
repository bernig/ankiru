<?php

dataset('russianStaticPages', [
    'about' => [
        'routeName' => 'about',
        'expectedTranslations' => [
            ['key' => 'about.title'],
            ['key' => 'about.subtitle', 'usesAppName' => true],
            ['key' => 'about.description', 'usesAppName' => true],
        ],
    ],
    'contact' => [
        'routeName' => 'contact',
        'expectedTranslations' => [
            ['key' => 'contact.title'],
            ['key' => 'contact.subheading'],
            ['key' => 'contact.send'],
        ],
    ],
    'legal notice' => [
        'routeName' => 'legal.mentions',
        'expectedTranslations' => [
            ['key' => 'legal.mentions_title'],
            ['key' => 'legal.mentions_subtitle'],
            ['key' => 'legal.publisher_title'],
        ],
    ],
    'privacy policy' => [
        'routeName' => 'legal.privacy',
        'expectedTranslations' => [
            ['key' => 'legal.privacy_title'],
            ['key' => 'legal.privacy_subtitle'],
            ['key' => 'legal.rights_title'],
        ],
    ],
]);

test('guest static pages render russian translations when locale is ru', function (string $routeName, array $expectedTranslations) {
    $response = $this->withSession(['locale' => 'ru'])->get(route($routeName));

    $response->assertOk();

    foreach ($expectedTranslations as $expectedTranslation) {
        $replace = ($expectedTranslation['usesAppName'] ?? false)
            ? ['app' => config('app.name')]
            : ($expectedTranslation['replace'] ?? []);

        $response->assertSee(__($expectedTranslation['key'], $replace, 'ru'));
    }
})->with('russianStaticPages');

test('footer navigation labels are translated in russian', function () {
    $response = $this->withSession(['locale' => 'ru'])->get(route('about'));

    $response->assertOk();
    $response->assertSee(__('about.title', [], 'ru'));
    $response->assertSee(__('contact.title', [], 'ru'));
    $response->assertSee(__('legal.mentions_title', [], 'ru'));
    $response->assertSee(__('legal.privacy_title', [], 'ru'));
});
