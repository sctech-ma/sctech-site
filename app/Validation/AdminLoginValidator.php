<?php

declare(strict_types=1);

namespace SCTech\Validation;

use SCTech\DTO\AdminLogin;

final class AdminLoginValidator
{
    public function validate(AdminLogin $input): ValidationResult
    {
        $errors = [];
        if (!filter_var($input->email, FILTER_VALIDATE_EMAIL) || mb_strlen($input->email) > 254) {
            $errors['email'][] = 'Saisissez une adresse e-mail valide.';
        }
        if ($input->password === '' || strlen($input->password) > 4096) {
            $errors['password'][] = 'Saisissez votre mot de passe.';
        }

        return $errors === [] ? ValidationResult::valid() : ValidationResult::invalid($errors);
    }
}
