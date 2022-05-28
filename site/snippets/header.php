<?php
/**
 * Snippets are a great way to store code snippets for reuse or to keep your templates clean.
 * This header snippet is reused in all templates. 
 * It fetches information from the `site.txt` content file and contains the site navigation.
 * More about snippets: https://getkirby.com/docs/guide/templates/snippets
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">

  <!-- The title tag we show the title of our site and the title of the current page -->
  <title>Bookmark.cards | bookmarket tool in its simplest form.</title>
  <meta name="description" content="Organize and store your articles, websites or other online links privacy friendly. Search and find those again with an easy to use tagging or search feature.">

  <link rel="dns-prefetch" href="//bookmark.cards">
  <link rel="preconnect" href="//bookmark.cards">

  <?php if ($kirby->user()): ?>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ffffff"/>
  <?php endif; ?>
    
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

  <link rel="stylesheet" href="assets/css/bulma-custom/bulma-custom.min.css">
  <!-- <link rel="stylesheet" href="assets/css/pico/css/pico.min.css"> -->
  <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script>

  <link rel="stylesheet" href="assets/css/main.min.css">
  <link rel="stylesheet" href="assets/css/brands.min.css">

  <script type="module">
    import 'https://cdn.jsdelivr.net/npm/@pwabuilder/pwaupdate';
    const el = document.createElement('pwa-update');
    document.body.appendChild(el);
  </script>

<script defer type="text/javascript" src="https://api.pirsch.io/pirsch.js"
    id="pirschjs"
    data-code="DKo22RMuBesw3XMADDNrwe0fQ7544AG1"></script>
  

</head>
<body>
  <div class="page">
    <header class="header">
      <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
          <a class="navbar-item" href="<?= $site->url() ?>">
            <section class="hero">
              <div class="hero-body" style="padding: 2rem 3rem 0rem 5rem">
                <div class="container">
                  <h1 class="title">
                    Bookmark            
                  </h1>
                  <h2 class="subtitle">
                    .cards 
                    <?php if ($kirby->user() && $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): ?>
                      <span><strong style="color: darkgoldenrod">Premium</strong></span>
                    <?php endif; ?>
                  </h2>
                </div>
              </div>
            </section>
          </a> 
          <a role="button" class="navbar-burger burger" aria-label="menu" aria-expanded="false" data-target="navbarBasic">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
          </a>
        </div>
        <div id="navbarBasic" class="navbar-menu">
          <div class="navbar-end">
            <div class="navbar-item">
              <div class="buttons is-right">
                <?php  if(!$kirby->user()): ?>
                  <button id="login" class="button is-light" onclick="document.getElementById('loginModal').classList.toggle('is-active');">Login</button>
                  <button id="register" class="button is-primary" onclick="document.getElementById('registerModal').classList.toggle('is-active');"><strong>Register</strong></button>
                <?php endif; ?>
                <?php  if($kirby->user()): ?>
                  <button id="user-settings" class="button is-success is-light is-hidden-tablet " onclick="document.getElementById('jumbotron').classList.toggle('is-hidden-mobile');">
                    <span class="icon is-small">
                      <i class="fas fa-plus"></i>
                    </span>
                  </button>
                  <button id="user" class="button is-black is-light is-medium" onclick="document.getElementById('userModal').classList.toggle('is-active');">
                    <span class="icon is-small">
                      <i class="fas fa-user-cog"></i>
                    </span>
                  </button>

                  <?php
                    $url = $kirby->user()->getStripeCheckoutURL(option('kreativ-anders.memberkit.tiers')[1]['name']);                            
                    if (!$kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): 
                  ?>
                  <?= 
                    snippet('stripe-checkout-button', [ 'id'      => 'premium-checkout-button'
                                                       ,'classes' => 'button is-warning is-light'
                                                       ,'text'    => '<span class="icon">
                                                                        <i class="fas fa-crown"></i>
                                                                      </span>
                                                                      <strong>Premium</strong>'
                                                       ,'url'     => $url]);
                  ?>
                  <?php endif ?>

                  <a id="logout" href="logout" class="button is-danger is-light"><span>Logout</span></a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </nav> 
    </header>