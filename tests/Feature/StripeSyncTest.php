<?php

use Kirby\Cms\User;

/*
| Stripe sync (site/plugins/account-cleanup/StripeSync.php)
| comparison Kirby users <-> Stripe customers, deleting orphaned customers, repairing accounts, Panel dialog
*/

function stripeCustomer(string $id, string $email, array $metadata = [], array $subscriptions = []): array
{
    return [
        'id'            => $id,
        'object'        => 'customer',
        'email'         => $email,
        'created'       => 1700000000,
        'metadata'      => $metadata,
        'subscriptions' => ['object' => 'list', 'data' => $subscriptions],
    ];
}

/** Customer of every registered Kirby user plus $extra, as returned by GET /v1/customers */
function stripeCustomerList(array $extra = [], array $skip = []): array
{
    $data = [];

    foreach (kirby()->users() as $user) {
        $id = $user->stripe_customer()->toString();

        if ($id !== '' && in_array($user->email(), $skip, true) === false) {
            $data[] = stripeCustomer($id, $user->email(), ['kirby_user' => $user->id()]);
        }
    }

    return ['object' => 'list', 'url' => '/v1/customers', 'has_more' => false, 'data' => [...$data, ...$extra]];
}

function syncDialog(): array
{
    foreach (kirby()->extensions('areas')['site'] as $area) {
        $area = $area(kirby());

        if (isset($area['dialogs']['stripe-sync'])) {
            return $area['dialogs']['stripe-sync'];
        }
    }

    throw new Exception('Dialog not registered');
}

describe('compare', function () {

    it('finds nothing when both sides match', function () {
        registerUser('jane@example.com');
        $this->stripe->respond('GET', '/v1/customers', stripeCustomerList());

        expect(StripeSync::compare())->toBe(['stripe' => [], 'kirby' => []]);
        expect($this->stripe->requestsTo('GET', '/v1/customers')[0]['params'])
            ->toMatchArray(['limit' => 100, 'expand' => ['data.subscriptions']]);
    });

    it('finds Stripe customers without account', function () {
        registerUser('jane@example.com');
        $this->stripe->respond('GET', '/v1/customers', stripeCustomerList([
            stripeCustomer('cus_orphan', 'gone@example.com', ['kirby_user' => 'abc12345']),
        ]));

        $diff = StripeSync::compare();

        expect(array_map(fn ($c) => $c->id, $diff['stripe']))->toBe(['cus_orphan'])
            ->and($diff['kirby'])->toBe([]);
    });

    it('finds accounts without or with an unknown Stripe customer', function () {
        registerUser('jane@example.com');
        registerUser('john@example.com');
        $this->kirby->impersonate('kirby', fn () => freshUser('john@example.com')->update(['stripe_customer' => '']));
        $this->stripe->respond('GET', '/v1/customers', stripeCustomerList(skip: ['jane@example.com']));

        $diff = StripeSync::compare();

        expect(array_map(fn (User $u) => $u->email(), $diff['kirby']))->toBe(['jane@example.com', 'john@example.com'])
            ->and($diff['stripe'])->toBe([]);
    });
});

describe('delete Stripe customers', function () {

    it('deletes a customer without account', function () {
        registerUser('jane@example.com');

        $result = StripeSync::deleteCustomers(['cus_orphan']);

        expect($result)->toBe(['deleted' => ['cus_orphan'], 'skipped' => [], 'failed' => []])
            ->and($this->stripe->requestsTo('DELETE', '/v1/customers/cus_orphan'))->toHaveCount(1);
    });

    it('never deletes a customer that is used by an account', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();

        $result = StripeSync::deleteCustomers([$customer]);

        expect($result['skipped'])->toBe([$customer => 'used by jane@example.com'])
            ->and($this->stripe->requestsTo('DELETE', '/v1/customers/*'))->toBeEmpty();
    });

    it('never deletes a customer with a subscription', function () {
        registerUser('jane@example.com');
        $this->stripe->respond('GET', '/v1/customers/cus_paying', stripeCustomer('cus_paying', 'paying@example.com', [], [
            ['id' => 'sub_1', 'object' => 'subscription', 'status' => 'active'],
        ]));

        $result = StripeSync::deleteCustomers(['cus_paying']);

        expect($result['skipped'])->toBe(['cus_paying' => 'has a subscription'])
            ->and($this->stripe->requestsTo('DELETE', '/v1/customers/*'))->toBeEmpty();
    });

    it('ignores customers that are already deleted', function () {
        registerUser('jane@example.com');
        $this->stripe->respond('GET', '/v1/customers/cus_gone', ['error' => [
            'type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such customer',
        ]], 404);

        expect(StripeSync::deleteCustomers(['cus_gone']))->toBe(['deleted' => [], 'skipped' => [], 'failed' => []]);
    });
});

describe('repair accounts', function () {

    it('creates a new customer for an account with an unknown customer', function () {
        $user = registerUser('jane@example.com');
        $this->stripe->respond('GET', '/v1/customers/' . freshUser('jane@example.com')->stripe_customer(), ['error' => [
            'type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such customer',
        ]], 404);

        $result = StripeSync::repairUsers([$user->id()]);

        expect($result)->toBe(['repaired' => ['jane@example.com'], 'failed' => []])
            ->and($this->stripe->requestsTo('POST', '/v1/customers'))->toHaveCount(2);
    });

    it('keeps a customer that exists', function () {
        $user = registerUser('jane@example.com');

        expect(StripeSync::repairUsers([$user->id()]))->toBe(['repaired' => [], 'failed' => []])
            ->and($this->stripe->requestsTo('POST', '/v1/customers'))->toHaveCount(1);
    });
});

describe('delete accounts', function () {

    it('deletes an account with an unknown customer', function () {
        registerUser('other@example.com'); // Kirby refuses to delete the last remaining user
        $user = registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $missing  = ['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such customer']];
        $this->stripe->respond('GET', "/v1/customers/$customer", $missing, 404);
        $this->stripe->respond('DELETE', "/v1/customers/$customer", $missing, 404);
        $this->kirby->impersonate('kirby');

        expect(StripeSync::deleteUsers([$user->id()]))->toBe(['deleted' => ['jane@example.com'], 'skipped' => [], 'failed' => []])
            ->and(freshUser('jane@example.com'))->toBeNull();
    });

    it('keeps an account whose customer exists', function () {
        registerUser('other@example.com');
        $user = registerUser('jane@example.com');

        expect(StripeSync::deleteUsers([$user->id()])['skipped'])->toBe(['jane@example.com' => 'has a Stripe customer'])
            ->and(freshUser('jane@example.com'))->not->toBeNull();
    });

    it('never deletes an admin', function () {
        registerUser('jane@example.com');
        $admin = createAdmin('admin@example.com');
        $this->kirby->impersonate('kirby', fn () => freshUser('admin@example.com')->update(['stripe_customer' => '']));

        expect(StripeSync::deleteUsers([$admin->id()])['skipped'])->toBe(['admin@example.com' => 'admin account'])
            ->and(freshUser('admin@example.com'))->not->toBeNull();
    });

    it('deletes via the dialog when "delete" is chosen', function () {
        createAdmin('admin@example.com');
        $user = registerUser('jane@example.com');
        $this->kirby->impersonate('kirby', fn () => freshUser('jane@example.com')->update(['stripe_customer' => '']));

        $this->kirby = $this->kirby->clone(['request' => ['body' => ['kirby' => [$user->id()], 'kirbyAction' => 'delete']]]);
        $this->kirby->impersonate('admin@example.com');

        expect(syncDialog()['submit']())->toBe(['message' => 'Deleted 0 Stripe customers, deleted 1 accounts'])
            ->and(freshUser('jane@example.com'))->toBeNull();
    });
});

describe('panel dialog', function () {

    it('lists both sides and disables paying customers', function () {
        createAdmin('admin@example.com');
        registerUser('jane@example.com');
        $this->stripe->respond('GET', '/v1/customers', stripeCustomerList([
            stripeCustomer('cus_orphan', 'gone@example.com'),
            stripeCustomer('cus_paying', 'paying@example.com', [], [['id' => 'sub_1', 'object' => 'subscription', 'status' => 'active']]),
        ], skip: ['jane@example.com']));

        $this->kirby->impersonate('admin@example.com');
        $fields = syncDialog()['load']()['props']['fields'];

        expect(array_column($fields['stripe']['options'], 'disabled', 'value'))->toBe(['cus_orphan' => false, 'cus_paying' => true])
            ->and(array_column($fields['kirby']['options'], 'value'))->toBe([freshUser('jane@example.com')->id()]);
    });

    it('shows a message when everything is in sync', function () {
        createAdmin('admin@example.com');
        $this->stripe->respond('GET', '/v1/customers', stripeCustomerList());

        $this->kirby->impersonate('admin@example.com');

        expect(syncDialog()['load']()['component'])->toBe('k-text-dialog');
    });

    it('is only available for admins', function () {
        registerUser('jane@example.com');

        $this->kirby->impersonate('jane@example.com');

        expect(fn () => syncDialog()['load']())->toThrow(Kirby\Exception\PermissionException::class)
            ->and(fn () => syncDialog()['submit']())->toThrow(Kirby\Exception\PermissionException::class);
    });
});
