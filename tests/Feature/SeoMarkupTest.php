<?php

test('welcome page has basic SEO meta tags', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('<meta name="robots" content="index, follow">', false);
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('rel="canonical"', false);
    $response->assertSee('<meta property="og:title"', false);
    $response->assertSee('<meta property="og:description"', false);
    $response->assertSee('<meta property="og:type" content="website">', false);
    $response->assertSee('<meta property="og:url"', false);
    $response->assertSee('<meta property="og:image"', false);
    $response->assertSee('<meta name="twitter:card"', false);
});

test('about page has basic SEO meta tags', function () {
    $response = $this->get('/about');

    $response->assertSuccessful();
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('rel="canonical"', false);
    $response->assertSee('<meta property="og:title"', false);
});

test('contact page has basic SEO meta tags', function () {
    $response = $this->get('/contact');

    $response->assertSuccessful();
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('rel="canonical"', false);
    $response->assertSee('<meta property="og:title"', false);
});

test('page title includes app name as suffix', function () {
    $response = $this->get('/about');

    $response->assertSuccessful();
    $response->assertSee('- '.config('app.name'), false);
});
