<?php

declare(strict_types=1);

namespace SCTech\Controllers\Admin;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\View;
use SCTech\Repositories\ContentRepository;
use SCTech\Services\AdminAuthService;
use SCTech\Services\CsrfTokenManager;

final class DashboardController
{
    public function __construct(
        private readonly View $view,
        private readonly AdminAuthService $auth,
        private readonly ContentRepository $content,
        private readonly CsrfTokenManager $csrf,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/admin/login');
        }
        $counts = [];
        foreach (['pages', 'expertises', 'secteurs', 'realisations', 'articles'] as $type) {
            $counts[$type] = $this->content->countPublished($type);
        }

        return $this->view->response('admin/dashboard', [
            'title' => 'Tableau de bord — SCTECH',
            'user' => $user,
            'counts' => $counts,
            'logoutCsrf' => $this->csrf->token('admin.logout'),
        ], 'admin/layout', 200, ['X-Robots-Tag' => 'noindex, nofollow']);
    }
}
