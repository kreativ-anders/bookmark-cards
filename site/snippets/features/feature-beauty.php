<section class="container feature">
  <h2><mark>Feature #3</mark> Beautiful, branded bookmark cards</h2>
  <p>Put a brand name in the title, for example GitHub, Notion or Stripe, and the card shows the matching logo. More than <?= count(glob(kirby()->root('assets') . '/brand-names/*.svg')) ?> brands are included.</p>
  <dl>
    <dt>Visual memory</dt>
    <dd>Logos and soft brand colors make your links easy to recognize at a glance.</dd>
    <dt>Community-driven</dt>
    <dd>Missing a brand? Contribute a logo on <a href="https://github.com/kreativ-anders/bookmark-cards/tree/main/assets/brand-names">GitHub</a> or open an issue.</dd>
  </dl>
  <p><small>Bookmark.cards is not affiliated with the brands shown; logos are used for visual styling only.</small></p>
  <picture>
    <source srcset="/assets/images/feature-brands.webp" type="image/webp">
    <img src="/assets/images/feature-brands.png" width="1280" height="720" loading="lazy" alt="Grid of bookmark cards showing brand logos like GitHub, Kirby, Stripe and Notion">
  </picture>
</section>
