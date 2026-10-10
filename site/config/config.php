<?php

// host specific settings (debug, secrets, Stripe keys) live in the git-ignored config.<host>.php
return [
  'debug' => false,
  'panel' =>[
      'install' => false,
      'slug' => 'dashboard',
      'menu' => [
        'site',
        'bookmarks',
        'users' => [
          'current' => fn (string|null $current = null) => in_array($current, ['users', 'user-statistics'], true)
        ],
        'system'
      ],
      'viewButtons' => [
        'users' => ['create', 'statistics', 'stripe-sync']
      ],
      // Panel plugins use render functions, never `template` strings
      'vue' => [
        'compiler' => false
      ]
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
    ['name' => 'Free',    'price' => null],
    ['name' => 'Basic',   'price' => 'price_xxxx'],
    ['name' => 'Premium', 'price' => 'price_xxxx'],
  ],
  'migrate' => false,
];
