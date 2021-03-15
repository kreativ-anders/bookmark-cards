<?php
/**
 * Snippets are a great way to store code snippets for reuse or to keep your templates clean.
 * This header snippet is reused in all templates. 
 * It fetches information from the `site.txt` content file and contains the site navigation.
 * More about snippets: https://getkirby.com/docs/guide/templates/snippets
 */
?>

<!DOCTYPE html>
<html lang="de">
<head>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">

  <!-- The title tag we show the title of our site and the title of the current page -->
  <title><?= $site->title() ?> | <?= $page->title() ?></title>
  <meta name="description" content="">

  <link rel="dns-prefetch" href="//mystartseite.net">
  <link rel="preconnect" href="//mystartseite.net">
  
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
  <link rel="manifest" href="/site.webmanifest">

  <link rel="stylesheet" href="assets/css/bulma-custom/bulma-custom.css">

  <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script>

  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/brands.css">
  

</head>
<pre>
<?php

var_dump(option('kreativ-anders.memberkit.secretKey'));

?>
</pre>
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
                    myStartseite              
                  </h1>
                  <h2 class="subtitle">
                    #StopPinTab
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
                <?php  if(!$kirby->user() && $page->id() != 'register'): ?>
                  <button id="register" class="button is-primary" onclick="$('#registerModal').toggleClass('is-active');"><strong>Registrieren</strong></button>
                  <button id="login" class="button is-light" onclick="$('#loginModal').toggleClass('is-active');">Anmelden</button>
                <?php endif; ?>
                <?php  if($kirby->user()): ?>
                  <button class="button is-success is-light is-hidden-tablet " onclick="$('#jumbotron').toggleClass('is-hidden-mobile');">
                    <span class="icon is-small">
                      <i class="fas fa-plus"></i>
                    </span>
                  </button>
                  <button id="user" class="button is-black is-light is-medium" onclick="$('#userModal').toggleClass('is-active');">
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

                  <a id="logout" href="logout" class="button is-danger is-light"><span>Abmelden</span></a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </nav> 
    </header>