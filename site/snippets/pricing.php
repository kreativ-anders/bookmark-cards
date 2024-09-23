<?php 
  $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'))
?>

<section class="container" id="pricing" style="text-align: center;">
  <h2>And finally easy Pricing</h2>
  <p><b><i>... hopefully</i></b></p> 
  <div class="grid">
    <article>
      <header>
        <h3>Basic</h3>
      </header>  
      <h4>0€ <small>/ month</small></h4>
      <p>Check if Bookmark.cards <br />is the right tool for you <br/>with <?= option('noPremiumLimit'); ?> bookmarks.</p>
      <button id="register" class="primary" data-target="registerModal" onclick="toggleModal(event)"><strong>Register</strong></button>
    </article>
    <article>
      <header>
        <h3>Premium</h3>
      </header>  
      <h4><?= $stripe->prices->retrieve(option('kreativ-anders.memberkit.tiers')[2]['price'], [])->unit_amount/100 ?>€ <small>/ year</small></h4>
      <p>You like Bookmark.cards? Awesome! <br> <i>(You can switch your plan interval after monthly initialisation.)</i></p>
    </article>
  </div>
</section>