<?php

return function ($kirby, $page) {

  if(!$kirby->user()) {
    go('/');
  } 

  $error = null;
  $alert = null;

  // UPDATE USER
  if($kirby->request()->is('post') && get('update')) {

    $data = [
      'email'     => get('email'),
      'password'  => get('password')
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

      // EMAIL
      if (V::email($data['email']) && !get('password')) {
        
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
      if ($data['password']) {
        
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

      // SUCCESSFUL
      if (empty($alert) === true) {

        $error = null;
      }
    } 
  }

  // DELETE USER
  if($kirby->request()->is('post') && get('delete')) {

    try {

      $kirby->user()->delete();
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
  }
    
  return [
    'error'   => $error,
    'alert'   => $alert,
    'data'    => $data ?? false,
    'success' => $success ?? false
  ];
};