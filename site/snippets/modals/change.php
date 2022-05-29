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
      <input type="text" id="title" name="u_title" value="" placeholder="Title" required>
      <input type="url" id="link" name="u_link" value="" placeholder="Link" required>
      <input class="input" type="text" id="tags" name="u_tags" placeholder="Tag1, Tag2, Tag3" value="">
      <button type="submit">Update Bookmark</button>
    </form>
  </article>
</dialog>