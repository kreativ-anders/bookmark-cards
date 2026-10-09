<section id="jumbotron" class="container">
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <fieldset role="group" aria-label="Add or search bookmarks">
      <input id="s_title" type="search" name="c_title" placeholder="Title / Brand" aria-label="Title or brand (also searches)" minlength="2" maxlength="200" autocomplete="on" required>
      <input id="s_link" type="url" name="c_link" placeholder="Web Link (https://)" aria-label="Web link" maxlength="255" onblur="checkURL(this)" required>
      <input id="s_tags" type="text" name="c_tags" placeholder="Search Tag" aria-label="Tags, comma separated" maxlength="200" autocomplete="on">
      <button type="submit" data-pirsch-event="Add Bookmark">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        Add Bookmark
      </button>
    </fieldset>
  </form>
</section>