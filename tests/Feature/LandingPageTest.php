<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home introduces the project and links to the demo', function (): void {
    $response = $this->get('/');

    $response->assertViewIs('landing')
        ->assertSee('Real workflows.')
        ->assertSee('href="'.route('bookings.index').'"', false)
        ->assertSee('href="'.route('bookings.create', ['scenario' => 'lost-response']).'"', false)
        ->assertSee('https://github.com/simonalanjones/laravel-workflow-demo');
});

test('booking dashboard is available at its own url', function (): void {
    $response = $this->get('/bookings');

    $response->assertViewIs('bookings.index')
        ->assertSee('href="'.route('bookings.index').'"', false)
        ->assertSee('href="'.route('home').'"', false);
});
