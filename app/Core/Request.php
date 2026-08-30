<?php

declare(strict_types=1);

namespace SCTech\Core;

use JsonException;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $parsedBody
     * @param array<string, string> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     * @param array<string, string|list<string>> $headers
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $parsedBody = [],
        private readonly array $cookies = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly array $headers = [],
        private readonly array $attributes = [],
        private readonly string $rawBody = ''
    ) {
    }

    /** @param array<string, mixed>|null $server */
    public static function fromGlobals(?array $server = null): self
    {
        $server ??= $_SERVER;
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        $path = str_starts_with($path, '/') ? $path : '/' . $path;
        $headers = self::headersFromServer($server);
        $rawBody = (string) file_get_contents('php://input');
        $body = $_POST;

        $contentType = strtolower((string) ($headers['content-type'] ?? ''));
        if ($rawBody !== '' && str_contains($contentType, 'application/json')) {
            try {
                $decoded = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            } catch (JsonException) {
                // Invalid JSON is left for endpoint validation to reject explicitly.
            }
        }

        return new self(
            $method,
            $uri,
            $path,
            $_GET,
            $body,
            $_COOKIE,
            self::normalizeFiles($_FILES),
            $server,
            $headers,
            [],
            $rawBody
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string|list<string>> $headers
     * @param array<string, mixed> $server
     */
    public static function create(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $headers = [],
        array $server = []
    ): self {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        $normalizedHeaders = [];
        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower((string) $name)] = is_array($value)
                ? array_map('strval', $value)
                : (string) $value;
        }

        return new self(
            strtoupper($method),
            $uri,
            str_starts_with($path, '/') ? $path : '/' . $path,
            $query,
            $body,
            [],
            [],
            $server,
            $normalizedHeaders
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function queryString(): string
    {
        $query = parse_url($this->uri, PHP_URL_QUERY);

        return is_string($query) ? $query : '';
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function body(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->parsedBody : ($this->parsedBody[$key] ?? $default);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_replace($this->query, $this->parsedBody);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function files(): array
    {
        return $this->files;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $value = $this->headers[strtolower($name)] ?? null;
        if ($value === null) {
            return $default;
        }

        return is_array($value) ? implode(', ', $value) : $value;
    }

    /** @return array<string, string|list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        $attributes = $this->attributes;
        $attributes[$key] = $value;

        return new self(
            $this->method,
            $this->uri,
            $this->path,
            $this->query,
            $this->parsedBody,
            $this->cookies,
            $this->files,
            $this->server,
            $this->headers,
            $attributes,
            $this->rawBody
        );
    }

    /** @param array<string, mixed> $attributes */
    public function withAttributes(array $attributes): self
    {
        $request = $this;
        foreach ($attributes as $key => $value) {
            $request = $request->withAttribute((string) $key, $value);
        }

        return $request;
    }

    public function isMethod(string ...$methods): bool
    {
        $methods = array_map('strtoupper', $methods);

        return in_array($this->method, $methods, true);
    }

    public function expectsJson(): bool
    {
        return str_contains(strtolower((string) $this->header('Accept', '')), 'application/json')
            || str_contains(strtolower((string) $this->header('Content-Type', '')), 'application/json')
            || strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /** @param list<string> $trustedProxies */
    public function clientIp(array $trustedProxies = []): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        if (!self::ipIsTrusted($remote, $trustedProxies)) {
            return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '0.0.0.0';
        }

        $forwarded = (string) $this->header('X-Forwarded-For', '');
        $chain = array_reverse(array_map('trim', explode(',', $forwarded)));
        foreach ($chain as $candidate) {
            if (
                filter_var($candidate, FILTER_VALIDATE_IP) !== false
                && !self::ipIsTrusted($candidate, $trustedProxies)
            ) {
                return $candidate;
            }
        }

        return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '0.0.0.0';
    }

    /** @param list<string> $trustedProxies */
    public function isSecure(array $trustedProxies = []): bool
    {
        $https = strtolower((string) ($this->server['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off' && $https !== '0') {
            return true;
        }

        if ((int) ($this->server['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '');
        if (self::ipIsTrusted($remote, $trustedProxies)) {
            $forwarded = array_map('trim', explode(',', (string) $this->header('X-Forwarded-Proto', '')));
            $proto = strtolower((string) end($forwarded));

            return $proto === 'https';
        }

        return false;
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function headersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[strtolower(str_replace('_', '-', $key))] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $files
     * @return array<string, mixed>
     */
    private static function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $file) {
            if (!is_array($file) || !isset($file['name'])) {
                continue;
            }

            if (!is_array($file['name'])) {
                $normalized[$key] = $file;
                continue;
            }

            $normalized[$key] = [];
            foreach (array_keys($file['name']) as $index) {
                $normalized[$key][$index] = [
                    'name' => $file['name'][$index] ?? '',
                    'full_path' => $file['full_path'][$index] ?? '',
                    'type' => $file['type'][$index] ?? '',
                    'tmp_name' => $file['tmp_name'][$index] ?? '',
                    'error' => $file['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $file['size'][$index] ?? 0,
                ];
            }
        }

        return $normalized;
    }

    /** @param list<string> $trusted */
    private static function ipIsTrusted(string $ip, array $trusted): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach ($trusted as $range) {
            if ($range === $ip) {
                return true;
            }

            if (!str_contains($range, '/')) {
                continue;
            }

            [$subnet, $bits] = explode('/', $range, 2);
            $packedIp = @inet_pton($ip);
            $packedSubnet = @inet_pton($subnet);
            if ($packedIp === false || $packedSubnet === false || strlen($packedIp) !== strlen($packedSubnet)) {
                continue;
            }

            $bits = (int) $bits;
            $maxBits = strlen($packedIp) * 8;
            if ($bits < 0 || $bits > $maxBits) {
                continue;
            }

            $bytes = intdiv($bits, 8);
            $remainder = $bits % 8;
            if (substr($packedIp, 0, $bytes) !== substr($packedSubnet, 0, $bytes)) {
                continue;
            }

            if ($remainder === 0) {
                return true;
            }

            $mask = (0xFF << (8 - $remainder)) & 0xFF;
            if ((ord($packedIp[$bytes]) & $mask) === (ord($packedSubnet[$bytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }
}
