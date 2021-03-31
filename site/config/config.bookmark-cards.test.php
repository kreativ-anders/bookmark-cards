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
      'install' => true
  ],
  'noPremiumLimit' => 4,
  'noPremiumTitle' => 'BECOME PREMIUM',
  'noPremiumLink' => "javascript:document.getElementById('premium-checkout-button').click()",
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
    ]
  ],
  'kreativ-anders.memberkit.secretKey'     => 'sk_test_v4lTx4UUJ27R8ENkRwpJnPun00EEj1Kajd',
  'kreativ-anders.memberkit.publicKey'     => 'pk_test_NPtMizChMDJ9SIRUKzEVQKxS00vzPp4bey',
  'kreativ-anders.memberkit.webhookSecret' => 'whsec_VsnjOx8yRSMs7cjwFxHfw0kaj3NASwKU',
  'kreativ-anders.memberkit.stripeURLSlug' => 'checkout',
  'kreativ-anders.memberkit.successURL'    => 'https://bookmarks-card.test/',
  'kreativ-anders.memberkit.cancelURL'     => 'https://bookmarks-card.test/',
  'kreativ-anders.memberkit.tiers'         => [
    // INDEX 0
    [ 'name'  => 'Basic'
     ,'price' => null],
    // INDEX 1
    [ 'name'  => 'Premium - Monthly'
     ,'price' => 'price_1IW22TKhtwhttZ1RFIUglWmm'],
    // INDEX 2
    [ 'name'  => 'Premium - Yearly'
     ,'price' => 'price_1IW22TKhtwhttZ1ROtTCAW2J'],
    // INDEX X
  ]
];
