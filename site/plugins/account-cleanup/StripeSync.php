<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\User;
use Stripe\Customer;
use Stripe\StripeClient;

/**
 * Compares Kirby users and Stripe customers (site view button "Stripe sync").
 *
 * - Stripe only: customer not referenced by any Kirby user (`stripe_customer`), e.g. left over
 *   from a failed rollback or a customer recreated by `ensureStripeCustomer`. Can be deleted,
 *   unless it still has a subscription (deleting would cancel a paying customer without account).
 * - Kirby only: user without customer id or with an id unknown to Stripe (deleted, other mode).
 *   Can be repaired by creating a new customer (memberkit `ensureStripeCustomer`) or deleted
 *   (admins excepted).
 *
 * Nothing is changed automatically, every action re-checks the state before it runs.
 */
class StripeSync
{
  // subscription states that are (still) billed or can become billed again
  public const LIVE_STATUSES = ['active', 'trialing', 'past_due', 'unpaid', 'incomplete', 'paused'];

  public static function client(): StripeClient
  {
    return Memberkit::stripe();
  }

  public static function mode(): string
  {
    return str_starts_with((string)option('kreativ-anders.memberkit.secretKey'), 'sk_live_') ? 'live' : 'test';
  }

  /**
   * All (not deleted) Stripe customers incl. subscriptions, keyed by id
   *
   * @return array<string, Customer>
   */
  public static function customers(StripeClient $stripe): array
  {
    $customers = [];
    $list      = $stripe->customers->all(['limit' => 100, 'expand' => ['data.subscriptions']]);

    foreach ($list->autoPagingIterator() as $customer) {
      $customers[$customer->id] = $customer;
    }

    return $customers;
  }

  /**
   * Kirby users keyed by their Stripe customer id
   *
   * @return array<string, User>
   */
  public static function usersByCustomer(): array
  {
    $users = [];

    foreach (Kirby::instance()->users() as $user) {
      $id = $user->stripe_customer()->toString();

      if ($id !== '') {
        $users[$id] = $user;
      }
    }

    return $users;
  }

  public static function hasLiveSubscription(Customer $customer): bool
  {
    foreach ($customer->subscriptions->data ?? [] as $subscription) {
      if (in_array($subscription->status, static::LIVE_STATUSES, true) === true) {
        return true;
      }
    }

    return false;
  }

  /**
   * ['stripe' => [Customer] oldest first, 'kirby' => [User] by email]
   */
  public static function compare(StripeClient|null $stripe = null): array
  {
    $customers = static::customers($stripe ?? static::client());
    $users     = static::usersByCustomer();

    $stripeOnly = array_values(array_diff_key($customers, $users));
    usort($stripeOnly, fn ($a, $b) => $a->created <=> $b->created);

    $kirbyOnly = [];

    foreach (Kirby::instance()->users() as $user) {
      if (isset($customers[$user->stripe_customer()->toString()]) === false) {
        $kirbyOnly[] = $user;
      }
    }

    usort($kirbyOnly, fn ($a, $b) => strcmp($a->email(), $b->email()));

    return ['stripe' => $stripeOnly, 'kirby' => $kirbyOnly];
  }

  /**
   * Deletes the given Stripe customers if they are (still) not used by a Kirby user
   * and have no live subscription.
   * Returns ['deleted' => [ids], 'skipped' => [id => reason], 'failed' => [id => message]]
   */
  public static function deleteCustomers(array $ids, StripeClient|null $stripe = null): array
  {
    $stripe ??= static::client();
    $users    = static::usersByCustomer();
    $result   = ['deleted' => [], 'skipped' => [], 'failed' => []];

    foreach (array_unique($ids) as $id) {
      if (isset($users[$id]) === true) {
        $result['skipped'][$id] = 'used by ' . $users[$id]->email();
        continue;
      }

      try {
        $customer = $stripe->customers->retrieve($id, ['expand' => ['subscriptions']]);

        if ($customer->deleted ?? false) {
          continue;
        }

        if (static::hasLiveSubscription($customer) === true) {
          $result['skipped'][$id] = 'has a subscription';
          continue;
        }

        $stripe->customers->delete($id, []);
        $result['deleted'][] = $id;
      } catch (\Stripe\Exception\InvalidRequestException $e) {
        // already gone => nothing left to do
        if ($e->getStripeCode() !== 'resource_missing') {
          $result['failed'][$id] = $e->getMessage();
        }
      } catch (Throwable $e) {
        $result['failed'][$id] = $e->getMessage();
      }
    }

    return $result;
  }

  /**
   * Deletes the given accounts if their Stripe customer is (still) missing. Admins are never deleted.
   * Deleting runs the memberkit `user.delete:before` hook (an unknown customer is skipped there).
   * Returns ['deleted' => [emails], 'skipped' => [email => reason], 'failed' => [email => message]]
   */
  public static function deleteUsers(array $ids, StripeClient|null $stripe = null): array
  {
    $stripe ??= static::client();
    $result   = ['deleted' => [], 'skipped' => [], 'failed' => []];

    foreach (array_unique($ids) as $id) {
      $user = Kirby::instance()->users()->find($id);

      if ($user === null) {
        continue;
      }

      if ($user->isAdmin() === true) {
        $result['skipped'][$user->email()] = 'admin account';
        continue;
      }

      try {
        if (static::customerExists($user->stripe_customer()->toString(), $stripe) === true) {
          $result['skipped'][$user->email()] = 'has a Stripe customer';
          continue;
        }

        $user->delete();
        $result['deleted'][] = $user->email();
      } catch (Throwable $e) {
        $result['failed'][$user->email()] = $e->getMessage();
      }
    }

    return $result;
  }

  public static function customerExists(string $id, StripeClient $stripe): bool
  {
    if ($id === '') {
      return false;
    }

    try {
      return ($stripe->customers->retrieve($id)->deleted ?? false) === false;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      if ($e->getStripeCode() === 'resource_missing') {
        return false;
      }

      throw $e;
    }
  }

  /**
   * Creates a Stripe customer for the given users if theirs is missing.
   * Returns ['repaired' => [emails], 'failed' => [email => message]]
   */
  public static function repairUsers(array $ids, StripeClient|null $stripe = null): array
  {
    $stripe ??= static::client();
    $result   = ['repaired' => [], 'failed' => []];

    foreach (array_unique($ids) as $id) {
      $user = Kirby::instance()->users()->find($id);

      if ($user === null) {
        continue;
      }

      try {
        $before = $user->stripe_customer()->toString();

        // retrieves the stored customer first, only recreates an unknown/deleted one
        if ($user->ensureStripeCustomer($stripe) !== $before) {
          $result['repaired'][] = $user->email();
        }
      } catch (Throwable $e) {
        $result['failed'][$user->email()] = $e->getMessage();
      }
    }

    return $result;
  }
}
