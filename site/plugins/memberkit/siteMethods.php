<?php

/*
  SITE-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/site-methods
*/

return [

  // VERIFY AND DISPATCH A STRIPE WEBHOOK EVENT - RETURNS THE HTTP STATUS FOR STRIPE --------------------------------------------------
  // https://stripe.com/docs/webhooks/signatures
  'handleStripeWebhook' => function (string $payload, string $signature) {

    $secret = option('kreativ-anders.memberkit.webhookSecret');

    // FAIL CLOSED: WITHOUT SECRET NO EVENT CAN BE TRUSTED (FORGED EVENTS COULD CHANGE EMAILS OR TIERS)
    if (empty($secret)) {

      error_log('memberkit: webhook rejected, no webhookSecret configured');
      return 500;
    }

    try {

      // VERIFIES SIGNATURE AND TIMESTAMP (REPLAY PROTECTION, 5 MIN TOLERANCE)
      $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);

    } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {

      return 400;
    }

    try {

      // HANDLE THE EVENT
      // https://stripe.com/docs/api/events/types
      switch ($event->type) {

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
          // ALSO COVERS FAILED PAYMENTS (STATUS BECOMES 'past_due')
          $this->updateStripeSubscriptionWebhook($event->data->object);
          break;

        case 'customer.subscription.deleted':
          $this->cancelStripeSubscriptionWebhook($event->data->object);
          break;

        case 'customer.updated':
          $this->updateStripeEmailWebhook($event->data->object);
          break;

        // OTHER EVENTS ARE ACKNOWLEDGED (OTHERWISE STRIPE RETRIES THEM FOR DAYS)
      }

    } catch (Exception $e) {

      // 500 => STRIPE RETRIES THE EVENT LATER
      error_log('memberkit: webhook ' . $event->type . ' (' . $event->id . ') failed: ' . $e->getMessage());
      return 500;
    }

    return 200;
  },
  // UPDATE STRIPE SUBSCRIPTION VIA WEBHOOK ------------------------------------------------------------------------------------------
  'updateStripeSubscriptionWebhook' => function ($subscription) {

    // MATCH BY STRIPE CUSTOMER ID, NEVER BY EMAIL (STRIPE EMAILS ARE EDITABLE BY THE CUSTOMER)
    $user = kirby()->users()->findBy('stripe_customer', $subscription->customer);

    if (!$user) {

      throw new Exception('Could not find kirby user for stripe customer!');
    }

    // DETERMINE TIER NAME BY STRIPE PRICE ID
    $price = $subscription->items['data'][0]->price->id;
    $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), true);

    if ($priceIndex === false) {

      throw new Exception('Unknown stripe price!');
    }

    $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];

    try {

      // UPDATE KIRBY USER SUBSCRIPTION INFORMATION
      kirby()->impersonate('kirby', fn () => $user->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status' => $subscription->status,
        'tier' => $tier
      ]));

    } catch (Exception $e) {

      throw new Exception('Could not update kirby user!');
    }
  },
  // CANCEL STRIPE SUBSCRIPTION VIA WEBHOOK -----------------------------------------------------------------------------------------
  'cancelStripeSubscriptionWebhook' => function ($subscription) {

    $user = kirby()->users()->findBy('stripe_customer', $subscription->customer);

    // USER ALREADY DELETED OR ANOTHER (E.G. REPLACED) SUBSCRIPTION ENDED => KEEP CURRENT STATE
    if (!$user || ($user->stripe_subscription()->isNotEmpty() && $user->stripe_subscription()->value() !== $subscription->id)) {

      return;
    }

    try {

      // RESET KIRBY USER SUBSCRIPTION INFORMATION
      kirby()->impersonate('kirby', fn () => $user->update([
        'stripe_subscription' => null,
        'stripe_status' => null,
        'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]));

    } catch (Exception $e) {

      throw new Exception('Could not reset kirby user!');
    }
  },
  // UPDATE KIRBY USER EMAIL VIA STRIPE WEBHOOK --------------------------------------------------------------------------------------
  'updateStripeEmailWebhook' => function ($customer) {

    $user = kirby()->users()->findBy('stripe_customer', $customer->id);

    if (!$user) {

      throw new Exception('Could not change kirby user email!');
    }

    // CUSTOMER.UPDATED IS ALSO SENT FOR NON-EMAIL CHANGES
    if (empty($customer->email) || Str::lower($customer->email) === Str::lower($user->email())) {

      return;
    }

    try {

      // UPDATE KIRBY USER EMAIL
      kirby()->impersonate('kirby', fn () => $user->changeEmail($customer->email));

    } catch (Exception $e) {

      throw new Exception('Could not change kirby user email!');
    }
  }

];
