<?php

/*
| memberkit hooks (site/plugins/memberkit/hooks.php)
| user.create:after, user.changeEmail:after, user.delete:after
*/

describe('register (user.create:after)', function () {

    it('creates a Stripe customer and stores its id with the free tier', function () {
        registerUser('jane@example.com');

        $requests = $this->stripe->requestsTo('POST', '/v1/customers');
        expect($requests)->toHaveCount(1)
            ->and($requests[0]['params'])->toBe(['email' => 'jane@example.com']);

        $user = freshUser('jane@example.com');
        expect($user->stripe_customer()->value())->toStartWith('cus_fake_')
            ->and($user->tier()->value())->toBe('Free')
            ->and($user->role()->id())->toBe('user');
    });

    it('fails the registration when Stripe is unavailable', function () {
        $this->stripe->fail('POST', '/v1/customers');

        expect(fn () => registerUser('jane@example.com'))
            ->toThrow(Exception::class, 'Could not create stripe customer!');
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

describe('delete account (user.delete:after)', function () {

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

    it('reports a failing Stripe deletion', function () {
        registerUser('jane@example.com');
        $customer = freshUser('jane@example.com')->stripe_customer()->value();
        $this->stripe->fail('DELETE', "/v1/customers/$customer");

        $this->kirby->impersonate('kirby');
        expect(fn () => freshUser('jane@example.com')->delete())
            ->toThrow(Exception::class, 'Could not delete stripe customer!');
    });
});
