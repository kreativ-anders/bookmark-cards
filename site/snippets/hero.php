<section class="container hero">
  <div class="hero-copy">
    <p class="eyebrow">Free bookmark manager · privacy-first</p>
    <h1>Save and tag your links as <em class="accent">cards</em>.</h1>
    <p>Bookmark.cards is a lightweight, privacy-friendly bookmark manager. Save websites and articles as visual cards, organize them with tags, search instantly and export your bookmarks anytime. <mark><?= count(glob($kirby->root('snippets') . '/features/*.php')) ?> features</mark>, no tracking, no ads.</p>
    <div class="hero-actions">
      <a role="button" class="primary" href="<?= url('register') ?>" data-pirsch-event="Open Register Modal" data-pirsch-meta-source="Hero">Start free</a>
      <a role="button" class="secondary outline" href="#features" data-pirsch-event="See Features">View Features</a>
    </div>
  </div>

  <div class="hero-fan" aria-hidden="true">
    <?php // last card sits in front, the rgb triple tints it like ColorThief does in the app
    foreach (['PayPal' => '23 155 215', 'Amazon' => '255 153 0', 'Google' => null] as $brand => $tint): $logo = $site->brandLogo($brand); ?>
    <div class="bookmark card-background" style="<?php if ($logo): ?>background-image: url('<?= esc($logo, 'attr') ?>');<?php endif ?><?php if ($tint): ?> --tint: <?= $tint ?>;<?php endif ?>">
      <header><?= esc($brand) ?></header>
    </div>
    <?php endforeach ?>
  </div>
</section>
