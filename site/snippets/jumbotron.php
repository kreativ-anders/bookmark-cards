<section id="jumbotron" class="container">
  <form method="POST">
    <div class="grid" style="grid-column-gap: unset;">
      <input id="s_title" type="search" name="c_title" placeholder="Title / Brand" minlength="2" maxlength="200" autocomplete="on" required>
      <input id="s_link" type="url" name="c_link" placeholder="Web Link (https://)" maxlength="255" onblur="checkURL(this)" required>
      <input id="s_tags" type="text" name="c_tags" placeholder="Search Tag" maxlength="200" autocomplete="on">
      <button type="submit" class="outline" style="margin-left: 2px; border: none;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g data-name="51.Add"><path d="M12 24a12 12 0 1 1 12-12 12.013 12.013 0 0 1-12 12zm0-22a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2z"/><path d="M11 6h2v12h-2z"/><path d="M6 11h12v2H6z"/></g></svg>
        Add Bookmark
    </button>
    </div>
  </form>
</section>