<?php

declare(strict_types=1);

namespace SCTech\Services;

use SCTech\DTO\ContactSubmission;
use SCTech\DTO\PersistedLead;
use SCTech\DTO\QuoteSubmission;
use SCTech\Support\ProjectInquiryCatalog;

final class LeadNotificationService
{
    public function __construct(private readonly MailTransport $transport)
    {
    }

    public function contact(ContactSubmission $submission, PersistedLead $lead): MailDelivery
    {
        $subject = sprintf('[SCTECH] Nouveau message — %s', $submission->subject);
        $fields = [
            'Référence' => $lead->publicId,
            'Nom' => $submission->fullName,
            'E-mail' => $submission->email,
            'Organisation' => $submission->organisation,
            'Téléphone' => $submission->phone,
            'Sujet' => $submission->subject,
            'Message' => $submission->message,
        ];

        return $this->transport->send($this->message($subject, $fields, $submission->email, $submission->fullName));
    }

    public function quote(QuoteSubmission $submission, PersistedLead $lead): MailDelivery
    {
        $subject = sprintf(
            '[SCTECH] Nouveau projet financier — %s',
            ProjectInquiryCatalog::label(ProjectInquiryCatalog::solutionDomains(), $submission->serviceKey),
        );
        $domains = array_map(
            static fn (string $key): string => ProjectInquiryCatalog::label(
                ProjectInquiryCatalog::solutionDomains(),
                $key,
            ),
            $submission->servicesResearched,
        );
        $fields = [
            'Référence' => $lead->publicId,
            'Nom' => $submission->fullName,
            'E-mail' => $submission->email,
            'Organisation' => $submission->organisation,
            'Téléphone' => $submission->phone,
            'Type de projet' => ProjectInquiryCatalog::label(
                ProjectInquiryCatalog::projectTypes(),
                $submission->projectType,
            ),
            'Domaines concernés' => implode(', ', $domains),
            'Contexte' => $submission->projectContext,
            'Objectif' => $submission->projectObjective,
            'Exigences de conformité' => $submission->complianceRequirements,
            'Systèmes à intégrer' => $submission->integrationRequirements,
            'Enveloppe' => ProjectInquiryCatalog::label(
                ProjectInquiryCatalog::budgets(),
                $submission->budgetRange,
            ),
            'Horizon' => ProjectInquiryCatalog::label(ProjectInquiryCatalog::timelines(), $submission->timeline),
            'Contact préféré' => ProjectInquiryCatalog::label(
                ProjectInquiryCatalog::contactMethods(),
                $submission->preferredContactMethod,
            ),
            'Message complémentaire' => $submission->projectSummary,
        ];

        return $this->transport->send($this->message($subject, $fields, $submission->email, $submission->fullName));
    }

    /** @param array<string, string> $fields */
    private function message(string $subject, array $fields, string $replyTo, string $replyToName): MailMessage
    {
        $html = '<h1>' . self::escape($subject) . '</h1><dl>';
        $text = $subject . "\n\n";
        foreach ($fields as $label => $value) {
            if ($value === '') {
                continue;
            }
            $html .= '<dt><strong>' . self::escape($label) . '</strong></dt><dd>'
                . nl2br(self::escape($value), false) . '</dd>';
            $text .= $label . ': ' . $value . "\n";
        }
        $html .= '</dl>';

        return new MailMessage($subject, $html, $text, $replyTo, $replyToName);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
