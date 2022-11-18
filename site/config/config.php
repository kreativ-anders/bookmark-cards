<?php

/**
 * The config file is optional. It accepts a return array with config options
 * Note: Never include more than one return statement, all options go within this single return array
 * In this example, we set debugging to true, so that errors are displayed onscreen. 
 * This setting must be set to false in production.
 * All config options: https://getkirby.com/docs/reference/system/options
 */
return [
  'debug' => true,
  'panel' =>[
      'install' => false,
      'slug' => 'dashboard'
  ],
  'content' => [
    'uuid' => false
  ],
  'noPremiumLimit' => 12,
  'noPremiumTitle' => 'BECOME PREMIUM',
  'noPremiumLink' => '#',
  'noPremiumTags' => 'NO LIMITS',
  'session' => [
    'durationNormal' => 1209600, 
    'timeout'        => 604800,
  ],
  'routes' => [
    [
      'pattern' => 'logout',
      'action'  => function() {

        if ($user = kirby()->user()) {
          $user->logout();
        }

        go('login');
      }
    ],
    [
      'pattern' => 'login/success',
      'action'  => function() {

        if ($user = kirby()->user()) {
          go('/#yee-haw');
        }

      }
    ]
  ],
  'kreativ-anders.memberkit.secretKey'     => 'sk_test_xxx',
  'kreativ-anders.memberkit.publicKey'     => 'pk_test_xxx',
  'kreativ-anders.memberkit.webhookSecret' => 'whsec_xxx',
  'kreativ-anders.memberkit.stripeURLSlug' => 'checkout',
  'kreativ-anders.memberkit.successURL'    => '../success',
  'kreativ-anders.memberkit.cancelURL'     => '../cancel',
  'kreativ-anders.memberkit.tiers'         => [
    // INDEX 0
    [ 'name'  => 'Free'
     ,'price' => null],
    // INDEX 1
    [ 'name'  => 'Basic'
     ,'price' => 'price_xxxx'],
    // INDEX 2
    [ 'name'  => 'Premium'
     ,'price' => 'price_xxxx'],
    // INDEX X
  ],
  'migrate' => false,
];
