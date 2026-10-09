<section class="container hero">
  <div class="hero-copy">
    <p class="eyebrow">Free bookmark manager · privacy-first</p>
    <h1>Save and tag your links as <em class="accent">cards</em>.</h1>
    <p>Bookmark.cards is a lightweight, privacy-friendly bookmark manager. Save websites and articles as visual cards, organize them with tags, search instantly and export your bookmarks anytime. <mark><?php echo count(glob("site/snippets/features/" . "*")); ?> features</mark>, no tracking, no ads.</p>
    <div class="hero-actions">
      <a role="button" class="primary" href="<?= url('register') ?>" data-pirsch-event="Open Register Modal">Start free</a>
      <a role="button" class="secondary outline" href="#features" data-pirsch-event="See Features">View Features</a>
    </div>
  </div>

  <div class="hero-fan" aria-hidden="true">
    <?php foreach (['Kirby', 'GitHub', 'Stripe'] as $brand): $logo = $site->brandLogo($brand); ?>
    <div class="bookmark card-background"<?php if ($logo): ?> style="background-image: url('<?= esc($logo, 'attr') ?>')"<?php endif ?>>
      <header><?= esc($brand) ?></header>
    </div>
    <?php endforeach ?>
  </div>
</section>
<br id="features" />
