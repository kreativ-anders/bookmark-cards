<footer class="container" style="text-align: center;">

  <?php if (!$kirby->user()): ?>
  <nav class="footer-nav" aria-label="Footer">
    <ul>
      <li><a href="<?= $site->url() ?>/#features">Features</a></li>
      <li><a href="<?= $site->url() ?>/#pricing">Pricing</a></li>
      <li><a href="<?= $site->url() ?>/#faq">FAQ</a></li>
      <li><a href="<?= url('register') ?>">Create free account</a></li>
      <li><a href="<?= url('login') ?>">Log in</a></li>
    </ul>
  </nav>
  <?php endif ?>

  <p>
    <span class="footer-brand">Bookmark<span class="accent">.cards</span></span>
    <br>
    by
    <a href="https://kreativ-anders.de/" target="_blank" rel="noopener">kreativ-anders</a>
  </p>
  <a href="https://kreativ-anders.de/" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
    <figure>
      <img src="/assets/images/kreativ-anders.svg" width="225" height="178" alt="kreativ-anders logo">
    </figure>
  </a>

  <p class="legal">
    <a href="<?= url('imprint') ?>">Imprint</a> ·
    <a href="<?= url('privacy') ?>">Privacy</a> ·
    <a href="<?= url('terms') ?>">Terms</a> ·
    <a href="https://github.com/kreativ-anders/bookmark-cards" target="_blank" rel="noopener">GitHub</a>
  </p>
  <p class="legal"><small>Brand names and logos are trademarks of their respective owners. They are shown for illustration only and do not imply any affiliation or endorsement.</small></p>


  <?php if ($kirby->user()): ?>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/instant.page/5.1.0/instantpage.min.js" integrity="sha512-1+qUtKoh9XZW7j+6LhRMAyOrgSQKenQ4mluTR+cvxXjP1Z54RxZuzstR/H9kgPXQsVB8IW7DMDFUJpzLjvhGSQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.4.0/color-thief.min.js" integrity="sha512-r2yd2GP87iHAsf2K+ARvu01VtR7Bs04la0geDLbFlB/38AruUbA5qfmtXwXx6FZBQGJRogiPtEqtfk/fnQfaYA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      topTags();
      generateBackgroundColors();
    });
  </script>
  <?php endif ?>

  <?php snippet('analytics') ?>

</footer>
</body>
</html>