<?php

namespace Tests\Support;

use Stripe\HttpClient\ClientInterface;

/**
 * Offline replacement for Stripe's HTTP client.
 *
 * memberkit creates `new \Stripe\StripeClient()` inside hooks and methods, so the
 * client can't be injected. Instead it is swapped globally via
 * `\Stripe\ApiRequestor::setHttpClient()`. Every request is recorded; responses
 * are generated (customers) or queued per "METHOD /path" via `respond()`.
 */
class FakeStripeClient implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, params: array}> */
    public array $requests = [];

    /** @var array<string, array<int, array{0: int, 1: array}>> */
    private array $queue = [];

    private int $counter = 0;

    public function respond(string $method, string $path, array $body, int $status = 200): static
    {
        $this->queue[strtoupper($method) . ' ' . $path][] = [$status, $body];
        return $this;
    }

    public function fail(string $method, string $path, int $status = 500, string $message = 'Fake Stripe error'): static
    {
        return $this->respond($method, $path, ['error' => ['type' => 'api_error', 'message' => $message]], $status);
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $method = strtoupper($method);
        $path   = parse_url($absUrl, PHP_URL_PATH);
        $query  = [];
        parse_str((string)parse_url($absUrl, PHP_URL_QUERY), $query);

        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params + $query];

        $key = $method . ' ' . $path;
        if (!empty($this->queue[$key])) {
            [$status, $body] = array_shift($this->queue[$key]);
        } else {
            [$status, $body] = $this->defaultResponse($method, $path, $params);
        }

        return [json_encode($body), $status, ['request-id' => 'req_fake_' . ++$this->counter]];
    }

    /** Requests matching "METHOD /path" (path may end with * as wildcard) */
    public function requestsTo(string $method, string $path): array
    {
        return array_values(array_filter($this->requests, function ($r) use ($method, $path) {
            $matches = str_ends_with($path, '*')
                ? str_starts_with($r['path'], rtrim($path, '*'))
                : $r['path'] === $path;

            return $r['method'] === strtoupper($method) && $matches;
        }));
    }

    private function defaultResponse(string $method, string $path, array $params): array
    {
        // POST /v1/customers -> create
        if ($method === 'POST' && $path === '/v1/customers') {
            return [200, ['id' => 'cus_fake_' . ++$this->counter, 'object' => 'customer', 'email' => $params['email'] ?? null]];
        }

        // /v1/customers/{id}
        if (preg_match('#^/v1/customers/([^/]+)$#', $path, $m)) {
            return match ($method) {
                'DELETE' => [200, ['id' => $m[1], 'object' => 'customer', 'deleted' => true]],
                default  => [200, ['id' => $m[1], 'object' => 'customer', 'email' => $params['email'] ?? null]],
            };
        }

        return [404, ['error' => ['type' => 'invalid_request_error', 'message' => "No fake response for $method $path"]]];
    }
}
