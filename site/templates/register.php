<?php
/**
 * Templates render the content of your pages. 
 * They contain the markup together with some control structures like loops or if-statements.
 * The `$page` variable always refers to the currently active page. 
 * To fetch the content from each field we call the field name as a method on the `$page` object, e.g. `$page->title()`. * 
 * This default template must not be removed. It is used whenever Kirby cannot find a template with the name of the content file.
 * Snippets like the header, footer and intro contain markup used in multiple templates. They also help to keep templates clean.
 * More about templates: https://getkirby.com/docs/guide/templates/basics
 */
?>
<?php snippet('header') ?>
<?php snippet('modals/login') ?>
<?php snippet('modals/register') ?>

<main class="section">
  <div class="container">
  <?php if(isset($error) && isset($alert) && $kirby->request()->is('POST')): ?> 
    <div class="notification is-danger">

      <?= isset($alert['error']) ? $alert['error'] : '' ?>
      <?= isset($alert['email']) ? $alert['email'] : '' ?>
      <?= isset($alert['password']) ? $alert['password'] : '' ?>
      <?= isset($alert['tos']) ? $alert['tos'] : '' ?>

    </div>
  <?php else: go(); endif;?> 
  </div>
</main>

<?php snippet('footer') ?>