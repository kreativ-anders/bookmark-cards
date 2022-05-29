<dialog id="loginModal">
  <article>
    <a href="#close"
      aria-label="Close"
      class="close"
      data-target="loginModal"
      onClick="toggleModal(event)">
    </a>
    <h3>Login</h3>
    <form action="login" method="POST">
      <input type="email" name="email" value="" placeholder="Email" required>
      <input type="password" name="password" value="" placeholder="Passwort" required>
      <input type="submit" name="login" value="Login" >
    </form>
  </article>
</dialog>