<?php

declare(strict_types=1);

namespace SCTech\Validation;

use SCTech\DTO\ContactSubmission;

final class ContactValidator
{
    private const SUBJECTS = [
        'data-ai',
        'software',
        'cloud',
        'cybersecurity',
        'transformation',
        'partnership',
        'other',
    ];

    public function validate(ContactSubmission $input): ValidationResult
    {
        $errors = [];
        $this->requiredLength($errors, 'full_name', $input->fullName, 2, 160, 'Indiquez votre nom complet.');

        if (!filter_var($input->email, FILTER_VALIDATE_EMAIL) || mb_strlen($input->email) > 254) {
            $errors['email'][] = 'Saisissez une adresse e-mail valide.';
        }

        $this->optionalMax($errors, 'organisation', $input->organisation, 190);
        if ($input->phone !== '' && !preg_match('/^[0-9+().\s-]{6,40}$/u', $input->phone)) {
            $errors['phone'][] = 'Saisissez un numéro de téléphone valide.';
        }

        if (!in_array($input->subject, self::SUBJECTS, true)) {
            $errors['subject'][] = 'Choisissez un sujet proposé.';
        }

        $this->requiredLength($errors, 'message', $input->message, 20, 5000, 'Décrivez votre besoin en quelques phrases.');

        if (!$input->consentPrivacy) {
            $errors['consent_privacy'][] = 'Votre accord est nécessaire pour traiter cette demande.';
        }

        if (!preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $input->locale)) {
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
    private function optionalMax(array &$errors, string $field, string $value, int $max): void
    {
        if (mb_strlen($value, 'UTF-8') > $max) {
            $errors[$field][] = sprintf('Ce champ ne peut pas dépasser %d caractères.', $max);
        }
    }
}
