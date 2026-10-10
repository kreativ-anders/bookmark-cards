<?php

return [
  'cache'         => true,
  'secretKey'     => 'sk_test_xxx',
  'publicKey'     => 'pk_test_xxx',
  'webhookSecret' => 'whsec_xxx',
  'apiBase'       => null,
  'stripeURLSlug' => 'checkout',
  'successURL'    => '../success',
  'cancelURL'     => '../cancel',
  'tiers'         => [
    ['name' => 'Free',    'price' => null],
    ['name' => 'Basic',   'price' => 'price_xxxx'],
    ['name' => 'Premium', 'price' => 'price_xxxx'],
  ],
];
