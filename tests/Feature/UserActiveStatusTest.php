<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('canAccessPanel returns false when user is inactive', function () {
    $user = User::factory()->create(['active' => false]);

    expect($user->canAccessPanel(app(\Filament\Panel::class)))->toBeFalse();
});

test('canAccessPanel returns true when user is active', function () {
    $user = User::factory()->create(['active' => true]);

    expect($user->canAccessPanel(app(\Filament\Panel::class)))->toBeTrue();
});

test('active status is cast to boolean', function () {
    $user = User::factory()->create(['active' => 1]);

    expect($user->active)->toBeTrue();
    expect($user->active)->toBeBool();

    $user->active = 0;
    $user->save();

    expect($user->fresh()->active)->toBeFalse();
    expect($user->fresh()->active)->toBeBool();
});

test('active defaults to true for new users', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($user->active)->toBeTrue();
});

test('active column is fillable', function () {
    $user = new User([
        'name' => 'Test User',
        'email' => 'fillable@example.com',
        'password' => 'password',
        'active' => false,
    ]);

    expect($user->active)->toBeFalse();
});
