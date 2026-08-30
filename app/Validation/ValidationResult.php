<?php

declare(strict_types=1);

namespace SCTech\Validation;

final readonly class ValidationResult
{
    /** @param array<string, list<string>> $errors */
    private function __construct(private array $errors)
    {
    }

    public static function valid(): self
    {
        return new self([]);
    }

    /** @param array<string, list<string>> $errors */
    public static function invalid(array $errors): self
    {
        return new self($errors);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function merge(self $other): self
    {
        $merged = $this->errors;
        foreach ($other->errors as $field => $messages) {
            $merged[$field] = array_values(array_unique(array_merge($merged[$field] ?? [], $messages)));
        }

        return new self($merged);
    }
}
