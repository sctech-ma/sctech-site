<?php

declare(strict_types=1);

namespace SCTech\Controllers\Admin;

use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\View;
use SCTech\Repositories\AdminAuditRepository;
use SCTech\Repositories\ContactMessageRepository;
use SCTech\Repositories\QuoteRequestRepository;
use SCTech\Services\AdminAuthService;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\SubmissionHasher;

final class LeadsController
{
    public function __construct(
        private readonly View $view,
        private readonly AdminAuthService $auth,
        private readonly ContactMessageRepository $contacts,
        private readonly QuoteRequestRepository $quotes,
        private readonly CsrfTokenManager $csrf,
        private readonly AdminAuditRepository $audit,
        private readonly SubmissionHasher $hasher,
    ) {
    }

    public function index(Request $request): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }
        $type = $this->type($request);
        if ($type === null) {
            return Response::text('Ressource introuvable.', 404);
        }
        $page = max(1, (int) $request->query('page', 1));
        $status = $this->scalar($request->query('statut'));
        $records = $type === 'messages'
            ? $this->contacts->paginate($page, 25, $status)
            : $this->quotes->paginate($page, 25, $status);

        return $this->adminView('admin/leads/index', [
            'title' => ($type === 'messages' ? 'Messages' : 'Demandes de projet') . ' — SCTECH',
            'type' => $type,
            'records' => $records,
            'statusFilter' => $status,
            'page' => $page,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }
        $type = $this->type($request);
        $record = $type === 'messages'
            ? $this->contacts->find((int) $id)
            : ($type === 'devis' ? $this->quotes->find((int) $id) : null);
        if ($record === null || $type === null) {
            return Response::text('Demande introuvable.', 404);
        }

        return $this->adminView('admin/leads/show', [
            'title' => 'Demande ' . (string) $record['public_id'] . ' — SCTECH',
            'type' => $type,
            'record' => $record,
            'csrf' => $this->csrf->token('admin.lead.' . $type . '.' . $id),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $guard = $this->guard();
        if ($guard instanceof Response) {
            return $guard;
        }
        $type = $this->type($request);
        if ($type === null) {
            return Response::text('Ressource introuvable.', 404);
        }
        $token = $request->input('_csrf', $request->input('_token', ''));
        if (!is_string($token) || !$this->csrf->validate('admin.lead.' . $type . '.' . $id, $token)) {
            return Response::text('Jeton de sécurité invalide.', 419);
        }
        $status = $this->scalar($request->input('workflow_status'));
        $version = (int) $request->input('version', 0);
        $updated = $type === 'messages'
            ? $this->contacts->updateWorkflowStatus((int) $id, $status, $version)
            : $this->quotes->updateWorkflowStatus((int) $id, $status, $version);
        if (!$updated) {
            return Response::text('Statut invalide ou demande modifiée dans une autre session.', 409);
        }
        $user = $this->auth->user();
        if ($user !== null) {
            $this->audit->record(
                $user['id'],
                'lead.status_updated',
                $type === 'messages' ? 'contact_message' : 'quote_request',
                (int) $id,
                (string) $request->attribute('request_id', ''),
                $this->hasher->ip($this->clientIp($request)),
                ['workflow_status' => $status],
            );
        }

        return Response::redirect('/admin/' . $type . '/' . $id . '?enregistre=1', 303);
    }

    private function guard(): ?Response
    {
        if ($this->auth->user() === null) {
            return Response::redirect('/admin/login');
        }

        return $this->auth->can('leads.view') ? null : Response::text('Accès refusé.', 403);
    }

    /** @param array<string, mixed> $data */
    private function adminView(string $template, array $data): Response
    {
        return $this->view->response($template, $data + [
            'user' => $this->auth->user(),
            'logoutCsrf' => $this->csrf->token('admin.logout'),
        ], 'admin/layout', 200, ['X-Robots-Tag' => 'noindex, nofollow']);
    }

    private function type(Request $request): ?string
    {
        $segments = explode('/', trim($request->path(), '/'));
        $type = $segments[1] ?? '';

        return in_array($type, ['messages', 'devis'], true) ? $type : null;
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
