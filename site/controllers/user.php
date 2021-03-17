<?php

return function ($kirby, $page) {

  if(!$kirby->user()) {
    go('/');
  } 

  $error = false;

  // UPDATE USER
  if($kirby->request()->is('post') && get('update')) {

    // EMAIL
    if (V::email(get('email')) && !get('password')) {
      
      try {

        $kirby->user()->changeEmail(get('email'));
        go();
      
      } catch(Exception $e) {
      
        $error = $e;
      }
    }

    // PASSWORD
    if (get('password')) {
      
      try {

        $kirby->user()->changePassword(get('password'));
        go();
      
      } catch(Exception $e) {
      
        $error = $e;  
      }
    } 
  }

  // DELETE USER
  if($kirby->request()->is('post') && get('delete')) {

    try {

      $kirby->user()->delete();
      go();
    
    } catch(Exception $e) {
    
      $error = $e;    
    }
  }
    
  return [
    'error' => $error
  ];
};