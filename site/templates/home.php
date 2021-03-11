<?php
/**
 * Templates render the content of your pages. 
 * They contain the markup together with some control structures like loops or if-statements.
 * The `$page` variable always refers to the currently active page. 
 * To fetch the content from each field we call the field name as a method on the `$page` object, e.g. `$page->title()`. 
 * This home template renders content from others pages, the children of the `photography` page to display a nice gallery grid.
 * Snippets like the header and footer contain markup used in multiple templates. They also help to keep templates clean.
 * More about templates: https://getkirby.com/docs/guide/templates/basics
 */
?>

<?php snippet('header') ?>
<?php snippet('modals/login') ?>
<?php snippet('modals/signup') ?>

<?php  if($kirby->user()) {
  snippet('modals/change'); 
  snippet('modals/user'); 
} ?>


<hr>

<?php if($kirby->user()): ?>
<div class="jumbotron container box is-hidden-mobile" id="jumbotron">
  <form method="POST">
    <div class="field is-grouped is-grouped-multiline has-addons">
      <p class="control has-icons-right">
        <input class="input" id="s_title" type="search" name="c_title" placeholder="Titel" minlength="2" maxlength="200" autocomplete="on" required>
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="control is-expanded has-icons-right">
        <input class="input" id="s_link" type="url" name="c_link" placeholder="Link" maxlength="255" onblur="checkURL(this)"
          required>
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="control has-icons-right">
        <input class="input" id="s_tags" type="search" name="c_tags" placeholder="Tag1, Tag2, Tag3" maxlength="200" autocomplete="on">
        <span class="icon is-small is-right">
          <i class="fas fa-search"></i>
        </span>
      </p>
      <p class="control">
        <button type="submit" class="button">Lesezeichen hinzufügen</button>
      </p>
    </div>
  </form>
</div> 
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="columns is-multiline" id="bookmarks">

      <?php foreach ($bookmarks as $i => $bookmark): ?>
      <div class="column is-one-quarter" 
        data-search="<?= $bookmark['title'] . ';' . $bookmark['link'] . ';' . $bookmark['tags'] ?>" 
        data-tags="<?= $bookmark['tags'] ?>">
        <div class="card card-background" brand="<?= Str::lower($bookmark['title']) ?>">
          <a rel="noopener noreferrer" target="_self" href="<?= $bookmark['link'] ?>">
            <header class="card-header">
              <p class="card-header-title"><?= $bookmark['title'] ?></p>
            </header>
            <div class="card-content">
              <div class="content">
              </div>
            </div>
          </a>
          <?php  if($kirby->user()): ?>
            <footer class="card-footer">     
              <span class="icon edit">                
                <i class="fas fa-edit" onclick="changeData('<?= $i ?>','<?= $bookmark['title'] ?>', '<?= $bookmark['link'] ?>', '<?= $bookmark['tags'] ?>'); $('#changeModal').toggleClass('is-active');"></i>
              </span>
            
                <?php foreach (Str::split($bookmark['tags']) as $tag): ?>
                  <span class="tag" onclick="toggleTag('<?= $tag ?>')"><?= $tag ?></span>
                <?php endforeach; ?>
              
              <form action="" method="POST">
                <input name="d_bookmark" value="<?= $i ?>" type="hidden" />
                <button class="delete" type="submit">
              </form>                   
            </footer>
          <?php else: ?>
            <footer class="card-footer">
              <span class="icon edit">                
                <i class="fas fa-edit"></i>
              </span>
              
              <?php foreach (Str::split($bookmark['tags']) as $tag): ?>
                <span class="tag" onclick="toggleTag('<?= $tag ?>')"><?= $tag ?></span>
              <?php endforeach; ?>
              
              <button class="delete" type="submit" disabled>
                
            </footer>
            <?php endif; ?>
        </div>
      </div>
      <?php endforeach ?>

    </div>
  </div>
</section>
</body>

</main>

<?php snippet('footer') ?>
