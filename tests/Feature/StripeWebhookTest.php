<?php

/*
| memberkit webhook endpoint (site method handleStripeWebhook)
| signature verification, replay protection, event dispatch
*/

/** Signs a payload like Stripe does (Stripe-Signature header) */
function stripeSignature(string $payload, string $secret = 'whsec_fake', ?int $timestamp = null): string
{
    $timestamp ??= time();
    return 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
}

function stripeEvent(string $type, array $object): string
{
    return json_encode(['id' => 'evt_fake', 'object' => 'event', 'type' => $type, 'data' => ['object' => $object]]);
}

beforeEach(function () {
    registerUser('jane@example.com');
    $this->customer = freshUser('jane@example.com')->stripe_customer()->value();
    $this->emailEvent = stripeEvent('customer.updated', ['id' => $this->customer, 'object' => 'customer', 'email' => 'mallory@example.com']);
});

describe('signature verification', function () {

    it('rejects events without signature', function () {
        expect(site()->handleStripeWebhook($this->emailEvent, ''))->toBe(400)
            ->and(freshUser('jane@example.com'))->not->toBeNull();
    });

    it('rejects events signed with another secret', function () {
        expect(site()->handleStripeWebhook($this->emailEvent, stripeSignature($this->emailEvent, 'whsec_attacker')))->toBe(400)
            ->and(freshUser('jane@example.com'))->not->toBeNull();
    });

    it('rejects a tampered payload', function () {
        $signature = stripeSignature($this->emailEvent);
        $tampered  = str_replace('mallory', 'eve', $this->emailEvent);

        expect(site()->handleStripeWebhook($tampered, $signature))->toBe(400);
    });

    it('rejects replayed (old) events', function () {
        expect(site()->handleStripeWebhook($this->emailEvent, stripeSignature($this->emailEvent, timestamp: time() - 3600)))->toBe(400)
            ->and(freshUser('jane@example.com'))->not->toBeNull();
    });

    it('rejects all events when no webhook secret is configured', function () {
        $this->kirby = $this->kirby->clone(['options' => ['kreativ-anders.memberkit.webhookSecret' => '']]);

        expect(site()->handleStripeWebhook($this->emailEvent, ''))->toBe(500)
            ->and(freshUser('jane@example.com'))->not->toBeNull();
    });

    it('rejects invalid JSON', function () {
        expect(site()->handleStripeWebhook('not json', stripeSignature('not json')))->toBe(400);
    });
});

describe('event dispatch', function () {

    it('changes the email for a signed customer.updated event', function () {
        expect(site()->handleStripeWebhook($this->emailEvent, stripeSignature($this->emailEvent)))->toBe(200)
            ->and(freshUser('mallory@example.com'))->not->toBeNull()
            ->and(freshUser('jane@example.com'))->toBeNull();
    });

    it('ignores customer.updated events without email change', function () {
        $event = stripeEvent('customer.updated', ['id' => $this->customer, 'object' => 'customer', 'email' => 'JANE@example.com']);

        expect(site()->handleStripeWebhook($event, stripeSignature($event)))->toBe(200)
            ->and($this->stripe->requestsTo('POST', "/v1/customers/{$this->customer}"))->toBeEmpty(); // no sync back to Stripe
    });

    it('updates the subscription for a signed subscription event', function () {
        $event = stripeEvent('customer.subscription.updated', stripeSubscription($this->customer, 'price_basic', 'active', 'sub_1')->toArray());

        expect(site()->handleStripeWebhook($event, stripeSignature($event)))->toBe(200)
            ->and(freshUser('jane@example.com')->tier()->value())->toBe('Basic');
    });

    it('acknowledges unhandled event types', function () {
        $event = stripeEvent('invoice.payment_failed', ['id' => 'in_1', 'object' => 'invoice', 'customer' => $this->customer, 'subscription' => 'sub_1']);

        expect(site()->handleStripeWebhook($event, stripeSignature($event)))->toBe(200)
            ->and(freshUser('jane@example.com')->stripe_subscription()->isEmpty())->toBeTrue();
    });

    it('returns 500 (Stripe retries) when the event cannot be processed', function () {
        $event = stripeEvent('customer.subscription.updated', stripeSubscription('cus_unknown', 'price_basic')->toArray());

        expect(site()->handleStripeWebhook($event, stripeSignature($event)))->toBe(500);
    });
});
