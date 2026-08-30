-- SCTECH baseline schema. Compatible with MySQL 8.0+ and MariaDB 10.6+.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    version VARCHAR(190) NOT NULL,
    checksum CHAR(64) NOT NULL,
    applied_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_migrations_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(120) NOT NULL,
    role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    eyebrow VARCHAR(120) NULL,
    summary TEXT NULL,
    blocks_json LONGTEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    social_image_media_id BIGINT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_locale_slug (locale, slug),
    UNIQUE KEY uq_pages_locale_key (locale, content_key),
    KEY idx_pages_publication (locale, status, published_at),
    KEY idx_pages_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    eyebrow VARCHAR(120) NULL,
    summary TEXT NULL,
    problem_text TEXT NULL,
    positioning_text TEXT NULL,
    blocks_json LONGTEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    social_image_media_id BIGINT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_locale_slug (locale, slug),
    UNIQUE KEY uq_services_locale_key (locale, content_key),
    KEY idx_services_publication (locale, status, published_at),
    KEY idx_services_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sectors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    eyebrow VARCHAR(120) NULL,
    summary TEXT NULL,
    blocks_json LONGTEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    social_image_media_id BIGINT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sectors_locale_slug (locale, slug),
    UNIQUE KEY uq_sectors_locale_key (locale, content_key),
    KEY idx_sectors_publication (locale, status, published_at),
    KEY idx_sectors_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS case_studies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    client_name VARCHAR(190) NULL,
    sector_id BIGINT UNSIGNED NULL,
    summary TEXT NULL,
    challenge_text TEXT NULL,
    approach_text TEXT NULL,
    outcome_text TEXT NULL,
    verification_notes TEXT NULL,
    blocks_json LONGTEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    social_image_media_id BIGINT UNSIGNED NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_case_studies_locale_slug (locale, slug),
    UNIQUE KEY uq_case_studies_locale_key (locale, content_key),
    KEY idx_case_studies_publication (locale, status, published_at),
    KEY idx_case_studies_sector (sector_id),
    CONSTRAINT fk_case_studies_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    eyebrow VARCHAR(120) NULL,
    summary TEXT NOT NULL,
    blocks_json LONGTEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    social_image_media_id BIGINT UNSIGNED NULL,
    reading_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_articles_locale_slug (locale, slug),
    UNIQUE KEY uq_articles_locale_key (locale, content_key),
    KEY idx_articles_publication (locale, status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    disk VARCHAR(32) NOT NULL DEFAULT 'public',
    path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    byte_size BIGINT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    alt_text VARCHAR(255) NOT NULL,
    caption VARCHAR(500) NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_media_locale_key (locale, content_key),
    UNIQUE KEY uq_media_path (path),
    KEY idx_media_status (status),
    CONSTRAINT fk_media_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(254) NOT NULL,
    organisation VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    consent_privacy TINYINT(1) NOT NULL,
    workflow_status ENUM('new', 'in_progress', 'closed', 'spam') NOT NULL DEFAULT 'new',
    notification_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    notification_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    notification_error_code VARCHAR(80) NULL,
    notified_at DATETIME(6) NULL,
    idempotency_key_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent_hash CHAR(64) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contact_public_id (public_id),
    UNIQUE KEY uq_contact_idempotency (idempotency_key_hash),
    KEY idx_contact_workflow (workflow_status, created_at),
    KEY idx_contact_notification (notification_status, created_at),
    KEY idx_contact_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(36) NOT NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(254) NOT NULL,
    organisation VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    project_type VARCHAR(80) NOT NULL,
    service_key VARCHAR(120) NOT NULL,
    services_researched_json LONGTEXT NOT NULL,
    budget_range VARCHAR(80) NULL,
    timeline VARCHAR(80) NULL,
    preferred_contact_method VARCHAR(40) NOT NULL,
    project_summary TEXT NOT NULL,
    consent_privacy TINYINT(1) NOT NULL,
    workflow_status ENUM('new', 'qualified', 'proposal', 'closed', 'spam') NOT NULL DEFAULT 'new',
    notification_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    notification_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    notification_error_code VARCHAR(80) NULL,
    notified_at DATETIME(6) NULL,
    idempotency_key_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent_hash CHAR(64) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quote_public_id (public_id),
    UNIQUE KEY uq_quote_idempotency (idempotency_key_hash),
    KEY idx_quote_workflow (workflow_status, created_at),
    KEY idx_quote_notification (notification_status, created_at),
    KEY idx_quote_service (service_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale VARCHAR(10) NOT NULL DEFAULT 'fr',
    content_key VARCHAR(120) NOT NULL,
    value_json LONGTEXT NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(190) NULL,
    seo_description VARCHAR(320) NULL,
    is_sensitive TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    version INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_site_settings_locale_key (locale, content_key),
    KEY idx_site_settings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    identity_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    succeeded TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_login_identity (identity_hash, attempted_at),
    KEY idx_login_ip (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket_key CHAR(64) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at DATETIME(6) NOT NULL,
    expires_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (bucket_key),
    KEY idx_rate_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    request_id VARCHAR(80) NULL,
    ip_hash CHAR(64) NULL,
    context_json LONGTEXT NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_audit_actor (user_id, created_at),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consent_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_type ENUM('contact_message', 'quote_request') NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    consent_key VARCHAR(120) NOT NULL,
    consented TINYINT(1) NOT NULL,
    policy_version VARCHAR(40) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_consent_subject (subject_type, subject_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_metadata (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_type ENUM('contact_message', 'quote_request') NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    metadata_key VARCHAR(120) NOT NULL,
    value_json LONGTEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_workflow_metadata (subject_type, subject_id, metadata_key),
    CONSTRAINT fk_workflow_metadata_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pages ADD CONSTRAINT fk_pages_social_media FOREIGN KEY (social_image_media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE services ADD CONSTRAINT fk_services_social_media FOREIGN KEY (social_image_media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE sectors ADD CONSTRAINT fk_sectors_social_media FOREIGN KEY (social_image_media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE case_studies ADD CONSTRAINT fk_case_studies_social_media FOREIGN KEY (social_image_media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE articles ADD CONSTRAINT fk_articles_social_media FOREIGN KEY (social_image_media_id) REFERENCES media (id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE case_studies
    ADD COLUMN verified_at DATETIME(6) NULL AFTER verification_notes,
    ADD COLUMN verified_by BIGINT UNSIGNED NULL AFTER verified_at,
    ADD COLUMN verification_content_hash CHAR(64) NULL AFTER verified_by,
    ADD KEY idx_case_studies_verification (verified_at, verified_by),
    ADD CONSTRAINT fk_case_studies_verified_by
        FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL;

UPDATE case_studies
SET status = 'draft',
    published_at = NULL,
    verified_at = NULL,
    verified_by = NULL,
    verification_content_hash = NULL
WHERE status = 'published';

ALTER TABLE quote_requests
    ADD COLUMN project_context TEXT NULL AFTER preferred_contact_method,
    ADD COLUMN project_objective TEXT NULL AFTER project_context,
    ADD COLUMN compliance_requirements TEXT NULL AFTER project_objective,
    ADD COLUMN integration_requirements TEXT NULL AFTER compliance_requirements,
    MODIFY COLUMN phone VARCHAR(40) NULL,
    MODIFY COLUMN preferred_contact_method VARCHAR(40) NULL,
    MODIFY COLUMN project_summary TEXT NULL;

ALTER TABLE articles
    ADD COLUMN category_key VARCHAR(80) NULL AFTER eyebrow,
    ADD KEY idx_articles_category (locale, category_key, status, published_at);
