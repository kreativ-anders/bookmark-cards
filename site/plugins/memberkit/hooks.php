<?php

/*
  HOOKS
  ----
  https://getkirby.com/docs/reference/plugins/hooks
*/

// REMOVE A HALF-REGISTERED KIRBY USER - NEVER THROWS, SO THE ORIGINAL ERROR IS REPORTED
$rollbackUser = function ($user) {

  try {
    kirby()->impersonate('kirby', fn () => $user->delete());
  } catch (Exception $e) {
    error_log('memberkit: rollback failed, could not delete user ' . $user->id() . ': ' . $e->getMessage());
  }
};

return [

  // CREATE STRIPE USER -----------------------------------------------------------------------------------------
  // https://stripe.com/docs/api/customers/create
  // ON ANY FAILURE THE REGISTRATION IS ROLLED BACK (NO KIRBY USER WITHOUT STRIPE CUSTOMER AND VICE VERSA)
  'user.create:after' => function ($user) use ($rollbackUser) {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    try {

      // CREATE STRIPE CUSTOMER
      // IDEMPOTENCY KEY: A RETRIED REQUEST FOR THE SAME KIRBY USER NEVER CREATES A SECOND CUSTOMER
      $customer = $stripe->customers->create([
        'email'    => $user->email(),
        'metadata' => ['kirby_user' => $user->id()]
      ], [
        'idempotency_key' => 'memberkit-create-' . $user->id()
      ]);

    } catch (Exception $e) {

      error_log('memberkit: could not create stripe customer for user ' . $user->id() . ': ' . $e->getMessage());

      // ROLLBACK: REMOVE KIRBY USER (HAS NO STRIPE CUSTOMER YET, SO THE DELETE HOOK SKIPS STRIPE)
      $rollbackUser($user);

      throw new Exception('Could not create stripe customer!');
    }

    try {

      // UPDATE KIRBY USER - ROOT TIER (INDEX=0)
      kirby()->impersonate('kirby', fn () => $user->update([
        'stripe_customer' => $customer->id,
        'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]));

    } catch (Exception $e) {

      error_log('memberkit: could not update user ' . $user->id() . ' with stripe customer ' . $customer->id . ': ' . $e->getMessage());

      // ROLLBACK: REMOVE STRIPE CUSTOMER AND KIRBY USER
      try {
        $stripe->customers->delete($customer->id, []);
      } catch (Exception $e) {
        error_log('memberkit: rollback failed, orphaned stripe customer ' . $customer->id . ': ' . $e->getMessage());
      }

      $rollbackUser($user);

      throw new Exception('Could not update kirby user!');
    }
  },
  // CHANGE STRIPE USER EMAIL -------------------------------------------------------------------------------------
  // https://stripe.com/docs/api/customers/update
  'user.changeEmail:after' => function ($newUser, $oldUser) {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    try {

      // UPDATE STRIPE CUSTOMER
      $stripe->customers->update(
        $oldUser->stripe_customer(),
        ['email' => $newUser->email()]
      );

    } catch(Exception $e) {

      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Could not update stripe customer!');
    }
  },
  // DELETE STRIPE CUSTOMER (CANCELS ALL STRIPE SUBSCRIPTIONS) BEFORE THE KIRBY USER ---------------------------------
  // https://stripe.com/docs/api/customers/delete
  // BEFORE HOOK: IF STRIPE FAILS, THE KIRBY USER IS KEPT AND THE USER CAN RETRY (NO ORPHANED PAYING CUSTOMER)
  'user.delete:before' => function ($user) {

    $customer = $user->stripe_customer()->value();

    // USER WITHOUT STRIPE CUSTOMER (E.G. NOT MIGRATED YET)
    if (empty($customer)) {
      return;
    }

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    try {

      // DELETE STRIPE CUSTOMER
      $stripe->customers->delete($customer, []);

    } catch (\Stripe\Exception\InvalidRequestException $e) {

      // ALREADY DELETED (E.G. EARLIER ATTEMPT WHERE KIRBY FAILED AFTERWARDS) => NOTHING LEFT TO DO
      if ($e->getStripeCode() !== 'resource_missing') {

        error_log('memberkit: could not delete stripe customer ' . $customer . ': ' . $e->getMessage());
        throw new Exception('Could not delete stripe customer!');
      }

    } catch (Exception $e) {

      error_log('memberkit: could not delete stripe customer ' . $customer . ': ' . $e->getMessage());
      throw new Exception('Could not delete stripe customer!');
    }
  },
  // RESERVE STRIPE ROUTES TO LOGGED-IN USERS
  // https://getkirby.com/docs/guide/routing#before-and-after-hooks__route-before
  'route:before' => function ($route, $path, $method) {

    // DETERMINE ROUTE PATH AS BEST AS POSSIBLE (TRUE = MATCH)
    $subscribe = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/subscribe/');
    $portal = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/portal');
    $checkout = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/success');
    $cancel = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/cancel/subscription');

    // CANCEL ROUTE IS DEBUG MODE EXCLUSIVE
    if ($cancel && !option('debug')) {

      throw new Exception('Cancel stripe subscription via URL is only available in debug mode!');
    }

    // REDIRECT TO HOMEPAGE WHEN USER IS NOT LOGGED-IN
    if (($subscribe || $portal || $checkout || $cancel) && !kirby()->user()) {
      go();
    }
  }

];
