<?php

// only the landing page is indexable, app and account pages send noindex (header.php)
Kirby::plugin('kreativ-anders/seo', [
    'routes' => [
        [
            'pattern' => 'sitemap.xml',
            'action'  => function () {
                $site = site();
                $home = $site->homePage();
                // content only: file times of templates change with every deploy
                $lastmod = max($home->modified(), $site->modified());

                $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . Xml::encode($site->url() . '/') . '</loc>' . "\n";
                $xml .= '    <lastmod>' . date('c', $lastmod) . '</lastmod>' . "\n";
                $xml .= '  </url>' . "\n";
                $xml .= '</urlset>' . "\n";

                return new Kirby\Cms\Response($xml, 'application/xml');
            }
        ],
        [
            'pattern' => 'robots.txt',
            'action'  => function () {
                $lines = [
                    'User-agent: *',
                    'Allow: /',
                    'Disallow: /user',
                    'Disallow: /logout',
                    'Disallow: /' . option('panel.slug', 'panel'),
                    'Disallow: /api',
                    '',
                    'Sitemap: ' . site()->url() . '/sitemap.xml',
                ];

                return new Kirby\Cms\Response(implode("\n", $lines) . "\n", 'text/plain');
            }
        ],
        [
            // RFC 9116. A route, not a file: on the server .well-known/ belongs to root
            // (Let's Encrypt), so a tracked file there makes git pull fail on deploy
            'pattern' => '.well-known/security.txt',
            'action'  => function () {
                $lines = [
                    'Contact: mailto:security@kreativ-anders.de',
                    // rolling, so it never goes stale (RFC: less than a year ahead)
                    'Expires: ' . gmdate('Y-m-d\T00:00:00.000\Z', strtotime('+180 days')),
                    'Preferred-Languages: en, de',
                    'Canonical: ' . site()->url() . '/.well-known/security.txt',
                ];

                return new Kirby\Cms\Response(implode("\n", $lines) . "\n", 'text/plain');
            }
        ],
    ],
]);
