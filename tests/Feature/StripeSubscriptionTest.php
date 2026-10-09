<?php

/*
| memberkit subscription handling
| site methods (webhooks), user methods (merge, checkout URLs)
*/

beforeEach(function () {
    registerUser('jane@example.com');
    $this->customer = freshUser('jane@example.com')->stripe_customer()->value();
});

describe('webhook: customer.subscription.updated', function () {

    it('stores subscription, status and tier derived from the price', function () {
        $this->stripe->respond('GET', "/v1/customers/{$this->customer}", ['id' => $this->customer, 'object' => 'customer', 'email' => 'jane@example.com']);

        site()->updateStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_premium', 'active', 'sub_123'));

        $user = freshUser('jane@example.com');
        expect($user->tier()->value())->toBe('Premium')
            ->and($user->stripe_status()->value())->toBe('active')
            ->and($user->stripe_subscription()->value())->toBe('sub_123');
    });

    it('stores a past_due status after a failed payment', function () {
        $this->stripe->respond('GET', "/v1/customers/{$this->customer}", ['id' => $this->customer, 'object' => 'customer', 'email' => 'jane@example.com']);

        site()->updateStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_basic', 'past_due'));

        expect(freshUser('jane@example.com')->stripe_status()->value())->toBe('past_due');
    });

    it('fails when no Kirby user belongs to the customer', function () {
        expect(fn () => site()->updateStripeSubscriptionWebhook(stripeSubscription('cus_unknown', 'price_basic')))
            ->toThrow(Exception::class, 'Could not find kirby user for stripe customer!');
    });

    it('matches the user by customer id, not by the (customer-editable) Stripe email', function () {
        registerUser('mallory@example.com');
        $mallory = freshUser('mallory@example.com')->stripe_customer()->value();

        // Mallory set the victim's email in the Stripe portal
        $this->stripe->respond('GET', "/v1/customers/$mallory", ['id' => $mallory, 'object' => 'customer', 'email' => 'jane@example.com']);

        site()->updateStripeSubscriptionWebhook(stripeSubscription($mallory, 'price_premium', 'active', 'sub_mallory'));

        expect(freshUser('jane@example.com')->stripe_subscription()->isEmpty())->toBeTrue()
            ->and(freshUser('mallory@example.com')->stripe_subscription()->value())->toBe('sub_mallory');
    });

    it('rejects unknown prices', function () {
        expect(fn () => site()->updateStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_other')))
            ->toThrow(Exception::class, 'Unknown stripe price!');

        expect(freshUser('jane@example.com')->tier()->value())->toBe('Free');
    });
});

describe('webhook: customer.subscription.deleted', function () {

    it('resets the user to the free tier', function () {
        $this->stripe
            ->respond('GET', "/v1/customers/{$this->customer}", ['id' => $this->customer, 'object' => 'customer', 'email' => 'jane@example.com'])
            ->respond('GET', "/v1/customers/{$this->customer}", ['id' => $this->customer, 'object' => 'customer', 'email' => 'jane@example.com']);

        site()->updateStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_basic'));
        site()->cancelStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_basic', 'canceled'));

        $user = freshUser('jane@example.com');
        expect($user->tier()->value())->toBe('Free')
            ->and($user->stripe_status()->isEmpty())->toBeTrue()
            ->and($user->stripe_subscription()->isEmpty())->toBeTrue();
    });

    it('keeps the current subscription when another one ends', function () {
        site()->updateStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_premium', 'active', 'sub_new'));
        site()->cancelStripeSubscriptionWebhook(stripeSubscription($this->customer, 'price_basic', 'canceled', 'sub_old'));

        $user = freshUser('jane@example.com');
        expect($user->tier()->value())->toBe('Premium')
            ->and($user->stripe_subscription()->value())->toBe('sub_new');
    });
});

describe('webhook: customer.updated', function () {

    it('changes the Kirby email of the matching Stripe customer', function () {
        $customer = Stripe\Customer::constructFrom(['id' => $this->customer, 'object' => 'customer', 'email' => 'jane.new@example.com']);

        site()->updateStripeEmailWebhook($customer);

        expect(freshUser('jane.new@example.com'))->not->toBeNull()
            ->and(freshUser('jane@example.com'))->toBeNull();
    });
});

describe('mergeStripeCustomer (checkout success)', function () {

    it('syncs tier, subscription and email from Stripe', function () {
        $this->stripe->respond('GET', "/v1/customers/{$this->customer}", [
            'id'            => $this->customer,
            'object'        => 'customer',
            'email'         => 'jane.billing@example.com',
            'subscriptions' => ['object' => 'list', 'data' => [stripeSubscription($this->customer, 'price_lifetime', 'active', 'sub_999')->toArray()]],
        ]);

        $this->kirby->impersonate('kirby');
        // update() and changeEmail() on the same instance — must keep working with Kirby 5 immutable models
        expect(freshUser('jane@example.com')->mergeStripeCustomer())->toBeTrue();

        $retrieve = $this->stripe->requestsTo('GET', "/v1/customers/{$this->customer}");
        expect($retrieve[0]['params']['expand'])->toBe(['subscriptions']);

        $user = freshUser('jane.billing@example.com');
        expect($user)->not->toBeNull()
            ->and($user->tier()->value())->toBe('Lifetime')
            ->and($user->stripe_subscription()->value())->toBe('sub_999')
            ->and($user->stripe_status()->value())->toBe('active');
    });
});

describe('checkout URLs', function () {

    it('builds the checkout URL for paid tiers', function () {
        expect(freshUser('jane@example.com')->getStripeCheckoutURL('Basic'))->toBe('checkout/subscribe/basic')
            ->and(freshUser('jane@example.com')->getStripeCheckoutURL('Lifetime'))->toBe('checkout/subscribe/lifetime');
    });

    it('rejects the free tier and unknown tiers', function (string $tier) {
        expect(fn () => freshUser('jane@example.com')->getStripeCheckoutURL($tier))
            ->toThrow(Exception::class, 'Tier does not exist!');
    })->with(['Free', 'Gold']);

    it('builds portal and webhook URLs', function () {
        $user = freshUser('jane@example.com');
        expect($user->getStripePortalURL())->toBe('checkout/portal')
            ->and($user->getStripeWebhookURL())->toBe('checkout/webhook');
    });
});
