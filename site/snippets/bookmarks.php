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

              <?php if ((option('kreativ-anders.memberkit.tiers')[0]['name'] === $kirby->user()->tier()->toString() && count($bookmarks) <= option('noPremiumLimit')) && $bookmark['title'] != option('noPremiumTitle') || $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): ?>   
              <span class="icon edit">                
                <i class="fas fa-edit" onclick="changeData('<?= $i ?>','<?= $bookmark['title'] ?>', '<?= htmlspecialchars($bookmark['link']) ?>', '<?= $bookmark['tags'] ?>'); $('#changeModal').toggleClass('is-active');"></i>
              </span>

              <?php endif; ?>
            
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