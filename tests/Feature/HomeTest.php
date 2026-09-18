<?php

it('renders the EmprendimientoOS home shell', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Home'));
});
