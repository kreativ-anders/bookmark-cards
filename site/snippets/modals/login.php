<dialog id="loginModal">
  <article>
    <header>
      <a href="#close"
        aria-label="Close"
        class="close"
        data-target="loginModal"
        onClick="toggleModal(event)">
      </a>
      <h3>Login</h3>
    </header>
    <form action="login" method="POST">
      <fieldset>
        <input type="email" name="email" value="" placeholder="Email" required>
        <input type="password" name="password" value="" placeholder="Passwort" required>
      </fieldset>
      <input type="submit" name="login" value="Login" >
    </form>
  </article>
</dialog>