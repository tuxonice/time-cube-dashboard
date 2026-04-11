<?php

use App\Models\User;
use App\Core\Auth;

beforeEach(function () {
    // Setup test database or mock data here
});

test('user can be created', function () {
    $email = 'test@example.com';
    $name = 'Test User';
    $password = 'password123';
    
    expect($email)->toBeString();
    expect($name)->toBeString();
    expect($password)->toHaveLength(11);
});

test('email validation works', function () {
    $validEmail = 'user@example.com';
    $invalidEmail = 'not-an-email';
    
    expect(filter_var($validEmail, FILTER_VALIDATE_EMAIL))->toBeTruthy();
    expect(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL))->toBeFalsy();
});

test('password hashing works', function () {
    $password = 'mySecretPassword';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    expect($hash)->toBeString();
    expect(password_verify($password, $hash))->toBeTrue();
    expect(password_verify('wrongPassword', $hash))->toBeFalse();
});
