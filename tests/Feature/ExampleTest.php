<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A basic test example.
 */
test('the application returns a successful response', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
});
