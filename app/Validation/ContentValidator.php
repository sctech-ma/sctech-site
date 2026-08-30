<?php

declare(strict_types=1);

namespace SCTech\Validation;

use SCTech\DTO\ContentInput;

final class ContentValidator
{
    private const ARTICLE_CATEGORIES = [
        'finance-islamique',
        'experience-investisseur',
        'conformite',
        'architecture-financiere',
        'data-reporting',
        'securite',
    ];

    public function __construct(private readonly StructuredBlocksValidator $blocksValidator)
    {
    }

    public function validate(ContentInput $input, ?string $type = null): ValidationResult
    {
        $errors = [];
        if (!preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $input->locale)) {
            $errors['locale'][] = 'Langue invalide.';
        }
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,119}$/', $input->contentKey)) {
            $errors['content_key'][] = 'Utilisez une clé stable de 3 à 120 caractères.';
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input->slug) || strlen($input->slug) > 190) {
            $errors['slug'][] = 'Le slug doit contenir uniquement des minuscules, chiffres et tirets simples.';
        }
        $titleLength = mb_strlen($input->title, 'UTF-8');
        if ($titleLength < 2 || $titleLength > 190) {
            $errors['title'][] = 'Le titre doit contenir entre 2 et 190 caractères.';
        }
        if (mb_strlen($input->summary, 'UTF-8') > 5000) {
            $errors['summary'][] = 'Le résumé ne peut pas dépasser 5 000 caractères.';
        }
        if ($type === 'articles' && $input->summary === '') {
            $errors['summary'][] = 'Le résumé est obligatoire pour un article.';
        }
        if (mb_strlen($input->eyebrow, 'UTF-8') > 120) {
            $errors['eyebrow'][] = 'Le surtitre ne peut pas dépasser 120 caractères.';
        }
        if (mb_strlen($input->problemText, 'UTF-8') > 5000) {
            $errors['problem_text'][] = 'Le contexte ne peut pas dépasser 5 000 caractères.';
        }
        if (mb_strlen($input->positioningText, 'UTF-8') > 5000) {
            $errors['positioning_text'][] = 'Le positionnement ne peut pas dépasser 5 000 caractères.';
        }
        if ($type === 'articles') {
            if (!in_array($input->categoryKey, self::ARTICLE_CATEGORIES, true)) {
                $errors['category_key'][] = 'Choisissez une catégorie éditoriale proposée.';
            }
            if ($input->readingMinutes < 1 || $input->readingMinutes > 60) {
                $errors['reading_minutes'][] = 'Le temps de lecture doit être compris entre 1 et 60 minutes.';
            }
        }
        if (!in_array($input->status, ['draft', 'published', 'archived'], true)) {
            $errors['status'][] = 'Statut invalide.';
        }
        if (mb_strlen($input->seoTitle, 'UTF-8') > 190) {
            $errors['seo_title'][] = 'Le titre SEO ne peut pas dépasser 190 caractères.';
        }
        if (mb_strlen($input->seoDescription, 'UTF-8') > 320) {
            $errors['seo_description'][] = 'La description SEO ne peut pas dépasser 320 caractères.';
        }

        $base = $errors === [] ? ValidationResult::valid() : ValidationResult::invalid($errors);

        return $base->merge($this->blocksValidator->validateJson($input->blocksJson));
    }
}
