<?php

use Kirby\Cms\Response;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

/**
 * Brand logos from the assets/brand-names submodule.
 *
 * Single source of truth for bookmark cards, panel stats and the offline page
 * (replaces the generated brands.css). A bookmark title is normalized to a-z
 * and the LONGEST logo token contained in it wins, e.g.
 * "Buy me a coffee" -> buymeacoffee.svg (not coffee.svg).
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

    /** Token of the best matching logo for a title, or null */
    public static function find(string $title): string|null
    {
        $haystack = static::token($title);
        $match    = null;

        if ($haystack === '') {
            return null;
        }

        foreach (array_keys(static::all()) as $token) {
            if (str_contains($haystack, $token) && strlen($token) > strlen($match ?? '')) {
                $match = $token;
            }
        }

        return $match;
    }

    public static function url(string $title): string|null
    {
        $token = static::find($title);
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
        'brandLogo' => fn (string $title): string|null => BrandLogos::url($title),
    ],
    'routes' => [
        [
            // token => logo URL, used by the offline page (offline.js)
            'pattern' => 'brands.json',
            'action'  => fn () => Response::json(array_map(BrandLogos::fileUrl(...), BrandLogos::all())),
        ],
    ],
]);
