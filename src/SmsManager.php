<?php

declare(strict_types=1);

namespace PortableSms;

use InvalidArgumentException;
use LogicException;
use PortableSms\Contracts\HttpClientInterface;
use PortableSms\Contracts\SmsProviderInterface;
use PortableSms\Http\Drivers\CurlHttpClient;
use PortableSms\Providers\RayganSms\RayganSmsProvider;
use PortableSms\Providers\RayganSms\RayganSmsProviderConfig;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/** Registers SMS providers and routes messages to the selected provider. */
final class SmsManager
{
    /** The name assigned to the built-in Raygan SMS provider. */
    public const RAYGAN_SMS = 'raygan_sms';

    /** The process-local manager instance. */
    private static ?self $instance = null;

    /** @var array<string, SmsProviderInterface> Registered providers keyed by name. */
    private array $providers = [];

    /** Creates an empty manager with a logger. */
    private function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Initializes the manager with Raygan SMS as its default provider.
     *
     * @param array<string, mixed> $config
     */
    public static function initialize(
        array $config = [],
        ?HttpClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?SmsProviderInterface $provider = null,
        string $providerName = self::RAYGAN_SMS,
    ): self {
        $manager = new self($logger ?? new NullLogger());

        if ($provider !== null) {
            $manager->registerProvider($providerName, $provider, true);

            return self::$instance = $manager;
        }

        $httpClient ??= new CurlHttpClient(
            self::positiveInt($config, 'timeout', 6),
            self::positiveInt($config, 'connect_timeout', 3),
        );
        $providerConfig = new RayganSmsProviderConfig(
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
            (string) ($config['url'] ?? 'http://smspanel.trez.ir/SendMessageWithCode.ashx'),
            isset($config['sender_number']) ? (string) $config['sender_number'] : null,
        );

        $manager->registerProvider(
            self::RAYGAN_SMS,
            new RayganSmsProvider($httpClient, $providerConfig),
            true,
        );

        return self::$instance = $manager;
    }

    /** Returns the initialized process-local manager. */
    public static function instance(): self
    {
        return self::$instance ?? throw new LogicException('Call SmsManager::initialize() first.');
    }

    /** Registers a provider and optionally makes it the default. */
    public function registerProvider(
        string $name,
        SmsProviderInterface $provider,
        bool $default = false,
    ): self {
        $name = $this->normalizeProviderName($name);
        $this->providers[$name] = $provider;

        if ($default || !isset($this->defaultProviderName)) {
            $this->defaultProviderName = $name;
        }

        return $this;
    }

    /** Selects a registered provider as the default. */
    public function useProvider(string $name): self
    {
        $name = $this->normalizeProviderName($name);
        $this->provider($name);
        $this->defaultProviderName = $name;

        return $this;
    }

    /** Sends a message through the requested or default provider. */
    public function send(string $to, string $message, ?string $provider = null): bool
    {
        $providerName = $provider === null
            ? $this->defaultProviderName
            : $this->normalizeProviderName($provider);

        try {
            $sent = $this->provider($providerName)->send($to, $message);
            $this->safeLog(
                $sent ? 'info' : 'warning',
                $sent ? 'SMS request accepted.' : 'SMS request rejected.',
                ['provider' => $providerName, 'to_masked' => self::mask($to)],
            );

            return $sent;
        } catch (Throwable $exception) {
            $this->safeLog('error', 'SMS request failed.', [
                'provider' => $providerName,
                'to_masked' => self::mask($to),
                'exception' => $exception::class,
            ]);
            throw $exception;
        }
    }

    /** The currently selected provider name. */
    private string $defaultProviderName;

    /** Returns a registered provider by name. */
    private function provider(string $name): SmsProviderInterface
    {
        return $this->providers[$name]
            ?? throw new InvalidArgumentException(sprintf('SMS provider "%s" is not registered.', $name));
    }

    /** Normalizes and validates a provider name. */
    private function normalizeProviderName(string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            throw new InvalidArgumentException('The provider name cannot be empty.');
        }

        return $name;
    }

    /**
     * Writes a log without changing the SMS result when logging fails.
     *
     * @param array<string, mixed> $context
     */
    private function safeLog(string $level, string $message, array $context): void
    {
        try {
            $this->logger->log($level, $message, $context);
        } catch (Throwable) {
            // Logging is intentionally best-effort.
        }
    }

    /** Masks all but the final four characters of a recipient. */
    private static function mask(string $phone): string
    {
        return strlen($phone) > 4
            ? str_repeat('*', strlen($phone) - 4) . substr($phone, -4)
            : '****';
    }

    /**
     * Reads a positive integer configuration value.
     *
     * @param array<string, mixed> $config
     */
    private static function positiveInt(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;
        if (!is_int($value) || $value < 1) {
            throw new InvalidArgumentException(sprintf('Configuration "%s" must be a positive integer.', $key));
        }

        return $value;
    }
}
