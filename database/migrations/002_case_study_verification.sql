ALTER TABLE case_studies
    ADD COLUMN verified_at DATETIME(6) NULL AFTER verification_notes,
    ADD COLUMN verified_by BIGINT UNSIGNED NULL AFTER verified_at,
    ADD COLUMN verification_content_hash CHAR(64) NULL AFTER verified_by,
    ADD KEY idx_case_studies_verification (verified_at, verified_by),
    ADD CONSTRAINT fk_case_studies_verified_by
        FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL;

-- Existing publication flags predate attributable verification and cannot be trusted.
UPDATE case_studies
SET status = 'draft',
    published_at = NULL,
    verified_at = NULL,
    verified_by = NULL,
    verification_content_hash = NULL
WHERE status = 'published';
