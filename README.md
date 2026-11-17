# PHP SMS Sender

[![CI](https://github.com/mirataollahi/php-sms-sender/actions/workflows/ci.yml/badge.svg)](https://github.com/mirataollahi/php-sms-sender/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/craftix/php-sms-sender/v/stable)](https://packagist.org/packages/craftix/php-sms-sender)
[![License](https://poser.pugx.org/craftix/php-sms-sender/license)](LICENSE)

SMS Sender is a clean, framework-independent PHP 8.2+ package for sending SMS messages through interchangeable providers.

The package currently includes Raygan SMS. Providers, HTTP clients, and PSR-3 loggers can be replaced without changing application code.

## Requirements

- PHP 8.2 or newer
- `ext-curl` when using the default cURL transport
- `ext-swoole` when using the Swoole transport

## Installation

Install the published package with Composer:

```bash
composer require craftix/php-sms-sender
```

For local development, add a path repository to your application's `composer.json`:

```json
{
    "repositories": [
        {"type": "path", "url": "packages/php-sms-sender"}
    ],
    "require": {
        "craftix/php-sms-sender": "@dev"
    }
}
```

## Basic usage

Initialize the manager once during application or worker startup. Raygan SMS is registered as the default provider.

```php
<?php

declare(strict_types=1);

use PortableSms\SmsManager;

$sms = SmsManager::initialize([
    'username' => getenv('SMS_USERNAME') ?: '',
    'password' => getenv('SMS_PASSWORD') ?: '',
    'url' => 'https://your-raygan-endpoint.example/send',
    'sender_number' => '30001234', // Optional.
    'timeout' => 6,
    'connect_timeout' => 3,
]);

$accepted = $sms->send('09120000000', 'Your verification code is 123456.');
```

`true` means the provider accepted the HTTP request. It does not prove handset delivery. Transport failures and non-2xx provider responses throw `PortableSms\Exceptions\SmsException`.

## PSR-3 logging

The manager accepts any `Psr\Log\LoggerInterface`. Logging is best-effort: logger failures do not change the SMS result. Recipient values are masked and message contents and credentials are never logged.

```php
use PortableSms\Logger\FileLogger;
use PortableSms\SmsManager;

$sms = SmsManager::initialize(
    config: [
        'username' => 'user',
        'password' => 'secret',
        'url' => 'https://sms.example.test/send',
    ],
    logger: new FileLogger(__DIR__ . '/var/sms.log'),
);
```

Without an explicit logger, the package uses PSR-3's `NullLogger`.

## HTTP clients

### cURL

`CurlHttpClient` is the default. Both timeouts are bounded and configured when the client is created.

```php
use PortableSms\Http\Drivers\CurlHttpClient;
use PortableSms\SmsManager;

$http = new CurlHttpClient(timeoutSeconds: 8, connectTimeoutSeconds: 2);
$sms = SmsManager::initialize($config, httpClient: $http);
```

### Swoole coroutine client

Initialize the manager at worker startup, then call `send()` inside a coroutine. Swoole's HTTP client yields while network I/O is pending, so it does not block the worker's event loop.

```php
use PortableSms\Http\Drivers\SwooleHttpClient;
use PortableSms\SmsManager;

$http = new SwooleHttpClient(timeoutSeconds: 6.0, connectTimeoutSeconds: 2.0);
$sms = SmsManager::initialize($config, httpClient: $http);

Swoole\Coroutine\run(function () use ($sms): void {
    $sms->send('09120000000', 'Hello from Swoole.');
});
```

Do not mutate the manager's provider registry per request in a concurrent worker. Register immutable providers once during worker startup.

### Custom HTTP client

Implement `PortableSms\Contracts\HttpClientInterface` and return an `HttpResponse` from `postForm()`:

```php
use PortableSms\Contracts\HttpClientInterface;
use PortableSms\Http\HttpResponse;

final class ApplicationHttpClient implements HttpClientInterface
{
    public function postForm(string $url, array $fields): HttpResponse
    {
        // Use your application's bounded HTTP client here.
        return new HttpResponse(200, 'OK');
    }
}
```

Custom clients must configure both connection and total request timeouts.

## Provider configuration

Each bundled provider has its own directory and config class. Common values live in `SmsProviderConfig`; provider-specific values may be added fluently and read as properties.

```php
use PortableSms\Providers\RayganSms\RayganSmsProviderConfig;

$config = new RayganSmsProviderConfig(
    username: 'user',
    password: 'secret',
    endpoint: 'https://sms.example.test/send',
    senderNumber: '30001234',
);

$config->addConfig('pin', '1234');
echo $config->pin;
```

When a provider has a known setting such as `pin`, prefer declaring a typed property on that provider's config class. Dynamic values are useful for custom or evolving provider APIs.

## Custom providers and provider selection

Implement `SmsProviderInterface`, register the instance under a name, and optionally make it the default:

```php
use PortableSms\Contracts\SmsProviderInterface;

final class MyProvider implements SmsProviderInterface
{
    public function send(string $to, string $message): bool
    {
        // Send using provider-specific logic and bounded network requests.
        return true;
    }
}

$sms->registerProvider('my_provider', new MyProvider(), default: true);
$sms->send('09120000000', 'Uses the default provider.');
```

You can also initialize directly with a custom default provider. In this form no Raygan credentials are required:

```php
$sms = SmsManager::initialize(
    logger: $logger,
    provider: new MyProvider(),
    providerName: 'my_provider',
);
```

Change the default later, or override it for one message:

```php
$sms->useProvider(SmsManager::RAYGAN_SMS);
$sms->send('09120000000', 'Uses Raygan SMS.');
$sms->send('09120000000', 'Uses custom provider once.', 'my_provider');
```

The initialized process-local instance is also available through `SmsManager::instance()`.

## Safety and delivery behavior

- Connection and request timeouts prevent unbounded network waits.
- Requests are not automatically retried because retrying an ambiguous timeout can send duplicate messages.
- Prefer an HTTPS provider endpoint. The historical Raygan endpoint defaults to HTTP for compatibility and can expose credentials and message content in transit.
- Generate and verify OTPs in the application, not in this transport package.
- Add authentication, rate limiting, and abuse prevention to application endpoints that trigger SMS messages.

## Development

Install dependencies and run the moderate PHPStan rules plus PHPUnit tests:

```bash
composer install
composer check
```

Run the tools separately with `composer analyse` and `composer test`.
