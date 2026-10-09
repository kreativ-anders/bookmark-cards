<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">

  <?php
    // SEO: only the landing page is indexable; app/account pages are noindex
    $isHome      = $page->isHomePage();
    $indexable   = $isHome && !$kirby->user();
    $siteName    = 'Bookmark.cards';
    $seoTitle    = $isHome
      ? 'Bookmark Manager: Save &amp; Tag Links as Cards | Bookmark.cards'
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
  <link rel="canonical" href="<?= esc($canonical, 'attr') ?>">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= $siteName ?>">
  <meta property="og:locale" content="en_US">
  <meta property="og:title" content="<?= $seoTitle ?>">
  <meta property="og:description" content="<?= $seoDesc ?>">
  <meta property="og:url" content="<?= esc($canonical, 'attr') ?>">
  <meta property="og:image" content="<?= esc($ogImage, 'attr') ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Bookmark.cards logo and branded bookmark cards">
  <meta name="twitter:card" content="summary_large_image">

  <!-- Color theme chosen by the user (main.js theme toggle); runs before CSS to avoid a flash -->
  <script>try{var t=localStorage.getItem('bookmark.cards.theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
  <meta name="theme-color" content="#f5f5f7" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#101012" media="(prefers-color-scheme: dark)">

  <link rel="dns-prefetch" href="//bookmark.cards">
  <link rel="preconnect" href="//bookmark.cards">

  <?php if ($kirby->user()): ?>
  <link rel="manifest" href="manifest.json">
  <!-- UpUp.js --> 
  <script src="/upup.min.js"></script>
  <?php
    // brand logos of the user's own bookmarks, so offline.html shows them too (absolute URLs like brands.json)
    $offlineLogos = [];
    foreach ((array) $kirby->user()->bookmarks()->yaml() as $bookmark) {
      if ($logo = $site->brandLogo((string) ($bookmark['title'] ?? ''), (string) ($bookmark['link'] ?? ''))) $offlineLogos[$logo] = true;
    }
  ?>
  <script>
  UpUp.start({
    'cache-version': Date.now(),
    'content-url': 'offline.html',
    'assets': [
      'favicon.ico', 
      'favicon.svg', 
      'brands.json', 
      'brands-rules.json', 
      'assets/css/main.min.css', 
      'assets/css/fonts/geist-latin-wght-normal.woff2', 
      'assets/css/fonts/instrument-serif-latin-400-italic.woff2', 
      'assets/images/kreativ-anders.svg', 
      'offline.min.js', 
      'user.json', 
      'assets/js/main.min.js'].concat(<?= json_encode(array_keys($offlineLogos), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>),
    'service-worker-url': '/upup.sw.min.js'
  });
  </script>
  <?php endif; ?>

  <script defer src="/assets/js/main.min.js"></script>

  <link rel="icon" href="/favicon.ico" sizes="32x32">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

  <link rel="preload" href="/assets/css/fonts/geist-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/main.min.css">

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
          <?php if (option('debug') || ($kirby->user() && $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name']))): ?>
          <p class="brand-meta">
            <?= e(option('debug'), "Kirby v" . Kirby::version() . " PHP " . phpversion())?>
            <?php if ($kirby->user() && $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): ?>
            <span class="premium-badge">Premium</span>
            <?php endif; ?>
          </p>
          <?php endif; ?>
        </li>
      </ul>
      <ul>
        <?php  if(!$kirby->user()): ?>
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

        <?php  if($kirby->user()): ?>
        <?php if (!$isHome): ?>
        <li>
          <a id="nav-bookmarks" href="<?= $site->url() ?>" role="button" class="secondary outline">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            My Bookmarks
          </a>
        </li>
        <?php endif ?>
        <!-- Top Tags (filled by main.js topTags) -->
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
        
        <?php
          $url = $kirby->user()->getStripeCheckoutURL(option('kreativ-anders.memberkit.tiers')[1]['name']);                            
          if (!$kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): 
        ?>
        <li>
        <?= snippet( 'stripe-checkout-button', [ 'id'      => 'premium-checkout-button'
                    ,'classes' => 'pirsch-event=Open+Stripe+Checkout pirsch-meta-source=Header'
                    ,'text'    => 'Premium'
                    ,'url'     => $url]);
                  ?>
        </li>
        <?php endif ?>

        <li><?php snippet('theme-toggle') ?></li>
        <li>
          <a id="logout" href="<?= url('logout') ?>" class="secondary outline" data-pirsch-event="Logout">Logout</a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>
  <div id="content" tabindex="-1"></div>