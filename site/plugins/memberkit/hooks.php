<?php

$rollbackUser = function ($user) {
  try {
    kirby()->impersonate('kirby', fn () => $user->delete());
  } catch (Exception $e) {
    error_log('memberkit: rollback failed, could not delete user ' . $user->id() . ': ' . $e->getMessage());
  }
};

return [

  // a registration is rolled back on any failure: never a Kirby user without Stripe customer or vice versa
  'user.create:after' => function ($user) use ($rollbackUser) {

    $stripe = Memberkit::stripe();

    try {
      $customer = $stripe->customers->create([
        'email'    => $user->email(),
        'metadata' => ['kirby_user' => $user->id()]
      ], [
        'idempotency_key' => 'memberkit-create-' . $user->id()
      ]);
    } catch (Exception $e) {
      error_log('memberkit: could not create stripe customer for user ' . $user->id() . ': ' . $e->getMessage());
      $rollbackUser($user);

      throw new Exception('Could not create stripe customer!', previous: $e);
    }

    try {
      kirby()->impersonate('kirby', fn () => $user->update([
        'stripe_customer' => $customer->id,
        'tier'            => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]));
    } catch (Exception $e) {
      error_log('memberkit: could not update user ' . $user->id() . ' with stripe customer ' . $customer->id . ': ' . $e->getMessage());

      try {
        $stripe->customers->delete($customer->id, []);
      } catch (Exception $deleteError) {
        error_log('memberkit: rollback failed, orphaned stripe customer ' . $customer->id . ': ' . $deleteError->getMessage());
      }

      $rollbackUser($user);

      throw new Exception('Could not update kirby user!', previous: $e);
    }
  },

  'user.changeEmail:after' => function ($newUser, $oldUser) {
    try {
      Memberkit::stripe()->customers->update(
        $oldUser->stripe_customer(),
        ['email' => $newUser->email()]
      );
    } catch (Exception $e) {
      error_log('memberkit: could not update email of stripe customer ' . $oldUser->stripe_customer() . ': ' . $e->getMessage());

      throw new Exception('Could not update stripe customer!', previous: $e);
    }
  },

  // before hook: if Stripe fails, the Kirby user is kept and can retry (no orphaned paying customer)
  'user.delete:before' => function ($user) {

    $customer = $user->stripe_customer()->value();

    if (empty($customer)) {
      return;
    }

    try {
      Memberkit::stripe()->customers->delete($customer, []);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // already deleted by an earlier attempt where Kirby failed afterwards
      if ($e->getStripeCode() !== 'resource_missing') {
        error_log('memberkit: could not delete stripe customer ' . $customer . ': ' . $e->getMessage());
        throw new Exception('Could not delete stripe customer!', previous: $e);
      }
    } catch (Exception $e) {
      error_log('memberkit: could not delete stripe customer ' . $customer . ': ' . $e->getMessage());
      throw new Exception('Could not delete stripe customer!', previous: $e);
    }
  },

  'route:before' => function ($route, $path, $method) {

    $slug      = Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $subscribe = Str::contains($path, $slug . '/subscribe/');
    $portal    = Str::contains($path, $slug . '/portal');
    $checkout  = Str::contains($path, $slug . '/success');
    $cancel    = Str::contains($path, $slug . '/cancel/subscription');

    if ($cancel && !option('debug')) {
      throw new Exception('Cancel stripe subscription via URL is only available in debug mode!');
    }

    if (($subscribe || $portal || $checkout || $cancel) && !kirby()->user()) {
      go();
    }
  }

];
