-- Positionnement « Principes programmables ». Idempotent, sans preuve ni compte utilisateur.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE services
SET status = 'archived', published_at = NULL, version = version + 1
WHERE locale = 'fr' AND content_key LIKE 'expertise.%';

INSERT INTO services
    (locale, content_key, slug, title, eyebrow, summary, problem_text, positioning_text,
     blocks_json, status, seo_title, seo_description, sort_order, published_at)
VALUES
    ('fr', 'solution.plateformes-investissement', 'plateformes-investissement',
     'Plateformes d’investissement', 'Piloter le cycle de vie des actifs',
     'Concevoir un système d’investissement autour de votre stratégie, de vos contrôles et de vos responsabilités.',
     'Les outils standard imposent souvent leurs structures de portefeuille, leurs statuts et leurs parcours. La logique réelle finit répartie entre tableurs, validations informelles et retraitements difficiles à auditer.',
     'SCTECH construit une plateforme qui relie actifs, portefeuilles, décisions, documents et événements selon le modèle opérationnel défini par votre organisation.',
     '{"version":1,"blocks":[{"type":"list","title":"Capacités éditoriales","items":["Vues portefeuille et actif","Circuits de décision multi-rôles","Gestion documentaire contextualisée","Suivi des événements et obligations"]}]}',
     'published', 'Plateformes d’investissement sur mesure — SCTECH',
     'Plateformes d’investissement configurées autour des portefeuilles, contrôles, validations et opérations du client.', 10, UTC_TIMESTAMP(6)),
    ('fr', 'solution.portails-investisseurs', 'portails-investisseurs',
     'Portails investisseurs', 'Orchestrer une relation exigeante',
     'Un espace sécurisé qui accompagne l’investisseur de l’onboarding au suivi, sans perdre les validations ni le contexte.',
     'Quand les échanges, pièces, relances et décisions circulent par plusieurs canaux, l’expérience se fragmente et les équipes perdent une vue fiable de l’avancement.',
     'SCTECH conçoit un portail relié au back-office : chaque action visible côté investisseur s’inscrit dans un workflow maîtrisé côté opérationnel.',
     '{"version":1,"blocks":[{"type":"list","title":"Capacités éditoriales","items":["Onboarding adaptatif","Espace documentaire sécurisé","Suivi des statuts","Back-office de traitement"]}]}',
     'published', 'Portails investisseurs sur mesure — SCTECH',
     'Portails investisseurs reliant onboarding, documents, validations et traitement opérationnel.', 20, UTC_TIMESTAMP(6)),
    ('fr', 'solution.workflows-conformite', 'workflows-conformite',
     'Workflows de conformité', 'Transformer un référentiel en opérations',
     'Formaliser des exigences, exécuter des contrôles explicables et organiser la validation humaine avec une trace complète.',
     'Un référentiel ne devient pas opérable par sa seule publication. Il faut identifier les données, les seuils, les exceptions, les responsables et les preuves associées à chaque décision.',
     'SCTECH traduit le référentiel validé par vos instances en règles configurables, contrôles, files de revue et rapports — sans se substituer à leur autorité.',
     '{"version":1,"blocks":[{"type":"list","title":"Capacités éditoriales","items":["Référentiel versionné","Moteur de règles explicable","Files de validation","Dossiers de preuve et rapports"]}]}',
     'published', 'Workflows de conformité sur mesure — SCTECH',
     'Moteurs de règles, contrôles, validations humaines et traçabilité adaptés à la gouvernance du client.', 30, UTC_TIMESTAMP(6)),
    ('fr', 'solution.data-reporting', 'data-reporting',
     'Data & reporting', 'Fiabiliser ce qui éclaire la décision',
     'Structurer les données financières, leurs contrôles et leurs définitions pour produire des lectures explicables.',
     'Lorsque la même donnée change de sens selon le rapport ou l’équipe, la production d’indicateurs absorbe le temps qui devrait servir à les interpréter.',
     'SCTECH relie sources, modèles, règles de qualité et usages de reporting dans une chaîne documentée qui rend chaque chiffre traçable jusqu’à son origine.',
     '{"version":1,"blocks":[{"type":"list","title":"Capacités éditoriales","items":["Ingestion et normalisation","Qualité et réconciliation","Indicateurs configurables","Lignage et documentation"]}]}',
     'published', 'Data et reporting financier sur mesure — SCTECH',
     'Chaînes de données financières structurées, contrôlées et explicables.', 40, UTC_TIMESTAMP(6)),
    ('fr', 'solution.integrations-financieres', 'integrations-financieres',
     'Intégrations financières', 'Faire circuler les données avec maîtrise',
     'Connecter systèmes financiers, identités, données et partenaires avec des contrats robustes et observables.',
     'Une intégration critique échoue rarement de façon simple : doublons, états intermédiaires, délais, secrets, ruptures de contrat et reprise doivent être pensés comme un système.',
     'SCTECH conçoit la couche d’orchestration qui rend les échanges explicites, sécurisés, idempotents et opérables par les équipes.',
     '{"version":1,"blocks":[{"type":"list","title":"Capacités éditoriales","items":["API sécurisées","Orchestration de workflows","Gestion des erreurs et reprises","Portail d’observabilité"]}]}',
     'published', 'Intégrations financières sur mesure — SCTECH',
     'API et orchestrations robustes entre systèmes financiers, identité, données et reporting.', 50, UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE
    title = VALUES(title), eyebrow = VALUES(eyebrow), summary = VALUES(summary),
    problem_text = VALUES(problem_text), positioning_text = VALUES(positioning_text),
    blocks_json = VALUES(blocks_json), status = 'published', seo_title = VALUES(seo_title),
    seo_description = VALUES(seo_description), sort_order = VALUES(sort_order),
    published_at = COALESCE(published_at, UTC_TIMESTAMP(6)), version = version + 1;

UPDATE sectors
SET status = 'archived', published_at = NULL, version = version + 1
WHERE locale = 'fr' AND content_key NOT LIKE 'segment.%';

INSERT INTO sectors
    (locale, content_key, slug, title, eyebrow, summary, blocks_json, status,
     seo_title, seo_description, sort_order, published_at)
VALUES
    ('fr', 'segment.fintechs-entrepreneurs', 'fintechs-entrepreneurs', 'Fintechs & entrepreneurs financiers',
     'Du modèle au produit', 'Transformer un modèle distinctif en produit crédible, opérable et prêt à évoluer.',
     '{"version":1,"blocks":[]}', 'published', 'Solutions pour fintechs — SCTECH', 'Développement de produits financiers sur mesure pour fintechs et entrepreneurs.', 10, UTC_TIMESTAMP(6)),
    ('fr', 'segment.asset-managers', 'asset-managers', 'Asset managers',
     'Outiller la décision', 'Outiller l’instruction, les portefeuilles, les contrôles et la relation investisseur.',
     '{"version":1,"blocks":[]}', 'published', 'Solutions pour asset managers — SCTECH', 'Plateformes, workflows, data et portails pour asset managers.', 20, UTC_TIMESTAMP(6)),
    ('fr', 'segment.institutions-financieres', 'institutions-financieres', 'Institutions financières',
     'Moderniser avec maîtrise', 'Moderniser des parcours et intégrations sans perdre la maîtrise des responsabilités.',
     '{"version":1,"blocks":[]}', 'published', 'Solutions pour institutions financières — SCTECH', 'Logiciels et intégrations financières sur mesure pour institutions.', 30, UTC_TIMESTAMP(6)),
    ('fr', 'segment.family-offices-plateformes', 'family-offices-plateformes', 'Family offices & plateformes',
     'Unifier la gouvernance', 'Unifier information, décisions, documents et reporting autour d’une gouvernance propre.',
     '{"version":1,"blocks":[]}', 'published', 'Solutions pour family offices — SCTECH', 'Systèmes sur mesure pour family offices et plateformes financières.', 40, UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE
    title = VALUES(title), eyebrow = VALUES(eyebrow), summary = VALUES(summary),
    blocks_json = VALUES(blocks_json), status = 'published', seo_title = VALUES(seo_title),
    seo_description = VALUES(seo_description), sort_order = VALUES(sort_order),
    published_at = COALESCE(published_at, UTC_TIMESTAMP(6)), version = version + 1;

UPDATE pages
SET status = 'archived', published_at = NULL, version = version + 1
WHERE locale = 'fr' AND content_key IN ('page.expertises', 'page.sectors', 'page.method', 'page.quote');

INSERT INTO pages
    (locale, content_key, slug, title, eyebrow, summary, blocks_json, status,
     seo_title, seo_description, sort_order, published_at)
VALUES
    ('fr', 'page.home', 'accueil', 'Votre logique financière. Construite dans le produit.',
     'FINTECH SUR MESURE · FINANCE ISLAMIQUE',
     'SCTECH conçoit des plateformes, portails et infrastructures financières sur mesure capables d’intégrer vos règles métier et les exigences de conformité Charia définies par vos instances compétentes.',
     '{"version":1,"blocks":[]}', 'published', 'SCTECH — Logiciels financiers sur mesure', 'Plateformes financières sur mesure intégrant règles métier, contrôles, validations et reporting.', 10, UTC_TIMESTAMP(6)),
    ('fr', 'page.solutions', 'solutions', 'Solutions financières sur mesure', 'CINQ SYSTÈMES À CONSTRUIRE',
     'Des plateformes et workflows construits autour du modèle opérationnel du client.',
     '{"version":1,"blocks":[]}', 'published', 'Solutions financières sur mesure — SCTECH', 'Plateformes, portails, conformité, data et intégrations financières.', 20, UTC_TIMESTAMP(6)),
    ('fr', 'page.expertise', 'expertise', 'Expertise produit, finance et ingénierie', 'UNE CONTINUITÉ DE CONCEPTION',
     'Formalisation métier, expérience, architecture, données et sécurité réunies autour du produit financier.',
     '{"version":1,"blocks":[]}', 'published', 'Expertise produit, finance et ingénierie — SCTECH', 'Construire la logique financière de bout en bout.', 30, UTC_TIMESTAMP(6)),
    ('fr', 'page.finance-islamique', 'finance-islamique', 'Finance islamique et logique logicielle', 'RESPONSABILITÉS CLAIRES',
     'Traduire les exigences validées par les instances compétentes du client en règles, contrôles, validations, traces et rapports.',
     '{"version":1,"blocks":[]}', 'published', 'Finance islamique et logique logicielle — SCTECH', 'Rendre un référentiel défini par le client applicable, explicable et traçable.', 40, UTC_TIMESTAMP(6)),
    ('fr', 'page.approach', 'approche', 'Faire progresser le produit sans perdre la logique', 'APPROCHE SCTECH',
     'Huit étapes de la découverte à l’évolution du produit.',
     '{"version":1,"blocks":[]}', 'published', 'Approche de réalisation — SCTECH', 'Une méthode en huit étapes pour les logiciels financiers sur mesure.', 50, UTC_TIMESTAMP(6)),
    ('fr', 'page.about', 'a-propos', 'À propos de SCTECH', 'CASABLANCA · MAROC–EUROPE',
     'Une équipe de conception au service des logiques financières exigeantes.',
     '{"version":1,"blocks":[]}', 'published', 'À propos — SCTECH', 'SCTECH conçoit des logiciels financiers sur mesure depuis Casablanca.', 60, UTC_TIMESTAMP(6)),
    ('fr', 'page.insights', 'insights', 'Insights financiers', 'FORMALISER AVANT D’AUTOMATISER',
     'Des analyses sur les règles, données, validations et architectures financières.',
     '{"version":1,"blocks":[]}', 'published', 'Insights financiers — SCTECH', 'Analyses originales sur les logiciels financiers explicables.', 70, UTC_TIMESTAMP(6)),
    ('fr', 'page.contact', 'contact', 'Contact SCTECH', 'COMMENCER PAR LE CONTEXTE',
     'Échangez avec SCTECH sur votre produit, workflow ou intégration financière.',
     '{"version":1,"blocks":[]}', 'published', 'Contact — SCTECH', 'Parlez à SCTECH de votre projet financier sur mesure.', 80, UTC_TIMESTAMP(6)),
    ('fr', 'page.project', 'demander-un-projet', 'Étudier votre projet', 'PROJET FINANCIER SUR MESURE',
     'Présentez le contexte, l’objectif, les exigences et les systèmes à intégrer.',
     '{"version":1,"blocks":[]}', 'published', 'Étudier votre projet — SCTECH', 'Préparez un échange structuré sur votre projet financier.', 90, UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE
    title = VALUES(title), eyebrow = VALUES(eyebrow), summary = VALUES(summary),
    blocks_json = VALUES(blocks_json), status = 'published', seo_title = VALUES(seo_title),
    seo_description = VALUES(seo_description), sort_order = VALUES(sort_order),
    published_at = COALESCE(published_at, UTC_TIMESTAMP(6)), version = version + 1;

INSERT INTO articles
    (locale, content_key, slug, title, eyebrow, category_key, summary, blocks_json,
     status, seo_title, seo_description, reading_minutes, sort_order, published_at)
VALUES
    ('fr', 'insight.sharia-rule-engine', 'referentiel-moteur-regles-conformite-charia',
     'Du référentiel au moteur de règles : formaliser des exigences de conformité Charia',
     'Finance islamique', 'finance-islamique',
     'Une méthode pour passer d’un principe validé à des données, contrôles, exceptions et preuves opérables.',
     '{"version":1,"blocks":[{"type":"paragraph","text":"Un référentiel validé exprime des principes et des critères. Un produit doit traiter des données parfois incomplètes, des exceptions, des versions et des responsabilités. La formalisation relie ces deux mondes sans transférer l’autorité au logiciel."},{"type":"heading","level":2,"text":"Commencer par l’autorité et le périmètre"},{"type":"paragraph","text":"Pour chaque exigence, identifiez qui la définit, qui peut l’interpréter, les opérations concernées et la date d’application."},{"type":"list","title":"Une fiche de règle utile précise","items":["La source et la version du référentiel","Les données nécessaires et leur origine","Les conditions, seuils et unités","Les cas réservés à une revue","Le rôle autorisé à conclure","La preuve à conserver"]},{"type":"heading","level":2,"text":"Prévoir un état de revue"},{"type":"paragraph","text":"Une donnée absente ou une exception ne doit pas devenir une conclusion automatique. Un état de revue explicite conserve la place de la validation humaine."},{"type":"callout","label":"Responsabilité","text":"Les instances compétentes du client définissent ou valident les critères. SCTECH ne délivre ni avis religieux ni certification."}]}',
     'published', 'Du référentiel au moteur de règles — SCTECH', 'Formaliser des exigences de conformité Charia dans un système logiciel gouverné.', 8, 10, UTC_TIMESTAMP(6)),
    ('fr', 'insight.investor-portal', 'portail-investisseur-onboarding-validations-tracabilite',
     'Portail investisseur : orchestrer onboarding, validations et traçabilité',
     'Expérience investisseur', 'experience-investisseur',
     'Concevoir un parcours cohérent côté investisseur sans séparer l’expérience du travail réel du back-office.',
     '{"version":1,"blocks":[{"type":"paragraph","text":"Un portail investisseur est souvent évalué par son interface. Sa fiabilité dépend de ce qu’il orchestre derrière chaque écran : documents, vérifications, responsabilités, relances et décisions."},{"type":"heading","level":2,"text":"Dessiner le parcours avec son envers opérationnel"},{"type":"paragraph","text":"Pour chaque action investisseur, décrivez le travail déclenché côté back-office, son propriétaire et son délai."},{"type":"list","title":"Chaque état doit préciser","items":["Sa signification exacte","Le rôle qui attend une action","La pièce ou donnée manquante","La prochaine transition autorisée","La trace conservée"]},{"type":"heading","level":2,"text":"Éviter les statuts décoratifs"},{"type":"paragraph","text":"Un statut trop générique rend les relances imprécises et les indicateurs peu fiables. Les états doivent refléter le travail réel."},{"type":"callout","label":"Question de conception","text":"L’investisseur peut-il comprendre ce qui est attendu sans exposer une information interne ou sensible ?"}]}',
     'published', 'Portail investisseur : onboarding et traçabilité — SCTECH', 'Concevoir un portail investisseur relié aux validations et opérations du back-office.', 7, 20, UTC_TIMESTAMP(6)),
    ('fr', 'insight.financial-screening', 'screening-financier-qualification-explicable-auditable',
     'Screening financier : concevoir une qualification explicable et auditable',
     'Data & conformité', 'conformite',
     'Structurer les sources, seuils, versions de règles et décisions humaines pour éviter la boîte noire.',
     '{"version":1,"blocks":[{"type":"paragraph","text":"Un screening utile ne produit pas seulement un statut. Il permet de comprendre les données utilisées, la transformation appliquée, la règle exécutée et la raison d’une revue."},{"type":"heading","level":2,"text":"Séparer source, calcul et décision"},{"type":"paragraph","text":"La donnée brute, l’indicateur calculé et la conclusion sont trois objets différents. Les séparer permet de corriger, rejouer et expliquer."},{"type":"steps","items":["Identifier la source et sa date","Normaliser unités et périodes","Calculer avec une formule versionnée","Appliquer la règle active","Router les exceptions","Conserver entrées et décision"]},{"type":"heading","level":2,"text":"Traiter l’incertitude comme un état normal"},{"type":"paragraph","text":"Une source absente ou contradictoire doit déclencher une demande de complément ou une décision manuelle, pas une valeur arbitraire."},{"type":"callout","label":"Auditabilité","text":"Reproduire un résultat exige les mêmes entrées, la même version de formule et la même version de règle."}]}',
     'published', 'Screening financier explicable et auditable — SCTECH', 'Concevoir un screening financier avec données, règles et décisions traçables.', 9, 30, UTC_TIMESTAMP(6))
ON DUPLICATE KEY UPDATE
    title = VALUES(title), eyebrow = VALUES(eyebrow), category_key = VALUES(category_key),
    summary = VALUES(summary), blocks_json = VALUES(blocks_json), status = 'published',
    seo_title = VALUES(seo_title), seo_description = VALUES(seo_description),
    reading_minutes = VALUES(reading_minutes), sort_order = VALUES(sort_order),
    published_at = COALESCE(published_at, UTC_TIMESTAMP(6)), version = version + 1;
