<?php

declare(strict_types=1);

namespace PortableSms\Logger;

use InvalidArgumentException;
use JsonException;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/** Writes structured PSR-3 log records to a local file. */
final class FileLogger extends AbstractLogger
{
    /** Creates a file logger for the supplied path. */
    public function __construct(private readonly string $path)
    {
        if (trim($this->path) === '') {
            throw new InvalidArgumentException('The log path cannot be empty.');
        }
    }

    /**
     * Writes one JSON log record.
     *
     * @param mixed                 $level
     * @param string|Stringable     $message
     * @param array<string, mixed>  $context
     *
     * @throws JsonException
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the SMS log directory.');
        }

        $record = json_encode([
            'time' => gmdate('c'),
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

        if (file_put_contents($this->path, $record . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the SMS log.');
        }
    }
}
