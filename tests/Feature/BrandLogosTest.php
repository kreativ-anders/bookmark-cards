<?php

/*
| Brand logos (site/plugins/brands) and panel brand stats (site/plugins/panel-stats)
*/

beforeEach(fn () => BrandLogos::reset());

describe('BrandLogos', function () {

    it('normalizes titles to lowercase a-z', function (string $title, string $token) {
        expect(BrandLogos::token($title))->toBe($token);
    })->with([
        ['GitHub', 'github'],
        ["Ernsting's Family", 'ernstingsfamily'],
        ['Foo & Bär 123', 'foobr'],
        ['', ''],
    ]);

    it('lists every SVG in assets/brand-names', function () {
        $svgs = glob(PROJECT_ROOT . '/assets/brand-names/*.svg');

        expect(BrandLogos::all())->toHaveCount(count($svgs))
            ->and(BrandLogos::all()['github'])->toBe('github.svg');
    });

    it('matches a logo contained in the title', function (string $title, string $logo) {
        expect(BrandLogos::all()[BrandLogos::find($title)])->toBe($logo);
    })->with([
        ['GitHub', 'github.svg'],
        ['My GitHub account', 'github.svg'],
        ['GITHUB', 'github.svg'],
    ]);

    it('prefers the longest matching logo', function (string $title, string $logo) {
        expect(BrandLogos::all()[BrandLogos::find($title)])->toBe($logo);
    })->with([
        ['Buy me a coffee', 'buymeacoffee.svg'],   // not coffee.svg
        ['SimiliarWeb', 'similiarweb.svg'],        // not web.svg
        ['Bing webmaster', 'bing.svg'],            // not web.svg
        ['DEVK', 'devk.svg'],                      // not dev.svg
    ]);

    it('returns null without a matching logo', function (string $title) {
        expect(BrandLogos::find($title))->toBeNull()
            ->and(site()->brandLogo($title))->toBeNull();
    })->with(['xyzzy', '123', '']);

    it('returns the logo URL via site()->brandLogo()', function () {
        expect(site()->brandLogo('GitHub'))->toBe(url('assets/brand-names/github.svg'));
    });

    it('serves token => URL as brands.json for the offline page', function () {
        $response = $this->kirby->call('brands.json');
        $json     = json_decode($response->body(), true);

        expect($response->type())->toBe('application/json')
            ->and($json)->toHaveCount(count(BrandLogos::all()))
            ->and($json['github'])->toBe(url('assets/brand-names/github.svg'));
    });
});

describe('panel brand stats', function () {

    beforeEach(function () {
        registerUser('jane@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'GitHub', 'link' => 'https://github.com', 'tags' => ''],
            ['title' => 'Buy me a coffee', 'link' => 'https://buymeacoffee.com', 'tags' => ''],
            ['title' => 'Xyzzy Tool', 'link' => 'https://example.org', 'tags' => ''],
            ['title' => 'Xyzzy Tool', 'link' => 'https://example.org/2', 'tags' => ''],
        ])]);
    });

    it('counts available logos', function () {
        expect(site()->totalAvailableBrands())->toBe(count(BrandLogos::all()))
            ->and(site()->totalAvailableBrands())->toBeGreaterThan(0);
    });

    it('counts bookmarks without logo and the coverage', function () {
        expect(site()->bookmarksWithoutBrands())->toBe(2)
            ->and(site()->brandCoveragePercentage())->toBe('50%');
    });

    it('lists missing logos with usage count and suggested file name', function () {
        expect(site()->missingBrandsList())->toBe([
            ['title' => 'Xyzzy Tool', 'count' => 2, 'suggested' => 'xyzzytool', 'users' => 1],
        ]);
    });

    it('groups missing logos by file name and ranks by number of users', function () {
        registerUser('john@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('john@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'Frobnicator', 'link' => 'https://example.org', 'tags' => ''],
            ['title' => 'xyzzy-tool', 'link' => 'https://example.org', 'tags' => ''],
        ])]);

        expect(site()->missingBrandsList())->toBe([
            ['title' => 'Xyzzy Tool', 'count' => 3, 'suggested' => 'xyzzytool', 'users' => 2],
            ['title' => 'Frobnicator', 'count' => 1, 'suggested' => 'frobnicator', 'users' => 1],
        ]);
    });

    it('shows missing logos as panel stat reports without evaluating queries in titles', function () {
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'Evil {{ site.title }}', 'link' => 'https://example.org', 'tags' => ''],
        ])]);

        expect(site()->missingBrandsReports())->toBe([
            ['label' => 'add evilsitetitle.svg', 'value' => 'Evil { { site.title } }', 'info' => '1× · 1 user', 'icon' => 'image'],
        ]);
    });

    it('lists logos that match only a part of the title', function () {
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'GitHub', 'link' => 'https://github.com', 'tags' => ''],
            ['title' => 'My GitHub account', 'link' => 'https://github.com/jane', 'tags' => ''],
            ['title' => 'Bing webmaster', 'link' => 'https://bing.com/webmasters', 'tags' => ''],
            ['title' => 'Bing Webmaster', 'link' => 'https://bing.com/webmasters/2', 'tags' => ''],
        ])]);

        // the full match "GitHub" is not listed
        expect(site()->partialBrandMatches())->toBe([
            ['title' => 'Bing webmaster', 'logo' => 'bing.svg', 'count' => 2],
            ['title' => 'My GitHub account', 'logo' => 'github.svg', 'count' => 1],
        ]);
    });

    it('rates the coverage and counts used logos', function () {
        expect(site()->brandCoverageInfo())->toBe('2 of 4 bookmarks')
            ->and(site()->brandCoverageTheme())->toBe('negative')
            ->and(site()->bookmarksWithoutBrandsTheme())->toBe('notice')
            ->and(site()->brandsInUse())->toBe(2);
    });
});

describe('panel user and bookmark stats', function () {

    it('reports bookmark usage and tagged share', function () {
        registerUser('jane@example.com');
        registerUser('john@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'GitHub', 'link' => 'https://github.com', 'tags' => 'dev, code'],
            ['title' => 'Notion', 'link' => 'https://notion.so', 'tags' => ''],
        ])]);

        expect(site()->bookmarksPerUser())->toBe('Ø 1 per user')
            ->and(site()->usersWithBookmarks())->toBe(1)
            ->and(site()->usersWithBookmarksInfo())->toBe('50% of all users')
            ->and(site()->taggedBookmarksInfo())->toBe('50% of bookmarks tagged');
    });

    it('counts inactive users like the clean-up dialog', function () {
        registerUser('jane@example.com');

        expect(site()->inactiveUsers())->toBe(count(AccountActivity::inactive()))
            ->and(site()->inactiveUsersInfo())->toBe('Free, no activity for ' . AccountActivity::months() . '+ months');
    });
});
