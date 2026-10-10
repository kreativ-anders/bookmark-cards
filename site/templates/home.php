<?php
  $user = $kirby->user();

  if (!$user) {
    // the checkout books monthly (tier 1), Stripe Checkout offers yearly (tier 2) as upsell
    $prices       = Memberkit::prices();
    $monthlyPrice = $prices[1] ?? null;
    $yearlyPrice  = $prices[2] ?? null;
  }
?>
<?php snippet('header') ?>

<main>

<?php if ($user): ?>
  <h1 class="visually-hidden">My bookmarks</h1>
  <?php snippet('modals/change') ?>
  <?php snippet('jumbotron') ?>

  <?php if ($error): ?>
  <section class="container">
    <?php snippet('alert', ['alert' => $error]) ?>
  </section>
  <?php endif ?>

  <?php if ($user->isFreeTier() && count($bookmarks) >= option('noPremiumLimit')): ?>
  <?php snippet('premiumbanner') ?>
  <?php endif ?>
<?php else: ?>
  <?php snippet('hero') ?>
  <div id="features" class="feature-stack">
    <?php snippet('features/feature-tags') ?>
    <?php snippet('features/feature-search') ?>
    <?php snippet('features/feature-beauty') ?>
    <?php snippet('features/feature-export') ?>
    <?php snippet('features/feature-extra') ?>
  </div>
  <?php snippet('values') ?>
  <?php snippet('pricing', ['price' => $yearlyPrice, 'monthly' => $monthlyPrice]) ?>
  <?php snippet('faq') ?>
  <?php snippet('techstack') ?>
  <?php snippet('seo-jsonld', ['price' => $yearlyPrice, 'monthly' => $monthlyPrice]) ?>
<?php endif ?>

<?php snippet('bookmarks') ?>

</main>

<?php snippet('footer') ?>
