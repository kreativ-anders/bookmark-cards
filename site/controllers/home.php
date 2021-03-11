<?php

return function ($kirby, $page) {

  $error = null;

  if($kirby->user()) {
    $bookmarks = $kirby->user()->bookmarks()->yaml();
  } 
  else {
    $bookmarks = $page->bookmarks()->yaml();
  }

  if (get('u_tags') !== null or get('c_tags') !== null) {
    $arr = Str::split(get('u_tags', '') . get('c_tags', ''), ",");
    $tags = A::join(array_intersect_key($arr, array_unique(array_map('strtolower', $arr))));
  }
  else {
    $tags = '';
  } 

	if($user = $kirby->user() and $kirby->request()->is('POST')) {

    // CRUD Naming Convention!
    // -----------------------
    // c_ => Create
    // r_ => Read
    // u_ => Update
    // d_ => Delete
    // -----------------------

    // UpdateBookmark()
    if((int) get('u_id') >= 0 and get('u_title') and get('u_link')) {

      $bookmarks = $user->bookmarks()->yaml();  

      $arr = array(
        (int) get('u_id') => array( 'title' => get('u_title')
                                   ,'link'  => get('u_link')
                                   ,'tags'  => $tags)
      );

      $bookmarks = array_replace($bookmarks, $arr);

      try {
        $user->update([
          'Bookmarks' => Yaml::encode($bookmarks)
        ]);
  
      } catch(Exception $e) {
  
        $error = $e->getMessage(); 
      }
    }

    // AddBookmark()
    if(get('c_title') and get('c_link')) {

      $bookmarks = $user->bookmarks()->yaml();

      $arr = array(
         'title' => get('c_title')
        ,'link'  => get('c_link')
        ,'tags'  => $tags);

      
      array_push($bookmarks, $arr);

      try {
        $user->update([
          'Bookmarks' => Yaml::encode($bookmarks)
        ]);
  
      } catch(Exception $e) {
  
        $error = $e->getMessage(); 
      }
    }

    // DeleteBookmark()
    if(get('d_bookmark')) {

      $bookmarks = $user->bookmarks()->yaml();

      array_splice($bookmarks, get('d_bookmark'), 1);

      try {
        $user->update([
          'Bookmarks' => Yaml::encode($bookmarks)
        ]);
  
      } catch(Exception $e) {
  
        $error = $e->getMessage(); 
      }
    }



  }
    
  return [
    'error' => $error,
    'bookmarks' => $bookmarks
  ];
};