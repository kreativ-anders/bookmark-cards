<dialog id="changeModal">
  <article>
    <header>
      <a href="#close"
        aria-label="Close"
        class="close"
        data-target="changeModal"
        onClick="toggleModal(event)">
      </a>
      <h3>Edit Bookmark</h3>
    </header>
    <form action="" method="POST" autocomplete="off">
      <input type="hidden" id="id" name="u_id" value="" placeholder="ID" readonly required>
      <fieldset>
        <legend>
          <label for="title">Title</label>
        </legend>
        <input type="text" id="title" name="u_title" value="" placeholder="Title" required>
      </fieldset>
      <fieldset>
        <legend>
          <label for="link">Web Link</label>
        </legend>
        <input type="url" id="link" name="u_link" value="" placeholder="Link" required>
      </fieldset>
      <fieldset>
        <legend>
          <label for="tags">Tags</label>
        </legend>
        <input class="input" type="text" id="tags" name="u_tags" placeholder="Tag1, Tag2, Tag3" value="">
        <small><i>Seperate values with a comma.</i></small>
      </fieldset>
      <button type="submit" data-pirsch-event="Update Bookmark">Update Bookmark</button>
    </form>
  </article>
</dialog>