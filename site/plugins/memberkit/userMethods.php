<?php

return [

  'getStripeCancelURL' => function () {

    if ($this->stripe_subscription()->isEmpty()) {
      throw new Exception('No subscription to cancel!');
    }

    return Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/cancel/subscription';
  },

  'getStripeWebhookURL' => function () {
    return Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/webhook';
  },

  /**
   * Valid Stripe customer id, recreates a customer that was deleted
   * or is unknown in Stripe (e.g. test mode reset)
   */
  'ensureStripeCustomer' => function (\Stripe\StripeClient $stripe) {

    $id = $this->stripe_customer()->toString();

    if ($id !== '') {
      try {
        $customer = $stripe->customers->retrieve($id);
        if (!($customer->deleted ?? false)) {
          return $id;
        }
      } catch (\Stripe\Exception\InvalidRequestException $e) {
        // unknown customer: recreated below
      }
    }

    $customer = $stripe->customers->create([
      'email'    => $this->email(),
      'metadata' => ['kirby_user' => $this->id()]
    ]);

    $user = $this;
    kirby()->impersonate('kirby', fn () => $user->update(['stripe_customer' => $customer->id]));

    return $customer->id;
  },

  'getStripeCheckoutURL' => function ($tier) {

    $tiers     = option('kreativ-anders.memberkit.tiers');
    $tierIndex = array_search($tier, array_column($tiers, 'name'), false);

    if (!$tierIndex || $tierIndex < 1) {
      throw new Exception('Tier does not exist!');
    }

    return Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'))
      . '/subscribe/'
      . rawurlencode(Str::lower(Str::trim($tiers[$tierIndex]['name'])));
  },

  'getStripePortalURL' => function () {
    return Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/portal';
  },

  'retrieveStripeCustomer' => function () {

    if (!option('debug')) {
      throw new Exception('Retrieve stripe customer is only available in debug mode!');
    }

    try {
      return Memberkit::stripe()->customers->retrieve($this->stripe_customer(), ['expand' => ['subscriptions']]);
    } catch (Exception $e) {
      throw new Exception('Retrieve stripe customer failed!', previous: $e);
    }
  },

  'mergeStripeCustomer' => function () {

    try {
      $customer = Memberkit::stripe()->customers->retrieve($this->stripe_customer(), ['expand' => ['subscriptions']]);
    } catch (Exception $e) {
      throw new Exception('Retrieve stripe customer failed!', previous: $e);
    }

    $subscription = $customer->subscriptions['data'][0];
    $price        = $subscription->items['data'][0]->price->id;
    $tiers        = option('kreativ-anders.memberkit.tiers');
    $priceIndex   = array_search($price, array_column($tiers, 'price'), false);

    try {
      $this->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status'       => $subscription->status,
        'tier'                => $tiers[$priceIndex]['name']
      ]);

      $this->changeEmail($customer->email);

      return true;
    } catch (Exception $e) {
      throw new Exception('Update kirby user failed!', previous: $e);
    }
  },

  /**
   * Active subscription of the given tier or a higher one (by index)
   */
  'isAllowed' => function ($tier) {

    if ($this->tier()->isEmpty() || $this->stripe_subscription()->isEmpty() || $this->stripe_status()->toString() !== 'active') {
      return false;
    }

    $userTier = $this->tier()->toString();

    if ($userTier === $tier) {
      return true;
    }

    $names = array_column(option('kreativ-anders.memberkit.tiers'), 'name');

    return array_search($userTier, $names, false) >= array_search($tier, $names, false);
  },

  'isFreeTier' => function (): bool {
    return $this->tier()->toString() === (option('kreativ-anders.memberkit.tiers')[0]['name'] ?? null);
  },

  'isPremium' => function (): bool {
    return $this->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'] ?? null);
  },

];
