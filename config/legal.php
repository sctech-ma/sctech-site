<?php

declare(strict_types=1);

return [
    'entity_name' => trim((string) env('COMPANY_LEGAL_NAME', '')),
    'legal_form' => trim((string) env('COMPANY_LEGAL_FORM', '')),
    'full_address' => trim((string) env('COMPANY_ADDRESS', '')),
    'rc' => trim((string) env('COMPANY_RC', '')),
    'ice' => trim((string) env('COMPANY_ICE', '')),
    'tax_id' => trim((string) env('COMPANY_IF', '')),
    'phone' => trim((string) env('COMPANY_PHONE', '')),
    'linkedin' => trim((string) env('COMPANY_LINKEDIN', '')),
    'publication_director' => trim((string) env('COMPANY_PUBLICATION_DIRECTOR', '')),
    'hosting' => trim((string) env('COMPANY_HOSTING_DETAILS', '')),
    'retention_policy' => trim((string) env('PRIVACY_RETENTION_POLICY', '')),
    'legal_review_approved' => filter_var(env('LEGAL_REVIEW_APPROVED', 'false'), FILTER_VALIDATE_BOOL),
    'privacy_review_approved' => filter_var(env('PRIVACY_REVIEW_APPROVED', 'false'), FILTER_VALIDATE_BOOL),
    'cookie_review_approved' => filter_var(env('COOKIE_REVIEW_APPROVED', 'false'), FILTER_VALIDATE_BOOL),
    'production_hosting_confirmed' => filter_var(
        env('PRODUCTION_HOSTING_CONFIRMED', 'false'),
        FILTER_VALIDATE_BOOL
    ),
    'logo_vector_master_received' => filter_var(
        env('LOGO_VECTOR_MASTER_RECEIVED', 'false'),
        FILTER_VALIDATE_BOOL
    ),
    'logo_rights_approved' => filter_var(env('LOGO_RIGHTS_APPROVED', 'false'), FILTER_VALIDATE_BOOL),
];
