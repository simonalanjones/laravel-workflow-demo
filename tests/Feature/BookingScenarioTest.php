<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only the lost response scenario preselects the option', function (string $url, bool $expectedChecked): void {
    $response = $this->get($url);

    $response->assertViewIs('bookings.create');
    if ($expectedChecked) {
        $response->assertSee('Lost response scenario');
    } else {
        $response->assertDontSee('Lost response scenario');
    }
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $this->assertSame($expectedChecked, $document->getElementById('simulate_lost_response')->hasAttribute('checked'));
    $this->assertDatabaseCount('bookings', 0);
})->with([
    ['/bookings/create?scenario=lost-response', true],
    ['/bookings/create', false],
    ['/bookings/create?scenario=unknown', false],
]);

test('failure buttons are disabled only for the guided scenario', function (string $url, bool $expectedDisabled): void {
    $response = $this->get($url);

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    foreach (['/demo/bookings/failing', '/demo/bookings/random-failure'] as $action) {
        $button = $xpath->query('//button[@formaction="'.$action.'"]')->item(0);
        $this->assertSame($expectedDisabled, $button->hasAttribute('disabled'));
    }
    $this->assertFalse($xpath->query('//button[@type="submit" and not(@formaction)]')->item(0)->hasAttribute('disabled'));
    if ($expectedDisabled) {
        $response->assertSee('Failing and random-failure bookings are disabled')
            ->assertSee('href="'.route('bookings.create').'"', false);
    }
})->with([
    ['/bookings/create?scenario=lost-response', true],
    ['/bookings/create', false],
    ['/bookings/create?scenario=unknown', false],
]);

test('validation return preserves the users unchecked option', function (): void {
    $url = '/bookings/create?scenario=lost-response';

    $response = $this->followingRedirects()->from($url)->post('/demo/bookings/successful', [
        'simulate_lost_response' => '0',
    ]);

    $response->assertSee('Choose at least one booking item.');
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $this->assertFalse($document->getElementById('simulate_lost_response')->hasAttribute('checked'));
    $this->assertDatabaseCount('bookings', 0);
});
