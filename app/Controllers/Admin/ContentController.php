<?php

declare(strict_types=1);

namespace SCTech\Controllers\Admin;

use JsonException;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\View;
use SCTech\DTO\ContentInput;
use SCTech\Repositories\ContentRepository;
use SCTech\Repositories\OptimisticLockException;
use SCTech\Services\AdminAuthService;
use SCTech\Services\ContentService;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\SubmissionHasher;
use SCTech\Validation\ValidationResult;

final class ContentController
{
    private const TYPES = ['pages', 'expertises', 'secteurs', 'realisations', 'articles'];

    public function __construct(
        private readonly View $view,
        private readonly AdminAuthService $auth,
        private readonly ContentRepository $content,
        private readonly ContentService $service,
        private readonly CsrfTokenManager $csrf,
        private readonly SubmissionHasher $hasher,
    ) {
    }

    public function index(Request $request): Response
    {
        $guard = $this->guard('content.view');
        if ($guard instanceof Response) {
            return $guard;
        }
        $type = $this->typeFromRequest($request);
        if ($type === null) {
            return Response::text('Ressource introuvable.', 404);
        }
        $status = $this->scalar($request->query('statut'));
        $page = max(1, (int) $request->query('page', 1));
        $records = $this->content->paginate($type, 'fr', $status, $page);

        return $this->adminView('admin/content/index', [
            'title' => $this->label($type) . ' — Administration SCTECH',
            'type' => $type,
            'typeLabel' => $this->label($type),
            'records' => $records,
            'statusFilter' => $status,
            'page' => $page,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->edit($request, '');
    }

    public function edit(Request $request, string $id = ''): Response
    {
        $guard = $this->guard('content.edit');
        if ($guard instanceof Response) {
            return $guard;
        }
        $type = $this->typeFromRequest($request);
        if ($type === null) {
            return Response::text('Ressource introuvable.', 404);
        }
        $record = $id !== '' ? $this->content->find($type, (int) $id) : null;
        if ($id !== '' && $record === null) {
            return Response::text('Contenu introuvable.', 404);
        }
        $action = $this->csrfAction($type, $record !== null ? (int) $record['id'] : null);

        return $this->renderForm($type, $record, ValidationResult::valid(), $this->csrf->token($action));
    }

    public function save(Request $request, string $id = ''): Response
    {
        $guard = $this->guard('content.edit');
        if ($guard instanceof Response) {
            return $guard;
        }
        $type = $this->typeFromRequest($request);
        if ($type === null) {
            return Response::text('Ressource introuvable.', 404);
        }
        $recordId = $id !== '' ? (int) $id : null;
        $inputData = $request->all();
        $inputData['id'] = $recordId;
        $input = ContentInput::fromArray($inputData);
        $action = $this->csrfAction($type, $recordId);
        $token = $request->input('_csrf', $request->input('_token', ''));
        if (!is_string($token) || !$this->csrf->validate($action, $token)) {
            return $this->renderForm(
                $type,
                $this->formRecord($input),
                ValidationResult::invalid(['_form' => ['Votre session a expiré. Rechargez la page puis réessayez.']]),
                $this->csrf->issue($action),
                419,
            );
        }
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/admin/login');
        }
        try {
            $result = $this->service->save(
                $type,
                $input,
                $user['id'],
                $user['role'],
                (string) $request->attribute('request_id', ''),
                $this->hasher->ip($this->clientIp($request)),
            );
        } catch (OptimisticLockException $exception) {
            return $this->renderForm(
                $type,
                $this->formRecord($input),
                ValidationResult::invalid(['_form' => [$exception->getMessage()]]),
                $this->csrf->issue($action),
                409,
            );
        }
        if (!$result->saved) {
            return $this->renderForm($type, $this->formRecord($input), $result->validation, $this->csrf->issue($action), 422);
        }

        return Response::redirect('/admin/' . $type . '/' . $result->id . '/modifier?enregistre=1', 303);
    }

    public function preview(Request $request, string $id): Response
    {
        $guard = $this->guard('preview');
        if ($guard instanceof Response) {
            return $guard;
        }
        $segments = explode('/', trim($request->path(), '/'));
        $type = (string) ($segments[2] ?? '');
        if (!in_array($type, self::TYPES, true)) {
            return Response::text('Ressource introuvable.', 404);
        }
        $record = $this->content->find($type, (int) $id);
        if ($record === null) {
            return Response::text('Contenu introuvable.', 404);
        }
        try {
            $document = json_decode((string) $record['blocks_json'], true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $document = ['version' => 1, 'blocks' => []];
        }

        return $this->view->response('admin/content/preview', [
            'title' => 'Prévisualisation — ' . (string) $record['title'],
            'record' => $record,
            'blocks' => is_array($document['blocks'] ?? null) ? $document['blocks'] : [],
            'user' => $this->auth->user(),
            'logoutCsrf' => $this->csrf->token('admin.logout'),
        ], 'admin/layout', 200, ['X-Robots-Tag' => 'noindex, nofollow']);
    }

    /** @param array<string, mixed>|null $record */
    private function renderForm(string $type, ?array $record, ValidationResult $validation, string $csrf, int $status = 200): Response
    {
        return $this->adminView('admin/content/edit', [
            'title' => ($record === null ? 'Nouveau contenu' : 'Modifier le contenu') . ' — SCTECH',
            'type' => $type,
            'typeLabel' => $this->label($type),
            'record' => $record ?? [],
            'errors' => $validation->errors(),
            'csrf' => $csrf,
        ], $status);
    }

    /** @param array<string, mixed> $data */
    private function adminView(string $template, array $data, int $status = 200): Response
    {
        return $this->view->response($template, $data + [
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

    private function typeFromRequest(Request $request): ?string
    {
        $segments = explode('/', trim($request->path(), '/'));
        $type = $segments[1] ?? '';

        return in_array($type, self::TYPES, true) ? $type : null;
    }

    private function csrfAction(string $type, ?int $id): string
    {
        return 'admin.content.' . $type . '.' . ($id ?? 'new');
    }

    /** @return array<string, mixed> */
    private function formRecord(ContentInput $input): array
    {
        return [
            'id' => $input->id,
            'locale' => $input->locale,
            'content_key' => $input->contentKey,
            'slug' => $input->slug,
            'title' => $input->title,
            'eyebrow' => $input->eyebrow,
            'summary' => $input->summary,
            'problem_text' => $input->problemText,
            'positioning_text' => $input->positioningText,
            'reading_minutes' => $input->readingMinutes,
            'category_key' => $input->categoryKey,
            'blocks_json' => $input->blocksJson,
            'status' => $input->status,
            'seo_title' => $input->seoTitle,
            'seo_description' => $input->seoDescription,
            'sort_order' => $input->sortOrder,
            'version' => $input->expectedVersion,
            'verification_notes' => $input->verificationNotes,
            'verify_for_publication' => $input->verifyForPublication,
        ];
    }

    private function label(string $type): string
    {
        return match ($type) {
            'pages' => 'Pages',
            'expertises' => 'Solutions',
            'secteurs' => 'Segments cibles',
            'realisations' => 'Réalisations',
            'articles' => 'Articles',
            default => 'Contenus',
        };
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
