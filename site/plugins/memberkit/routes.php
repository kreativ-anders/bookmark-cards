<?php

return function ($kirby) {

  $slug = Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));

  return [

    [
      'pattern' => $slug . '/subscribe/(:all)',
      'action'  => function ($tier) use ($slug) {

        $tiers     = option('kreativ-anders.memberkit.tiers');
        $tierIndex = array_search(rawurldecode($tier), array_map('Str::lower', array_column($tiers, 'name')), false);
        $price     = $tiers[$tierIndex]['price'];
        $stripe    = Memberkit::stripe();

        try {
          $checkout = $stripe->checkout->sessions->create([
            'success_url'           => kirby()->site()->url() . '/' . $slug . '/success',
            'cancel_url'            => option('kreativ-anders.memberkit.cancelURL'),
            'allow_promotion_codes' => true,
            'line_items'            => [['price' => $price, 'quantity' => 1]],
            'mode'                  => 'subscription',
            'customer'              => kirby()->user()->ensureStripeCustomer($stripe),
          ]);
        } catch (Exception $e) {
          error_log('memberkit: could not create stripe checkout session: ' . $e->getMessage());
          return Kirby\Http\Response::json(['error' => 'Could not create stripe checkout session!'], 500);
        }

        return ['url' => $checkout->url];
      }
    ],

    [
      'pattern' => $slug . '/portal',
      'action'  => function () {

        $stripe = Memberkit::stripe();

        try {
          $session = $stripe->billingPortal->sessions->create([
            'customer'   => kirby()->user()->ensureStripeCustomer($stripe),
            'return_url' => kirby()->site()->url() . '/',
          ]);
        } catch (Exception $e) {
          error_log('memberkit: could not create stripe portal session: ' . $e->getMessage());
          throw new Exception('Could not create stripe portal session!', previous: $e);
        }

        return go($session->url);
      }
    ],

    [
      'pattern' => $slug . '/cancel/subscription',
      'action'  => function () {

        $user = kirby()->user();

        try {
          Memberkit::stripe()->subscriptions->cancel($user->stripe_subscription(), []);
        } catch (Exception $e) {
          error_log('memberkit: could not cancel stripe subscription: ' . $e->getMessage());
          throw new Exception('Could not cancel stripe subscription!', previous: $e);
        }

        try {
          kirby()->user($user->email())->update([
            'stripe_subscription' => null,
            'stripe_status'       => null,
            'tier'                => option('kreativ-anders.memberkit.tiers')[0]['name']
          ]);
        } catch (Exception $e) {
          throw new Exception('Could not reset kirby user subscriptions!', previous: $e);
        }

        return go();
      }
    ],

    [
      'pattern' => $slug . '/success',
      'action'  => function () {

        try {
          kirby()->user()->mergeStripeCustomer();

          if (class_exists('Analytics')) {
            Analytics::track('Premium Purchased');
          }
        } catch (Exception $e) {
          error_log('memberkit: could not merge stripe customer: ' . $e->getMessage());
          throw new Exception('Could not merge stripe customer into kirby user!', previous: $e);
        }

        return go(option('kreativ-anders.memberkit.successURL'));
      }
    ],

    [
      'pattern' => $slug . '/webhook',
      'method'  => 'POST',
      'action'  => function () {

        // the signature check needs the raw body
        $status = kirby()->site()->handleStripeWebhook(
          (string)@file_get_contents('php://input'),
          (string)kirby()->request()->header('Stripe-Signature', '')
        );

        return new \Kirby\Cms\Response($status === 200 ? 'OK' : 'Error', 'text/plain', $status);
      }
    ],
  ];
};
