<?php

declare(strict_types=1);

namespace PortableSms\Http\Drivers;

use CurlHandle;
use InvalidArgumentException;
use PortableSms\Contracts\HttpClientInterface;
use PortableSms\Exceptions\SmsException;
use PortableSms\Http\HttpResponse;

/** Sends bounded HTTP requests through PHP cURL. */
final readonly class CurlHttpClient implements HttpClientInterface
{
    /** Creates a cURL client with request and connection timeouts. */
    public function __construct(
        private int $timeoutSeconds = 6,
        private int $connectTimeoutSeconds = 3,
    ) {
        if ($this->timeoutSeconds < 1 || $this->connectTimeoutSeconds < 1) {
            throw new InvalidArgumentException('HTTP timeouts must be at least one second.');
        }
    }

    /**
     * Sends a URL-encoded POST request through cURL.
     *
     * @param array<string, scalar|null> $fields
     */
    public function postForm(string $url, array $fields): HttpResponse
    {
        if (!extension_loaded('curl')) {
            throw new SmsException('The cURL extension is required by CurlHttpClient.');
        }

        $handle = curl_init($url);
        if (!$handle instanceof CurlHandle) {
            throw new SmsException('Unable to initialize cURL.');
        }

        try {
            $this->configureHandle($handle, $fields);
            $body = curl_exec($handle);
            if (!is_string($body)) {
                throw new SmsException('SMS HTTP transport failed: ' . curl_error($handle));
            }

            return new HttpResponse((int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $body);
        } finally {
            curl_close($handle);
        }
    }

    /**
     * Applies safe request options to a cURL handle.
     *
     * @param array<string, scalar|null> $fields
     */
    private function configureHandle(CurlHandle $handle, array $fields): void
    {
        $configured = curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min($this->connectTimeoutSeconds, $this->timeoutSeconds),
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'],
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if (!$configured) {
            throw new SmsException('Unable to configure the cURL request.');
        }
    }
}
