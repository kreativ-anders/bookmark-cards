<div class="jumbotron container box is-hidden-mobile" id="jumbotron">
  <form method="POST">
    <div class="field is-grouped is-grouped-multiline has-addons">
      <p class="control has-icons-right">
        <input class="input" id="s_title" type="search" name="c_title" placeholder="Titel" minlength="2" maxlength="200" autocomplete="on" required>
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="control is-expanded has-icons-right">
        <input class="input" id="s_link" type="url" name="c_link" placeholder="Link" maxlength="255" onblur="checkURL(this)"
          required>
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="control has-icons-right">
        <input class="input" id="s_tags" type="search" name="c_tags" placeholder="Tag1, Tag2, Tag3" maxlength="200" autocomplete="on">
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="control">
        <button type="submit" class="button">Lesezeichen hinzufügen</button>
      </p>
    </div>
  </form>
</div>