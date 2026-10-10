<?php
  $user = $kirby->user();
  $tiers = option('kreativ-anders.memberkit.tiers');
?>
<?php snippet('header') ?>

<main class="container account-page">
  <article>
    <header>
      <h2>Settings</h2>
      <p><?= esc($user->email()) ?> · <strong><?= esc($user->tier()->or($tiers[0]['name'])) ?></strong></p>
    </header>

    <?php snippet('alert', ['alert' => $alert, 'success' => $success]) ?>

    <section>
      <h3>Email</h3>
      <form action="<?= $page->url() ?>" method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <fieldset role="group">
          <input type="password" id="email-current-password" name="current_password" placeholder="Current Password" aria-label="Current Password" autocomplete="current-password" required>
          <input type="email" id="email" name="email" value="<?= esc(is_array($data) && !empty($data['email']) ? $data['email'] : $user->email(), 'attr') ?>" aria-label="Email" autocomplete="email" required>
          <input type="submit" name="update" value="Change Email" data-pirsch-event="Update User Email">
        </fieldset>
      </form>
    </section>

    <section>
      <h3>Password</h3>
      <form action="<?= $page->url() ?>" method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <fieldset role="group">
          <input type="password" id="password-current-password" name="current_password" placeholder="Current Password" aria-label="Current Password" autocomplete="current-password" required>
          <input type="password" id="password" name="password" minlength="8" placeholder="New Password" aria-label="New Password" autocomplete="new-password" required>
          <input type="submit" name="update" value="Change Password" data-pirsch-event="Update User Password">
        </fieldset>
      </form>
    </section>

    <section>
      <h3>Subscription</h3>
      <a role="button" class="contrast" data-no-instant data-pirsch-event="Manage Subscription" href="<?= url($user->getStripePortalURL()) ?>">Manage Subscriptions</a>
    </section>

    <section>
      <h3>Your Data</h3>
      <div role="group">
        <a role="button" class="outline" href="<?= url('user.json') ?>" target="_blank" data-pirsch-event="Export Data" data-pirsch-meta-format="JSON">Export JSON</a>
        <a role="button" class="outline" href="<?= url('user.csv') ?>" target="_blank" data-pirsch-event="Export Data" data-pirsch-meta-format="CSV">Export CSV</a>
      </div>
    </section>

    <?php
      // paying users learn that deleting the account ends the subscription (Stripe customer is deleted, see memberkit hooks)
      $premium = $user->isAllowed($tiers[1]['name']);
      $confirm = $premium
        ? 'Delete your account? Your bookmarks are deleted and your subscription ends immediately, without refund. This cannot be undone.'
        : 'Delete your account? Your bookmarks are deleted. This cannot be undone.';
    ?>
    <footer>
      <?php if ($premium): ?>
      <p><small>Deleting your account also ends your subscription immediately.</small></p>
      <?php endif ?>
      <form action="<?= $page->url() ?>" method="POST" onsubmit="<?= esc('return confirm(' . json_encode($confirm) . ');', 'attr') ?>">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <fieldset role="group">
          <input type="password" id="delete-current-password" name="current_password" placeholder="Current Password" aria-label="Current Password" autocomplete="current-password" required>
          <input class="danger" type="submit" name="delete" value="Delete Account" data-pirsch-event="Delete User">
        </fieldset>
      </form>
    </footer>
  </article>
</main>

<?php snippet('footer') ?>
