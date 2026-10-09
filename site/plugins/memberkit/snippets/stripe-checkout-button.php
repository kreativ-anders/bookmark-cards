<?php 
// CHECK KIRBY USER FOR SUBSCRIBTIONS
if($kirby->user()->stripe_subscription()->isEmpty()): ?>

<?php
// THE SNIPPET MAY BE RENDERED MORE THAN ONCE PER PAGE (HEADER + BANNER) => LOAD STRIPE.JS ONLY ONCE
if (!defined('MEMBERKIT_STRIPE_JS')):
  define('MEMBERKIT_STRIPE_JS', true);
?>
<?= js('https://js.stripe.com/clover/stripe.js'); ?>
<?php endif; ?>

<button type="button" id="<?= $id ?>" class="<?= $classes ?>"><?= $text ?></button>

<script type="text/javascript">

  document.addEventListener("DOMContentLoaded", function(event) { 
        
    var stripe = Stripe("<?= option('kreativ-anders.memberkit.publicKey') ?>");
    var checkoutButton = document.getElementById("<?= $id ?>");

    checkoutButton.addEventListener("click", function () {
      checkoutButton.disabled = true;
      fetch("<?= url($url) ?>", {
        method: "GET",
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (session) {
          if (!session || !session.url) {
            throw new Error(session && session.error ? session.error : "Could not open Stripe checkout.");
          }
          window.location.href = session.url;
        })
        .catch(function (error) {
          console.error("Error:", error);
          alert(error.message || "Could not open Stripe checkout. Please try again later.");
          checkoutButton.disabled = false;
        });
    });
  });

</script>

<?php endif; ?>
