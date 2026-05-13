<?php

it('renders the FAQ page successfully', function () {
    $this->get(route('faq'))
        ->assertOk()
        ->assertSee(__('faq.title'));
});

it('displays all four FAQ sections', function () {
    $this->get(route('faq'))
        ->assertOk()
        ->assertSee(__('faq.section_ai_title'))
        ->assertSee(__('faq.section_api_title'))
        ->assertSee(__('faq.section_usage_title'))
        ->assertSee(__('faq.section_export_title'));
});

it('shows at least the stress mark question', function () {
    $this->get(route('faq'))
        ->assertOk()
        ->assertSee(__('faq.q_stress_unreliable'));
});

it('shows the FAQ link in the footer', function () {
    $this->get(route('faq'))
        ->assertOk()
        ->assertSee(route('faq'));
});

it('displays the FAQ page in english', function () {
    $this->get('/locale/en');

    $this->get(route('faq'))
        ->assertOk()
        ->assertSee('FAQ')
        ->assertSee('Are AI-generated phrases reliable?');
});
