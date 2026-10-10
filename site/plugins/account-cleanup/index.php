<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\User;
use Kirby\Exception\Exception;
use Kirby\Exception\PermissionException;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Escape;
use Kirby\Toolkit\Str;

require_once __DIR__ . '/StripeSync.php';

/**
 * Finds inactive free accounts for a manual clean-up in the Panel
 * (site view button "Inactive accounts"). Nothing is deleted automatically.
 *
 * Activity = last visit/login (stored in the dotfile `.activity` in the account folder,
 * ignored by Kirby and removed together with the account) or the last change of the account,
 * whichever is newer. Deleting runs the memberkit `user.delete:before` hook, which deletes
 * the Stripe customer first.
 */
class AccountActivity
{
  // write the visit at most once a day
  public const THROTTLE = 86400;

  public static function file(User $user): string
  {
    return $user->root() . '/.activity';
  }

  /**
   * Records a visit of the user, never throws (a visit must never fail because of it)
   */
  public static function touch(User $user, int|null $time = null): void
  {
    $time ??= time();

    try {
      if (static::seen($user) > $time - static::THROTTLE) {
        return;
      }

      F::write(static::file($user), (string)$time);
    } catch (Throwable $e) {
      error_log('account-cleanup: could not record activity of ' . $user->id() . ': ' . $e->getMessage());
    }
  }

  /**
   * Last recorded visit/login (0 = none recorded yet)
   */
  public static function seen(User $user): int
  {
    $value = F::read(static::file($user));

    return is_string($value) && ctype_digit(trim($value)) ? (int)trim($value) : 0;
  }

  /**
   * Last activity: visit/login or change of the account (content, credentials, password)
   */
  public static function last(User $user): int
  {
    return max(
      static::seen($user),
      (int)$user->modified(),
      (int)F::modified($user->root() . '/.htpasswd')
    );
  }

  /**
   * Paying users are never cleaned up (deleting them would cancel their subscription)
   */
  public static function isPaid(User $user): bool
  {
    $tier = $user->tier()->toString();

    return $tier !== '' && $tier !== (option('kreativ-anders.memberkit.tiers')[0]['name'] ?? null);
  }

  public static function months(): int
  {
    return max(1, (int)option('inactiveAccountsMonths', 12));
  }

  public static function isInactive(User $user, int|null $now = null): bool
  {
    if ($user->isAdmin() === true || static::isPaid($user) === true) {
      return false;
    }

    $limit = strtotime('-' . static::months() . ' months', $now ?? time());

    return static::last($user) < $limit;
  }

  /**
   * Inactive users, longest inactive first
   */
  public static function inactive(): array
  {
    $users = [];

    foreach (Kirby::instance()->users() as $user) {
      if (static::isInactive($user) === true) {
        $users[] = $user;
      }
    }

    usort($users, fn ($a, $b) => static::last($a) <=> static::last($b));

    return $users;
  }

  /**
   * Deletes the given users if they are (still) inactive.
   * Returns ['deleted' => [emails], 'failed' => [email => message]]
   */
  public static function delete(array $ids): array
  {
    $kirby  = Kirby::instance();
    $result = ['deleted' => [], 'failed' => []];

    foreach (array_unique($ids) as $id) {
      $user = $kirby->users()->find($id);

      // gone, became active or paying since the list was loaded => skip
      if ($user === null || static::isInactive($user) === false) {
        continue;
      }

      try {
        $user->delete();
        $result['deleted'][] = $user->email();
      } catch (Throwable $e) {
        $result['failed'][$user->email()] = $e->getMessage();
      }
    }

    return $result;
  }
}

$adminOnly = function () {
  if (kirby()->user()?->isAdmin() !== true) {
    throw new PermissionException('Only admins can clean up accounts');
  }
};

Kirby::plugin('bookmark-cards/account-cleanup', [
  'hooks' => [
    'user.login:after' => function (User $user) {
      AccountActivity::touch($user);
    }
  ],
  'areas' => [
    // "Stripe sync" in the users list (panel.viewButtons.users in config.php), same dialog as site.stripe-sync
    'users' => fn () => [
      'buttons' => [
        'users.stripe-sync' => fn () => [
          'icon'   => 'refresh',
          'text'   => 'Stripe sync',
          'dialog' => 'stripe-sync',
        ]
      ]
    ],
    'site' => fn () => [
      'buttons' => [
        'site.inactive-accounts' => fn () => [
          'icon'   => 'trash',
          'text'   => 'Inactive accounts',
          'dialog' => 'inactive-accounts',
        ],
        'site.stripe-sync' => fn () => [
          'icon'   => 'refresh',
          'text'   => 'Stripe sync',
          'dialog' => 'stripe-sync',
        ]
      ],
      'dialogs' => [
        'inactive-accounts' => [
          'load' => function () use ($adminOnly) {
            $adminOnly();

            $months = AccountActivity::months();
            $users  = AccountActivity::inactive();

            $text = 'Free accounts without visit, login or change in the last <strong>' . $months . ' months</strong>. ' .
                    'Admins and paying accounts are never listed. Deleting also deletes the Stripe customer and cannot be undone.';

            if ($users === []) {
              return [
                'component' => 'k-text-dialog',
                'props' => [
                  'text'         => $text . '<br><br>✅ No inactive accounts found.',
                  'cancelButton' => false,
                  'submitButton' => false,
                ]
              ];
            }

            $options = array_map(function (User $user) {
              $bookmarks = $user->bookmarks()->yaml();

              return [
                'value' => $user->id(),
                'text'  => Escape::html($user->email()) . ' · last active ' . date('Y-m-d', AccountActivity::last($user)) .
                           ' · ' . (is_array($bookmarks) ? count($bookmarks) : 0) . ' bookmarks',
              ];
            }, $users);

            return [
              'component' => 'k-form-dialog',
              'props' => [
                'size'   => 'large',
                'fields' => [
                  'info' => [
                    'type'  => 'info',
                    'theme' => 'notice',
                    'text'  => $text,
                  ],
                  'accounts' => [
                    'type'    => 'checkboxes',
                    'label'   => count($users) . ' inactive accounts',
                    'options' => $options,
                    'columns' => 1,
                    'help'    => 'Last active = newest of last visit/login (recorded since the clean-up was installed) and last account change.',
                  ],
                ],
                'value'        => ['accounts' => []],
                'submitButton' => [
                  'icon'  => 'trash',
                  'text'  => 'Delete selected',
                  'theme' => 'negative',
                ],
              ]
            ];
          },
          'submit' => function () use ($adminOnly) {
            $adminOnly();

            $ids = kirby()->request()->get('accounts', []);
            $ids = is_array($ids) ? $ids : Str::split((string)$ids, ',');
            $ids = array_filter($ids, 'is_string');

            if ($ids === []) {
              throw new Exception('Please select at least one account');
            }

            $result  = AccountActivity::delete($ids);
            $deleted = count($result['deleted']);

            if ($result['failed'] !== []) {
              $failed = [];

              foreach ($result['failed'] as $email => $message) {
                $failed[] = $email . ' (' . $message . ')';
              }

              throw new Exception('Deleted ' . $deleted . ' accounts. Could not delete: ' . implode(', ', $failed));
            }

            return [
              'message' => 'Deleted ' . $deleted . ' accounts'
            ];
          }
        ],
        'stripe-sync' => [
          'load' => function () use ($adminOnly) {
            $adminOnly();

            try {
              $diff = StripeSync::compare();
            } catch (Throwable $e) {
              throw new Exception('Could not load Stripe customers: ' . $e->getMessage());
            }

            $text = 'Compares Kirby accounts with the customers in Stripe (<strong>' . StripeSync::mode() . ' mode</strong>). ' .
                    'Nothing is changed until you select entries and confirm.';

            if ($diff['stripe'] === [] && $diff['kirby'] === []) {
              return [
                'component' => 'k-text-dialog',
                'props' => [
                  'text'         => $text . '<br><br>✅ Every account has a Stripe customer and every Stripe customer has an account.',
                  'cancelButton' => false,
                  'submitButton' => false,
                ]
              ];
            }

            $stripeOptions = array_map(function ($customer) {
              $live     = StripeSync::hasLiveSubscription($customer);
              $kirbyId  = $customer->metadata['kirby_user'] ?? null;
              $owner    = $kirbyId ? kirby()->users()->find($kirbyId) : null;
              $metadata = match (true) {
                $kirbyId === null => 'no kirby_user metadata',
                $owner !== null   => 'duplicate of ' . Escape::html($owner->email()),
                default           => 'account deleted',
              };

              return [
                'value'    => $customer->id,
                'disabled' => $live,
                'text'     => Escape::html($customer->email ?? '(no email)') . ' · ' . Escape::html($customer->id) .
                              ' · created ' . date('Y-m-d', $customer->created) . ' · ' . $metadata .
                              ($live ? ' · ⚠️ has a subscription, handle in Stripe' : ''),
              ];
            }, $diff['stripe']);

            $kirbyOptions = array_map(function (User $user) {
              $id = $user->stripe_customer()->toString();

              return [
                'value' => $user->id(),
                'text'  => Escape::html($user->email()) . ' · ' . Escape::html($user->tier()->or('no tier')->toString()) . ' · ' .
                           ($id === '' ? 'no Stripe customer id' : 'unknown customer ' . Escape::html($id)),
              ];
            }, $diff['kirby']);

            $fields = [
              'info' => [
                'type'  => 'info',
                'theme' => 'notice',
                'text'  => $text,
              ],
            ];

            if ($stripeOptions !== []) {
              $fields['stripe'] = [
                'type'    => 'checkboxes',
                'label'   => count($stripeOptions) . ' Stripe customers without account',
                'options' => $stripeOptions,
                'columns' => 1,
                'help'    => 'Selected customers are deleted in Stripe. Customers with a subscription can\'t be selected. ' .
                             'If the Stripe account is shared with other sites, their customers are listed here too.',
              ];
            }

            if ($kirbyOptions !== []) {
              $fields['kirby'] = [
                'type'    => 'checkboxes',
                'label'   => count($kirbyOptions) . ' accounts without Stripe customer',
                'options' => $kirbyOptions,
                'columns' => 1,
              ];
              $fields['kirbyAction'] = [
                'type'    => 'toggles',
                'label'   => 'Action for selected accounts',
                'options' => [
                  ['value' => 'create', 'text' => 'Create Stripe customer', 'icon' => 'add'],
                  ['value' => 'delete', 'text' => 'Delete account', 'icon' => 'trash'],
                ],
                'grow'    => true,
                'help'    => 'Deleting removes the account and its bookmarks and cannot be undone. Admin accounts are never deleted.',
              ];
            }

            return [
              'component' => 'k-form-dialog',
              'props' => [
                'size'         => 'large',
                'fields'       => $fields,
                'value'        => ['stripe' => [], 'kirby' => [], 'kirbyAction' => 'create'],
                'submitButton' => [
                  'icon'  => 'check',
                  'text'  => 'Apply selected',
                  'theme' => 'notice',
                ],
              ]
            ];
          },
          'submit' => function () use ($adminOnly) {
            $adminOnly();

            $request = kirby()->request();
            $ids     = [];

            foreach (['stripe', 'kirby'] as $key) {
              $value     = $request->get($key, []);
              $value     = is_array($value) ? $value : Str::split((string)$value, ',');
              $ids[$key] = array_filter($value, 'is_string');
            }

            if ($ids['stripe'] === [] && $ids['kirby'] === []) {
              throw new Exception('Please select at least one entry');
            }

            $deleted  = StripeSync::deleteCustomers($ids['stripe']);
            $message  = 'Deleted ' . count($deleted['deleted']) . ' Stripe customers';

            if ($request->get('kirbyAction') === 'delete') {
              $accounts = StripeSync::deleteUsers($ids['kirby']);
              $message .= ', deleted ' . count($accounts['deleted']) . ' accounts';
            } else {
              $accounts = StripeSync::repairUsers($ids['kirby']) + ['skipped' => []];
              $message .= ', created ' . count($accounts['repaired']) . ' Stripe customers';
            }

            $problems = [];

            foreach ($deleted['skipped'] + $accounts['skipped'] as $key => $reason) {
              $problems[] = $key . ' skipped (' . $reason . ')';
            }

            foreach ($deleted['failed'] + $accounts['failed'] as $key => $error) {
              $problems[] = $key . ' (' . $error . ')';
            }

            if ($problems !== []) {
              throw new Exception($message . '. Not done: ' . implode(', ', $problems));
            }

            return [
              'message' => $message
            ];
          }
        ]
      ]
    ]
  ]
]);
