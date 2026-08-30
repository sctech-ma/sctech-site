<?php

declare(strict_types=1);

namespace SCTech\Core;

use RuntimeException;
use Throwable;

final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(private readonly string $basePath)
    {
    }

    public function share(string $key, mixed $value): self
    {
        $this->shared[$key] = $value;

        return $this;
    }

    /** @param array<string, mixed> $values */
    public function shareMany(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->shared[(string) $key] = $value;
        }

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = $this->renderFile($template, [...$this->shared, ...$data]);
        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($layout, [...$this->shared, ...$data, 'content' => $content]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $headers
     */
    public function response(
        string $template,
        array $data = [],
        ?string $layout = null,
        int $status = 200,
        array $headers = []
    ): Response {
        return Response::html($this->render($template, $data, $layout), $status, $headers);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderFile($template, [...$this->shared, ...$data]);
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $template, array $data): string
    {
        $file = $this->resolve($template);
        ob_start();

        try {
            extract($data, EXTR_SKIP);
            require $file;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    private function resolve(string $template): string
    {
        $template = str_replace('\\', '/', trim($template));
        if (
            $template === ''
            || str_starts_with($template, '/')
            || preg_match('~(^|/)\.\.(/|$)~', $template) === 1
            || preg_match('/[\x00-\x1F\x7F]/', $template) === 1
        ) {
            throw new RuntimeException('Invalid view template path.');
        }

        $template = str_ends_with($template, '.php') ? $template : $template . '.php';
        $base = realpath($this->basePath);
        $file = realpath(rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $template);
        if ($base === false || $file === false || !is_file($file)) {
            throw new RuntimeException(sprintf('View template "%s" was not found.', $template));
        }

        $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($file, $prefix)) {
            throw new RuntimeException('View template resolves outside the configured view directory.');
        }

        return $file;
    }
}
