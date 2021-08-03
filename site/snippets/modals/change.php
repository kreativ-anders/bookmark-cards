<div class="modal" id="changeModal">
  <div class="modal-background" onclick="$('#changeModal').toggleClass('is-active');"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">Edit Bookmark</p>
      <button onclick="$('#changeModal').toggleClass('is-active');" class="delete" aria-label="close"></button>
    </header>
    <form action="" method="POST" autocomplete="off">
      <section class="modal-card-body">
        <div class="field">
          <p class="control">
            <input class="input is-static" type="hidden" id="id" name="u_id" value="" placeholder="ID" readonly required>
          </p>
        </div>
        <div class="field">
          <p class="control has-icons-left">
            <input class="input" type="text" id="title" name="u_title" value="" placeholder="Title" required>
            <span class="icon is-small is-left">
              <i class="fas fa-heading"></i>
            </span>
          </p>
        </div>
        <div class="field">
          <p class="control has-icons-left">
            <input class="input" type="url" id="link" name="u_link" value="" placeholder="Link" required>
            <span class="icon is-small is-left">
              <i class="fas fa-link"></i>
            </span>
          </p>
        </div>
        <div class="field">
          <p class="control has-icons-left">
            <input class="input" type="text" id="tags" name="u_tags" placeholder="Tag1, Tag2, Tag3" value="">
            <span class="icon is-small is-left">
              <i class="fas fa-tags"></i>
            </span>
          </p>
        </div>
      </section>
      <footer class="modal-card-foot">
        <button class="button is-success" type="submit">Update</button>
      </footer>
    </form>
  </div>
</div>