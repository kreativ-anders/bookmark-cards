<?php

use Kirby\Cms\User;

/*
| Inactive accounts clean-up (site/plugins/account-cleanup)
| activity tracking, selection rules, deletion incl. Stripe customer, Panel dialog
*/

/** Makes the account look untouched since $time (content, credentials, password, visits) */
function ageUser(string $email, int $time): User
{
    $root = freshUser($email)->root();

    foreach (['user.txt', 'index.php', '.htpasswd', '.activity'] as $file) {
        if (is_file($root . '/' . $file)) {
            touch($root . '/' . $file, $time);
        }
    }

    if (is_file($root . '/.activity')) {
        file_put_contents($root . '/.activity', (string)$time);
    }

    clearstatcache();

    return freshUser($email);
}

function inactiveEmails(): array
{
    return array_map(fn (User $user) => $user->email(), AccountActivity::inactive());
}

/** The Panel dialog registered for the site area */
function cleanupDialog(): array
{
    foreach (kirby()->extensions('areas')['site'] as $area) {
        $area = $area(kirby());

        if (isset($area['dialogs']['inactive-accounts'])) {
            return $area['dialogs']['inactive-accounts'];
        }
    }

    throw new Exception('Dialog not registered');
}

describe('activity', function () {

    it('records a visit', function () {
        registerUser('jane@example.com');
        AccountActivity::touch(freshUser('jane@example.com'), 1700000000);

        expect(AccountActivity::seen(freshUser('jane@example.com')))->toBe(1700000000);
    });

    it('writes a visit at most once a day', function () {
        registerUser('jane@example.com');
        AccountActivity::touch(freshUser('jane@example.com'), 1700000000);
        AccountActivity::touch(freshUser('jane@example.com'), 1700000000 + 3600);

        expect(AccountActivity::seen(freshUser('jane@example.com')))->toBe(1700000000);

        AccountActivity::touch(freshUser('jane@example.com'), 1700000000 + 86400);

        expect(AccountActivity::seen(freshUser('jane@example.com')))->toBe(1700000000 + 86400);
    });

    it('records a login', function () {
        registerUser('jane@example.com', 'Password123');
        ageUser('jane@example.com', strtotime('-2 years'));

        $this->kirby->auth()->login('jane@example.com', 'Password123');

        expect(AccountActivity::seen(freshUser('jane@example.com')))->toBeGreaterThan(time() - 60);
    });

    it('is not a file of the user', function () {
        registerUser('jane@example.com');
        AccountActivity::touch(freshUser('jane@example.com'));

        expect(freshUser('jane@example.com')->files())->toHaveCount(0);
    });
});

describe('inactive accounts', function () {

    it('lists free accounts without activity for 12 months, longest inactive first', function () {
        registerUser('recent@example.com');
        registerUser('old@example.com');
        registerUser('older@example.com');
        ageUser('old@example.com', strtotime('-13 months'));
        ageUser('older@example.com', strtotime('-3 years'));
        ageUser('recent@example.com', strtotime('-11 months'));

        expect(inactiveEmails())->toBe(['older@example.com', 'old@example.com']);
    });

    it('counts a recent visit as activity', function () {
        registerUser('jane@example.com');
        ageUser('jane@example.com', strtotime('-2 years'));
        AccountActivity::touch(freshUser('jane@example.com'));

        expect(inactiveEmails())->toBe([]);
    });

    it('never lists paying accounts or admins', function () {
        registerUser('paid@example.com');
        $this->kirby->impersonate('kirby', fn () => freshUser('paid@example.com')->update(['tier' => 'Premium']));
        createAdmin('admin@example.com');
        ageUser('paid@example.com', strtotime('-2 years'));
        ageUser('admin@example.com', strtotime('-2 years'));

        expect(inactiveEmails())->toBe([]);
    });

    it('uses the configured number of months', function () {
        $this->kirby = $this->kirby->clone(['options' => ['inactiveAccountsMonths' => 24]]);
        registerUser('jane@example.com');
        ageUser('jane@example.com', strtotime('-13 months'));

        expect(inactiveEmails())->toBe([]);
    });
});

describe('delete', function () {

    beforeEach(fn () => createAdmin('admin@example.com'));

    it('deletes the selected inactive accounts and their Stripe customers', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $id = ageUser('jane@example.com', strtotime('-2 years'))->id();

        $this->kirby->impersonate('admin@example.com');
        $result = AccountActivity::delete([$id]);

        expect($result)->toBe(['deleted' => ['jane@example.com'], 'failed' => []])
            ->and(freshUser('jane@example.com'))->toBeNull()
            ->and($this->stripe->requestsTo('DELETE', "/v1/customers/$customer"))->toHaveCount(1);
    });

    it('skips accounts that are not inactive (any more)', function () {
        registerUser('active@example.com');
        registerUser('paid@example.com');
        $this->kirby->impersonate('kirby', fn () => freshUser('paid@example.com')->update(['tier' => 'Premium']));
        ageUser('paid@example.com', strtotime('-2 years'));

        $this->kirby->impersonate('admin@example.com');
        $result = AccountActivity::delete([
            freshUser('active@example.com')->id(),
            freshUser('paid@example.com')->id(),
            freshUser('admin@example.com')->id(),
            'unknown',
        ]);

        expect($result)->toBe(['deleted' => [], 'failed' => []])
            ->and(freshUser('active@example.com'))->not->toBeNull()
            ->and(freshUser('paid@example.com'))->not->toBeNull()
            ->and($this->stripe->requestsTo('DELETE', '/v1/customers/*'))->toBeEmpty();
    });

    it('keeps an account whose Stripe customer cannot be deleted and reports it', function () {
        registerUser('jane@example.com');
        registerUser('john@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $this->stripe->fail('DELETE', "/v1/customers/$customer");
        $jane = ageUser('jane@example.com', strtotime('-2 years'))->id();
        $john = ageUser('john@example.com', strtotime('-2 years'))->id();

        $this->kirby->impersonate('admin@example.com');
        $result = AccountActivity::delete([$jane, $john]);

        expect($result)->toBe(['deleted' => ['john@example.com'], 'failed' => ['jane@example.com' => 'Could not delete stripe customer!']])
            ->and(freshUser('jane@example.com'))->not->toBeNull();
    });
});

describe('panel dialog', function () {

    it('lists the inactive accounts for admins', function () {
        createAdmin('admin@example.com');
        registerUser('jane@example.com');
        $id = ageUser('jane@example.com', strtotime('-2 years'))->id();

        $this->kirby->impersonate('admin@example.com');
        $dialog = cleanupDialog()['load']();

        expect($dialog['component'])->toBe('k-form-dialog')
            ->and(array_column($dialog['props']['fields']['accounts']['options'], 'value'))->toBe([$id]);
    });

    it('shows a message when there is nothing to clean up', function () {
        createAdmin('admin@example.com');
        registerUser('jane@example.com');

        $this->kirby->impersonate('admin@example.com');

        expect(cleanupDialog()['load']()['component'])->toBe('k-text-dialog');
    });

    it('is only available for admins', function () {
        registerUser('jane@example.com');

        $this->kirby->impersonate('jane@example.com');

        expect(fn () => cleanupDialog()['load']())->toThrow(Kirby\Exception\PermissionException::class)
            ->and(fn () => cleanupDialog()['submit']())->toThrow(Kirby\Exception\PermissionException::class);
    });
});
