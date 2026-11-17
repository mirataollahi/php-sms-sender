<?php

declare(strict_types=1);

namespace PortableSms\Providers\RayganSms;

use InvalidArgumentException;
use PortableSms\Contracts\HttpClientInterface;
use PortableSms\Contracts\SmsProviderInterface;
use PortableSms\Exceptions\SmsException;

/** Sends SMS messages through the Raygan SMS form API. */
final readonly class RayganSmsProvider implements SmsProviderInterface
{
    /** Creates a Raygan SMS provider. */
    public function __construct(
        private HttpClientInterface $httpClient,
        private RayganSmsProviderConfig $config,
    ) {
    }

    /** Sends a message through Raygan SMS. */
    public function send(string $to, string $message): bool
    {
        $to = trim($to);
        $message = trim($message);

        if ($to === '' || $message === '') {
            throw new InvalidArgumentException('The recipient and message are required.');
        }

        $fields = [
            'Username' => $this->config->username,
            'Password' => $this->config->password,
            'Mobile' => $to,
            'Message' => $message,
        ];

        if ($this->config->senderNumber !== null) {
            $fields['Number'] = $this->config->senderNumber;
        }

        $response = $this->httpClient->postForm($this->config->endpoint, $fields);
        if (!$response->successful()) {
            throw new SmsException(sprintf('Raygan SMS returned HTTP status %d.', $response->statusCode));
        }

        return true;
    }
}
