<?php

test('guests see the welcome page at the root', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(__('welcome.hero_title'));
});
