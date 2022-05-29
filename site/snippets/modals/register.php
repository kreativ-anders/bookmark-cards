<dialog id="registerModal">
  <article>
    <header>
      <a href="#close"
        aria-label="Close"
        class="close"
        data-target="registerModal"
        onClick="toggleModal(event)">
      </a>
      <h3>Register</h3>
    </header>
    <form action="register" method="POST">
      <input type="email" name="email" value="" placeholder="Email" required>
      <p><i style="color: rgba(80,80,80,.5)">The email address is used for authentification and payments (later).</i></p>

      <input type="password" name="password" value="" placeholder="Password" required>
      <p><i style="color: rgba(80,80,80,.5)">Re-type the password? Nah... You can do it!</i></p>

      <br>
      <label for="tos">
        <input type="checkbox" id="tos" name="tos" required>
        I agree that my email address is stored for authentification and shared with <a href="https://stripe.com/">Stripe</a>. 

      </label>
      
      <br><br>      

      <input type="submit" name="register" value="Register" >
    </form>
  </article>
</dialog>