<?php

declare(strict_types=1);

namespace PortableSms\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PortableSms\SmsManager;
use PortableSms\Tests\Fakes\FakeHttpClient;
use PortableSms\Tests\Fakes\FakeProvider;
use Psr\Log\NullLogger;

/** Tests provider registration and message routing. */
final class SmsManagerTest extends TestCase
{
    /** Verifies Raygan SMS is used by default. */
    public function testItUsesRayganSmsByDefault(): void
    {
        $http = new FakeHttpClient();
        $manager = SmsManager::initialize([
            'username' => 'user',
            'password' => 'secret',
            'url' => 'https://sms.example.test/send',
        ], $http, new NullLogger());

        self::assertTrue($manager->send('09120000000', 'Hello'));
        self::assertSame('Hello', $http->fields['Message']);
    }

    /** Verifies a custom provider can replace the default. */
    public function testItRegistersAndSelectsCustomProviders(): void
    {
        $manager = SmsManager::initialize([
            'username' => 'user',
            'password' => 'secret',
            'url' => 'https://sms.example.test/send',
        ], new FakeHttpClient(), new NullLogger());
        $custom = new FakeProvider();

        $manager->registerProvider('custom', $custom, true);

        self::assertTrue($manager->send('09120000000', 'Custom'));
        self::assertSame([['to' => '09120000000', 'message' => 'Custom']], $custom->messages);
    }

    /** Verifies send can override the default provider for one message. */
    public function testItOverridesProviderPerMessage(): void
    {
        $manager = SmsManager::initialize([
            'username' => 'user',
            'password' => 'secret',
            'url' => 'https://sms.example.test/send',
        ], new FakeHttpClient(), new NullLogger());
        $custom = new FakeProvider();
        $manager->registerProvider('custom', $custom);

        self::assertTrue($manager->send('09120000000', 'Custom', 'custom'));
        self::assertCount(1, $custom->messages);
    }

    /** Verifies initialization can start with a custom default provider. */
    public function testItInitializesWithACustomProvider(): void
    {
        $custom = new FakeProvider();
        $manager = SmsManager::initialize(
            provider: $custom,
            providerName: 'custom',
        );

        self::assertTrue($manager->send('09120000000', 'Custom'));
        self::assertCount(1, $custom->messages);
    }

    /** Verifies unknown providers are rejected clearly. */
    public function testItRejectsUnknownProviders(): void
    {
        $manager = SmsManager::initialize([
            'username' => 'user',
            'password' => 'secret',
            'url' => 'https://sms.example.test/send',
        ], new FakeHttpClient(), new NullLogger());

        $this->expectException(InvalidArgumentException::class);
        $manager->send('09120000000', 'Hello', 'missing');
    }
}
