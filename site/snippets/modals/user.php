<div class="modal" id="userModal">
  <div class="modal-background" onclick="$('#userModal').toggleClass('is-active');"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title"><i><?= $kirby->user()->email(); ?></i> | Einstellungen</p>
      <button onclick="$('#userModal').toggleClass('is-active');" class="delete" aria-label="close"></button>
    </header>
    <section class="modal-card-body">
      <form action="" method="POST">
        <div class="field has-addons">
          <p class="control">
            <label for="email" class="button is-fullwidth">Email</label>
          </p>
          <div class="control is-expanded">
            <input class="input" type="email" id="email" name="email" value="<?= esc(get('email')) ?>"
            placeholder="<?= (get('email'))? get('email') : $kirby->user()->email() ?>" autocomplete="email">
          </div>
          <div class="control">
            <input type="submit" class="button is-primary" name="update" value="Updaten">
          </div>
        </div>
      </form>
      <br />
      <form action="" method="POST">
        <div class="field has-addons">
          <p class="control">
            <label for="password" class="button is-fullwidth">Password</label>
          </p>
          <div class="control is-expanded">
            <input class="input" type="password" id="password" name="password" value="<?= esc(get('password')) ?>"
            placeholder="Password" autocomplete="new-password">
          </div>
          <div class="control">
            <input type="submit" class="button is-primary" name="update" value="Updaten"> 
          </div>
        </div>
      </form>
    </section>
    <footer class="modal-card-foot">  

      <?php
        $portal_url = $kirby->user()->getStripePortalURL();                           
        if ($kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): 
      ?>
      <a class="button is-info is-light" href="<?= $portal_url ?>">
        <span class="icon">
          <i class="fas fa-money-check"></i>
        </span>
        <p>Manage Subscriptions</p>
      </a>
      <?php endif ?>

      <a href="user.json" target="_blank" class="button is-info is-light">meine Daten anfordern</a>

      <form action="user" method="POST" onsubmit="return confirm('Cannot be revert!');">
        <div class="buttons">
          <input class="button is-danger" type="submit" name="delete" value="Delete Account">
        </div>
        
      </form>
    </footer>
  </div>
</div>