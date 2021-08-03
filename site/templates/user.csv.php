<?php

header('Content-type: text/plain; charset=UTF-8');

$csv = "";

if($kirby->user()) {

  $bookmarks = $kirby->user()->bookmarks()->yaml();

  $csv .= "Email;" . $kirby->user()->email() . ";\n";
  $csv .= "password;'*********************'" . ";\n";
  $csv .= "Subscription;" . $kirby->user()->tier()->toString() . ";\n";
  $csv .= "\n";

  $csv .= "Title;Link;Tags;\n";

  foreach ($bookmarks as $bookmark) {
    $csv .= $bookmark['title'] . ";" . $bookmark['link'] . ";" . $bookmark['tags'] . ";\n";
  }

}
else {
  $csv = [];
}

echo $csv;