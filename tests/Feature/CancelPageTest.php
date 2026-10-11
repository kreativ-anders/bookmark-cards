<?php

/*
| /cancel: how to cancel Premium (and Stripe's return page for an aborted checkout)
*/

function cancelPage(): string
{
    $response = kirby()->render('cancel');

    expect($response->code())->toBe(200);

    return $response->body();
}

it('explains login and contact form to guests', function () {
    expect(cancelPage())->toContain('Log in')
        ->toContain('Forgot your login?')
        ->toContain('https://kreativ-anders.de/kontakt')
        ->toContain('Nothing was charged');
});

it('tells users without subscription that there is nothing to cancel', function () {
    registerUser('jane@example.com');
    $this->kirby->impersonate('jane@example.com');

    expect(cancelPage())->toContain('Nothing to cancel')
        ->not->toContain('Forgot your login?');
});

it('links premium users to the Stripe portal', function () {
    registerUser('jane@example.com');
    $customer = freshUser('jane@example.com')->stripe_customer()->value();
    site()->updateStripeSubscriptionWebhook(stripeSubscription($customer, 'price_premium', 'active', 'sub_1'));
    $this->kirby->impersonate('jane@example.com');

    expect(cancelPage())->toContain('Cancel now')
        ->toContain('checkout/portal');
});

it('shows the end date when premium is already canceled', function () {
    registerUser('jane@example.com');
    $customer = freshUser('jane@example.com')->stripe_customer()->value();
    $end      = strtotime('+1 month 12:00 UTC');
    site()->updateStripeSubscriptionWebhook(stripeSubscription($customer, 'price_premium', 'active', 'sub_1', ['cancel_at' => $end, 'cancel_at_period_end' => true]));
    $this->kirby->impersonate('jane@example.com');

    expect(cancelPage())->toContain('Already canceled')
        ->toContain(date('F j, Y', $end));
});

it('is linked in the footer of every page', function () {
    expect(cancelPage())->toContain('>Cancel subscription</a>');
});
