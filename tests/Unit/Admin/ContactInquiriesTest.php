<?php

declare(strict_types=1);

use App\Models\ContactInquiry;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();

    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);
});

test('admin can view contact inquiries page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.contact-inquiries.index'))
        ->assertOk()
        ->assertSee('Pesan Kontak');
});

test('contact inquiries page shows empty state when no inquiries exist', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.contact-inquiries.index'))
        ->assertOk()
        ->assertSee('Belum ada pesan');
});

test('contact inquiries page shows inquiries when they exist', function () {
    $inquiry = ContactInquiry::factory()->create([
        'name' => 'John Doe',
        'subject' => 'Test Subject',
        'message' => 'Test message content',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-inquiries.index'))
        ->assertOk()
        ->assertSee('John Doe')
        ->assertSee('Test Subject')
        ->assertSee('Test message content');
});

test('contact inquiries page shows unread count badge', function () {
    ContactInquiry::factory()->unread()->count(3)->create();
    ContactInquiry::factory()->read()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.contact-inquiries.index'))
        ->assertOk()
        ->assertSee('3 pesan belum dibaca');
});
