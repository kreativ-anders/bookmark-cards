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
  </div>
</div>