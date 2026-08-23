<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => 'on',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('camps.index', absolute: false));

    // Nothing is "new" to someone who just signed up.
    expect(User::where('email', 'test@example.com')->value('last_seen_version'))
        ->toBe(config('app.version'));
});

test('registration requires agreeing to the terms and the privacy policy', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('terms');
    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
