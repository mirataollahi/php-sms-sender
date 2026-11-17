<?php

declare(strict_types=1);

namespace PortableSms\Providers;

use InvalidArgumentException;

/** Holds common and provider-specific SMS configuration. */
abstract class SmsProviderConfig
{
    /** @var array<string, mixed> Additional provider-specific configuration. */
    private array $extraConfig = [];

    /** Creates the common provider configuration. */
    public function __construct(
        public readonly string $endpoint,
        public readonly string $username = '',
        public readonly string $password = '',
        public readonly ?string $senderNumber = null,
    ) {
        if (filter_var($this->endpoint, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('The provider endpoint must be a valid URL.');
        }
    }

    /** Adds or replaces a provider-specific setting. */
    public function addConfig(string $name, mixed $value): static
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('The configuration name cannot be empty.');
        }

        $this->extraConfig[$name] = $value;

        return $this;
    }

    /** Returns a provider-specific setting or a fallback value. */
    public function getConfig(string $name, mixed $default = null): mixed
    {
        return $this->extraConfig[$name] ?? $default;
    }

    /** Reports whether a provider-specific setting exists. */
    public function hasConfig(string $name): bool
    {
        return array_key_exists($name, $this->extraConfig);
    }

    /** Reads provider-specific settings as properties. */
    public function __get(string $name): mixed
    {
        if (!$this->hasConfig($name)) {
            throw new InvalidArgumentException(sprintf('Unknown provider configuration "%s".', $name));
        }

        return $this->extraConfig[$name];
    }

    /** Supports isset() for provider-specific settings. */
    public function __isset(string $name): bool
    {
        return $this->hasConfig($name);
    }
}
