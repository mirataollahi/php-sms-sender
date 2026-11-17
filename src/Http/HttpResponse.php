<?php

declare(strict_types=1);

namespace PortableSms\Http;

/** Immutable HTTP response returned by an HTTP client. */
final readonly class HttpResponse
{
    /** Creates an HTTP response value object. */
    public function __construct(
        public int $statusCode,
        public string $body,
    ) {
    }

    /** Reports whether the response has a successful HTTP status. */
    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
