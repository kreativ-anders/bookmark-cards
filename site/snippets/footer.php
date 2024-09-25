<footer class="container" style="text-align: center;">

  <p>
    <b>Bookmarks.cards</b>
    <br>
    by
    <a href="https://kreativ-anders.de/" target="_blank">
    <figure style="margin: auto">
      <img src="https://github.kreativ-anders.dev/images/share_square.png" style="width: 10vw" alt="kreativ-anders logo">
    </figure>

    </a>
  </p> 
    
  <link rel="stylesheet" href="assets/css/brands.min.css">  
  
  <?php  if($kirby->user()): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/instant.page/5.1.0/instantpage.min.js"></script>  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.4.0/color-thief.min.js"></script>  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      topTags();
      generateBackgroundColors();
    });

  </script>
  <?php endif; ?>


</footer>
</body>
</html>