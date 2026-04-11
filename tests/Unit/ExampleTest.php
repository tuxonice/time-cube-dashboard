<?php

test('example unit test', function () {
    expect(true)->toBeTrue();
});

test('basic math operations', function () {
    expect(1 + 1)->toBe(2);
    expect(10 - 5)->toBe(5);
    expect(2 * 3)->toBe(6);
});

test('string operations', function () {
    expect('hello')->toBeString();
    expect('hello world')->toContain('world');
    expect('test')->toHaveLength(4);
});
