<?php

test('guests are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
