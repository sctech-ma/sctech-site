<?php

declare(strict_types=1);

namespace SCTech\Core;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];
    private bool $ran = false;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     */
    private function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $messages = [],
        private readonly array $attributes = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<mixed>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     */
    public static function make(
        array $data,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): self {
        return new self($data, $rules, $messages, $attributes);
    }

    public function fails(): bool
    {
        $this->run();

        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        $this->run();

        return $this->errors;
    }

    public function first(string $field): ?string
    {
        $this->run();

        return $this->errors[$field][0] ?? null;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        $this->run();

        if ($this->errors !== []) {
            throw new InvalidArgumentException('Validated data is unavailable while validation errors exist.');
        }

        return $this->validated;
    }

    private function run(): void
    {
        if ($this->ran) {
            return;
        }
        $this->ran = true;

        foreach ($this->rules as $field => $declaration) {
            $rules = $this->normalizeRules($declaration);
            $exists = array_key_exists($field, $this->data);
            $value = $exists ? $this->data[$field] : null;
            $names = array_map(fn (mixed $rule): string => $this->ruleName($rule), $rules);

            if (in_array('sometimes', $names, true) && !$exists) {
                continue;
            }

            if (in_array('nullable', $names, true) && ($value === null || $value === '')) {
                $this->validated[$field] = null;
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $parameters, $callable] = $this->parseRule($rule);
                if (in_array($name, ['bail', 'sometimes', 'nullable'], true)) {
                    continue;
                }

                $message = $callable instanceof Closure
                    ? $this->applyCallable($callable, $field, $value)
                    : $this->applyRule($name, $field, $value, $exists, $parameters);

                if ($message !== null) {
                    $this->addError($field, $name, $message, $parameters);
                    if (in_array('bail', $names, true)) {
                        break;
                    }
                }
            }

            if (!isset($this->errors[$field]) && $exists) {
                $this->validated[$field] = $value;
            }
        }
    }

    /**
     * @param string|list<mixed> $rules
     * @return list<mixed>
     */
    private function normalizeRules(string|array $rules): array
    {
        if (is_string($rules)) {
            return $rules === '' ? [] : explode('|', $rules);
        }

        return $rules;
    }

    private function ruleName(mixed $rule): string
    {
        if (is_callable($rule)) {
            return 'custom';
        }
        if (is_array($rule)) {
            return strtolower((string) ($rule[0] ?? ''));
        }

        return strtolower(explode(':', (string) $rule, 2)[0]);
    }

    /** @return array{0: string, 1: list<mixed>, 2: ?Closure} */
    private function parseRule(mixed $rule): array
    {
        if (is_callable($rule)) {
            return ['custom', [], Closure::fromCallable($rule)];
        }

        if (is_array($rule)) {
            $values = array_values($rule);
            $name = strtolower((string) array_shift($values));

            return [$name, $values, null];
        }

        [$name, $parameterString] = array_pad(explode(':', (string) $rule, 2), 2, '');
        $parameters = $parameterString === '' ? [] : str_getcsv($parameterString);

        return [strtolower($name), $parameters, null];
    }

    /** @param list<mixed> $parameters */
    private function applyRule(
        string $rule,
        string $field,
        mixed $value,
        bool $exists,
        array $parameters
    ): ?string {
        if (!$exists && !in_array($rule, ['required', 'present', 'required_if'], true)) {
            return null;
        }

        return match ($rule) {
            'required' => $exists && !$this->isEmpty($value) ? null : 'Le champ :attribute est requis.',
            'present' => $exists ? null : 'Le champ :attribute doit être présent.',
            'required_if' => $this->requiredIf($value, $parameters),
            'string' => is_string($value) && preg_match('//u', $value) === 1
                ? null : 'Le champ :attribute doit être un texte valide.',
            'email' => is_string($value)
                && strlen($value) <= 254
                && filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? null : 'Le champ :attribute doit être une adresse e-mail valide.',
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? null : 'Le champ :attribute doit être un nombre entier.',
            'numeric' => is_numeric($value) ? null : 'Le champ :attribute doit être un nombre.',
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true)
                ? null : 'Le champ :attribute doit être vrai ou faux.',
            'array' => is_array($value) ? null : 'Le champ :attribute doit être une liste.',
            'min' => $this->size($value) >= $this->numericParameter($parameters, 0, $rule)
                ? null : 'Le champ :attribute doit contenir au moins :min caractères.',
            'max' => $this->size($value) <= $this->numericParameter($parameters, 0, $rule)
                ? null : 'Le champ :attribute ne peut pas dépasser :max caractères.',
            'between' => $this->between($value, $parameters),
            'in' => in_array((string) $value, array_map('strval', $parameters), true)
                ? null : 'La valeur choisie pour :attribute n’est pas autorisée.',
            'not_in' => !in_array((string) $value, array_map('strval', $parameters), true)
                ? null : 'La valeur choisie pour :attribute n’est pas autorisée.',
            'accepted' => in_array($value, ['yes', 'on', '1', 1, true, 'oui'], true)
                ? null : 'Le champ :attribute doit être accepté.',
            'same' => $value === ($this->data[(string) ($parameters[0] ?? '')] ?? null)
                ? null : 'Le champ :attribute doit correspondre à :other.',
            'confirmed' => $value === ($this->data[$field . '_confirmation'] ?? null)
                ? null : 'La confirmation de :attribute ne correspond pas.',
            'regex' => $this->matchesRegex($value, $parameters),
            'url' => $this->validUrl($value),
            'date' => $this->validDate($value),
            'slug' => is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) === 1
                ? null : 'Le champ :attribute doit être un identifiant URL valide.',
            default => throw new InvalidArgumentException(sprintf('Unknown validation rule "%s".', $rule)),
        };
    }

    /** @param list<mixed> $parameters */
    private function requiredIf(mixed $value, array $parameters): ?string
    {
        $other = (string) ($parameters[0] ?? '');
        $expected = array_map('strval', array_slice($parameters, 1));
        $actual = $this->data[$other] ?? null;

        return in_array((string) $actual, $expected, true) && $this->isEmpty($value)
            ? 'Le champ :attribute est requis.'
            : null;
    }

    /** @param list<mixed> $parameters */
    private function between(mixed $value, array $parameters): ?string
    {
        $minimum = $this->numericParameter($parameters, 0, 'between');
        $maximum = $this->numericParameter($parameters, 1, 'between');
        $size = $this->size($value);

        return $size >= $minimum && $size <= $maximum
            ? null
            : 'Le champ :attribute doit contenir entre :min et :max caractères.';
    }

    /** @param list<mixed> $parameters */
    private function matchesRegex(mixed $value, array $parameters): ?string
    {
        if (!is_string($value) || !isset($parameters[0]) || @preg_match((string) $parameters[0], '') === false) {
            return 'Le format du champ :attribute est invalide.';
        }

        return preg_match((string) $parameters[0], $value) === 1
            ? null
            : 'Le format du champ :attribute est invalide.';
    }

    private function validUrl(mixed $value): ?string
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return 'Le champ :attribute doit être une URL valide.';
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true)
            ? null
            : 'Le champ :attribute doit être une URL HTTP ou HTTPS.';
    }

    private function validDate(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return 'Le champ :attribute doit être une date valide.';
        }

        try {
            new DateTimeImmutable($value);

            return null;
        } catch (\Throwable) {
            return 'Le champ :attribute doit être une date valide.';
        }
    }

    private function applyCallable(Closure $rule, string $field, mixed $value): ?string
    {
        $result = $rule($value, $this->data, $field);
        if ($result === null || $result === true) {
            return null;
        }

        return is_string($result) ? $result : 'Le champ :attribute est invalide.';
    }

    /** @param list<mixed> $parameters */
    private function addError(string $field, string $rule, string $fallback, array $parameters): void
    {
        $message = $this->messages[$field . '.' . $rule]
            ?? $this->messages[$rule]
            ?? $fallback;
        $label = $this->attributes[$field] ?? str_replace('_', ' ', $field);
        $replacements = [
            ':attribute' => $label,
            ':min' => (string) ($parameters[0] ?? ''),
            ':max' => (string) ($parameters[1] ?? $parameters[0] ?? ''),
            ':other' => $this->attributes[(string) ($parameters[0] ?? '')] ?? (string) ($parameters[0] ?? ''),
        ];

        $this->errors[$field][] = strtr($message, $replacements);
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    private function size(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_array($value)) {
            return count($value);
        }

        if (is_string($value)) {
            return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        }

        return 0;
    }

    /** @param list<mixed> $parameters */
    private function numericParameter(array $parameters, int $index, string $rule): float
    {
        if (!isset($parameters[$index]) || !is_numeric($parameters[$index])) {
            throw new InvalidArgumentException(sprintf('Validation rule "%s" requires a numeric parameter.', $rule));
        }

        return (float) $parameters[$index];
    }
}
