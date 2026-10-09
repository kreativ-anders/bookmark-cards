<?php
/**
 * Sends server-confirmed events (site/plugins/analytics) to Pirsch.
 * Delayed after load, so the page view is counted first (Pirsch docs).
 */
// only an existing session can hold events: guests never get a session cookie from this
$events = Kirby\Http\Cookie::exists('kirby_session') ? Analytics::pull() : [];
?>
<?php if ($events): ?>
<script>
  window.addEventListener('load', function () {
    setTimeout(function () {
      <?= json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>.forEach(function (e) {
        trackEvent(e.name, e.meta);
      });
    }, 200);
  });
</script>
<?php endif ?>
