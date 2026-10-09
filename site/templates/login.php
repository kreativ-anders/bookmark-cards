<?php snippet('header') ?>

<main class="container account-page">
  <article>
    <header>
      <h1>Login</h1>
      <p><?= $page->text()->escape() ?></p>
    </header>

    <?php snippet('alert', ['alert' => $alert]) ?>

    <form action="<?= $page->url() ?>" method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <label for="email">
        Email
        <input type="email" id="email" name="email" value="<?= esc(is_array($data) ? ($data['email'] ?? '') : '', 'attr') ?>" autocomplete="email" required autofocus>
      </label>
      <label for="password">
        Password
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </label>
      <input type="submit" name="login" value="Login" data-pirsch-event="Login">
    </form>

    <footer>
      No account yet? <a href="<?= url('register') ?>">Register for free</a>
    </footer>
  </article>
</main>

<?php snippet('footer') ?>
