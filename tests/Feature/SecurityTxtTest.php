<?php

/*
| /.well-known/security.txt (RFC 9116) is a Kirby route, not a file:
| on the server .well-known/ belongs to root (Let's Encrypt), git can't write there
*/

function securityTxt(): Kirby\Http\Response
{
    return kirby()->call('.well-known/security.txt');
}

it('serves security.txt as plain text', function () {
    $response = securityTxt();

    expect($response->code())->toBe(200)
        ->and($response->type())->toBe('text/plain')
        ->and($response->body())
        ->toContain('Contact: mailto:security@kreativ-anders.de')
        ->toContain('Preferred-Languages: en, de')
        ->toContain('Canonical: ' . site()->url() . '/.well-known/security.txt');
});

it('never expires and stays under the recommended one year', function () {
    preg_match('/^Expires: (.+)$/m', securityTxt()->body(), $match);
    $expires = strtotime($match[1]);

    expect($expires)->toBeGreaterThan(strtotime('+30 days'))
        ->toBeLessThan(strtotime('+1 year'));
});

it('is not tracked as a file, so deploys never write into .well-known', function () {
    expect(file_exists(PROJECT_ROOT . '/.well-known/security.txt'))->toBeFalse();
});
