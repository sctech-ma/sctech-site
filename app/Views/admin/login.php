<?php

declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
?>
<section class="admin-login" aria-labelledby="titre-connexion">
    <p class="eyebrow">Espace sécurisé</p>
    <h1 id="titre-connexion">Connexion à l’administration</h1>
    <p>Utilisez le compte créé en ligne de commande. Aucun compte par défaut n’est installé.</p>

    <?php if ($errors !== []): ?>
        <div class="form-error-summary" role="alert" tabindex="-1">
            <h2>La connexion n’a pas abouti</h2>
            <ul>
                <?php foreach ($errors as $field => $messages): ?>
                    <?php foreach ((array) $messages as $message): ?>
                        <li><a href="#<?= e($field === '_form' ? 'formulaire-connexion' : $field) ?>"><?= e((string) $message) ?></a></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="formulaire-connexion" method="post" action="<?= e(url('/admin/login')) ?>" novalidate>
        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
        <div class="field">
            <label for="email">Adresse e-mail</label>
            <input id="email" name="email" type="email" autocomplete="username" required maxlength="254"
                   value="<?= e((string) $email) ?>"<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="email-erreur"' : '' ?>>
            <?php if (isset($errors['email'])): ?><p id="email-erreur" class="field-error"><?= e((string) $errors['email'][0]) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-erreur"' : '' ?>>
            <?php if (isset($errors['password'])): ?><p id="password-erreur" class="field-error"><?= e((string) $errors['password'][0]) ?></p><?php endif; ?>
        </div>
        <button type="submit" class="button button--primary">Se connecter</button>
    </form>
</section>
