<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('dropdown navigation has proper z-index to appear above page content', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that the dropdown has z-[9999] class which is higher than navigation z-50
    $response->assertSee('z-[9999]');

    // Check that the navigation bar has z-50
    $response->assertSee('z-50');
});

test('mobile menu has proper z-index', function () {
    $response = get('/');

    $response->assertStatus(200);

    // Check that mobile menu has z-50 class
    $response->assertSee('z-50');
});
