<?php

declare(strict_types=1);

namespace SCTech\Controllers\Admin;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\View;
use SCTech\DTO\AdminLogin;
use SCTech\Services\AdminAuthService;
use SCTech\Services\CsrfTokenManager;
use SCTech\Validation\AdminLoginValidator;
use SCTech\Validation\ValidationResult;

final class AuthController
{
    public function __construct(
        private readonly View $view,
        private readonly AdminAuthService $auth,
        private readonly AdminLoginValidator $validator,
        private readonly CsrfTokenManager $csrf,
    ) {
    }

    public function loginForm(Request $request): Response
    {
        if ($this->auth->user() !== null) {
            return Response::redirect('/admin');
        }

        return $this->renderLogin(ValidationResult::valid(), '', $this->csrf->token('admin.login'));
    }

    public function login(Request $request): Response
    {
        $credentials = AdminLogin::fromArray($request->all());
        $validation = $this->validator->validate($credentials);
        if (!$this->csrf->validate('admin.login', $credentials->csrfToken)) {
            $validation = $validation->merge(ValidationResult::invalid([
                '_form' => ['Votre session a expiré. Rechargez la page puis réessayez.'],
            ]));
        }
        if (!$validation->isValid()) {
            return $this->renderLogin($validation, $credentials->email, $this->csrf->issue('admin.login'), 422);
        }

        $result = $this->auth->attempt(
            $credentials,
            $this->clientIp($request),
            (string) $request->attribute('request_id', ''),
        );
        if (!$result->authenticated) {
            $response = $this->renderLogin(
                ValidationResult::invalid(['_form' => ['Identifiants incorrects ou connexion temporairement indisponible.']]),
                $credentials->email,
                $this->csrf->issue('admin.login'),
                $result->rateLimited ? 429 : 422,
            );

            return $result->rateLimited ? $response->withHeader('Retry-After', '900') : $response;
        }

        return Response::redirect('/admin', 303);
    }

    public function logout(Request $request): Response
    {
        $token = $request->input('_csrf', $request->input('_token', ''));
        if (!is_string($token) || !$this->csrf->validate('admin.logout', $token)) {
            return Response::html('<h1>Action refusée</h1><p>Le jeton de sécurité est invalide.</p>', 419);
        }
        $this->auth->logout(
            (string) $request->attribute('request_id', ''),
            $this->clientIp($request),
        );

        return Response::redirect('/admin/login', 303);
    }

    private function renderLogin(ValidationResult $validation, string $email, string $csrf, int $status = 200): Response
    {
        return $this->view->response('admin/login', [
            'title' => 'Connexion — Administration SCTECH',
            'errors' => $validation->errors(),
            'email' => $email,
            'csrf' => $csrf,
            'user' => null,
        ], 'admin/layout', $status, ['X-Robots-Tag' => 'noindex, nofollow']);
    }

    private function clientIp(Request $request): string
    {
        $ip = $request->attribute('client_ip');

        return is_string($ip) ? $ip : $request->clientIp();
    }
}
