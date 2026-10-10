<?php
/**
 * Error page (404): Kirby renders it with the right status code for every unknown URL
 */
?>
<?php snippet('header') ?>

<main class="container error-page">
  <div class="error-cards" aria-hidden="true">
    <span></span>
    <span></span>
    <span>?</span>
  </div>

  <p class="eyebrow">Error 404 · <?= $page->title()->escape() ?></p>
  <h1><?= $page->intro()->escape() ?> <em class="accent">lost</em>.</h1>
  <?= $page->text()->kt() ?>

  <div class="hero-actions">
    <?php if ($kirby->user()): ?>
      <a href="<?= url() ?>" role="button">Back to your bookmarks</a>
    <?php else: ?>
      <a href="<?= url() ?>" role="button">Go to the homepage</a>
      <a href="<?= url('login') ?>" role="button" class="outline">Login</a>
    <?php endif ?>
  </div>
</main>

<?php snippet('footer') ?>
