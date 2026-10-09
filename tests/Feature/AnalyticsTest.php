<?php

describe('Analytics', function () {

    it('queues events with string meta and hands them out only once', function () {
        Analytics::track('Registration Completed');
        Analytics::track('Add Bookmark Completed', ['tags' => 2, 'brand_logo' => 'yes']);

        expect(Analytics::pull())->toBe([
            ['name' => 'Registration Completed', 'meta' => []],
            ['name' => 'Add Bookmark Completed', 'meta' => ['tags' => '2', 'brand_logo' => 'yes']],
        ])->and(Analytics::pull())->toBe([]);
    });
});
