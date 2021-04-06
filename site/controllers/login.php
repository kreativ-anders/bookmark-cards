<?php

return function ($kirby) {

  if ($kirby->user()) {
    go();
  }

  $error = null;
  $alert = null;

  if ($kirby->request()->is('POST') && get('login')) {

    $data = [
      'email'     => get('email'),
      'password'  => get('password')
    ];

    $rules = [
      'email'     => ['required', 'email'],
      'password'  => ['required']
    ];

    $messages = [
      'email'     => 'Please enter a valid email address',
      'password'  => 'Please enter a valid password'
    ];

    // INVALID DATA
    if($invalid = invalid($data, $rules, $messages)) {

      $alert = $invalid;
      $error = true;

    // DATA IS GOOD
    } else {

      // LOGIN USER
      try {

        $kirby->auth()->login(get('email'), get('password'));
        go('/#yee-haw', 202);

      } catch (Exception $e) {

        if(option('debug')) {
          $alert['error'] = 'Login failed: <strong>' . $e->getMessage() . '</strong>';
        }
        else {
          $alert['error'] = 'Could not log in user!';
        } 
        
        $error = true;
      }

      // SUCCESS
      if (empty($alert) === true) {
        $data = [];
      }
    }
  }

  return [
    'error'   => $error,
    'alert'   => $alert,
    'data'    => $data ?? false,
    'success' => $success ?? false
  ];
};