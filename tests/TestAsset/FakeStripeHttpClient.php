<?php

declare(strict_types=1);

namespace Contenir\Commerce\Tests\TestAsset;

use Stripe\HttpClient\ClientInterface;

use function array_shift;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Offline stand-in for Stripe's HTTP layer. Queue responses before use and
 * inspect captured requests afterwards.
 */
final class FakeStripeHttpClient implements ClientInterface
{
    /** @var list<array{method: string, url: string, params: array<mixed>}> */
    public array $requests = [];

    /** @var list<array{body: array<mixed>, code: int}> */
    private array $responses = [];

    /**
     * @param array<mixed> $body
     */
    public function queueResponse(array $body, int $code = 200): void
    {
        $this->responses[] = ['body' => $body, 'code' => $code];
    }

    /**
     * @param mixed $method
     * @param mixed $absUrl
     * @param mixed $headers
     * @param mixed $params
     * @param mixed $hasFile
     * @param mixed $apiMode
     * @param mixed $maxNetworkRetries
     * @return array{string, int, array<mixed>}
     */
    public function request(
        $method,
        $absUrl,
        $headers,
        $params,
        $hasFile,
        $apiMode = 'v1',
        $maxNetworkRetries = null
    ): array {
        $this->requests[] = [
            'method' => (string) $method,
            'url'    => (string) $absUrl,
            'params' => (array) $params,
        ];

        $response = array_shift($this->responses)
            ?? ['body' => ['error' => ['message' => 'No queued response', 'type' => 'api_error']], 'code' => 500];

        return [json_encode($response['body'], JSON_THROW_ON_ERROR), $response['code'], []];
    }
}
