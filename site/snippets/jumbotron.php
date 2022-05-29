<section id="jumbotron" class="container">
  <form method="POST">
    <div class="grid">
      <input id="s_title" type="search" name="c_title" placeholder="Title" minlength="2" maxlength="200" autocomplete="on" required>
      <input id="s_link" type="url" name="c_link" placeholder="Link" maxlength="255" onblur="checkURL(this)" required>
      <input id="s_tags" type="search" name="c_tags" placeholder="Tag1, Tag2, Tag3" maxlength="200" autocomplete="on">
      <button type="submit" class="outline">Add Bookmark</button>
    </div>
  </form>
</section>