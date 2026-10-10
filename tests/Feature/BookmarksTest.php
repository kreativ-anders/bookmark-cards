<?php

/*
| Bookmark helpers (site/plugins/bookmarks) used by site/controllers/home.php
*/

describe('Bookmarks::link', function () {

    it('keeps allowed links unchanged (as before)', function (string $link) {
        expect(Bookmarks::link($link))->toBe($link);
    })->with([
        ['https://github.com'],
        ['http://example.org/?a=1&b=2'],
        ['example.org'],
        ['mailto:jane@example.com'],
        ['tel:+491234'],
    ]);

    it('rejects script schemes and empty links', function (string $input) {
        expect(Bookmarks::link($input))->toBeNull();
    })->with([
        [''],
        ['javascript:alert(1)'],
        [' javascript:alert(1)'],
        ['javascript://%0aalert(1)'],
        ['JavaScript://x'],
        ["java\tscript://x"],
        ['vbscript://x'],
        ['data://text/html,x'],
    ]);

    it('neutralizes unsafe stored links on output', function () {
        expect(Bookmarks::href('javascript:alert(1)'))->toBe('#')
            ->and(Bookmarks::href('https://github.com'))->toBe('https://github.com');
    });

    it('keeps the configured premium card link, but still rejects it as user input', function () {
        $link = "javascript:document.getElementById('premium-checkout-button').click()";
        $this->kirby->clone(['options' => ['noPremiumLink' => $link]]);

        expect(Bookmarks::href($link))->toBe($link)
            ->and(Bookmarks::link($link))->toBeNull()
            ->and(Bookmarks::href('javascript:alert(1)'))->toBe('#');
    });
});

describe('Bookmarks::tags', function () {

    it('trims and dedupes case-insensitively, keeping the first spelling', function () {
        expect(Bookmarks::tags(' Dev, code,dev ,, CODE, git'))->toBe('Dev, code, git')
            ->and(Bookmarks::tags(''))->toBe('');
    });
});

describe('Bookmarks::find', function () {

    $bookmarks = [
        ['title' => 'A', 'link' => 'https://a.example', 'tags' => ''],
        ['title' => 'B', 'link' => 'https://b.example', 'tags' => ''],
    ];

    it('trusts the index without fingerprint (pages rendered before it existed)', function () use ($bookmarks) {
        expect(Bookmarks::find($bookmarks, '1', null))->toBe(1)
            ->and(Bookmarks::find($bookmarks, '', null))->toBe(0) // changeData() sends '' for index 0
            ->and(Bookmarks::find($bookmarks, '2', null))->toBeNull()
            ->and(Bookmarks::find($bookmarks, '-1', null))->toBeNull()
            ->and(Bookmarks::find($bookmarks, '1e2', null))->toBeNull()
            ->and(Bookmarks::find($bookmarks, null, null))->toBeNull();
    });

    it('follows the fingerprint when the index is stale', function () use ($bookmarks) {
        $b = Bookmarks::fingerprint($bookmarks[1]);

        expect(Bookmarks::find($bookmarks, '1', $b))->toBe(1)
            ->and(Bookmarks::find($bookmarks, '0', $b))->toBe(1)
            ->and(Bookmarks::find($bookmarks, '0', 'deadbeefdeadbeef'))->toBeNull();
    });
});

describe('Bookmarks::modify', function () {

    it('writes the returned list and skips writing on null', function () {
        $user = registerUser();
        $this->kirby->impersonate('kirby');

        $user = Bookmarks::modify($user, fn (array $bookmarks) => [...$bookmarks, ['title' => 'A', 'link' => 'https://a.example', 'tags' => '']]);
        $user = Bookmarks::modify($user, fn (array $bookmarks) => null);

        expect(freshUser('jane@example.com')->bookmarks()->yaml())
            ->toBe([['title' => 'A', 'link' => 'https://a.example', 'tags' => '']]);
    });

    it('reads fresh content instead of a stale copy from earlier in the request', function () {
        $user = registerUser();
        $this->kirby->impersonate('kirby');
        $user->bookmarks()->yaml(); // content is now cached for this model

        // another request (PHP process) writes the content file in the meantime
        $file = $user->root() . '/user.txt';
        Kirby\Data\Data::write($file, [
            ...Kirby\Data\Data::read($file),
            'Bookmarks' => Kirby\Data\Yaml::encode([['title' => 'Other', 'link' => 'https://other.example', 'tags' => '']]),
        ]);

        Bookmarks::modify($user, function (array $bookmarks) {
            expect(array_column($bookmarks, 'title'))->toBe(['Other']);
            return null;
        });
    });
});
