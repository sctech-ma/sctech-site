<?php

declare(strict_types=1);

namespace SCTech\Validation;

use SCTech\DTO\QuoteSubmission;
use SCTech\Support\ProjectInquiryCatalog;

final class QuoteValidator
{
    public function validate(QuoteSubmission $input): ValidationResult
    {
        $errors = [];
        if ($this->validUtf8($errors, 'full_name', $input->fullName)) {
            $this->requiredLength($errors, 'full_name', $input->fullName, 2, 160, 'Indiquez votre nom complet.');
        }

        if (
            $this->validUtf8($errors, 'email', $input->email)
            && (!filter_var($input->email, FILTER_VALIDATE_EMAIL) || mb_strlen($input->email, 'UTF-8') > 254)
        ) {
            $errors['email'][] = 'Saisissez une adresse e-mail valide.';
        }

        if ($this->validUtf8($errors, 'organisation', $input->organisation)) {
            $this->requiredLength($errors, 'organisation', $input->organisation, 2, 190, 'Indiquez votre organisation.');
        }
        if (
            $this->validUtf8($errors, 'phone', $input->phone)
            && $input->phone !== ''
            && !preg_match('/^[0-9+().\s-]{6,40}$/u', $input->phone)
        ) {
            $errors['phone'][] = 'Saisissez un numéro de téléphone valide et joignable.';
        }

        if (
            $this->validUtf8($errors, 'project_type', $input->projectType)
            && !ProjectInquiryCatalog::contains(ProjectInquiryCatalog::projectTypes(), $input->projectType)
        ) {
            $errors['project_type'][] = 'Choisissez un type de projet proposé.';
        }
        if ($input->servicesResearched === [] || count($input->servicesResearched) > 3) {
            $errors['services_researched'][] = 'Choisissez entre un et trois domaines.';
        } else {
            foreach ($input->servicesResearched as $service) {
                if (!$this->validUtf8($errors, 'services_researched', $service)) {
                    break;
                }
                if (!ProjectInquiryCatalog::contains(ProjectInquiryCatalog::solutionDomains(), $service)) {
                    $errors['services_researched'][] = 'Un domaine sélectionné est invalide.';
                    break;
                }
            }
        }
        if (
            $this->validUtf8($errors, 'budget_range', $input->budgetRange)
            && $input->budgetRange !== ''
            && !ProjectInquiryCatalog::contains(ProjectInquiryCatalog::budgets(), $input->budgetRange)
        ) {
            $errors['budget_range'][] = 'Choisissez une enveloppe proposée.';
        }
        if (
            $this->validUtf8($errors, 'timeline', $input->timeline)
            && !ProjectInquiryCatalog::contains(ProjectInquiryCatalog::timelines(), $input->timeline)
        ) {
            $errors['timeline'][] = 'Choisissez un horizon proposé.';
        }
        if (
            $this->validUtf8($errors, 'preferred_contact_method', $input->preferredContactMethod)
            && $input->preferredContactMethod !== ''
            && !ProjectInquiryCatalog::contains(ProjectInquiryCatalog::contactMethods(), $input->preferredContactMethod)
        ) {
            $errors['preferred_contact_method'][] = 'Choisissez un mode de contact proposé.';
        }

        if ($this->validUtf8($errors, 'project_context', $input->projectContext)) {
            $this->requiredLength(
                $errors,
                'project_context',
                $input->projectContext,
                20,
                5000,
                'Décrivez brièvement le contexte du projet.',
            );
        }
        if ($this->validUtf8($errors, 'project_objective', $input->projectObjective)) {
            $this->requiredLength(
                $errors,
                'project_objective',
                $input->projectObjective,
                10,
                3000,
                'Précisez l’objectif principal du projet.',
            );
        }
        if ($this->validUtf8($errors, 'compliance_requirements', $input->complianceRequirements)) {
            $this->optionalLength($errors, 'compliance_requirements', $input->complianceRequirements, 4000);
        }
        if ($this->validUtf8($errors, 'integration_requirements', $input->integrationRequirements)) {
            $this->optionalLength($errors, 'integration_requirements', $input->integrationRequirements, 4000);
        }
        if ($this->validUtf8($errors, 'project_summary', $input->projectSummary)) {
            $this->optionalLength($errors, 'project_summary', $input->projectSummary, 4000);
        }
        if (!$input->consentPrivacy) {
            $errors['consent_privacy'][] = 'Votre accord est nécessaire pour traiter cette demande.';
        }
        if (
            $this->validUtf8($errors, 'locale', $input->locale)
            && !preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $input->locale)
        ) {
            $errors['locale'][] = 'Langue invalide.';
        }

        return $errors === [] ? ValidationResult::valid() : ValidationResult::invalid($errors);
    }

    /** @param array<string, list<string>> $errors */
    private function requiredLength(array &$errors, string $field, string $value, int $min, int $max, string $required): void
    {
        $length = mb_strlen($value, 'UTF-8');
        if ($length < $min) {
            $errors[$field][] = $required;
        } elseif ($length > $max) {
            $errors[$field][] = sprintf('Ce champ ne peut pas dépasser %d caractères.', $max);
        }
    }

    /** @param array<string, list<string>> $errors */
    private function optionalLength(array &$errors, string $field, string $value, int $max): void
    {
        if (mb_strlen($value, 'UTF-8') > $max) {
            $errors[$field][] = sprintf('Ce champ ne peut pas dépasser %d caractères.', $max);
        }
    }

    /** @param array<string, list<string>> $errors */
    private function validUtf8(array &$errors, string $field, string $value): bool
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return true;
        }

        $errors[$field][] = 'Ce champ contient des caractères invalides.';

        return false;
    }
}
