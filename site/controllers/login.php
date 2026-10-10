<?php

return function ($kirby) {

  if ($kirby->user()) {
    go('/');
  }

  $error = null;
  $alert = null;

  if ($kirby->request()->is('POST') && get('login')) {

    if (csrf(get('csrf')) === true) {

      $data = [
        'email'     => is_string(get('email')) ? trim(get('email')) : '',
        'password'  => is_string(get('password')) ? get('password') : ''
      ];

      $rules = [
        'email'     => ['required', 'email'],
        'password'  => ['required']
      ];

      $messages = [
        'email'     => 'Please enter a valid email address',
        'password'  => 'Please enter a password'
      ];

      if($invalid = invalid($data, $rules, $messages)) {

        $alert = $invalid;
        $error = true;

      } else {

        try {

          try {

            $kirby->auth()->login($data['email'], $data['password']);

          } catch (Exception $e) {

            // LEGACY: accounts registered before the fix stored esc()'d passwords.
            // Accept the escaped variant once, re-save the raw password and log in.
            // (password_verify instead of a 2nd login() so it doesn't count as another failed trial)
            // Never while rate-limited, otherwise this path would allow unlimited guesses.
            $escaped = esc($data['password']);
            $legacy  = $kirby->user($data['email']);

            if ($escaped === $data['password'] || !$legacy || $kirby->auth()->isBlocked($data['email']) || !password_verify($escaped, (string)$legacy->password())) {
              throw $e;
            }

            $kirby->impersonate('kirby', fn () => $legacy->changePassword($data['password']));

            $kirby->auth()->login($data['email'], $data['password']);
          }

        } catch (Exception $e) {

          if(option('debug')) {

            $alert['error'] = 'Invalid email or password: ' . $e->getMessage();
          }
          else {

            $alert['error'] = 'Invalid email or password!';
          }
        }

        if (empty($alert) === true) {

          $data = [];
          go();
        }
      }
    } else {

      $alert['error'] = 'Invalid CSRF token!';
    }
  }

  // never hand the password back to the template
  if (isset($data['password'])) {
    unset($data['password']);
  }

  return [
    'error'   => $error,
    'alert'   => $alert,
    'data'    => $data ?? false
  ];
};