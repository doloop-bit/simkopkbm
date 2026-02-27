<?php

declare(strict_types=1);

use App\Models\ContactInquiry;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->withoutVite();
});

test('contact form submission creates inquiry that appears in admin', function () {
    // Submit contact form
    Volt::test('public.contact')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '081234567890')
        ->set('subject', 'Test Inquiry')
        ->set('message', 'This is a test message from the contact form.')
        ->call('submit')
        ->assertHasNoErrors();

    // Verify inquiry was created
    $inquiry = ContactInquiry::where('email', 'john@example.com')->first();
    expect($inquiry)->not->toBeNull()
        ->and($inquiry->name)->toBe('John Doe')
        ->and($inquiry->subject)->toBe('Test Inquiry')
        ->and($inquiry->message)->toBe('This is a test message from the contact form.')
        ->and($inquiry->is_read)->toBeFalse();

    // Verify admin can see the inquiry
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.contact-inquiries.index'))
        ->assertOk()
        ->assertSee('John Doe')
        ->assertSee('Test Inquiry')
        ->assertSee('This is a test message from the contact form.')
        ->assertSee('1 pesan belum dibaca');
});
