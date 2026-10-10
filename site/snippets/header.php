<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">

  <?php
    // only the landing page is indexable
    $isHome      = $page->isHomePage();
    $indexable   = $isHome && !$kirby->user();
    $siteName    = 'Bookmark.cards';
    $seoTitle    = $isHome
      ? 'Bookmark Manager: Save Links as Cards | Bookmark.cards'
      : $page->title()->escape() . ' | Bookmark.cards';
    $seoDesc     = $page->description()->isNotEmpty()
      ? $page->description()->escape()
      : 'Free, privacy-friendly bookmark manager. Save links as visual cards, organize them with tags, search instantly and export to JSON or CSV anytime.';
    $canonical   = $isHome ? $site->url() . '/' : $page->url();
    $ogImage     = $site->url() . '/assets/images/og-image.png';
  ?>
  <title><?= $seoTitle ?></title>
  <meta name="description" content="<?= $seoDesc ?>">
  <meta name="robots" content="<?= $indexable ? 'index, follow, max-image-preview:large' : 'noindex, follow' ?>">
  <link rel="canonical" href="<?= esc($canonical) ?>">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= $siteName ?>">
  <meta property="og:locale" content="en_US">
  <meta property="og:title" content="<?= $seoTitle ?>">
  <meta property="og:description" content="<?= $seoDesc ?>">
  <meta property="og:url" content="<?= esc($canonical) ?>">
  <meta property="og:image" content="<?= esc($ogImage) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Bookmark.cards logo and branded bookmark cards">
  <meta name="twitter:card" content="summary_large_image">

  <!-- stored color theme, applied before the CSS to avoid a flash -->
  <script>try{var t=localStorage.getItem('bookmark.cards.theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
  <meta name="theme-color" content="#f5f5f7" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#101012" media="(prefers-color-scheme: dark)">

  <link rel="preconnect" href="https://api.pirsch.io">

  <?php
    $asset = fn (string $path) => '/' . $path . '?v=' . (@filemtime($kirby->root('index') . '/' . $path) ?: 0);
  ?>
  <?php if ($user = $kirby->user()): ?>
  <link rel="manifest" href="manifest.json">
  <script src="/upup.min.js"></script>
  <?php
    $offlineFiles = [
      'favicon.ico',
      'favicon.svg',
      'assets/css/main.min.css',
      'assets/css/fonts/geist-latin-wght-normal.woff2',
      'assets/css/fonts/instrument-serif-latin-400-italic.woff2',
      'assets/images/kreativ-anders.svg',
      'offline.min.js',
      'assets/js/main.min.js',
    ];
    $offlineLogos = [];
    foreach ((array)$user->bookmarks()->yaml() as $bookmark) {
      if ($logo = $site->brandLogo((string)($bookmark['title'] ?? ''), (string)($bookmark['link'] ?? ''))) {
        $offlineLogos[$logo] = true;
      }
    }
    $offlineAssets = array_merge($offlineFiles, ['brands.json', 'brands-rules.json', 'user.json'], array_keys($offlineLogos));
    $offlineStamps = array_map(fn ($path) => @filemtime($kirby->root('index') . '/' . $path), [...$offlineFiles, 'offline.html', 'assets/brand-names', 'site/plugins/brands/index.php']);
    $offlineVersion = substr(md5(json_encode([$offlineAssets, $offlineStamps, $user->id(), $user->modified()])), 0, 12);
  ?>
  <script>
  // the service worker re-downloads every asset whenever UpUp starts, so only when something changed
  (function (version, assets) {
    if (!window.UpUp) return;
    try {
      if (navigator.serviceWorker.controller && localStorage.getItem('bookmark.cards.offline') === version) return;
      localStorage.setItem('bookmark.cards.offline', version);
    } catch (e) {}
    UpUp.start({
      'cache-version': version,
      'content-url': 'offline.html',
      'assets': assets,
      'service-worker-url': '/upup.sw.min.js'
    });
  })(<?= json_encode($offlineVersion) ?>, <?= json_encode($offlineAssets, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>);
  </script>
  <?php endif ?>

  <script defer src="<?= $asset('assets/js/main.min.js') ?>"></script>

  <link rel="icon" href="/favicon.ico" sizes="32x32">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

  <link rel="preload" href="/assets/css/fonts/geist-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= $asset('assets/css/main.min.css') ?>">

  <script defer src="https://api.pirsch.io/pa.js"
    id="pianjs"
    <?php if ($kirby->user()): ?>data-disable-outbound-links<?php endif ?>
    data-code="DKo22RMuBesw3XMADDNrwe0fQ7544AG1"></script>

</head>

<body>
  <a class="skip-link" href="#content">Skip to content</a>
  <header>
    <nav aria-label="Main navigation">
      <ul>
        <li class="brand">
          <a class="navbar-item" href="<?= $site->url() ?>" aria-label="Bookmark.cards home">
            <?php snippet('logo') ?>
            <span class="brand-name">Bookmark<span class="accent">.cards</span></span>
          </a>
          <?php $isPremium = $user && $user->isPremium() ?>
          <?php if (option('debug') || $isPremium): ?>
          <p class="brand-meta">
            <?= e(option('debug'), "Kirby v" . Kirby::version() . " PHP " . phpversion())?>
            <?php if ($isPremium): ?>
            <span class="premium-badge">Premium</span>
            <?php endif; ?>
          </p>
          <?php endif; ?>
        </li>
      </ul>
      <ul>
        <?php if (!$user): ?>
        <?php if ($isHome): ?>
        <li class="nav-anchor"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-anchor"><a class="nav-link" href="#pricing">Pricing</a></li>
        <li class="nav-anchor"><a class="nav-link" href="#faq">FAQ</a></li>
        <?php endif ?>
        <li><?php snippet('theme-toggle') ?></li>
        <li>
          <a id="login" href="<?= url('login') ?>" role="button" class="contrast outline" data-pirsch-event="Open Login Modal">Login</a>
        </li>
        <li>
          <a id="register" href="<?= url('register') ?>" role="button" data-pirsch-event="Open Register Modal" data-pirsch-meta-source="Header">Register</a>
        </li>
        <?php endif; ?>

        <?php if ($user): ?>
        <?php if (!$isHome): ?>
        <li>
          <a id="nav-bookmarks" href="<?= $site->url() ?>" role="button" class="secondary outline">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            My Bookmarks
          </a>
        </li>
        <?php endif ?>
        <li class="top-tags">
          <ul aria-label="Top tags">
            <li id="top-tags-placeholder"></li>
          </ul>
        </li>
        <li>
          <a id="user" href="<?= url('user') ?>" role="button" title="User Settings" class="secondary outline"<?= e($page->is('user'), ' aria-current="page"') ?> data-pirsch-event="Open User Settings Modal">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20c1.4-3.6 4.2-5.5 7.5-5.5s6.1 1.9 7.5 5.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Settings
          </a>
        </li>

        <?php if (!$isPremium): ?>
        <li>
          <?php snippet('stripe-checkout-button', [
            'id'      => 'premium-checkout-button',
            'classes' => 'pirsch-event=Open+Stripe+Checkout pirsch-meta-source=Header',
            'text'    => 'Premium',
            'url'     => $user->getStripeCheckoutURL(option('kreativ-anders.memberkit.tiers')[1]['name'])
          ]) ?>
        </li>
        <?php endif ?>

        <li><?php snippet('theme-toggle') ?></li>
        <li>
          <a id="logout" href="<?= url('logout') ?>" data-no-instant class="secondary outline" data-pirsch-event="Logout">Logout</a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>
  <div id="content" tabindex="-1"></div>