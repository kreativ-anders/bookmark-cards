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
        ['Foo & Bär 123', 'foobaer'],              // umlauts spelled out
        ['Sinnesräuber', 'sinnesraeuber'],
        ['HÜMMER', 'huemmer'],
        ['Straße', 'strasse'],
        ['Café Crème', 'cafecreme'],               // accents removed
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
        ['SimiliarWeb', 'similiarweb.svg'],        // not web.svg
        ['Bing webmaster', 'bing.svg'],            // not web.svg
        ['DEVK', 'devk.svg'],                      // not dev.svg
    ]);

    it('matches whole words only', function (string $title, string $logo) {
        expect(BrandLogos::all()[BrandLogos::find($title)])->toBe($logo);
    })->with([
        ['Deutsche Bahn', 'deutschebahn.svg'],     // spans words
        ['MyGitHub', 'github.svg'],                // camel case is split into words
        ['DuckDuckGo', 'duckduckgo.svg'],
        ["Ernsting's Family", 'ernstingsfamily.svg'],
    ]);

    it('ignores logos inside other words and everyday words in titles', function (string $title) {
        expect(BrandLogos::find($title))->toBeNull();
    })->with([
        'Developer guide',    // not dev.svg
        'Webmaster',          // not web.svg
        'Notebook',           // not notebooklm.svg
        'Medium rare steak',  // medium.svg via link only
        'UI kit for designers', // kit.svg via link only
        'Night at the opera',   // opera.svg via link only
    ]);

    it('matches the domain of the link first', function (string $title, string $link, string $logo) {
        expect(BrandLogos::all()[BrandLogos::find($title, $link)])->toBe($logo);
    })->with([
        ['Team chat', 'https://app.slack.com/client', 'slack.svg'],
        ['Repo', 'github.com/kreativ-anders', 'github.svg'],              // without scheme
        ['Kirby plugin', 'https://github.com/getkirby', 'github.svg'],    // link before title
        ['How to write', 'https://medium.com/@jane/post', 'medium.svg'],  // everyday word via link
        ['Assistant', 'https://gemini.google.com/app', 'gemini.svg'],     // subdomain wins a tie
        ['Docs', 'https://developer.apple.com', 'apple.svg'],
        ['Tickets', 'https://www.bahn.de/', 'deutschebahn.svg'],          // alias
        ['Kirby', 'https://getkirby.com', 'kirby.svg'],                   // alias
        ['Router', 'http://fritz.box', 'fritzbox.svg'],                   // alias without TLD label
        ['Mail', 'https://outlook.office.com/mail/', 'microsoft.svg'],    // alias
        ['News', 'https://www.nytimes.com', 'newyorktimes.svg'],          // alias
        ['Chat', 'https://t.me/durov', 'telegram.svg'],                   // alias
        ['GitHub', 'https://example.org', 'github.svg'],                  // unknown domain -> title
    ]);

    it('suggests a new logo when title and domain name the same brand', function (string $title, string $link, string|null $token) {
        expect(BrandLogos::suggest($title, $link))->toBe($token);
    })->with([
        ['NomadList', 'https://nomadlist.com', 'nomadlist'],                         // camel case
        ['Page2API', 'https://www.page2api.com', 'pageapi'],
        ['Tiny House Manufaktur', 'https://tinyhouse-manufaktur.de', 'tinyhousemanufaktur'],
        ['Hümmer Fenster', 'https://huemmer.de/fenster', 'huemmer'],                 // part of the title
        ['Kirby', 'https://getkirby.com', 'kirby'],                                  // domain affix
        ['Alianz X', 'https://allianzx.com', 'allianzx'],                            // typo, domain wins
        ['Bookmark-Cards', 'https://shop.bookmark-cards.de', 'bookmarkcards'],
        ['LDA Bayern', 'https://www.lda.bayern.de', 'ldabayern'],                   // labels joined
        ['Firma.de', 'https://www.firma.de', 'firmade'],                             // alias without logo yet
        ['Scrivenor', 'https://www.literatureandlatte.com/scrivener', 'scrivener'], // alias, title irrelevant
        ['Trapped in Transformative Agreements?', 'https://arxiv.org/abs/1', null],  // paper on another site
        ['Email Obfuscator', 'https://jane.dev/tools/email', null],                  // tool page on a blog
        ['Blog', 'https://blog.jane.dev', null],                                     // generic subdomain
        ['NomadList', '', null],                                                     // no link
        ['', 'https://nomadlist.com', null],
    ]);

    it('returns null without a matching logo', function (string $title) {
        expect(BrandLogos::find($title))->toBeNull()
            ->and(site()->brandLogo($title))->toBeNull();
    })->with(['xyzzy', '123', '']);

    it('returns the logo URL via site()->brandLogo()', function () {
        expect(site()->brandLogo('GitHub'))->toBe(url('assets/brand-names/github.svg'));
    });

    it('returns the dark card variant via site()->brandLogoDark(), null when the logo needs none', function () {
        expect(site()->brandLogoDark('GitHub'))->toBe(url('assets/brand-names/dark/github.svg'))
            ->and(site()->brandLogoDark('Adobe'))->toBeNull()
            ->and(site()->brandLogoDark('xyzzy'))->toBeNull();
    });

    it('serves token => dark URL as brands-dark.json, only logos with a variant', function () {
        $json = json_decode($this->kirby->call('brands-dark.json')->body(), true);

        expect($json['github'])->toBe(url('assets/brand-names/dark/github.svg'))
            ->and($json)->not->toHaveKey('adobe')
            ->and(count($json))->toBe(count(glob(kirby()->root('assets') . '/brand-names/dark/*.svg')));
    });

    it('serves token => URL as brands.json for the offline page', function () {
        $response = $this->kirby->call('brands.json');
        $json     = json_decode($response->body(), true);

        expect($response->type())->toBe('application/json')
            ->and($json)->toHaveCount(count(BrandLogos::all()))
            ->and($json['github'])->toBe(url('assets/brand-names/github.svg'));
    });

    it('serves the matching rules as brands-rules.json for the offline page', function () {
        $json = json_decode($this->kirby->call('brands-rules.json')->body(), true);

        expect($json['aliases'])->toBe(BrandLogos::ALIASES)
            ->and($json['domainOnly'])->toBe(BrandLogos::DOMAIN_ONLY);
    });

    it('passes the link through site()->brandLogo()', function () {
        expect(site()->brandLogo('Team chat', 'https://app.slack.com'))->toBe(url('assets/brand-names/slack.svg'));
    });
});

describe('panel brand stats', function () {

    beforeEach(function () {
        registerUser('jane@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'GitHub', 'link' => 'https://github.com', 'tags' => ''],
            ['title' => 'Buy me a coffee', 'link' => 'https://buymeacoffee.com', 'tags' => ''],
            ['title' => 'Xyzzy Tool', 'link' => 'https://xyzzytool.com', 'tags' => ''],
            ['title' => 'Xyzzy Tool', 'link' => 'https://www.xyzzy-tool.de/2', 'tags' => ''],
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
            ['title' => 'Xyzzy Tool', 'domain' => 'xyzzytool.com', 'count' => 2, 'suggested' => 'xyzzytool', 'users' => 1],
        ]);
    });

    it('lists only bookmarks whose title and domain name the same brand', function () {
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'Trapped in Transformative Agreements? An Analysis of >1,000 Contracts', 'link' => 'https://arxiv.org/abs/2409.0001', 'tags' => ''],
            ['title' => 'Sprite Generator', 'link' => 'https://codeshack.io/images-sprite-sheet-generator/', 'tags' => ''],
            ['title' => 'Without link', 'link' => '', 'tags' => ''],
            ['title' => 'FrobNicator', 'link' => 'https://frobnicator.io', 'tags' => ''],
        ])]);

        expect(array_column(site()->missingBrandsList(), 'suggested'))->toBe(['frobnicator']);
    });

    it('groups missing logos by file name and ranks by number of users', function () {
        registerUser('john@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('john@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'Frobnicator', 'link' => 'https://frobnicator.io', 'tags' => ''],
            ['title' => 'xyzzy-tool', 'link' => 'https://xyzzytool.com', 'tags' => ''],
        ])]);

        expect(site()->missingBrandsList())->toBe([
            ['title' => 'Xyzzy Tool', 'domain' => 'xyzzytool.com', 'count' => 3, 'suggested' => 'xyzzytool', 'users' => 2],
            ['title' => 'Frobnicator', 'domain' => 'frobnicator.io', 'count' => 1, 'suggested' => 'frobnicator', 'users' => 1],
        ]);
    });

    it('copies missing logos as plain text via an actionstats button', function () {
        expect(site()->missingBrandsClipboard())->toBe('Xyzzy Tool (xyzzytool.com) -> xyzzytool.svg (2×, 1 user)');

        $section = new Kirby\Cms\Section('actionstats', [
            'model'   => site(),
            'name'    => 'test',
            'reports' => 'site.missingBrandsReports',
            'buttons' => [
                ['text' => 'Copy list', 'icon' => 'copy', 'copy' => 'site.missingBrandsClipboard'],
                ['text' => 'Inactive accounts', 'icon' => 'trash', 'dialog' => 'inactive-accounts'],
            ],
        ]);

        expect($section->toArray()['buttons'])->toBe([
                ['text' => 'Copy list', 'icon' => 'copy', 'copy' => site()->missingBrandsClipboard(), 'dialog' => null],
                ['text' => 'Inactive accounts', 'icon' => 'trash', 'copy' => null, 'dialog' => 'inactive-accounts'],
            ])
            ->and(array_column($section->toArray()['reports'], 'value'))->toBe(['Xyzzy Tool']);
    });

    it('shows missing logos as panel stat reports without evaluating queries in titles', function () {
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'Evil {{ site.title }}', 'link' => 'https://evil.example.org', 'tags' => ''],
        ])]);

        expect(site()->missingBrandsReports())->toBe([
            ['label' => 'add evil.svg', 'value' => 'Evil { { site.title } }', 'info' => 'evil.example.org · 1× · 1 user', 'icon' => 'image'],
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

        // averages and shares refer to bookmarks, not to all users
        expect(site()->bookmarksPerUser())->toBe('Ø 2 per user with bookmarks')
            ->and(site()->usersWithBookmarks())->toBe(1)
            ->and(site()->taggedBookmarks())->toBe(1)
            ->and(site()->taggedBookmarksInfo())->toBe('50% of all bookmarks');
    });

    it('counts inactive users like the clean-up dialog', function () {
        registerUser('jane@example.com');

        expect(site()->inactiveUsers())->toBe(count(AccountActivity::inactive()))
            ->and(site()->inactiveUsersInfo())->toBe('Free, no activity for ' . AccountActivity::months() . '+ months');
    });
});

describe('panel charts', function () {

    beforeEach(function () {
        registerUser('jane@example.com');
        registerUser('john@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'GitHub', 'link' => 'https://github.com', 'tags' => 'Dev, code'],
            ['title' => 'My GitHub account', 'link' => 'https://github.com/jane', 'tags' => 'dev'],
            ['title' => 'Xyzzy Tool', 'link' => 'https://example.org', 'tags' => ''],
        ])]);
    });

    it('splits users into paid, active and inactive free accounts', function () {
        expect(array_column(site()->userMixChart(), 'value', 'label'))->toBe([
            'Paid'           => 0,
            'Free, active'   => 2,
            'Free, inactive' => 0,
        ]);
    });

    it('shows the activation steps relative to all users', function () {
        expect(array_column(site()->activationChart(), 'info', 'label'))->toBe([
            'Registered'      => '100%',
            'Saved bookmarks' => '50%',
            'Uses tags'       => '50%',
        ]);
    });

    it('puts every user into exactly one activity bucket', function () {
        $chart = array_column(site()->activityChart(), 'value', 'label');

        expect(array_sum($chart))->toBe(2)
            ->and($chart['Last 7 days'])->toBe(2);
    });

    it('ranks tags case-insensitively by users, then by bookmarks', function () {
        expect(array_column(site()->topTagsChart(), 'info', 'label'))->toBe([
            'dev'  => 'user · 2×',
            'code' => 'user · 1×',
        ]);
    });

    it('ranks used logos with their image', function () {
        expect(site()->topBrandsChart())->toBe([
            ['label' => 'github', 'value' => 1, 'info' => 'user · 2×', 'color' => 'series-1', 'image' => url('assets/brand-names/github.svg')],
        ]);
    });

    it('ranks a value used by many users above one used often by a single user', function () {
        freshUser('john@example.com')->update(['bookmarks' => Kirby\Data\Yaml::encode([
            ['title' => 'Notes', 'link' => 'https://example.org', 'tags' => 'code'],
        ])]);

        // dev: 1 user, 2 bookmarks; code: 2 users, 2 bookmarks
        expect(array_column(site()->topTagsChart(), 'value', 'label'))->toBe(['code' => 2, 'dev' => 1])
            ->and(site()->topTagsChart()[0]['info'])->toBe('users · 2×');
    });

    it('splits the brand coverage into bookmarks with and without logo', function () {
        expect(array_column(site()->brandCoverageChart(), 'value', 'label'))->toBe([
            'Logo'    => 2,
            'No logo' => 1,
        ]);
    });

    it('resolves the chart section data from a site method', function () {
        $section = new Kirby\Cms\Section('chart', [
            'model'  => site(),
            'name'   => 'test',
            'layout' => 'stack',
            'data'   => 'site.brandCoverageChart',
        ]);

        expect($section->toArray()['layout'])->toBe('stack')
            ->and($section->toArray()['data'])->toBe(site()->brandCoverageChart());
    });
});

describe('panel dashboard views', function () {

    it('loads the sections of both dashboards from their blueprints', function (string $dashboard, string $section, string $type) {
        expect(panelStatsDashboard($dashboard)->section($section)->type())->toBe($type);
    })->with([
        ['users', 'Users', 'actionstats'],
        ['users', 'Activation', 'chart'],
        ['bookmarks', 'TopTags', 'chart'],
        ['bookmarks', 'MissingBrands', 'actionstats'],
    ]);

    it('knows only the bookmarks and users dashboards', function () {
        expect(panelStatsDashboard('../site'))->toBeNull()
            ->and(panelStatsDashboard('system'))->toBeNull();
    });

    it('builds the view for admins with the first tab and the section API parent', function () {
        createAdmin();
        kirby()->impersonate('admin@example.com');

        $view = panelStatsView('users', [['icon' => 'users', 'text' => 'All users', 'link' => 'users']]);

        expect($view['component'])->toBe('k-dashboard-view')
            ->and($view['props']['parent'])->toBe('panel-stats/users')
            ->and(array_keys($view['props']['tab']['columns']))->toContain('users')
            ->and($view['props']['buttons'][0]['link'])->toBe('users');
    });

    it('hides the statistics from other users', function () {
        registerUser('jane@example.com');
        kirby()->impersonate('jane@example.com');

        panelStatsView('bookmarks');
    })->throws(Kirby\Exception\PermissionException::class);
});
