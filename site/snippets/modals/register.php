<div class="modal" id="registerModal">
  <div class="modal-background" onclick="$('#registerModal').toggleClass('is-active');"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">Registrieren</p>
      <button onclick="$('#registerModal').toggleClass('is-active');" class="delete" aria-label="close"></button>
    </header>
    <form action="register" method="POST">
      <section class="modal-card-body">
        <div class="field">
          <p class="control has-icons-left has-icons-right">
            <input class="input" type="email" name="email" value="" placeholder="Email" required>
            <span class="icon is-small is-left">
              <i class="fas fa-envelope"></i>
            </span>
            <p class="help"><i style="color: rgba(80,80,80,.5)">Die Email Adresse wird lediglich für die Authentifizierung gespeichert und verwendet.</i> 😬</p>
          </p>
        </div>
        <div class="field">
          <p class="control has-icons-left">
            <input class="input" type="password" name="password" value="" placeholder="Passwort" required>
            <span class="icon is-small is-left">
              <i class="fas fa-lock"></i>
            </span>
            <p class="help"><i style="color: rgba(80,80,80,.5)">Einmal das Passwort eingeben sollte ausreichen!?</i></p>
          </p>
        </div>
        <div class="field">
          <label class="checkbox has-text-primary">
            <input type="checkbox" name="tos" required>
            <?= $pages->findById('register')->tos()->kirbytextinline(); ?>
          </label>
        </div>
      </section>
      <footer class="modal-card-foot">
        <input class="button is-success" name="register" type="submit" value="Register">     
      </footer>
    </form>
  </div>
</div>