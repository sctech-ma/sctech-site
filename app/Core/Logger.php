<?php

declare(strict_types=1);

namespace SCTech\Core;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Stringable;
use Throwable;

final class Logger
{
    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600,
    ];

    /** @var array<string, mixed> */
    private array $baseContext;

    /** @param array<string, mixed> $baseContext */
    public function __construct(
        private readonly ?string $file,
        private readonly string $minimumLevel = 'info',
        array $baseContext = []
    ) {
        if (!isset(self::LEVELS[$minimumLevel])) {
            throw new \InvalidArgumentException(sprintf('Unknown log level "%s".', $minimumLevel));
        }
        $this->baseContext = $baseContext;
    }

    /** @param array<string, mixed> $context */
    public function withContext(array $context): self
    {
        return new self($this->file, $this->minimumLevel, [...$this->baseContext, ...$context]);
    }

    /** @param array<string, mixed> $context */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function log(string $level, string|Stringable $message, array $context = []): void
    {
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException(sprintf('Unknown log level "%s".', $level));
        }

        if (self::LEVELS[$level] < self::LEVELS[$this->minimumLevel]) {
            return;
        }

        $context = [...$this->baseContext, ...$context];
        $record = [
            'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'),
            'level' => $level,
            'message' => $this->sanitizeText($this->interpolate((string) $message, $context)),
            'context' => $this->redact($context),
        ];
        $json = json_encode(
            $record,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if (!is_string($json)) {
            $json = '{"level":"error","message":"Unable to encode log record"}';
        }

        if ($this->file === null || $this->file === '') {
            error_log($json);
            return;
        }

        $directory = dirname($this->file);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create log directory "%s".', $directory));
        }

        if (file_put_contents($this->file, $json . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write log file "%s".', $this->file));
        }
    }

    /** @param array<string, mixed> $context */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value instanceof Stringable || $value === null) {
                $replace['{' . $key . '}'] = $this->isSensitiveKey((string) $key)
                    ? '[redacted]'
                    : (string) ($value ?? '');
            }
        }

        return strtr($message, $replace);
    }

    private function redact(mixed $value, ?string $key = null, int $depth = 0): mixed
    {
        if ($depth > 8) {
            return '[depth-limit]';
        }

        if ($key !== null && $this->isSensitiveKey($key)) {
            return '[redacted]';
        }

        if ($value instanceof Throwable) {
            return [
                'type' => $value::class,
                'message' => $this->sanitizeText($value->getMessage()),
                'code' => $value->getCode(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $childKey => $child) {
                $clean[$childKey] = $this->redact($child, (string) $childKey, $depth + 1);
            }

            return $clean;
        }

        if (is_object($value)) {
            return '[object:' . $value::class . ']';
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match(
            '/password|passwd|secret|token|authorization|cookie|session|csrf|api[_-]?key|private[_-]?key/i',
            $key
        ) === 1;
    }

    private function sanitizeText(string $value): string
    {
        $value = preg_replace(
            '/\b(password|passwd|secret|token|api[_-]?key)\s*[=:]\s*[^\s,;]+/i',
            '$1=[redacted]',
            $value
        ) ?? $value;

        return preg_replace('~(https?://)[^/@\s:]+:[^/@\s]+@~i', '$1[redacted]@', $value) ?? $value;
    }
}
