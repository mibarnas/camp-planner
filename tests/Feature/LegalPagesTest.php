<?php

use App\Models\User;

test('the privacy policy is public', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/Document')
            ->where('document', 'privacy')
        );
});

test('the terms of use are public', function () {
    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('legal/Document')
            ->where('document', 'terms')
        );
});

test('legal pages follow the chosen locale', function () {
    $this->withUnencryptedCookie('locale', 'en')
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'html',
            fn (string $html) => str_contains($html, 'Privacy Policy'),
        ));

    $this->withUnencryptedCookie('locale', 'sk')
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'html',
            fn (string $html) => str_contains($html, 'Ochrana osobných údajov'),
        ));
});

test('a locale without its own legal text falls back to English', function () {
    // German is a supported UI locale, but the legal documents are only
    // written in Slovak and English — those need a human, not a translation.
    $this->withUnencryptedCookie('locale', 'de')
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'de')
            ->where('html', fn (string $html) => str_contains($html, 'Privacy Policy'))
        );
});

test('the locale can be switched and is shared with the front end', function () {
    $this->from(route('home'))
        ->put(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'))
        ->assertCookie('locale', 'en', encrypted: false);
});

test('an unsupported locale is rejected', function () {
    $this->from(route('home'))
        ->put(route('locale.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

// Listed literally rather than read from config: adding a locale should make
// this fail until someone confirms it really is switchable end to end.
test('every supported locale can be switched to', function (string $locale) {
    $this->from(route('home'))
        ->put(route('locale.update'), ['locale' => $locale])
        ->assertSessionHasNoErrors()
        ->assertCookie('locale', $locale, encrypted: false);
})->with(['sk', 'en', 'de']);

test('the supported locales are the ones covered above', function () {
    expect(config('app.supported_locales'))->toEqualCanonicalizing(['sk', 'en', 'de']);
});

test('the locale cookie drives the shared inertia locale', function () {
    $this->withUnencryptedCookie('locale', 'en')
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

test('a guest cannot export data', function () {
    $this->get(route('profile.dataExport'))->assertRedirect(route('login'));
});

test('a user can export their own data as json', function () {
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

    $response = $this->actingAs($user)->get(route('profile.dataExport'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/json');

    $payload = json_decode($response->streamedContent(), true);

    expect($payload['account']['email'])->toBe('test@example.com')
        ->and($payload['account']['name'])->toBe('Test User')
        ->and($payload)->toHaveKeys(['export', 'account', 'camps', 'activity_libraries', 'my_feedback']);
});

test('the data export never contains credentials', function () {
    $user = User::factory()->create();

    $body = $this->actingAs($user)
        ->get(route('profile.dataExport'))
        ->streamedContent();

    expect($body)
        ->not->toContain($user->password)
        ->not->toContain('two_factor_secret')
        ->not->toContain('gemini_api_key"');
});

test('a first-time visitor gets the language their browser asks for', function () {
    $this->withHeader('Accept-Language', 'en-GB,en;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));

    $this->withHeader('Accept-Language', 'sk-SK,sk;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'sk'));

    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'de'));
});

test('an unknown browser language falls back to the app default', function () {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'sk'));
});

test('a stored choice beats the browser language', function () {
    $this->withUnencryptedCookie('locale', 'sk')
        ->withHeader('Accept-Language', 'en-GB,en;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('locale', 'sk'));
});
