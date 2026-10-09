<?php

// STRIPE IS LOADED VIA COMPOSER (vendor/autoload.php, included by kirby/bootstrap.php)

Kirby::plugin('kreativ-anders/memberkit', [

  'options'       => include_once __DIR__ . '/options.php',
  'snippets'      => include_once __DIR__ . '/snippets.php',
  'hooks'         => include_once __DIR__ . '/hooks.php',
  'routes'        => include_once __DIR__ . '/routes.php',
  'userMethods'   => include_once __DIR__ . '/userMethods.php',
  'usersMethods'  => include_once __DIR__ . '/usersMethods.php',
  'siteMethods'   => include_once __DIR__ . '/siteMethods.php',
]);
?>