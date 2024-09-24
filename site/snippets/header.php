<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">

  <title>Bookmark.cards | bookmarket tool in its simplest form.</title>
  <meta name="description"
    content="Organize and store your articles, websites or other online links privacy friendly. Search and find those again with an easy to use tagging or search feature.">

  <link rel="dns-prefetch" href="//bookmark.cards">
  <link rel="preconnect" href="//bookmark.cards">

  <?php if ($kirby->user()): ?>
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#ffffff" />
  <!-- UpUp.js --> 
  <script src="/upup.min.js"></script>
  <script>
  UpUp.start({
    'cache-version': Date.now(),
    'content-url': 'offline.html',
    'assets': [
      'favicon.ico', 
      'assets/css/brands.min.css', 
      'assets/css/main.min.css', 
      'offline.min.js', 
      'user.json', 
      'assets/js/main.min.js'],
    'service-worker-url': '/upup.sw.min.js'
  });
  </script>
  <?php endif; ?>

  <script src="assets/js/main.min.js"></script>

  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

  <!-- <link rel="stylesheet" href="assets/css/bulma-custom/bulma-custom.min.css"> -->
  <!-- <link rel="stylesheet" href="assets/css/pico/css/pico.min.css"> -->
  <!-- <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script> -->
  <!-- <script src="https://kit.fontawesome.com/4520751a1c.js" crossorigin="anonymous"></script> -->

  <link rel="stylesheet" href="assets/css/main.min.css">

  <script defer type="text/javascript" src="https://api.pirsch.io/pirsch.js" id="pirschjs"
    data-code="DKo22RMuBesw3XMADDNrwe0fQ7544AG1"></script>


</head>

<body>
  <header>
    <nav role="navigation" aria-label="main navigation">
      <ul>
        <li>
          <a class="navbar-item" href="<?= $site->url() ?>">
            <h1>
              Bookmark
            </h1>
          </a>
          <p style="margin: 0">
            .cards <?= e(option('debug'), " Kirby v" . Kirby::version() . " PHP " . phpversion())?>
            <?php if ($kirby->user() && $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): ?>
            <span><strong style="color: darkgoldenrod">Premium</strong></span>
            <?php endif; ?>
          </p>
        </li>
      </ul>
      <ul>
        <?php  if(!$kirby->user()): ?>
        <li>
          <button id="login" class="contrast outline" data-target="loginModal" onclick="toggleModal(event)">Login</button>
        </li>  
        <li>
          <button id="register" data-target="registerModal" onclick="toggleModal(event)"><strong>Register</strong></button>
        </li>
        <?php endif; ?>

        <?php  if($kirby->user()): ?>
        <!-- Top Tags -->  
         <ul>
          <li id="top-tags-placeholder"></li> 
         </ul>
        <li>
          <button id="user" title="User Settings" class="secondary outline" data-target="userModal" onclick="toggleModal(event)">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M425.1 482.6c-2.303-1.25-4.572-2.559-6.809-3.93l-7.818 4.493c-6.002 3.504-12.83 5.352-19.75 5.352c-10.71 0-21.13-4.492-28.97-12.75c-18.41-20.09-32.29-44.15-40.22-69.9c-5.352-18.06 2.343-36.87 17.83-45.24l8.018-4.669c-.0664-2.621-.0664-5.242 0-7.859l-7.655-4.461c-12.3-6.953-19.4-19.66-19.64-33.38C305.6 306.3 290.4 304 274.7 304H173.3C77.61 304 0 381.7 0 477.4C0 496.5 15.52 512 34.66 512H413.3c5.727 0 10.9-1.727 15.66-4.188c-2.271-4.984-3.86-10.3-3.86-16.06V482.6zM224 256c70.7 0 128-57.31 128-128S294.7 0 224 0C153.3 0 96 57.31 96 128S153.3 256 224 256zM610.5 373.3c2.625-14 2.625-28.5 0-42.5l25.75-15c3-1.625 4.375-5.125 3.375-8.5c-6.75-21.5-18.25-41.13-33.25-57.38c-2.25-2.5-6-3.125-9-1.375l-25.75 14.88c-10.88-9.25-23.38-16.5-36.88-21.25V212.3c0-3.375-2.5-6.375-5.75-7c-22.25-5-45-4.875-66.25 0c-3.25 .625-5.625 3.625-5.625 7v29.88c-13.5 4.75-26 12-36.88 21.25L394.4 248.5c-2.875-1.75-6.625-1.125-9 1.375c-15 16.25-26.5 35.88-33.13 57.38c-1 3.375 .3751 6.875 3.25 8.5l25.75 15c-2.5 14-2.5 28.5 0 42.5l-25.75 15c-3 1.625-4.25 5.125-3.25 8.5c6.625 21.5 18.13 41 33.13 57.38c2.375 2.5 6 3.125 9 1.375l25.88-14.88c10.88 9.25 23.38 16.5 36.88 21.25v29.88c0 3.375 2.375 6.375 5.625 7c22.38 5 45 4.875 66.25 0c3.25-.625 5.75-3.625 5.75-7v-29.88c13.5-4.75 26-12 36.88-21.25l25.75 14.88c2.875 1.75 6.75 1.125 9-1.375c15-16.25 26.5-35.88 33.25-57.38c1-3.375-.3751-6.875-3.375-8.5L610.5 373.3zM496 400.5c-26.75 0-48.5-21.75-48.5-48.5s21.75-48.5 48.5-48.5c26.75 0 48.5 21.75 48.5 48.5S522.8 400.5 496 400.5z"/></svg>
            Settings
          </button>
        </li>
        
        <?php
          $url = $kirby->user()->getStripeCheckoutURL(option('kreativ-anders.memberkit.tiers')[1]['name']);                            
          if (!$kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): 
        ?>
        <li>
        <?= snippet( 'stripe-checkout-button', [ 'id'      => 'premium-checkout-button'
                    ,'classes' => ''
                    ,'text'    => 'Premium'
                    ,'url'     => $url]);
                  ?>
        </li>
        <?php endif ?>

        <li>
          <a id="logout" href="logout" class="secondary outline">Logout</a>
        </li>  
        <?php endif; ?>
      </ul>
    </nav>
  </header>