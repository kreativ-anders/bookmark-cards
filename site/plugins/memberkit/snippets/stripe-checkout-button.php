<?php if ($kirby->user()->stripe_subscription()->isEmpty()): ?>
<button type="button" id="<?= $id ?>" class="<?= $classes ?>"><?= $text ?></button>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var checkoutButton = document.getElementById(<?= json_encode($id) ?>);

    // the server creates the Checkout Session, the browser only follows its URL (no Stripe.js needed)
    checkoutButton.addEventListener('click', function () {
      checkoutButton.disabled = true;
      fetch(<?= json_encode(url($url), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>)
        .then(function (response) { return response.json(); })
        .then(function (session) {
          if (!session || !session.url) {
            throw new Error(session && session.error ? session.error : 'Could not open Stripe checkout.');
          }
          window.location.href = session.url;
        })
        .catch(function (error) {
          console.error('Error:', error);
          alert(error.message || 'Could not open Stripe checkout. Please try again later.');
          checkoutButton.disabled = false;
        });
    });
  });
</script>
<?php endif ?>
