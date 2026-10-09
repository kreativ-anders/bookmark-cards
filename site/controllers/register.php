<?php

use Kirby\Cache\FileCache;

return function ($kirby) {

  if($kirby->user()) {
    go('/');
  }

  $error = null;
  $alert = null;

	if($kirby->request()->is('post') && get('register')) {

    // REGISTRATIONS PER IP AND HOUR (EVERY ACCOUNT CREATES A FOLDER AND A STRIPE CUSTOMER)
    $throttle = new FileCache(['root' => $kirby->root('cache') . '/registrations']);
    $visitor  = $kirby->visitor()->ip(hash: true);
    $attempts = (int)$throttle->get($visitor, 0);

    // VALIDATE CSRF TOKEN
    if (csrf(get('csrf')) !== true) {

      $alert['error'] = 'Invalid CSRF token!';

    // HONEYPOT: HIDDEN FIELD ONLY BOTS FILL IN
    } elseif (get('bc_hp')) {

      $alert['error'] = 'Could not register user!';
      $error = true;

    } elseif ($attempts >= (int)option('registerLimit', 10)) {

      $alert['error'] = 'Too many registrations. Please try again later.';
      $error = true;

    } else {

      $data = [
        'email'     => is_string(get('email')) ? trim(get('email')) : '',
        'password'  => is_string(get('password')) ? get('password') : '',
        'tos'       => get('tos')
      ];

      $rules = [
        'email'     => ['required', 'email'],
        'password'  => ['required', 'minLength' => 8],
        'tos'       => ['required'],
      ];

      $messages = [
        'email'     => 'Please enter a valid email address',
        'password'  => 'Please enter a valid password',
        'tos'       => 'Please check the box or close the browser window'
      ];

      // INVALID DATA
      if($invalid = invalid($data, $rules, $messages)) {

        $alert = $invalid;
        $error = true;

      // DATA IS GOOD
      } else {

        try {

          // CREATE USER (IMPERSONATION IS RESET EVEN IF CREATION FAILS)
          // Raw values: Kirby validates the email and hashes the password.
          // esc() is for HTML output only — escaping here broke logins with &, <, > or quotes.
          $user = $kirby->impersonate('kirby', fn () => $kirby->users()->create([
            'email'     => $data['email'],
            'role'      => 'user',
            'language'  => 'en',
            'password'  => $data['password']
          ]));

          $throttle->set($visitor, $attempts + 1, 60);

        } catch(Exception $e) {

          if(option('debug')) {
            $alert['error'] = 'Register failed: ' . $e->getMessage();
          }
          else {
            $alert['error'] = 'Could not register user!';
          }

          $error = true;
        }

        // LOGIN USER (THE ACCOUNT EXISTS, SO A FAILED AUTO LOGIN LEADS TO THE LOGIN PAGE)
        if (isset($user)) {

          try {
            $user->login($data['password']);
          } catch(Exception $e) {
            go('login');
          }

          Analytics::track('Registration Completed');
          go('/#welcome');
        }
      }
    }
  }

  // never hand the password back to the template
  if (isset($data['password'])) {
    unset($data['password']);
  }

  return [
    'error'   => $error,
    'alert'   => $alert,
    'data'    => $data ?? false,
    'success' => false
  ];
};
