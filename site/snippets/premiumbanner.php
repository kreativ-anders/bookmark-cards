<section id="premium-banner" class="container">
  <?= $page->Premium()->markdown(); ?>
  <?php $premiumTier = option('kreativ-anders.memberkit.tiers')[1]['name']; ?>
  <?php if (!$kirby->user()->isAllowed($premiumTier)): ?>
  <p class="premium-banner-cta">
    <?php snippet('stripe-checkout-button', [
      'id'      => 'premium-banner-checkout-button',
      'classes' => 'pirsch-event=Open+Stripe+Checkout pirsch-meta-source=Banner',
      'text'    => 'Become Premium',
      'url'     => $kirby->user()->getStripeCheckoutURL($premiumTier)
    ]) ?>
  </p>
  <?php endif ?>
</section>
