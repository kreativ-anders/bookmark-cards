<?php

/*
| Imports a user.json export into an existing account (CLI only, not exposed in the frontend yet)
|
| php site/plugins/bookmarks/import.php <email> <export.json> [--replace]
|
| Appends by default and skips exact duplicates; --replace drops the existing bookmarks first.
*/

if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

$root = dirname(__DIR__, 3);
require $root . '/kirby/bootstrap.php';

$args    = array_values(array_filter(array_slice($argv, 1), fn ($arg) => $arg !== '--replace'));
$replace = in_array('--replace', $argv, true);

if (count($args) !== 2) {
  fwrite(STDERR, "Usage: php site/plugins/bookmarks/import.php <email> <export.json> [--replace]\n");
  exit(1);
}

[$email, $file] = $args;

$kirby = new Kirby(['roots' => ['index' => $root]]);
$kirby->impersonate('kirby');

if (!($user = $kirby->user($email))) {
  fwrite(STDERR, "No user with the email {$email}\n");
  exit(1);
}

try {
  $export = json_decode((string)@file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
  fwrite(STDERR, "Could not read {$file}: {$e->getMessage()}\n");
  exit(1);
}

$result = Bookmarks::import($user, is_array($export) ? $export : [], $replace);

foreach ($result['skipped'] as [$entry, $reason]) {
  echo "Skipped ({$reason}): " . json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "{$result['imported']} bookmarks imported for {$email}" . ($replace ? ' (replaced the existing ones)' : '') . ", " . count($result['skipped']) . " skipped\n";
