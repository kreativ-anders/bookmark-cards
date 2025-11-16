<?php 
  $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'))
?>

<section class="container" id="pricing" style="text-align: center;">
  <h2>Simple Pricing</h2>
  <p><em>Choose a plan that fits your bookmarking needs.</em></p>
  <div class="grid">
    <article>
      <header>
        <h3>Basic</h3>
      </header>  
    <h4>0€ <small>/ month</small></h4>
    <p>Try Bookmark.cards for free — store up to <?= option('noPremiumLimit'); ?> bookmarks and explore core features.</p>
    <button type="button" id="register" class="primary" data-target="registerModal" onclick="toggleModal(event)" data-pirsch-event="Open Register Modal"><strong>Get started (Free)</strong></button>
    </article>
    <article>
      <header>
        <h3>Premium</h3>
      </header>  
  <h4><?= $stripe->prices->retrieve(option('kreativ-anders.memberkit.tiers')[2]['price'], [])->unit_amount/100 ?>€ <small>/ year</small></h4>
  <p>Upgrade for additional features and priority support. You can change billing intervals later if needed.</p>
    </article>
  </div>
</section>