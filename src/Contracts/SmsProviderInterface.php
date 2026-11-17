<?php

declare(strict_types=1);

namespace PortableSms\Contracts;

/** Defines an SMS service provider. */
interface SmsProviderInterface
{
    /** Sends a message and reports whether the provider accepted it. */
    public function send(string $to, string $message): bool;
}
