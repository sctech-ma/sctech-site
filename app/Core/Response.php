<?php

declare(strict_types=1);

namespace SCTech\Core;

use InvalidArgumentException;
use JsonException;
use SCTech\Helpers\Url;

final class Response
{
    /** @var array<string, array{name: string, values: list<string>}> */
    private array $headers = [];

    /** @param array<string, string|list<string>> $headers */
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        array $headers = []
    ) {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('HTTP status must be between 100 and 599.');
        }

        foreach ($headers as $name => $values) {
            $this->headers = self::setHeader($this->headers, $name, $values);
        }
    }

    /** @param array<string, string|list<string>> $headers */
    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8'] + $headers);
    }

    /** @param array<string, string|list<string>> $headers */
    public static function text(string $text, int $status = 200, array $headers = []): self
    {
        return new self($text, $status, ['Content-Type' => 'text/plain; charset=UTF-8'] + $headers);
    }

    /** @param array<string, string|list<string>> $headers
     * @throws JsonException
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $json = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return new self($json, $status, ['Content-Type' => 'application/json; charset=UTF-8'] + $headers);
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @param list<string> $allowedHosts
     */
    public static function redirect(
        string $location,
        int $status = 302,
        array $headers = [],
        array $allowedHosts = []
    ): self {
        if (!in_array($status, [301, 302, 303, 307, 308], true)) {
            throw new InvalidArgumentException('Redirect status must be 301, 302, 303, 307, or 308.');
        }

        if (!Url::isSafeRedirect($location, $allowedHosts)) {
            throw new InvalidArgumentException('Unsafe redirect target rejected.');
        }

        return new self('', $status, ['Location' => $location] + $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            $headers[$header['name']] = $header['values'];
        }

        return $headers;
    }

    /** @return list<string> */
    public function header(string $name): array
    {
        return $this->headers[strtolower($name)]['values'] ?? [];
    }

    public function headerLine(string $name): string
    {
        return implode(', ', $this->header($name));
    }

    public function withStatus(int $status): self
    {
        return new self($this->body, $status, $this->headersForConstructor());
    }

    public function withBody(string $body): self
    {
        return new self($body, $this->status, $this->headersForConstructor());
    }

    /** @param string|list<string> $value */
    public function withHeader(string $name, string|array $value): self
    {
        $headers = $this->headers;
        $headers = self::setHeader($headers, $name, $value);

        return self::fromNormalized($this->body, $this->status, $headers);
    }

    public function withAddedHeader(string $name, string $value): self
    {
        self::assertHeader($name, $value);
        $headers = $this->headers;
        $key = strtolower($name);
        if (!isset($headers[$key])) {
            $headers[$key] = ['name' => $name, 'values' => [$value]];
        } else {
            $headers[$key]['values'][] = $value;
        }

        return self::fromNormalized($this->body, $this->status, $headers);
    }

    public function withoutHeader(string $name): self
    {
        $headers = $this->headers;
        unset($headers[strtolower($name)]);

        return self::fromNormalized($this->body, $this->status, $headers);
    }

    public function forHead(): self
    {
        $response = $this;
        if ($this->headerLine('Content-Length') === '' && $this->body !== '') {
            $response = $response->withHeader('Content-Length', (string) strlen($this->body));
        }

        return $response->withBody('');
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $header) {
                $first = true;
                foreach ($header['values'] as $value) {
                    header($header['name'] . ': ' . $value, $first);
                    $first = false;
                }
            }
        }

        if ($this->status !== 204 && $this->status !== 304) {
            echo $this->body;
        }
    }

    /** @return array<string, string|list<string>> */
    private function headersForConstructor(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            $headers[$header['name']] = $header['values'];
        }

        return $headers;
    }

    /**
     * @param array<string, array{name: string, values: list<string>}> $headers
     * @param string|list<string> $value
     * @return array<string, array{name: string, values: list<string>}>
     */
    private static function setHeader(array $headers, string $name, string|array $value): array
    {
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $item) {
            self::assertHeader($name, $item);
        }

        $headers[strtolower($name)] = ['name' => $name, 'values' => $values];

        return $headers;
    }

    private static function assertHeader(string $name, string $value): void
    {
        if ($name === '' || preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name) !== 1) {
            throw new InvalidArgumentException('Invalid HTTP header name.');
        }

        if (preg_match('/[\r\n]/', $value) === 1) {
            throw new InvalidArgumentException('HTTP header values may not contain newlines.');
        }
    }

    /** @param array<string, array{name: string, values: list<string>}> $headers */
    private static function fromNormalized(string $body, int $status, array $headers): self
    {
        $response = new self($body, $status);
        $response->headers = $headers;

        return $response;
    }
}
