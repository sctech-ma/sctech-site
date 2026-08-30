<?php

declare(strict_types=1);

namespace SCTech\Controllers\Admin;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\View;
use SCTech\Repositories\MediaRepository;
use SCTech\Services\AdminAuthService;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\MediaUploadService;
use SCTech\Services\SubmissionHasher;
use SCTech\Validation\ValidationResult;

final class MediaController
{
    public function __construct(
        private readonly View $view,
        private readonly AdminAuthService $auth,
        private readonly MediaRepository $media,
        private readonly MediaUploadService $uploads,
        private readonly CsrfTokenManager $csrf,
        private readonly SubmissionHasher $hasher,
    ) {
    }

    public function index(Request $request): Response
    {
        $guard = $this->guard('media.view');
        if ($guard instanceof Response) {
            return $guard;
        }

        return $this->render(ValidationResult::valid());
    }

    public function upload(Request $request): Response
    {
        $guard = $this->guard('media.upload');
        if ($guard instanceof Response) {
            return $guard;
        }
        $token = $request->input('_csrf', $request->input('_token', ''));
        if (!is_string($token) || !$this->csrf->validate('admin.media.upload', $token)) {
            return $this->render(ValidationResult::invalid(['_form' => ['Votre session a expiré.']]), 419);
        }
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/admin/login');
        }
        $file = $request->file('media');
        $result = $this->uploads->store(
            is_array($file) ? $file : [],
            $this->scalar($request->input('alt_text')),
            'fr',
            $user['id'],
            (string) $request->attribute('request_id', ''),
            $this->hasher->ip($this->clientIp($request)),
        );
        if (!$result->stored) {
            return $this->render($result->validation, 422);
        }

        return Response::redirect('/admin/medias?televerse=1', 303);
    }

    private function render(ValidationResult $validation, int $status = 200): Response
    {
        return $this->view->response('admin/media/index', [
            'title' => 'Médias — Administration SCTECH',
            'media' => $this->media->all(),
            'errors' => $validation->errors(),
            'csrf' => $this->csrf->token('admin.media.upload'),
            'user' => $this->auth->user(),
            'logoutCsrf' => $this->csrf->token('admin.logout'),
        ], 'admin/layout', $status, ['X-Robots-Tag' => 'noindex, nofollow']);
    }

    private function guard(string $capability): ?Response
    {
        if ($this->auth->user() === null) {
            return Response::redirect('/admin/login');
        }

        return $this->auth->can($capability) ? null : Response::text('Accès refusé.', 403);
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
