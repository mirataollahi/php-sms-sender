<?php

declare(strict_types=1);

namespace PortableSms\Providers\RayganSms;

use InvalidArgumentException;
use PortableSms\Providers\SmsProviderConfig;

/** Configures credentials and endpoint details for Raygan SMS. */
final class RayganSmsProviderConfig extends SmsProviderConfig
{
    /** Creates and validates Raygan SMS configuration. */
    public function __construct(
        string $username,
        string $password,
        string $endpoint = 'http://smspanel.trez.ir/SendMessageWithCode.ashx',
        ?string $senderNumber = null,
    ) {
        parent::__construct($endpoint, $username, $password, $senderNumber);

        if (trim($username) === '' || trim($password) === '') {
            throw new InvalidArgumentException('Raygan SMS username and password are required.');
        }
    }
}
