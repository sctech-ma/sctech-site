<?php

declare(strict_types=1);

namespace SCTech\Middleware;

use SCTech\Core\Logger;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Helpers\Escaper;
use Throwable;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Logger $logger,
        private readonly bool $debug = false
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try {
            return $next->handle($request);
        } catch (Throwable $exception) {
            $requestId = (string) $request->attribute('request_id', 'unavailable');
            try {
                $this->logger->error('Unhandled request exception.', [
                    'request_id' => $requestId,
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'exception' => $exception,
                ]);
            } catch (Throwable) {
                error_log('Unable to write structured exception log. Request ID: ' . $requestId);
            }

            if ($request->expectsJson()) {
                $payload = ['error' => 'Une erreur interne est survenue.', 'request_id' => $requestId];
                if ($this->debug) {
                    $payload['debug'] = $exception->getMessage();
                }

                return Response::json($payload, 500)->withHeader('Cache-Control', 'no-store');
            }

            $debug = $this->debug
                ? '<pre>' . Escaper::html($exception::class . ': ' . $exception->getMessage()) . '</pre>'
                : '';

            return Response::html(
                '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Erreur interne</title>'
                . '<main><h1>Un incident est survenu</h1><p>Veuillez réessayer. Référence : <code>'
                . Escaper::html($requestId) . '</code></p>' . $debug . '</main>',
                500,
                ['Cache-Control' => 'no-store']
            );
        }
    }
}
