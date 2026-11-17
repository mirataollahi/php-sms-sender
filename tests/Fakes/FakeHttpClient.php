<?php

declare(strict_types=1);

namespace PortableSms\Tests\Fakes;

use PortableSms\Contracts\HttpClientInterface;
use PortableSms\Http\HttpResponse;

/** Records HTTP calls and returns a configured response. */
final class FakeHttpClient implements HttpClientInterface
{
    /** The last requested URL. */
    public ?string $url = null;

    /** @var array<string, scalar|null> The last submitted fields. */
    public array $fields = [];

    /** Creates a fake client with a response. */
    public function __construct(private readonly HttpResponse $response = new HttpResponse(200, 'OK'))
    {
    }

    /** Records and completes a form request. */
    public function postForm(string $url, array $fields): HttpResponse
    {
        $this->url = $url;
        $this->fields = $fields;

        return $this->response;
    }
}
