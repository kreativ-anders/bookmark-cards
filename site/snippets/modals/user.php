<dialog id="userModal">
  <article>
    <header>
      <a href="#close"
        aria-label="Close"
        class="close"
        data-target="userModal"
        onClick="toggleModal(event)">
      </a>
      <h3><?= $kirby->user()->email(); ?></i> | Settings</h3>
    </header>
    <form action="/user" method="POST">
      <fieldset>
        <input type="email" id="email" name="email" value="<?= esc(get('email', '')) ?>" placeholder="<?= (get('email'))? get('email') : $kirby->user()->email() ?>" autocomplete="email" required>
      </fieldset>
      <input type="submit" name="update" value="Change Email" data-pirsch-event="Update User Email">
    </form>
    <form action="/user" method="POST">
      <fieldset>
        <input type="password" id="password" name="password" value="<?= esc(get('password', '')) ?>" placeholder="New Password" autocomplete="new-password" required>
      </fieldset>
      <input type="submit" name="update" value="Change Password" data-pirsch-event="Update User Password">
    </form>
    <a role="button" data-pirsch-event="Manage Subscription" class="contrast" href="<?= $kirby->user()->getStripePortalURL() ?>">Manage Subscriptions</a>
    <a href="user.json" class="outline" target="_blank" data-tooltip="in JSON or CSV format" role="button">Export Data</a>
    <footer>
      <form action="user" method="POST" onsubmit="return confirm('This action cannot be revert! Are you sure?');">
        <input style="background-color: red; border-color: grey;" type="submit" name="delete" value="Delete Account" data-pirsch-event="Delete User">
      </form>
    </footer>
  </article>
</dialog>