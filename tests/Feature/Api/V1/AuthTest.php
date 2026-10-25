<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('customer can register and receives a token', function () {
    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'jane@example.com')
        ->assertJsonPath('user.is_admin', false)
        ->assertJsonStructure(['token']);
});

test('registration ignores is_admin from the request', function () {
    $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'is_admin' => true,
    ])->assertCreated();

    expect(User::where('email', 'sneaky@example.com')->first()->is_admin)->toBeFalse();
});

test('user can log in with valid credentials', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['user', 'token']);
});

test('login fails with wrong password', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('authenticated user can fetch their profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.auth.me'))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.auth.logout'))
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
