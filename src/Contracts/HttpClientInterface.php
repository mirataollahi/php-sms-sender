<?php

declare(strict_types=1);

namespace PortableSms\Contracts;

use PortableSms\Http\HttpResponse;

/** Sends form-encoded HTTP requests for SMS providers. */
interface HttpClientInterface
{
    /**
     * Sends a form-encoded POST request.
     *
     * @param array<string, scalar|null> $fields
     */
    public function postForm(string $url, array $fields): HttpResponse;
}
