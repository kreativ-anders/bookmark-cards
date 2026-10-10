<?php

/*
| Memberkit::prices() (landing page pricing) and Memberkit::stripe()
*/

function respondPrices(Tests\Support\FakeStripeClient $stripe, int $basic = 499, int $premium = 4900, int $lifetime = 9900): void
{
    $stripe->respond('GET', '/v1/prices/price_basic', ['id' => 'price_basic', 'object' => 'price', 'unit_amount' => $basic])
        ->respond('GET', '/v1/prices/price_premium', ['id' => 'price_premium', 'object' => 'price', 'unit_amount' => $premium])
        ->respond('GET', '/v1/prices/price_lifetime', ['id' => 'price_lifetime', 'object' => 'price', 'unit_amount' => $lifetime]);
}

it('resolves the price of every paid tier', function () {
    respondPrices($this->stripe);

    expect(Memberkit::prices())->toEqual([1 => 4.99, 2 => 49, 3 => 99]);
});

it('asks Stripe only once while the prices are cached', function () {
    respondPrices($this->stripe);

    Memberkit::prices();
    Memberkit::prices();

    expect($this->stripe->requestsTo('GET', '/v1/prices/*'))->toHaveCount(3);
});

it('keeps the page working and retries soon when Stripe fails', function () {
    $this->stripe->fail('GET', '/v1/prices/price_basic')
        ->respond('GET', '/v1/prices/price_premium', ['id' => 'price_premium', 'object' => 'price', 'unit_amount' => 4900])
        ->fail('GET', '/v1/prices/price_lifetime');

    expect(Memberkit::prices())->toEqual([1 => null, 2 => 49, 3 => null]);

    $cache = $this->kirby->cache('kreativ-anders.memberkit');
    expect($cache->expires('prices') - time())->toBeLessThanOrEqual(Memberkit::PRICES_RETRY_TTL * 60);
});

it('drops the cached prices on a signed price.updated webhook', function () {
    respondPrices($this->stripe);
    Memberkit::prices();

    $payload   = json_encode(['id' => 'evt_price', 'object' => 'event', 'type' => 'price.updated', 'data' => ['object' => ['id' => 'price_basic', 'object' => 'price']]]);
    $timestamp = time();
    $signature = 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_fake');

    expect(site()->handleStripeWebhook($payload, $signature))->toBe(200);

    respondPrices($this->stripe, basic: 599);
    expect(Memberkit::prices()[1])->toBe(5.99);
});

it('uses a custom API base (stripe-mock in CI) only when configured', function () {
    expect(Memberkit::stripe()->getApiBase())->toBe(\Stripe\BaseStripeClient::DEFAULT_API_BASE);

    $this->kirby = $this->kirby->clone(['options' => ['kreativ-anders.memberkit.apiBase' => 'http://localhost:12111']]);

    expect(Memberkit::stripe()->getApiBase())->toBe('http://localhost:12111');
});

describe('editing bookmarks', function () {

    beforeEach(function () {
        $this->kirby = $this->kirby->clone(['options' => ['noPremiumLimit' => 2, 'noPremiumTitle' => 'BECOME PREMIUM']]);
        $this->user  = registerUser();
    });

    it('lets free users edit up to the limit, never the upsell card', function () {
        $card = ['title' => 'GitHub', 'link' => 'https://github.com', 'tags' => ''];

        expect(Bookmarks::editable($this->user, [$card, $card], $card))->toBeTrue()
            ->and(Bookmarks::editable($this->user, [$card, $card, $card], $card))->toBeFalse()
            ->and(Bookmarks::editable($this->user, [$card], ['title' => 'BECOME PREMIUM']))->toBeFalse();
    });

    it('lets premium users always edit', function () {
        $this->kirby->impersonate('kirby', fn () => freshUser('jane@example.com')->update([
            'tier' => 'Basic', 'stripe_subscription' => 'sub_1', 'stripe_status' => 'active',
        ]));
        $card = ['title' => 'BECOME PREMIUM'];

        expect(Bookmarks::editable(freshUser('jane@example.com'), [$card, $card, $card], $card))->toBeTrue();
    });
});
