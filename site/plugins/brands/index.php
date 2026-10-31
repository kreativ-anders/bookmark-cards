<?php

use Kirby\Cms\Response;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

/**
 * Brand logos from assets/brand-names.
 *
 * Single source of truth for bookmark cards, panel stats and the offline page
 * (replaces the generated brands.css). The logo token is the file name reduced to a-z.
 *
 * 1. Link: a domain label equals a token ("app.slack.com" -> slack.svg),
 *    domains that differ from the brand name go through ALIASES ("bahn.de" -> deutschebahn.svg)
 * 2. Title: a token equals whole consecutive words ("Buy me a coffee" -> buymeacoffee.svg,
 *    "Deutsche Bahn" -> deutschebahn.svg, but "Otherwise" never -> wise.svg)
 *    Everyday words in DOMAIN_ONLY match via the link only ("Brave New World" -> no brave.svg)
 *
 * The longest token wins, the first one on a tie (subdomain before domain, e.g. "gemini.google.com").
 *
 * suggest() names the logo still missing for a bookmark, but only if title and domain agree on a brand.
 */
class BrandLogos
{
    /** @var array<string, string>|null token => file name */
    protected static array|null $logos = null;

    /** @var array<string, true>|null file names with a variant for dark cards in brand-names/dark */
    protected static array|null $dark = null;

    /** @var array<string, string|null> per request: header and grid ask for the same bookmarks */
    protected static array $tokens = [];

    /** Lowercase a-z only, e.g. "Ernsting's Family" -> "ernstingsfamily" */
    public static function token(string $value): string
    {
        return preg_replace('/[^a-z]+/', '', static::lower($value));
    }

    /** Lowercase with German umlauts spelled out and accents removed, e.g. "Sinnesräuber" -> "sinnesraeuber" */
    public static function lower(string $value): string
    {
        return Str::ascii(strtr(Str::lower($value), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']));
    }

    /** @return array<string, string> token => file name, sorted by token */
    public static function all(): array
    {
        if (static::$logos !== null) {
            return static::$logos;
        }

        $logos = [];
        foreach (Dir::files(kirby()->root('assets') . '/brand-names') as $file) {
            if (F::extension($file) === 'svg' && ($token = static::token(F::name($file))) !== '') {
                $logos[$token] = $file;
            }
        }
        ksort($logos);

        return static::$logos = $logos;
    }

    /** Domains whose name differs from the logo token, domain => token (subdomains included) */
    public const ALIASES = [
        'amzn.to'                => 'amazon',
        'archive.org'            => 'internetarchive',
        'atlantic.vc'            => 'atlanticlabs',
        'auone.jp'               => 'au',
        'avm.de'                 => 'fritzbox',
        'bahn.de'                => 'deutschebahn',
        'blogspot.com'           => 'blogger',
        'chat.openai.com'        => 'chatgpt',
        'cloud.microsoft'        => 'microsoft',
        'convertkit.com'         => 'kit',
        'desk.ly'                => 'deskly',
        'discord.gg'             => 'discord',
        'dvinci-hr.com'          => 'dvinci',
        'endoflife.date'         => 'eol',
        'fb.com'                 => 'facebook',
        'firma.de'               => 'firmade',
        'fritz.box'              => 'fritzbox',
        'fritz.com'              => 'fritzbox',
        'getkirby.com'           => 'kirby',
        'getoutline.com'         => 'outline',
        'getpocket.com'          => 'pocket',
        'icloud.com'             => 'apple',
        'icon.kitchen'           => 'iconkitchen',
        'ing.de'                 => 'ingdiba',
        'lda.bayern.de'          => 'ldabayern',
        'literatureandlatte.com' => 'scrivener',
        'live.com'               => 'microsoft',
        'mail.ru'                => 'mailru',
        'max.com'                => 'hbomax',
        'myfritz.net'            => 'fritzbox',
        'namu.wiki'              => 'namuwiki',
        'nomads.com'             => 'nomadlist',
        'nyti.ms'                => 'newyorktimes',
        'nytimes.com'            => 'newyorktimes',
        'office.com'             => 'microsoft',
        'ok.ru'                  => 'odnoklassniki',
        'outlook.com'            => 'microsoft',
        'redd.it'                => 'reddit',
        'steampowered.com'       => 'steam',
        't.me'                   => 'telegram',
        'ui.vision'              => 'uivision',
        'vkvideo.ru'             => 'vk',
        'w3.org'                 => 'wc',
        'wa.me'                  => 'whatsapp',
        'wbs-law.de'             => 'wbs',
        'wbs.legal'              => 'wbs',
        'weather.com'            => 'weatherchannel',
        'x.com'                  => 'twitter',
        'ya.ru'                  => 'yandex',
        'youtu.be'               => 'youtube',
    ];

    /** Tokens that are everyday words: matched via the link only, never via the title */
    public const DOMAIN_ONLY = [
        'absence', 'acquire', 'au', 'bild', 'brave', 'buffer', 'bunch', 'canny', 'carbon', 'chip',
        'circle', 'copy', 'coworker', 'dev', 'eol', 'fandom', 'favicon', 'frameshift', 'gamma', 'ghost',
        'globo', 'heap', 'indeed', 'intuit', 'kicker', 'kit', 'lemonade', 'linear', 'liner', 'loaded',
        'lumen', 'marca', 'medium', 'moss', 'napkin', 'notion', 'ohm', 'opera', 'otto', 'outline',
        'palmo', 'pocket', 'rows', 'sandstorm', 'shuffle', 'signal', 'steam', 'threads', 'uber',
        'wattstunde', 'wave', 'wbs', 'wc', 'web', 'wise', 'wlw', 'wormhole', 'zoom',
    ];

    /** Token of the best matching logo for a bookmark (link first, then title), or null */
    public static function find(string $title, string $link = ''): string|null
    {
        return static::fromLink($link) ?? static::fromTitle($title);
    }

    /** Lowercase host of a link, also without scheme ("github.com/foo"), '' if there is none */
    public static function host(string $link): string
    {
        $link = trim($link);
        if ($link !== '' && !preg_match('~^[a-z][a-z0-9+.-]*://~i', $link)) {
            $link = 'http://' . $link;
        }

        $host = parse_url($link, PHP_URL_HOST);
        return is_string($host) ? rtrim(Str::lower($host), '.') : '';
    }

    public static function fromLink(string $link): string|null
    {
        $host = static::host($link);

        if (($token = static::alias($host)) !== null && isset(static::all()[$token])) {
            return $token;
        }

        // every label but the top-level domain, e.g. "developer.apple.com" -> developer, apple
        $labels = array_slice(explode('.', $host), 0, -1);

        return static::longest(array_map(static::token(...), $labels));
    }

    /** Token of ALIASES for a host (subdomains included), or null */
    protected static function alias(string $host): string|null
    {
        foreach (static::ALIASES as $domain => $token) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return $token;
            }
        }

        return null;
    }

    public static function fromTitle(string $title): string|null
    {
        $max  = max(array_map('strlen', array_keys(static::all())) ?: [0]);
        $runs = array_filter(static::runs($title, $max), fn ($run) => !in_array($run, static::DOMAIN_ONLY, true));

        return static::longest($runs);
    }

    /**
     * Consecutive title words joined, up to $max letters, so tokens can span words
     * ("Buy me a coffee" -> buy, buyme, buymea, ...); camel case is split ("GitHub" -> git hub)
     *
     * @return array<int, string>
     */
    protected static function runs(string $title, int $max): array
    {
        $words = preg_split('/[^a-z]+/', static::lower(preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $title)), -1, PREG_SPLIT_NO_EMPTY);
        $runs  = [];

        foreach ($words as $i => $word) {
            for ($j = $i, $run = ''; $j < count($words) && strlen($run .= $words[$j]) <= $max; $j++) {
                $runs[] = $run;
            }
        }

        return $runs;
    }

    /** Pre- and suffixes of brand domains that are not part of the name ("getkirby.com", "notionhq.com") */
    public const DOMAIN_AFFIXES = ['get', 'go', 'join', 'my', 'try', 'use', 'app', 'hq', 'hub'];

    /**
     * Token for a new logo, when title and domain name the same brand
     * ("NomadList" on nomadlist.com -> nomadlist, "Kirby" on getkirby.com -> kirby).
     * Null for anything else, e.g. a paper on arxiv.org or a tool page on someone's blog.
     */
    public static function suggest(string $title, string $link): string|null
    {
        $host = static::host($link);

        // the domain is already known to name a brand, only its logo is missing ("literatureandlatte.com")
        if (($token = static::alias($host)) !== null) {
            return $token;
        }

        // every label but the top-level domain and neighbours joined,
        // e.g. "www.lda.bayern.de" -> www, lda, bayern, wwwlda, ldabayern, wwwldabayern
        $parts  = array_map(static::token(...), array_slice(explode('.', $host), 0, -1));
        $labels = [];
        foreach ($parts as $i => $part) {
            for ($j = $i, $label = ''; $j < count($parts); $j++) {
                $labels[] = $label .= $parts[$j];
            }
        }
        $names = [];

        foreach ($labels as $label) {
            $names[] = $label;
            foreach (static::DOMAIN_AFFIXES as $affix) {
                if (str_starts_with($label, $affix)) {
                    $names[] = substr($label, strlen($affix));
                }
                if (str_ends_with($label, $affix)) {
                    $names[] = substr($label, 0, -strlen($affix));
                }
            }
        }

        // generic subdomains name no brand ("blog.jane.dev" titled "Blog")
        $names = array_filter($names, fn ($name) => strlen($name) >= 3
            && !in_array($name, ['www', 'app', 'blog', 'docs', 'help', 'mail', 'shop', 'store', 'support'], true));
        if ($names === []) {
            return null;
        }

        $runs  = static::runs($title, max(array_map('strlen', $names)) + 1);
        $match = null;

        foreach ($names as $name) {
            foreach ($runs as $run) {
                // same words, or one typo in a longer name ("Alianz X" on allianzx.com)
                $same = $run === $name || (strlen($name) >= 6 && levenshtein($run, $name) <= 1);

                if ($same && strlen($name) > strlen($match ?? '')) {
                    $match = $name;
                }
            }
        }

        return $match;
    }

    /** Longest candidate with a logo, the first one on a tie */
    protected static function longest(array $candidates): string|null
    {
        $match = null;

        foreach ($candidates as $token) {
            if (isset(static::all()[$token]) && strlen($token) > strlen($match ?? '')) {
                $match = $token;
            }
        }

        return $match;
    }

    public static function url(string $title, string $link = ''): string|null
    {
        $token = static::cachedFind($title, $link);

        return $token !== null ? static::fileUrl(static::all()[$token]) : null;
    }

    /** Variant for dark cards (dark-logos.mjs), null without a logo or when the logo needs none */
    public static function darkUrl(string $title, string $link = ''): string|null
    {
        $token = static::cachedFind($title, $link);

        return $token !== null ? static::darkFileUrl(static::all()[$token]) : null;
    }

    public static function fileUrl(string $file): string
    {
        return url('assets/brand-names/' . rawurlencode($file));
    }

    public static function darkFileUrl(string $file): string|null
    {
        static::$dark ??= array_fill_keys(Dir::files(kirby()->root('assets') . '/brand-names/dark'), true);

        return isset(static::$dark[$file]) ? url('assets/brand-names/dark/' . rawurlencode($file)) : null;
    }

    /** find() once per title and link */
    protected static function cachedFind(string $title, string $link): string|null
    {
        $key = $title . "\n" . $link;

        if (!array_key_exists($key, static::$tokens)) {
            static::$tokens[$key] = static::find($title, $link);
        }

        return static::$tokens[$key];
    }

    /** Clears the in-memory lists (tests) */
    public static function reset(): void
    {
        static::$logos  = null;
        static::$dark   = null;
        static::$tokens = [];
    }
}

Kirby::plugin('kreativ-anders/brands', [
    'siteMethods' => [
        'brandLogo'     => fn (string $title, string $link = ''): string|null => BrandLogos::url($title, $link),
        'brandLogoDark' => fn (string $title, string $link = ''): string|null => BrandLogos::darkUrl($title, $link),
    ],
    'routes' => [
        [
            // token => logo URL, used by the offline page (offline.js)
            'pattern' => 'brands.json',
            'action'  => fn () => Response::json(array_map(BrandLogos::fileUrl(...), BrandLogos::all())),
        ],
        [
            // token => URL of the dark card variant, only logos that have one (offline.js)
            'pattern' => 'brands-dark.json',
            'action'  => fn () => Response::json(array_filter(array_map(BrandLogos::darkFileUrl(...), BrandLogos::all()))),
        ],
        [
            // matching rules for the offline page, so it picks the same logo as the server
            'pattern' => 'brands-rules.json',
            'action'  => fn () => Response::json(['aliases' => BrandLogos::ALIASES, 'domainOnly' => BrandLogos::DOMAIN_ONLY]),
        ],
    ],
]);
