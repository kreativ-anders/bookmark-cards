<?php

return function ($kirby) {

  if($kirby->user()) {
    go('/');
  } 
  
  $error = null;
  $alert = null;

	if($kirby->request()->is('post') && get('register')) {

    // VALIDATE CSRF TOKEN
    if (csrf(get('csrf')) === true) {

      $data = [
        'email'     => get('email'),
        'password'  => get('password'),
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
      
        $kirby = kirby();
        $kirby->impersonate('kirby');
  
        try {
  
          // CREATE USER
          $user = $kirby->users()->create([
            'email'     => esc(get('email')),
            'role'      => 'user',
            'language'  => 'en',
            'password'  => esc(get('password'))
          ]);
  
          $kirby->impersonate();
  
          // LOGIN USER
          if($user && $user->login(get('password'))) {
            go('/#welcome', 204);
          } 
  
        } catch(Exception $e) {
        
          if(option('debug')) {
            $alert['error'] = 'Register failed: <strong>' . $e->getMessage() . '</strong>';
          }
          else {
            $alert['error'] = 'Could not register user!';
          } 
          
          $error = true;          
        }
  
        // SUCCESS
        if (empty($alert) === true) {
          $data = [];
        }
      } 
    // INVALID CSRF TOKEN  
    } else {

      $alert['error'] = 'Invalid CSRF token!';
    }
  }

  return [
    'error'   => $error,
    'alert'   => $alert,
    'data'    => $data ?? false,
    'success' => $success ?? false
  ];     
};