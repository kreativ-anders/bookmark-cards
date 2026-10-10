<?php

/*
| memberkit hooks (site/plugins/memberkit/hooks.php)
| user.create:after, user.changeEmail:after, user.delete:before
*/

describe('register (user.create:after)', function () {

    it('creates a Stripe customer and stores its id with the free tier', function () {
        registerUser('jane@example.com');

        $user = freshUser('jane@example.com');

        $requests = $this->stripe->requestsTo('POST', '/v1/customers');
        expect($requests)->toHaveCount(1)
            ->and($requests[0]['params'])->toBe(['email' => 'jane@example.com', 'metadata' => ['kirby_user' => $user->id()]])
            ->and($requests[0]['headers'])->toContain('Idempotency-Key: memberkit-create-' . $user->id());

        expect($user->stripe_customer()->value())->toStartWith('cus_fake_')
            ->and($user->tier()->value())->toBe('Free')
            ->and($user->role()->id())->toBe('user');
    });

    it('fails the registration when Stripe is unavailable', function () {
        $this->stripe->fail('POST', '/v1/customers');

        expect(fn () => registerUser('jane@example.com'))
            ->toThrow(Exception::class, 'Could not create stripe customer!');
    });

    it('rolls back the Kirby user when Stripe is unavailable', function () {
        registerUser('other@example.com'); // Kirby refuses to delete the last remaining user
        $this->stripe->fail('POST', '/v1/customers');

        expect(fn () => registerUser('jane@example.com'))->toThrow(Exception::class);

        expect(freshUser('jane@example.com'))->toBeNull()
            ->and($this->stripe->requestsTo('DELETE', '/v1/customers/*'))->toBeEmpty(); // no customer to clean up
    });

    it('sends the Stripe secret key from the config', function () {
        registerUser();

        expect(Stripe\ApiRequestor::httpClient())->toBe($this->stripe)
            ->and(option('kreativ-anders.memberkit.secretKey'))->toBe('sk_test_fake');
    });
});

describe('login', function () {

    it('accepts the password chosen at registration', function () {
        registerUser('jane@example.com', 'Password123');
        $requestsAfterRegister = count($this->stripe->requests);

        $user = $this->kirby->auth()->validatePassword('jane@example.com', 'Password123');

        expect($user->email())->toBe('jane@example.com')
            ->and($this->stripe->requests)->toHaveCount($requestsAfterRegister); // login never calls Stripe
    });

    it('rejects a wrong password', function () {
        registerUser('jane@example.com', 'Password123');

        expect(fn () => $this->kirby->auth()->validatePassword('jane@example.com', 'WrongPassword'))
            ->toThrow(Kirby\Exception\PermissionException::class);
    });

    it('rejects an unknown user', function () {
        expect(fn () => $this->kirby->auth()->validatePassword('nobody@example.com', 'Password123'))
            ->toThrow(Kirby\Exception\PermissionException::class);
    });
});

describe('change email (user.changeEmail:after)', function () {

    it('updates the email of the Stripe customer', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();

        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->changeEmail('jane.doe@example.com');

        $requests = $this->stripe->requestsTo('POST', "/v1/customers/$customer");
        expect($requests)->toHaveCount(1)
            ->and($requests[0]['params'])->toBe(['email' => 'jane.doe@example.com'])
            ->and(freshUser('jane.doe@example.com'))->not->toBeNull();
    });

    it('reports a failing Stripe update', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $this->stripe->fail('POST', "/v1/customers/$customer");

        $this->kirby->impersonate('kirby');
        expect(fn () => freshUser('jane@example.com')->changeEmail('jane.doe@example.com'))
            ->toThrow(Exception::class, 'Could not update stripe customer!');
    });
});

describe('delete account (user.delete:before)', function () {

    // Kirby refuses to delete the last remaining user
    beforeEach(fn () => registerUser('other@example.com'));

    it('deletes the Stripe customer', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();

        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->delete();

        expect($this->stripe->requestsTo('DELETE', "/v1/customers/$customer"))->toHaveCount(1)
            ->and(freshUser('jane@example.com'))->toBeNull();
    });

    it('keeps the Kirby user when the Stripe deletion fails', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $this->stripe->fail('DELETE', "/v1/customers/$customer");

        $this->kirby->impersonate('kirby');
        expect(fn () => freshUser('jane@example.com')->delete())
            ->toThrow(Exception::class, 'Could not delete stripe customer!');

        // still there, so the user can retry - no paying Stripe customer without account
        expect(freshUser('jane@example.com')->stripe_customer()->value())->toBe($customer);
    });

    it('deletes the Kirby user when the Stripe customer is already gone', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $this->stripe->respond('DELETE', "/v1/customers/$customer", ['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => "No such customer: '$customer'"]], 404);

        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->delete();

        expect(freshUser('jane@example.com'))->toBeNull();
    });

    it('deletes a user without Stripe customer without calling Stripe', function () {
        registerUser('jane@example.com');
        $this->kirby->impersonate('kirby');
        freshUser('jane@example.com')->update(['stripe_customer' => null]);

        freshUser('jane@example.com')->delete();

        expect(freshUser('jane@example.com'))->toBeNull()
            ->and($this->stripe->requestsTo('DELETE', '/v1/customers/*'))->toBeEmpty();
    });
});
