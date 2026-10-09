<?php

return function ($kirby, $page) {

  if(!$kirby->user()) {
    go('login');
  }

  $error   = null;
  $alert   = null;
  $session = $kirby->session();

  // POST REQUESTS (CHANGE EMAIL / CHANGE PASSWORD / DELETE) REQUIRE A VALID CSRF TOKEN
  if($kirby->request()->is('post') && csrf(get('csrf')) !== true) {

    $alert['error'] = 'Invalid CSRF token!';

  // UPDATE USER
  } elseif($kirby->request()->is('post') && get('update')) {

    $data = [
      'email'     => is_string(get('email')) ? trim(get('email')) : '',
      'password'  => is_string(get('password')) ? get('password') : ''
    ];

    $rules = [
      'email'     => ['email'],
      'password'  => ['minLength' => 8]
    ];

    $messages = [
      'email'     => 'Please enter a valid email adress',
      'password'  => 'Please enter an eight character password'
    ];

    // INVALID DATA
    if($invalid = invalid($data, $rules, $messages)) {

      $alert = $invalid;
      $error = true;

    // VALID DATA
    } else {

      try {

        // RE-AUTHENTICATE: A HIJACKED SESSION ALONE MUST NOT TAKE OVER THE ACCOUNT
        $kirby->auth()->validatePassword($kirby->user()->email(), (is_string(get('current_password')) ? get('current_password') : ''));

      } catch(Exception $e) {

        $alert['error'] = 'Wrong current password, nothing was changed!';
        $error = true;
      }

      // EMAIL
      if (empty($alert) === true && $data['email'] !== '') {

        try {

          $kirby->user()->changeEmail($data['email']);
          $success = 'Your email has been changed!';

        } catch(Exception $e) {

          if(option('debug')) {

            $alert['error'] = 'The user email could not be changed: ' . $e->getMessage();
          }
          else {

            $alert['error'] = 'The user email could not be changed!';
          }
        }
      }

      // PASSWORD
      if (empty($alert) === true && $data['password'] !== '') {

        try {

          $kirby->user()->changePassword($data['password']);
          $success = 'Your password has been changed!';

        } catch(Exception $e) {

          if(option('debug')) {

            $alert['error'] = 'The user password could not be changed: ' . $e->getMessage();
          }
          else {

            $alert['error'] = 'The user password could not be changed!';
          }
        }
      }

      // SUCCESSFUL: POST/REDIRECT/GET (no resubmission on reload, fresh user object)
      if (empty($alert) === true && isset($success)) {

        $session->set('user.success', $success);
        go($page->url());
      }
    }

  // DELETE USER
  } elseif($kirby->request()->is('post') && get('delete')) {

    try {

      // RE-AUTHENTICATE: A HIJACKED SESSION ALONE MUST NOT DELETE THE ACCOUNT
      // (AUTH TRACKS FAILED ATTEMPTS AND BLOCKS BRUTE FORCE LIKE THE LOGIN DOES)
      $kirby->auth()->validatePassword($kirby->user()->email(), (is_string(get('current_password')) ? get('current_password') : ''));

      try {

        $tier = $kirby->user()->tier()->toString();
        $kirby->user()->delete();
        Analytics::track('Delete User Completed', ['plan' => $tier]);
        go('/');

      } catch(Exception $e) {

        if(option('debug')) {

          $alert['error'] = 'The user could not be deleted: ' . $e->getMessage();
        }
        else {

          $alert['error'] = 'The user could not be deleted!';
        }
        $error = true;
      }

    } catch(Exception $e) {

      $alert['error'] = 'Wrong password, the account was not deleted!';
      $error = true;
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
    'success' => $session->pull('user.success')
  ];
};
