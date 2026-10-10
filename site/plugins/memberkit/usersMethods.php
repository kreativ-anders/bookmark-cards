<?php

return [

  /**
   * Creates Stripe customers for Kirby users without one (admin task, errors are not caught)
   */
  'migrateStripeCustomers' => function () {

    if (!kirby()->user()->isAdmin()) {
      throw new Exception('This is an admin task!');
    }

    $kirby   = kirby();
    $users   = $kirby->users();
    $stripe  = Memberkit::stripe();
    $counter = 0;

    foreach ($users as $user) {

      if ($user->stripe_customer()->isNotEmpty()) {
        continue;
      }

      $customer = $stripe->customers->create(['email' => $user->email()]);

      $kirby->impersonate('kirby', fn () => $kirby->user($user->email())->update([
        'stripe_customer' => $customer->id,
        'tier'            => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]));

      $counter++;
    }

    return [
      'users'      => count($users),
      'migrations' => $counter
    ];
  }
];
