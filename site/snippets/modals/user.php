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
          <p class="control is-expanded">
            <a class="button is-static is-fullwidth">
              Neue
            </a>
          </p>
          <div class="control">
            <input class="input" type="email" placeholder="Email" disabled>
          </div>
          <div class="control">
            <button type="submit" class="button is-primary is-static">Aktualisieren&nbsp;<b>(Bald verfügbar!)</b></button>
          </div>
        </div>
      </form>
      <br />
      <form action="" method="POST">
        <div class="field has-addons">
          <p class="control is-expanded">
            <a class="button is-static is-fullwidth">
              Neues
            </a>
          </p>
          <div class="control">
            <input class="input" type="password" placeholder="Password" disabled>
          </div>
          <div class="control">
            <button type="submit" class="button is-primary is-static">Aktualisieren&nbsp;<b>(Bald verfügbar!)</b></button>
          </div>
        </div>
      </form>
    </section>
    <footer class="modal-card-foot">
      <a href="user.json" target="_blank" class="button is-info is-light">meine Daten anfordern</a>
      <form action="user" method="POST" onsubmit="return confirm('Diese Aktion kann nicht rückgängig gemacht werden! Sind Sie sich sicher, dass der Account gelöscht werden soll?');">
        <div class="buttons">
          <input type="hidden" name="user" value="<?= $kirby->user()->email(); ?>" />
          <input class="button is-danger" type="submit" name="deleteUser" value="Account löschen" />
        </div>
        
      </form>
      
      
    </footer>
    <form method="post" action="<?= $page->url() ?>">
      <fieldset>
        <legend><?= $kirby->user()->email() ?></legend>

        <section>
          <label for="email"><?= $page->email()->html() ?></label>
          <input type="email" id="email" name="email" value="<?= esc(get('email')) ?>"
            placeholder="<?= (get('email'))? get('email') : $kirby->user()->email() ?>" autocomplete="email">
        </section>

        <section>
          <label for="password"><?= $page->password()->html() ?></label>
          <input type="password" id="password" name="password" value="<?= esc(get('password')) ?>"
            placeholder="<?= $page->password()->html() ?>" autocomplete="new-password">
        </section>

        <section>
          <input type="submit" name="update" value="<?= $page->button()->html() ?>">
        </section>

      </fieldset>
    </form>

    <p>
      <?= $page->export()->html() ?> ( <a href="user.json" target="_blank"><abbr
          title="JavaScript Object Notation">JSON</abbr></a> | <a href="user.csv" target="_blank"><abbr
          title="Comma-Separated Values">CSV</abbr></a> )
    </p>

    <form method="post" action="<?= $page->url() ?>" onsubmit="return confirm('<?= $page->delete_warning()->html() ?>');">
      <fieldset>
        <legend><?=$page->danger()->html() ?></legend>

        <section>
          <input type="submit" name="delete" value="<?= $page->delete_button()->email() ?>">
        </section>

      </fieldset>
    </form>
  </div>
</div>