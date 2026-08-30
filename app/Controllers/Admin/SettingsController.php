<?php

declare(strict_types=1);

namespace SCTech\Controllers\Admin;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\View;
use SCTech\Repositories\OptimisticLockException;
use SCTech\Repositories\SettingsRepository;
use SCTech\Services\AdminAuthService;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\SettingsService;
use SCTech\Services\SubmissionHasher;
use SCTech\Validation\ValidationResult;

final class SettingsController
{
    public function __construct(
        private readonly View $view,
        private readonly AdminAuthService $auth,
        private readonly SettingsRepository $settings,
        private readonly SettingsService $service,
        private readonly CsrfTokenManager $csrf,
        private readonly SubmissionHasher $hasher,
    ) {
    }

    public function index(Request $request): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }

        return $this->render(ValidationResult::valid());
    }

    public function save(Request $request): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }
        $token = $request->input('_csrf', $request->input('_token', ''));
        if (!is_string($token) || !$this->csrf->validate('admin.settings', $token)) {
            return $this->render(ValidationResult::invalid(['_form' => ['Votre session a expiré.']]), 419);
        }
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/admin/login');
        }
        try {
            $validation = $this->service->save(
                $this->scalar($request->input('locale')) ?: 'fr',
                $this->scalar($request->input('content_key')),
                $this->scalar($request->input('value_json')),
                $this->scalar($request->input('status')),
                in_array($request->input('is_sensitive'), ['1', 1, true, 'on'], true),
                ($version = (int) $request->input('version', 0)) > 0 ? $version : null,
                $user['id'],
                (string) $request->attribute('request_id', ''),
                $this->hasher->ip($this->clientIp($request)),
            );
        } catch (OptimisticLockException $exception) {
            return $this->render(ValidationResult::invalid(['_form' => [$exception->getMessage()]]), 409);
        }
        if (!$validation->isValid()) {
            return $this->render($validation, 422);
        }

        return Response::redirect('/admin/parametres?enregistre=1', 303);
    }

    private function render(ValidationResult $validation, int $status = 200): Response
    {
        return $this->view->response('admin/settings/index', [
            'title' => 'Paramètres — Administration SCTECH',
            'settings' => $this->settings->allForAdmin(),
            'errors' => $validation->errors(),
            'csrf' => $this->csrf->token('admin.settings'),
            'user' => $this->auth->user(),
            'logoutCsrf' => $this->csrf->token('admin.logout'),
        ], 'admin/layout', $status, ['X-Robots-Tag' => 'noindex, nofollow']);
    }

    private function guard(): ?Response
    {
        if ($this->auth->user() === null) {
            return Response::redirect('/admin/login');
        }

        return $this->auth->can('settings.edit') ? null : Response::text('Accès refusé.', 403);
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function clientIp(Request $request): string
    {
        $ip = $request->attribute('client_ip');

        return is_string($ip) ? $ip : $request->clientIp();
    }
}
