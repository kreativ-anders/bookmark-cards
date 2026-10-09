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
 */
class BrandLogos
{
    /** @var array<string, string>|null token => file name */
    protected static array|null $logos = null;

    /** Lowercase a-z only, e.g. "Ernsting's Family" -> "ernstingsfamily" */
    public static function token(string $value): string
    {
        return preg_replace('/[^a-z]+/', '', Str::lower($value));
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
        'amzn.to'          => 'amazon',
        'bahn.de'          => 'deutschebahn',
        'chat.openai.com'  => 'chatgpt',
        'fb.com'           => 'facebook',
        'getkirby.com'     => 'kirby',
        'getpocket.com'    => 'pocket',
        'ing.de'           => 'ingdiba',
        'redd.it'          => 'reddit',
        'steampowered.com' => 'steam',
        'w3.org'           => 'wc',
        'wa.me'            => 'whatsapp',
        'x.com'            => 'twitter',
        'youtu.be'         => 'youtube',
    ];

    /** Tokens that are everyday words: matched via the link only, never via the title */
    public const DOMAIN_ONLY = [
        'absence', 'bild', 'brave', 'buffer', 'bunch', 'canny', 'carbon', 'chip', 'circle', 'coffee',
        'copy', 'coworker', 'dev', 'gamma', 'ghost', 'heap', 'intuit', 'kicker', 'linear', 'liner',
        'loaded', 'lumen', 'medium', 'moss', 'napkin', 'notion', 'ohm', 'otto', 'pocket', 'signal',
        'steam', 'threads', 'uber', 'wave', 'wbs', 'wc', 'web', 'wise', 'wlw', 'zoom',
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
        $host  = static::host($link);
        $logos = static::all();

        foreach (static::ALIASES as $domain => $token) {
            if (($host === $domain || str_ends_with($host, '.' . $domain)) && isset($logos[$token])) {
                return $token;
            }
        }

        // every label but the top-level domain, e.g. "developer.apple.com" -> developer, apple
        $labels = array_slice(explode('.', $host), 0, -1);

        return static::longest(array_map(static::token(...), $labels));
    }

    public static function fromTitle(string $title): string|null
    {
        // words of letters, camel case split ("GitHub" -> git hub), so tokens can span words
        $words = preg_split('/[^a-z]+/', Str::lower(preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $title)), -1, PREG_SPLIT_NO_EMPTY);
        $max   = max(array_map('strlen', array_keys(static::all())) ?: [0]);
        $runs  = [];

        foreach ($words as $i => $word) {
            for ($j = $i, $run = ''; $j < count($words) && strlen($run .= $words[$j]) <= $max; $j++) {
                if (!in_array($run, static::DOMAIN_ONLY, true)) {
                    $runs[] = $run;
                }
            }
        }

        return static::longest($runs);
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
        $token = static::find($title, $link);
        return $token !== null ? static::fileUrl(static::all()[$token]) : null;
    }

    public static function fileUrl(string $file): string
    {
        return url('assets/brand-names/' . rawurlencode($file));
    }

    /** Clears the in-memory list (tests) */
    public static function reset(): void
    {
        static::$logos = null;
    }
}

Kirby::plugin('kreativ-anders/brands', [
    'siteMethods' => [
        'brandLogo' => fn (string $title, string $link = ''): string|null => BrandLogos::url($title, $link),
    ],
    'routes' => [
        [
            // token => logo URL, used by the offline page (offline.js)
            'pattern' => 'brands.json',
            'action'  => fn () => Response::json(array_map(BrandLogos::fileUrl(...), BrandLogos::all())),
        ],
        [
            // matching rules for the offline page, so it picks the same logo as the server
            'pattern' => 'brands-rules.json',
            'action'  => fn () => Response::json(['aliases' => BrandLogos::ALIASES, 'domainOnly' => BrandLogos::DOMAIN_ONLY]),
        ],
    ],
]);
