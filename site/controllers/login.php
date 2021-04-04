<?php

return function ($kirby) {

  if ($kirby->user()) {
    go();
  }

  $error = false;

  if ($kirby->request()->is('POST') && get('login')) {

    // LOGIN USER
    try {

      $kirby->auth()->login(get('email'), get('password'));
      go('/#yee-haw');

    } catch (Exception $e) {

      $error = 'Email or Password invalid.';
    }
  }

  return [
    'error' => $error
  ];

};