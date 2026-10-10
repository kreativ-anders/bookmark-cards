<?php

use Kirby\Cms\App;
use Kirby\Cms\User;
use Kirby\Filesystem\Dir;
use Stripe\ApiRequestor;
use Tests\Support\FakeStripeClient;

define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/kirby/bootstrap.php';

// No Whoops error/exception handlers in tests (PHPUnit flags leaked handlers as risky)
App::$enableWhoops = false;

/*
|--------------------------------------------------------------------------
| Test setup
|--------------------------------------------------------------------------
| Every test gets a fresh Kirby instance of this project (real plugins,
| blueprints and config) with throwaway accounts/sessions/cache folders,
| and a fake Stripe HTTP client — no network, no real accounts touched.
*/

const TEST_TIERS = [
    ['name' => 'Free',     'price' => null],
    ['name' => 'Basic',    'price' => 'price_basic'],
    ['name' => 'Premium',  'price' => 'price_premium'],
    ['name' => 'Lifetime', 'price' => 'price_lifetime'],
];

uses()
    ->beforeEach(function () {
        $this->tmp = sys_get_temp_dir() . '/bookmark-cards-tests/' . uniqid('', true);

        $this->stripe = new FakeStripeClient();
        ApiRequestor::setHttpClient($this->stripe);

        $this->kirby = new App([
            'roots' => [
                'index'    => PROJECT_ROOT,
                'accounts' => $this->tmp . '/accounts',
                'sessions' => $this->tmp . '/sessions',
                'cache'    => $this->tmp . '/cache',
                'media'    => $this->tmp . '/media',
            ],
            'options' => [
                'debug' => false,
                'auth.debug' => false, // don't error_log() failed logins
                'kreativ-anders.memberkit.secretKey'     => 'sk_test_fake',
                'kreativ-anders.memberkit.publicKey'     => 'pk_test_fake',
                'kreativ-anders.memberkit.webhookSecret' => 'whsec_fake',
                'kreativ-anders.memberkit.tiers'         => TEST_TIERS,
            ],
        ]);
    })
    ->afterEach(function () {
        ApiRequestor::setHttpClient(null);
        Dir::remove($this->tmp);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** Registers a user the same way site/controllers/register.php does */
function registerUser(string $email = 'jane@example.com', string $password = 'Password123'): User
{
    $kirby = kirby();
    $kirby->impersonate('kirby');
    $user = $kirby->users()->create([
        'email'    => $email,
        'role'     => 'user',
        'language' => 'en',
        'password' => $password,
    ]);
    $kirby->impersonate();

    return $user;
}

/** Creates an admin (gets a Stripe customer like every user) */
function createAdmin(string $email = 'admin@example.com'): User
{
    $kirby = kirby();
    $kirby->impersonate('kirby');
    $user = $kirby->users()->create(['email' => $email, 'role' => 'admin', 'password' => 'Password123']);
    $kirby->impersonate();

    return $user;
}

/** Fresh user instance (Kirby 5 models are immutable after changes) */
function freshUser(string $email): ?User
{
    return kirby()->users()->find($email) ?? kirby()->user($email);
}

/** Builds a Stripe subscription object like the ones sent in webhooks */
function stripeSubscription(string $customer, string $price, string $status = 'active', string $id = 'sub_fake_1'): Stripe\Subscription
{
    return Stripe\Subscription::constructFrom([
        'id'       => $id,
        'object'   => 'subscription',
        'customer' => $customer,
        'status'   => $status,
        'items'    => ['object' => 'list', 'data' => [['id' => 'si_fake', 'object' => 'subscription_item', 'price' => ['id' => $price, 'object' => 'price']]]],
    ]);
}
