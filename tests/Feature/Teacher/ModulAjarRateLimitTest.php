<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('it handles gemini rate limit error gracefully', function () {
    // Fake the first request with 429
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 429,
                'message' => 'Quota exceeded',
                'status' => 'RESOURCE_EXHAUSTED',
            ],
        ], 429),
    ]);

    $user = User::factory()->create(['role' => 'guru']);
    actingAs($user);

    Livewire::test('teacher.modul-ajar.create')
        ->set('theme', 'Ekosistem Laut')
        ->set('subject', 'IPAS')
        ->call('startChat')
        ->assertHasNoErrors()
        ->assertSet('isGenerating', false);
});

test('it prunes old messages to save tokens', function () {
    // Fill up message history
    $messages = [];
    for ($i = 0; $i < 20; $i++) {
        $messages[] = ['role' => 'user', 'parts' => [['text' => "Message $i"]]];
    }

    Http::fake(function ($request) {
        $body = json_decode($request->body(), true);
        // Expect only last 12 messages according to our pruning logic
        if (count($body['contents']) <= 12) {
            return Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Response']]]]],
            ], 200);
        }

        return Http::response(['error' => 'Too many messages'], 400);
    });

    $user = User::factory()->create(['role' => 'guru']);
    actingAs($user);

    Livewire::test('teacher.modul-ajar.create')
        ->set('messages', $messages)
        ->set('newMessage', 'Last message')
        ->call('sendMessage')
        ->assertHasNoErrors();
});

test('it prevents concurrent generations', function () {
    $user = User::factory()->create(['role' => 'guru']);
    actingAs($user);

    $component = Livewire::test('teacher.modul-ajar.create');

    // Set generating to true manually to simulate a request in progress
    $component->set('isGenerating', true);

    // Attempting to send message should return early
    $component->call('sendMessage')
        ->assertSet('isGenerating', true);

    // Check that messages didn't grow
    expect($component->get('messages'))->toBeEmpty();
});
