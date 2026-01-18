<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('navigation links have wire:navigate for SPA performance', function () {
    $response = get('/');
    
    $response->assertStatus(200);
    
    // Check that main navigation links have wire:navigate
    $response->assertSee('wire:navigate', false); // Check raw HTML
    
    // Check specific navigation links
    $response->assertSee('href="' . route('public.programs.index') . '"', false);
    $response->assertSee('href="' . route('public.news.index') . '"', false);
    $response->assertSee('href="' . route('public.gallery') . '"', false);
    $response->assertSee('href="' . route('public.contact') . '"', false);
    
    // Check dropdown links
    $response->assertSee('href="' . route('public.about') . '"', false);
    $response->assertSee('href="' . route('public.organizational-structure') . '"', false);
    $response->assertSee('href="' . route('public.facilities') . '"', false);
});

test('mobile navigation links have wire:navigate for SPA performance', function () {
    $response = get('/');
    
    $response->assertStatus(200);
    
    // Mobile menu should also have wire:navigate on all links
    $content = $response->getContent();
    
    // Count occurrences of wire:navigate in mobile menu section
    $mobileMenuStart = strpos($content, 'Mobile menu');
    $mobileMenuEnd = strpos($content, '</div>', $mobileMenuStart + 500); // Approximate end
    
    if ($mobileMenuStart !== false && $mobileMenuEnd !== false) {
        $mobileMenuContent = substr($content, $mobileMenuStart, $mobileMenuEnd - $mobileMenuStart);
        
        // Should have multiple wire:navigate attributes in mobile menu
        expect(substr_count($mobileMenuContent, 'wire:navigate'))->toBeGreaterThan(5);
    }
});