<?php

declare(strict_types=1);

namespace PortableSms\Http\Drivers;

use InvalidArgumentException;
use PortableSms\Contracts\HttpClientInterface;
use PortableSms\Exceptions\SmsException;
use PortableSms\Http\HttpResponse;
use Swoole\Coroutine\Http\Client;

/** Sends coroutine-friendly HTTP requests through Swoole. */
final readonly class SwooleHttpClient implements HttpClientInterface
{
    /** Creates a Swoole client with request and connection timeouts. */
    public function __construct(
        private float $timeoutSeconds = 6.0,
        private float $connectTimeoutSeconds = 3.0,
    ) {
        if ($this->timeoutSeconds <= 0 || $this->connectTimeoutSeconds <= 0) {
            throw new InvalidArgumentException('HTTP timeouts must be positive.');
        }
    }

    /**
     * Sends a URL-encoded POST request through a Swoole coroutine client.
     *
     * @param array<string, scalar|null> $fields
     */
    public function postForm(string $url, array $fields): HttpResponse
    {
        if (!class_exists(Client::class)) {
            throw new SmsException('The Swoole extension is required by SwooleHttpClient.');
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('The HTTP URL is invalid.');
        }

        $secure = strtolower((string) $parts['scheme']) === 'https';
        $port = isset($parts['port']) ? (int) $parts['port'] : ($secure ? 443 : 80);
        $path = (string) ($parts['path'] ?? '/');
        if (isset($parts['query'])) {
            $path .= '?' . $parts['query'];
        }

        $client = new Client((string) $parts['host'], $port, $secure);
        try {
            $client->set([
                'timeout' => $this->timeoutSeconds,
                'connect_timeout' => min($this->connectTimeoutSeconds, $this->timeoutSeconds),
            ]);
            $client->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            ]);
            $body = http_build_query($fields, '', '&', PHP_QUERY_RFC3986);

            if (!$client->post($path, $body)) {
                throw new SmsException(sprintf('Swoole HTTP request failed with error %d.', $client->errCode));
            }

            return new HttpResponse($client->statusCode, $client->body);
        } finally {
            $client->close();
        }
    }
}
