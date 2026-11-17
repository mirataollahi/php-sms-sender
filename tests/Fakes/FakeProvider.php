<?php

declare(strict_types=1);

namespace PortableSms\Tests\Fakes;

use PortableSms\Contracts\SmsProviderInterface;

/** Records messages accepted by a fake provider. */
final class FakeProvider implements SmsProviderInterface
{
    /** @var list<array{to: string, message: string}> Recorded messages. */
    public array $messages = [];

    /** Records a message and returns the configured result. */
    public function __construct(private readonly bool $result = true)
    {
    }

    /** Records a message send request. */
    public function send(string $to, string $message): bool
    {
        $this->messages[] = ['to' => $to, 'message' => $message];

        return $this->result;
    }
}
