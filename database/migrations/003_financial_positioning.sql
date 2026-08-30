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
