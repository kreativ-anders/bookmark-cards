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
    ],
]);
