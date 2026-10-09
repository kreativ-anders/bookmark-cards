<?php snippet('header') ?>

<main class="container account-page">
  <article>
    <header>
      <h2>Register</h2>
      <p><?= $page->text()->escape() ?></p>
    </header>

    <?php snippet('alert', ['alert' => $alert]) ?>

    <form action="<?= $page->url() ?>" method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <label for="email">
        Email
        <input type="email" id="email" name="email" value="<?= esc(is_array($data) ? ($data['email'] ?? '') : '', 'attr') ?>" autocomplete="email" aria-describedby="email-help" required autofocus>
        <small id="email-help">The email address is used for authentification and payments (later).</small>
      </label>
      <label for="password">
        Password
        <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="password-help" required>
        <small id="password-help">At least 8 characters. Re-type the password? Nah... You can do it!</small>
      </label>
      <label for="tos">
        <input type="checkbox" id="tos" name="tos" required>
        I accept the <a href="<?= url('terms') ?>" target="_blank">Terms</a> and have read the <a href="<?= url('privacy') ?>" target="_blank">Privacy policy</a>.
      </label>
      <!-- honeypot: hidden from humans, bots fill it in -->
      <div aria-hidden="true" style="position: absolute; left: -10000px;">
        <label for="bc_hp">Leave this field empty <input type="text" id="bc_hp" name="bc_hp" tabindex="-1" autocomplete="off"></label>
      </div>
      <input type="submit" name="register" value="Register" data-pirsch-event="Register">
    </form>

    <footer>
      Already registered? <a href="<?= url('login') ?>">Login</a>
    </footer>
  </article>
</main>

<?php snippet('footer') ?>
