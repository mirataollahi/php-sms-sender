<?php

declare(strict_types=1);

namespace PortableSms\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PortableSms\Exceptions\SmsException;
use PortableSms\Http\HttpResponse;
use PortableSms\Providers\RayganSms\RayganSmsProvider;
use PortableSms\Providers\RayganSms\RayganSmsProviderConfig;
use PortableSms\Tests\Fakes\FakeHttpClient;

/** Tests Raygan SMS request mapping and failures. */
final class RayganSmsProviderTest extends TestCase
{
    /** Verifies provider fields are mapped without static configuration. */
    public function testItSendsExpectedFormFields(): void
    {
        $http = new FakeHttpClient();
        $config = new RayganSmsProviderConfig(
            'user',
            'secret',
            'https://sms.example.test/send',
            '30001234',
        );
        $provider = new RayganSmsProvider($http, $config);

        self::assertTrue($provider->send('09120000000', 'Hello'));
        self::assertSame('https://sms.example.test/send', $http->url);
        self::assertSame([
            'Username' => 'user',
            'Password' => 'secret',
            'Mobile' => '09120000000',
            'Message' => 'Hello',
            'Number' => '30001234',
        ], $http->fields);
    }

    /** Verifies non-successful HTTP responses become package exceptions. */
    public function testItRejectsFailedHttpResponses(): void
    {
        $provider = new RayganSmsProvider(
            new FakeHttpClient(new HttpResponse(503, 'Unavailable')),
            new RayganSmsProviderConfig('user', 'secret', 'https://sms.example.test/send'),
        );

        $this->expectException(SmsException::class);
        $provider->send('09120000000', 'Hello');
    }

    /** Verifies blank recipient values are rejected before an HTTP call. */
    public function testItRejectsBlankRecipients(): void
    {
        $provider = new RayganSmsProvider(
            new FakeHttpClient(),
            new RayganSmsProviderConfig('user', 'secret', 'https://sms.example.test/send'),
        );

        $this->expectException(InvalidArgumentException::class);
        $provider->send(' ', 'Hello');
    }
}
