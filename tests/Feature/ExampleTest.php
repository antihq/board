<?php

it('returns a successful response from the home page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
