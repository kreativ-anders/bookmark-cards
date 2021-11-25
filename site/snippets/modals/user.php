<div class="modal" id="userModal">
  <div class="modal-background" onclick="$('#userModal').toggleClass('is-active');"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title"><i><?= $kirby->user()->email(); ?></i> | Settings</p>
      <button onclick="$('#userModal').toggleClass('is-active');" class="delete" aria-label="close"></button>
    </header>
    <section class="modal-card-body">
      <form action="/user" method="POST">
        <div class="field has-addons">
          <p class="control">
            <label for="email" class="button is-fullwidth">Email</label>
          </p>
          <div class="control is-expanded">
            <input class="input" type="email" id="email" name="email" value="<?= esc(get('email', '')) ?>"
              placeholder="<?= (get('email'))? get('email') : $kirby->user()->email() ?>" autocomplete="email">
          </div>
          <div class="control">
            <input type="submit" class="button is-primary" name="update" value="Update">
          </div>
        </div>
      </form>
      <br />
      <form action="/user" method="POST">
        <div class="field has-addons">
          <p class="control">
            <label for="password" class="button is-fullwidth">Password</label>
          </p>
          <div class="control is-expanded">
            <input class="input" type="password" id="password" name="password" value="<?= esc(get('password', '')) ?>"
              placeholder="Password" autocomplete="new-password">
          </div>
          <div class="control">
            <input type="submit" class="button is-primary" name="update" value="Update">
          </div>
        </div>
      </form>
    </section>
    <footer class="modal-card-foot">
      <a class="button is-info is-light" href="<?= $kirby->user()->getStripePortalURL() ?>">
        <span class="icon">
          <i class="fas fa-money-check"></i>
        </span>
        <span>Manage Subscriptions</span>
      </a>
      <a href="user.json" target="_blank" class="button is-info is-light">
        <span class="icon">
          <i class="fas fa-file-export"></i>
        </span>
        <span>Export Data</span>
      </a>
    </footer>
    <div class="card-content">
      <form action="user" method="POST" onsubmit="return confirm('This action cannot be revert! Are you sure?');">
        <div class="buttons is-pulled-right">
          <input class="button is-danger" type="submit" name="delete" value="Delete Account">
        </div>
      </form>
    </div>
  </div>
</div>