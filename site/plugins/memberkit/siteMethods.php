<?php

return [

  /**
   * Verifies and dispatches a Stripe webhook event, returns the HTTP status for Stripe
   * (500 makes Stripe retry the event later)
   */
  'handleStripeWebhook' => function (string $payload, string $signature) {

    $secret = option('kreativ-anders.memberkit.webhookSecret');

    // fail closed: forged events could change emails or tiers
    if (empty($secret)) {
      error_log('memberkit: webhook rejected, no webhookSecret configured');
      return 500;
    }

    try {
      $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
    } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
      return 400;
    }

    try {
      switch ($event->type) {
        // also covers failed payments (status becomes 'past_due')
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
          $this->updateStripeSubscriptionWebhook($event->data->object);
          break;

        case 'customer.subscription.deleted':
          $this->cancelStripeSubscriptionWebhook($event->data->object);
          break;

        case 'customer.updated':
          $this->updateStripeEmailWebhook($event->data->object);
          break;

        case 'price.created':
        case 'price.updated':
          Memberkit::flushPrices();
          break;
      }
    } catch (Exception $e) {
      error_log('memberkit: webhook ' . $event->type . ' (' . $event->id . ') failed: ' . $e->getMessage());
      return 500;
    }

    return 200;
  },

  'updateStripeSubscriptionWebhook' => function ($subscription) {

    // by customer id, never by email (customers can edit their email in Stripe)
    $user = kirby()->users()->findBy('stripe_customer', $subscription->customer);

    if (!$user) {
      throw new Exception('Could not find kirby user for stripe customer!');
    }

    $price      = $subscription->items['data'][0]->price->id;
    $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), true);

    if ($priceIndex === false) {
      throw new Exception('Unknown stripe price!');
    }

    try {
      kirby()->impersonate('kirby', fn () => $user->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status'       => $subscription->status,
        'tier'                => option('kreativ-anders.memberkit.tiers')[$priceIndex]['name']
      ]));
    } catch (Exception $e) {
      throw new Exception('Could not update kirby user!', previous: $e);
    }
  },

  'cancelStripeSubscriptionWebhook' => function ($subscription) {

    $user = kirby()->users()->findBy('stripe_customer', $subscription->customer);

    // user already deleted or another (e.g. replaced) subscription ended
    if (!$user || ($user->stripe_subscription()->isNotEmpty() && $user->stripe_subscription()->value() !== $subscription->id)) {
      return;
    }

    try {
      kirby()->impersonate('kirby', fn () => $user->update([
        'stripe_subscription' => null,
        'stripe_status'       => null,
        'tier'                => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]));
    } catch (Exception $e) {
      throw new Exception('Could not reset kirby user!', previous: $e);
    }
  },

  'updateStripeEmailWebhook' => function ($customer) {

    $user = kirby()->users()->findBy('stripe_customer', $customer->id);

    if (!$user) {
      throw new Exception('Could not change kirby user email!');
    }

    // customer.updated is sent for every change, not only the email
    if (empty($customer->email) || Str::lower($customer->email) === Str::lower($user->email())) {
      return;
    }

    try {
      kirby()->impersonate('kirby', fn () => $user->changeEmail($customer->email));
    } catch (Exception $e) {
      throw new Exception('Could not change kirby user email!', previous: $e);
    }
  }

];
