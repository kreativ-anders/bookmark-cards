<?php

return function ($kirby, $page) {

  if(!$kirby->user()) {
    go('login');
  }

  $error   = null;
  $alert   = null;
  $session = $kirby->session();

  if($kirby->request()->is('post') && csrf(get('csrf')) !== true) {

    $alert['error'] = 'Invalid CSRF token!';

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
      'email'     => 'Please enter a valid email address',
      'password'  => 'Please enter an eight character password'
    ];

    if($invalid = invalid($data, $rules, $messages)) {

      $alert = $invalid;
      $error = true;

    } else {

      try {

        // re-authenticate: a hijacked session alone must not take over the account
        $kirby->auth()->validatePassword($kirby->user()->email(), (is_string(get('current_password')) ? get('current_password') : ''));

      } catch(Exception $e) {

        $alert['error'] = 'Wrong current password, nothing was changed!';
        $error = true;
      }

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

      // POST/REDIRECT/GET: no resubmission on reload, fresh user object
      if (empty($alert) === true && isset($success)) {

        $session->set('user.success', $success);
        go($page->url());
      }
    }

  } elseif($kirby->request()->is('post') && get('delete')) {

    try {

      // re-authenticate: a hijacked session alone must not delete the account
      // (auth tracks failed attempts and blocks brute force like the login does)
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
