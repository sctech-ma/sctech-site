<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
?>
<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert" tabindex="-1" data-error-summary>
        <strong>Vérifiez les informations indiquées.</strong>
        <ul>
            <?php foreach ($errors as $field => $message): ?>
                <li>
                    <?php if (is_string($field) && $field !== '' && !str_starts_with($field, '_')): ?>
                        <a href="#<?= e($field) ?>"><?= e(is_array($message) ? (string) reset($message) : (string) $message) ?></a>
                    <?php else: ?>
                        <?= e(is_array($message) ? (string) reset($message) : (string) $message) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
