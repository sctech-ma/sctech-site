<?php

declare(strict_types=1);

namespace SCTech\Controllers\PublicSite;

use SCTech\Core\Config;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Session;
use SCTech\DTO\ContactSubmission;
use SCTech\DTO\QuoteSubmission;
use SCTech\DTO\SubmissionContext;
use SCTech\Services\ContactWorkflow;
use SCTech\Services\QuoteWorkflow;
use SCTech\Services\WorkflowResult;

final class FormController
{
    public function __construct(
        private readonly ContactWorkflow $contacts,
        private readonly QuoteWorkflow $quotes,
        private readonly Session $session,
        private readonly Config $config,
    ) {
    }

    public function contact(Request $request): Response
    {
        if ($this->tooLarge($request)) {
            return $this->payloadTooLarge();
        }
        $this->session->start();
        $submission = ContactSubmission::fromArray($request->all());
        $result = $this->contacts->submit($submission, $this->context($request));

        return $this->finish($result, '/contact', $submission->safeFormValues(), $submission->idempotencyKey);
    }

    public function quote(Request $request): Response
    {
        if ($this->tooLarge($request)) {
            return $this->payloadTooLarge();
        }
        $this->session->start();
        $submission = QuoteSubmission::fromArray($request->all());
        $result = $this->quotes->submit($submission, $this->context($request));

        return $this->finish(
            $result,
            '/demander-un-projet',
            $submission->safeFormValues(),
            $submission->idempotencyKey
        );
    }

    public function legacy(Request $request): Response
    {
        if ($request->isMethod('GET', 'HEAD')) {
            return Response::redirect('/contact', 301);
        }

        return Response::html(
            '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
            . '<title>Formulaire remplacé — SCTECH</title><main><h1>Ce formulaire a été remplacé.</h1>'
            . '<p>Pour protéger votre demande, utilisez le nouveau formulaire sécurisé.</p>'
            . '<p><a href="/contact">Ouvrir le formulaire de contact</a></p></main>',
            410,
            ['Cache-Control' => 'no-store']
        );
    }

    /** @param array<string, scalar|list<string>> $old */
    private function finish(WorkflowResult $result, string $redirect, array $old, string $idempotencyKey): Response
    {
        if ($result->successful) {
            $this->session->flash('flash', [
                'success' => 'Votre demande a bien été enregistrée et transmise. Nous vous répondrons via les coordonnées indiquées.',
            ]);

            return Response::redirect($redirect . '?statut=recu', 303, ['Cache-Control' => 'no-store']);
        }

        $this->session->flash('form.errors', $result->validation->errors());
        $this->session->flash('form.old', $old);
        if ($result->notificationPending && $idempotencyKey !== '') {
            $this->session->flash('form.idempotency', $idempotencyKey);
        }
        if ($result->retryAfter > 0) {
            return Response::html(
                '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
                . '<title>Trop de demandes — SCTECH</title><main><h1>Un peu de patience.</h1>'
                . '<p>Trop de demandes ont été reçues. Réessayez après le délai indiqué.</p>'
                . '<p><a href="' . e($redirect) . '">Revenir au formulaire</a></p></main>',
                429,
                ['Retry-After' => (string) $result->retryAfter, 'Cache-Control' => 'no-store']
            );
        }

        return Response::redirect($redirect . '#formulaire', 303, ['Cache-Control' => 'no-store']);
    }

    private function context(Request $request): SubmissionContext
    {
        $trusted = $this->config->get('security.trusted_proxies', []);
        $trusted = is_array($trusted) ? array_values(array_filter($trusted, 'is_string')) : [];

        return new SubmissionContext(
            $request->clientIp($trusted),
            mb_substr((string) $request->header('User-Agent', ''), 0, 500, 'UTF-8'),
            (string) $request->attribute('request_id', ''),
            $this->config->string('security.consent_policy_version', 'draft'),
        );
    }

    private function tooLarge(Request $request): bool
    {
        $length = (int) $request->header('Content-Length', '0');

        return $length > 131_072 || strlen($request->rawBody()) > 131_072;
    }

    private function payloadTooLarge(): Response
    {
        return Response::html(
            '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
            . '<title>Demande trop volumineuse — SCTECH</title><main><h1>Demande trop volumineuse</h1>'
            . '<p>Réduisez la longueur du message puis réessayez.</p></main>',
            413,
            ['Cache-Control' => 'no-store']
        );
    }
}
