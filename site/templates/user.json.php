<?php

$json = null;

if($kirby->user()) {

  $bookmarks = $kirby->user()->bookmarks()->yaml();

  $json = [
    'User'          => [
      'Email'       => $kirby->user()->email(),
      'Password'    => '*********************',
      'LastLogin'   => $kirby->user()->lastLogin()->toString(),
      'Subscription'  => $kirby->user()->tier()->toString()],
    'Bookmarks'     => $bookmarks,
  ];

}
else {
  $json = [];
}

echo json_encode($json);