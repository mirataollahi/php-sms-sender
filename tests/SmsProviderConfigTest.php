<?php

declare(strict_types=1);

namespace PortableSms\Tests;

use PHPUnit\Framework\TestCase;
use PortableSms\Providers\RayganSms\RayganSmsProviderConfig;

/** Tests extensible provider configuration. */
final class SmsProviderConfigTest extends TestCase
{
    /** Verifies extra values can be added and read through magic properties. */
    public function testItStoresProviderSpecificConfiguration(): void
    {
        $config = new RayganSmsProviderConfig(
            'user',
            'secret',
            'https://sms.example.test/send',
        );

        $result = $config->addConfig('pin', '1234');

        self::assertSame($config, $result);
        self::assertTrue(isset($config->pin));
        self::assertSame('1234', $config->pin);
        self::assertSame('1234', $config->getConfig('pin'));
    }
}
