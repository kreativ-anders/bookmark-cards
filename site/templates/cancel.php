<?php
/**
 * How to cancel Premium (linked in the footer); also Stripe's return page for an aborted checkout
 */
  $user    = $kirby->user();
  $tiers   = option('kreativ-anders.memberkit.tiers');
  $contact = 'https://kreativ-anders.de/kontakt';
?>
<?php snippet('header') ?>

<main class="container account-page">
  <article>
    <header>
      <h2>Cancel subscription</h2>
      <p>Premium stays active until the end of the period you already paid for. After that nothing is charged and your account becomes <?= esc($tiers[0]['name']) ?>. Your bookmarks stay.</p>
    </header>

    <?php if ($user && $user->isPremium()): ?>

      <?php if ($user->stripe_cancel_at()->isNotEmpty()): ?>
      <section>
        <h3>Already canceled</h3>
        <p><?= esc($user->tier()) ?> stays active until <strong><?= $user->stripe_cancel_at()->toDate('F j, Y') ?></strong>. Changed your mind? Renew it under <em>Manage Subscriptions</em>.</p>
        <a role="button" class="contrast" data-no-instant data-pirsch-event="Manage Subscription" href="<?= url($user->getStripePortalURL()) ?>">Manage Subscriptions</a>
      </section>
      <?php else: ?>
      <section>
        <h3>Cancel now</h3>
        <p>Open <em>Manage Subscriptions</em> and choose <em>Cancel subscription</em>. You get a confirmation right there.</p>
        <a role="button" class="contrast" data-no-instant data-pirsch-event="Manage Subscription" href="<?= url($user->getStripePortalURL()) ?>">Manage Subscriptions</a>
      </section>
      <?php endif ?>

    <?php elseif ($user): ?>

      <section>
        <h3>Nothing to cancel</h3>
        <p>Your account has no active subscription.</p>
        <a role="button" class="outline" href="<?= url() ?>">Back to your bookmarks</a>
      </section>

    <?php else: ?>

      <section>
        <h3>With your account</h3>
        <p>Log in, open <em>Settings</em> and choose <em>Manage Subscriptions</em>. There you cancel with one click.</p>
        <a role="button" class="contrast" href="<?= url('login') ?>">Log in</a>
      </section>

      <section>
        <h3>Forgot your login?</h3>
        <p>Write us through the contact form of kreativ-anders. Include the email of your Bookmark.cards account. We cancel at the end of the paid period and confirm it by email.</p>
        <a role="button" class="outline" href="<?= $contact ?>" target="_blank" rel="noopener">Contact form</a>
      </section>

    <?php endif ?>

    <footer>
      <p><small>Left the checkout without paying? Nothing was charged.</small></p>
    </footer>
  </article>
</main>

<?php snippet('footer') ?>
