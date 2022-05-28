<?php
/**
 * Snippets are a great way to store code snippets for reuse or to keep your templates clean.
 * in loops or simply to keep your templates clean.
 * This footer snippet is reused in all templates. In fetches information from the `site.txt` content file
 * and from the `about` page.
 * More about snippets: https://getkirby.com/docs/guide/templates/snippets
 */
?>

<footer class="footer">

  <div class="container">
    <div class="content has-text-centered">
      <p>
        <strong>Bookmarks.cards</strong>
        <br>
        by 
        <a href="https://kreativ-anders.de/" target="_blank" rel="noopener noreferrer">
          <figure class="image is-128x128" style="margin: auto">
            <img src="https://github.kreativ-anders.dev/logo/dark.svg" style="width: 90px">
          </figure>
        </a>
      </p>
    </div>
  </div>

  <script src="assets/js/main.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/instant.page/5.1.0/instantpage.min.js"></script>
  <?php  if($kirby->user()): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      topTags()
    });
  </script>
  <?php endif; ?>

</footer>

</body>

</html>