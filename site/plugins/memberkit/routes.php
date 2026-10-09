<?php

/*
  ROUTES
  ----
  https://getkirby.com/docs/reference/plugins/extensions/routes
*/
   
return function ($kirby) {
  return [
    
    // CREATE STRIPE CHECKOUT SESSION --------------------------------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME (RAWURLENCODED)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/subscribe/(:all)',
      'action' => function ($tier) {

        // DETERMINE PRICE BY TIER NAME
        $tier = rawurldecode($tier);
        $tierIndex = array_search($tier, array_map("Str::lower", array_column(option('kreativ-anders.memberkit.tiers'), 'name')), false);
        $price = option('kreativ-anders.memberkit.tiers')[$tierIndex]['price'];

        // BUILD MAN-IN-THE-MIDDLE/SUCCESS URL => SITE URL / STRIPE SLUG / ACTION NAME (SUCCESS)
        $successURL  = kirby()->site()->url() . '/';
        $successURL .= Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
        $successURL .= '/success';

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

        try {

          $customer = kirby()->user()->ensureStripeCustomer($stripe);

          // CREATE STRIPE CHECKOUT SESSION
          $checkout = $stripe->checkout->sessions->create([
            'success_url' => $successURL,
            'cancel_url' => option('kreativ-anders.memberkit.cancelURL'),
            'allow_promotion_codes' => true,
            'line_items' => [
              [
                'price' => $price,
                'quantity' => 1,
              ],
            ],
            'mode' => 'subscription',
            'customer' => $customer,
          ]);
      
        } catch(Exception $e) {
        
          // JSON ERROR FOR THE CHECKOUT BUTTON (FETCH) INSTEAD OF AN HTML ERROR PAGE
          error_log('memberkit: could not create stripe checkout session: ' . $e->getMessage());
          return Kirby\Http\Response::json(['error' => 'Could not create stripe checkout session!'], 500);
        }     

        return [
          'url' => $checkout->url
        ];
      }
    ],
    // CREATE STRIPE CUSTOMER PORTAL SESSION ----------------------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / STRIPE PORTAL
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/portal',
      'action' => function () {

        // BUILD MAN-IN-THE-MIDDLE/RETURN URL => SITE URL
        $returnURL  = kirby()->site()->url() . '/';
        
        $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

        try {

          $customer = kirby()->user()->ensureStripeCustomer($stripe);

          // CREATE STRIPE PORTAL SESSION
          $session = $stripe->billingPortal->sessions->create([
            'customer' => $customer,
            'return_url' => $returnURL,
          ]);

          $url = $session->url; 
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not create stripe portal session!');
        }     

        // GO TO STRIPE PORTAL
        return go($url);
      }
    ],
    // CANCEL STRIPE SUBSCRIPTION -------------------------------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (CANCEL) / TYPE NAME (SUBSCRIPTION)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/cancel/subscription',
      'action' => function () {

        $subscription = kirby()->user()->stripe_subscription();
        $email = kirby()->user()->email();
        
        $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

        try {

          // CANCEL STRIPE SUBSCRIPTION
          $stripe->subscriptions->cancel(
            $subscription,
            []
          );
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not cancel stripe subscription!');
        } 

        try {

          // RESET KIRBY USER SUBSCRIPTION - ROOT TIER (INDEX=0)
          kirby()->user($email)->update([
            'stripe_subscription' => null,
            'stripe_status' => null,
            'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
          ]);
                
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not reset kirby user subscriptions!');
        }     

        return go();
      }
    ],
    // UPDATE/MERGE KIRBY USER AFTER (SUCCESSFUL) CHECKOUT --------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (SUCCESS)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/success',
      'action' => function () {

        try {

          // MERGE STRIPE USER WITH KIRBY USER
          kirby()->user()->mergeStripeCustomer();

          if (class_exists('Analytics')) {
            Analytics::track('Premium Purchased');
          }

        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not merge stripe customer into kirby user!');
        }       

        // REDIRECT TO CUSTOM SUCCESS PAGE
        return go(option('kreativ-anders.memberkit.successURL'));
      }
    ],
    // LISTEN TO STRIPE NOTIFICATIONS AKA STRIPE WEBHOOK ----------------------------------------------------------------------------
    // https://stripe.com/docs/webhooks/integration-builder
    // SIGNED EVENTS ONLY - SEE SITE METHOD handleStripeWebhook
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (WEBHOOK)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/webhook',
      'action' => function () {

        // RAW BODY IS REQUIRED FOR THE SIGNATURE CHECK
        $status = kirby()->site()->handleStripeWebhook(
          (string)@file_get_contents('php://input'),
          (string)kirby()->request()->header('Stripe-Signature', '')
        );

        return new \Kirby\Cms\Response($status === 200 ? 'OK' : 'Error', 'text/plain', $status);
      },
      // ENSURE ONLY POST REQUESTS ARE CAPTURED
      'method' => 'POST'
    ],
  ];
};

