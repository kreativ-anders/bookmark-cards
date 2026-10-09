<?php
/**
 * Templates render the content of your pages. 
 * They contain the markup together with some control structures like loops or if-statements.
 * The `$page` variable always refers to the currently active page. 
 * To fetch the content from each field we call the field name as a method on the `$page` object, e.g. `$page->title()`. 
 * This home template renders content from others pages, the children of the `photography` page to display a nice gallery grid.
 * Snippets like the header and footer contain markup used in multiple templates. They also help to keep templates clean.
 * More about templates: https://getkirby.com/docs/guide/templates/basics
 */
?>

<?php
  // Premium prices for pricing + structured data, page still renders without Stripe
  // monthly (tier 1) is what the checkout buttons book, Stripe Checkout offers yearly (tier 2) as upsell
  $premiumPrice = null;
  $monthlyPrice = null;
  if (!$kirby->user()) {
    try {
      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
      $monthlyPrice = $stripe->prices->retrieve(option('kreativ-anders.memberkit.tiers')[1]['price'], [])->unit_amount / 100;
      $premiumPrice = $stripe->prices->retrieve(option('kreativ-anders.memberkit.tiers')[2]['price'], [])->unit_amount / 100;
    } catch (\Throwable $e) {
      // keep whatever was resolved, the pricing snippet shows '—' for the rest
    }
  }
?>
<?php snippet('header') ?>

<main>

<?php  if ($kirby->user()) {
  echo '<h1 class="visually-hidden">My bookmarks</h1>';
  snippet('modals/change'); 
} ?>

<?php if (!$kirby->user()) {
  snippet('hero');
  // wrapper bounds the sticky stacking of the feature cards (main.css)
  echo '<div class="feature-stack">';
  snippet('features/feature-tags');
  snippet('features/feature-search');
  snippet('features/feature-beauty');
  snippet('features/feature-export');
  snippet('features/feature-extra');
  echo '</div>';
} ?>

<?php if ($kirby->user()) {
  snippet('jumbotron'); 
} ?>

<?php if ($kirby->user() && $error): ?>
<section class="container">
  <?php snippet('alert', ['alert' => $error]) ?>
</section>
<?php endif ?>

<?php if ($kirby->user() && option('kreativ-anders.memberkit.tiers')[0]['name'] === $kirby->user()->tier()->toString() && count($bookmarks) >= option('noPremiumLimit')) {
  snippet('premiumbanner');
} ?>

<?php if (!$kirby->user()) {
  snippet('values');  
  snippet('pricing', ['price' => $premiumPrice, 'monthly' => $monthlyPrice]);
  snippet('faq');
  snippet('techstack');
  snippet('seo-jsonld', ['price' => $premiumPrice]);
} ?>

<?php snippet('bookmarks') ?>

</main>

<?php snippet('footer') ?>
