<section id="jumbotron" class="container">
  <form method="POST">
    <div class="grid" style="grid-column-gap: unset;">
      <input id="s_title" type="search" name="c_title" placeholder="Title / Brand" minlength="2" maxlength="200" autocomplete="on" required>
      <input id="s_link" type="url" name="c_link" placeholder="Web Link (https://)" maxlength="255" onblur="checkURL(this)" required>
      <input id="s_tags" type="text" name="c_tags" placeholder="Search Tag" maxlength="200" autocomplete="on">
      <button type="submit" class="outline" style="margin-left: 2px;">Add Bookmark</button>
    </div>
  </form>
</section>