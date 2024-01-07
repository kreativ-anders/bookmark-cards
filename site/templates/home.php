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

<?php snippet('header') ?>
<?php snippet('modals/login') ?>
<?php snippet('modals/register') ?>

<?php  if ($kirby->user()) {
  snippet('modals/change'); 
  snippet('modals/user'); 
} ?>

<hr style="margin-bottom: unset;">

<?php if (!$kirby->user()) {
  snippet('hero'); 
  snippet('features/feature-tags'); 
  snippet('features/feature-search'); 
  snippet('features/feature-beauty'); 
  snippet('features/feature-export'); 
  snippet('features/feature-extra'); 
} ?>

<?php if ($kirby->user()) {
  snippet('jumbotron'); 
} ?>

<?php if ($kirby->user() && option('kreativ-anders.memberkit.tiers')[0]['name'] === $kirby->user()->tier()->toString() && count($bookmarks) >= option('noPremiumLimit')) {
  snippet('premiumbanner');
} ?>

<?php if (!$kirby->user()) {
  snippet('values');  
  snippet('pricing');
} ?>

<?php snippet('bookmarks') ?>




</body>

</main>

<?php snippet('footer') ?>
