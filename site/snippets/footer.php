<footer class="container" style="text-align: center;">
  <div class="grid">
    <p>
      <b>Bookmarks.cards</b>
      <br>
      by Manuel Steinberg
    </p> 
    <a href="https://kreativ-anders.de/" target="_blank" rel="noopener noreferrer">
      <figure class="image is-128x128" style="margin: auto">
        <img src="https://github.kreativ-anders.dev/logo/dark.svg" style="width: 100px" alt="kreativ-anders logo">
      </figure>
    </a>
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