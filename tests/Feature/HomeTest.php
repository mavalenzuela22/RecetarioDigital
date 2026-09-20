<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the operational Today surface', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Home')->where('title', 'Hoy en tu cocina')->has('production')->has('money'));
});
