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
      <input type="email" id="email" name="email" value="<?= esc(get('email', '')) ?>" placeholder="<?= (get('email'))? get('email') : $kirby->user()->email() ?>" autocomplete="email">
      <input type="submit" name="update" value="Change Email">
    </form>
      
    <form action="/user" method="POST">
      <input type="password" id="password" name="password" value="<?= esc(get('password', '')) ?>" placeholder="Password" autocomplete="new-password">
      <input type="submit" name="update" value="Change Password">
    </form>
    <a role="button" class="contrast" href="<?= $kirby->user()->getStripePortalURL() ?>">Manage Subscriptions</a>
    <a href="user.json" class="outline" target="_blank" data-tooltip="in JSON or CSV format" role="button">Export Data</a>
    <footer>
      <form action="user" method="POST" onsubmit="return confirm('This action cannot be revert! Are you sure?');">
        <input style="background-color: red;" type="submit" name="delete" value="Delete Account">
      </form>
    </footer>
  </article>
</dialog>