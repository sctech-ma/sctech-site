<?php

declare(strict_types=1);

namespace SCTech\Controllers\PublicSite;

use DateTimeImmutable;
use DateTimeZone;
use SCTech\Core\Config;
use SCTech\Core\LegalProfile;
use SCTech\Core\Request;
use SCTech\Core\Response;
use SCTech\Core\Session;
use SCTech\Core\View;
use SCTech\Repositories\ContentRepository;
use SCTech\Services\CsrfTokenManager;
use SCTech\Services\FormTokenService;
use SCTech\Services\PublicContentMapper;
use SCTech\Support\ProjectInquiryCatalog;

final class SiteController
{
    /** @var array<string, string> */
    private const PAGE_CONTENT_KEYS = [
        'accueil' => 'page.home',
        'solutions' => 'page.solutions',
        'expertise' => 'page.expertise',
        'finance-islamique' => 'page.finance-islamique',
        'approche' => 'page.approach',
        'a-propos' => 'page.about',
        'realisations' => 'page.realizations',
        'insights' => 'page.insights',
        'contact' => 'page.contact',
        'demander-un-projet' => 'page.project',
    ];

    /** @var array<string, string> */
    private const ARTICLE_CATEGORIES = [
        'finance-islamique' => 'Finance islamique',
        'experience-investisseur' => 'Expérience investisseur',
        'conformite' => 'Data & conformité',
        'architecture-financiere' => 'Architecture financière',
        'data-reporting' => 'Data & reporting',
        'securite' => 'Sécurité',
    ];

    /** @var array<string, mixed> */
    private array $language;

    public function __construct(
        private readonly View $view,
        private readonly ContentRepository $content,
        private readonly Config $config,
        private readonly Session $session,
        private readonly CsrfTokenManager $csrf,
        private readonly FormTokenService $formTokens,
        private readonly PublicContentMapper $contentMapper,
        private readonly LegalProfile $legalProfile,
    ) {
        /** @var array<string, mixed> $language */
        $language = require dirname(__DIR__, 3) . '/lang/fr.php';
        $this->language = $language;
    }

    public function home(Request $request): Response
    {
        return $this->page('pages/home', $request, $this->withCmsPage('accueil', [
            'title' => 'SCTECH — Logiciels financiers sur mesure',
            'description' => 'SCTECH conçoit des plateformes financières sur mesure capables d’intégrer vos règles métier et les exigences définies par vos instances compétentes.',
            'solutions' => $this->publishedSolutions(),
            'articles' => $this->publishedArticles(3, true),
            'caseStudies' => $this->publishedCases(3),
            'schema' => [$this->organizationSchema(), $this->websiteSchema()],
            'pageClass' => 'page-home',
        ]));
    }

    public function solutions(Request $request): Response
    {
        return $this->page('pages/solutions', $request, $this->withCmsPage('solutions', [
            'title' => 'Solutions financières sur mesure — SCTECH',
            'description' => 'Plateformes d’investissement, portails investisseurs, workflows de conformité, data et intégrations financières.',
            'solutions' => $this->publishedSolutions(),
            'pageClass' => 'page-solutions',
        ]));
    }

    public function solution(Request $request, string $slug): Response
    {
        $solutions = $this->publishedSolutions();
        $solution = $solutions[$slug] ?? null;
        if (!is_array($solution)) {
            return $this->notFound($request);
        }

        return $this->page('pages/solution', $request, [
            'title' => $this->seoTitle($solution, (string) $solution['title'] . ' — SCTECH'),
            'description' => $this->seoDescription($solution, (string) $solution['summary']),
            'solution' => $solution,
            'slug' => $slug,
            'cmsBlocks' => $solution['blocks'] ?? [],
            'pageClass' => 'page-solution page-solution--' . $slug,
        ]);
    }

    public function expertise(Request $request): Response
    {
        return $this->page('pages/expertise', $request, $this->withCmsPage('expertise', [
            'title' => 'Expertise produit, finance et ingénierie — SCTECH',
            'description' => 'Formalisation métier, expérience produit, architecture sécurisée et données explicables pour les logiciels financiers.',
            'solutions' => $this->publishedSolutions(),
            'pageClass' => 'page-expertise',
        ]));
    }

    public function islamicFinance(Request $request): Response
    {
        return $this->page('pages/islamic-finance', $request, $this->withCmsPage('finance-islamique', [
            'title' => 'Finance islamique et logique logicielle — SCTECH',
            'description' => 'Traduire les exigences validées par les instances compétentes du client en règles, contrôles, validations, traces et rapports logiciels.',
            'solutions' => $this->publishedSolutions(),
            'pageClass' => 'page-islamic-finance',
        ]));
    }

    public function approach(Request $request): Response
    {
        return $this->page('pages/method', $request, $this->withCmsPage('approche', [
            'title' => 'Approche de réalisation — SCTECH',
            'description' => 'Découvrir, formaliser, architecturer, prototyper, développer, valider, déployer et faire évoluer.',
            'pageClass' => 'page-approach',
        ]));
    }

    public function about(Request $request): Response
    {
        return $this->page('pages/about', $request, $this->withCmsPage('a-propos', [
            'title' => 'À propos — SCTECH',
            'description' => 'SCTECH conçoit des logiciels financiers sur mesure depuis Casablanca pour le Maroc, l’Afrique et l’Europe.',
            'pageClass' => 'page-about',
        ]));
    }

    public function realizations(Request $request): Response
    {
        return $this->page('pages/realizations', $request, $this->withCmsPage('realisations', [
            'title' => 'Réalisations vérifiées — SCTECH',
            'description' => 'Les réalisations sont publiées uniquement lorsque leur contexte et leur autorisation sont vérifiés.',
            'caseStudies' => $this->publishedCases(50),
            'pageClass' => 'page-realizations',
        ]));
    }

    public function realization(Request $request, string $slug): Response
    {
        $row = $this->content->findPublishedBySlug('realisations', 'fr', $slug);
        if ($row === null) {
            return $this->notFound($request);
        }
        $caseStudy = $this->mapCase($row);

        return $this->page('pages/realization', $request, [
            'title' => (string) $caseStudy['title'] . ' — SCTECH',
            'description' => (string) ($caseStudy['summary'] ?? ''),
            'caseStudy' => $caseStudy,
            'pageClass' => 'page-realization',
        ]);
    }

    public function insights(Request $request): Response
    {
        $category = (string) $request->query('categorie', 'tous');
        if ($category !== 'tous' && !array_key_exists($category, self::ARTICLE_CATEGORIES)) {
            $category = 'tous';
        }
        $articles = $this->publishedArticles(100, false);
        if ($category !== 'tous') {
            $articles = array_values(array_filter(
                $articles,
                static fn (array $article): bool => ($article['category_key'] ?? '') === $category
            ));
        }

        return $this->page('pages/insights', $request, $this->withCmsPage('insights', [
            'title' => 'Insights financiers — SCTECH',
            'description' => 'Analyses sur les moteurs de règles, portails investisseurs, données et architectures financières explicables.',
            'articles' => $articles,
            'activeCategory' => $category,
            'pageClass' => 'page-insights',
        ]));
    }

    public function article(Request $request, string $slug): Response
    {
        $row = $this->content->findPublishedBySlug('articles', 'fr', $slug);
        if ($row === null) {
            return $this->notFound($request);
        }
        $article = $this->mapArticle($row);

        return $this->page('pages/article', $request, [
            'title' => (string) $article['title'] . ' — SCTECH',
            'description' => (string) $article['excerpt'],
            'article' => $article,
            'slug' => $slug,
            'ogType' => 'article',
            'schema' => [$this->articleSchema($article)],
            'pageClass' => 'page-article',
        ]);
    }

    public function contact(Request $request): Response
    {
        return $this->formPage('pages/contact', 'contact', $request, $this->withCmsPage('contact', [
            'title' => 'Contact — SCTECH',
            'description' => 'Échangez avec SCTECH sur un produit, un workflow, des données ou une intégration financière.',
            'pageClass' => 'page-contact',
        ]));
    }

    public function project(Request $request): Response
    {
        return $this->formPage('pages/quote', 'quote', $request, $this->withCmsPage('demander-un-projet', [
            'title' => 'Étudier votre projet — SCTECH',
            'description' => 'Présentez le contexte, l’objectif, les règles de conformité et les intégrations de votre projet financier.',
            'projectCatalog' => [
                'projectTypes' => ProjectInquiryCatalog::projectTypes(),
                'solutionDomains' => ProjectInquiryCatalog::solutionDomains(),
                'timelines' => ProjectInquiryCatalog::timelines(),
                'budgets' => ProjectInquiryCatalog::budgets(),
                'contactMethods' => ProjectInquiryCatalog::contactMethods(),
            ],
            'pageClass' => 'page-project',
        ]));
    }

    /** Temporary controller aliases retained for internal compatibility. */
    public function expertises(Request $request): Response
    {
        return $this->expertise($request);
    }

    public function method(Request $request): Response
    {
        return $this->approach($request);
    }

    public function quote(Request $request): Response
    {
        return $this->project($request);
    }

    public function legal(Request $request): Response
    {
        return $this->legalPage('pages/mentions-legales', 'Mentions légales — SCTECH', $request);
    }

    public function privacy(Request $request): Response
    {
        return $this->legalPage('pages/privacy', 'Politique de confidentialité — SCTECH', $request);
    }

    public function cookies(Request $request): Response
    {
        return $this->legalPage('pages/cookies', 'Politique de cookies — SCTECH', $request);
    }

    public function robots(): Response
    {
        $base = rtrim($this->config->string('app.url'), '/');
        return Response::text(
            "User-agent: *\nDisallow: /admin\nSitemap: {$base}/sitemap.xml\n",
            200,
            ['Cache-Control' => 'public, max-age=3600']
        );
    }

    public function sitemap(): Response
    {
        $base = rtrim($this->config->string('app.url'), '/');
        $paths = [
            '/', '/solutions', '/expertise', '/finance-islamique', '/approche',
            '/a-propos', '/insights', '/contact', '/demander-un-projet',
        ];
        foreach (array_keys($this->publishedSolutions()) as $slug) {
            $paths[] = '/solutions/' . rawurlencode($slug);
        }
        foreach ($this->publishedArticles(100, false) as $article) {
            $paths[] = '/insights/' . rawurlencode((string) $article['slug']);
        }
        $cases = $this->publishedCases(100);
        if ($cases !== []) {
            $paths[] = '/realisations';
            foreach ($cases as $caseStudy) {
                $paths[] = '/realisations/' . rawurlencode((string) $caseStudy['slug']);
            }
        }
        if ($this->legalProfile->isPublicReady()) {
            array_push($paths, '/mentions-legales', '/politique-confidentialite', '/politique-cookies');
        }
        $urls = implode('', array_map(
            static fn (string $path): string => '<url><loc>'
                . htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</loc></url>',
            array_values(array_unique($paths))
        ));
        return new Response(
            '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                . $urls . '</urlset>',
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']
        );
    }

    public function notFound(Request $request): Response
    {
        return $this->page('pages/404', $request, [
            'title' => 'Page introuvable — SCTECH',
            'description' => 'La page demandée est introuvable.',
            'robots' => 'noindex,nofollow',
            'pageClass' => 'page-error',
        ], 404);
    }

    /** @param array<string, mixed> $data */
    private function formPage(string $template, string $action, Request $request, array $data): Response
    {
        $this->session->start();
        $old = $this->session->pull('form.old', []);
        $old = is_array($old) ? $old : [];
        if ($action === 'quote' && $old === []) {
            $solution = (string) $request->query('solution', '');
            if (ProjectInquiryCatalog::contains(ProjectInquiryCatalog::solutionDomains(), $solution)) {
                $old['services_researched'] = [$solution];
            }
        }
        $data += [
            'errors' => $this->session->pull('form.errors', []),
            'old' => $old,
            'csrfToken' => $this->csrf->token($action),
            'formTimingToken' => $this->formTokens->issueTimingToken($action),
            'idempotencyToken' => $this->session->pull('form.idempotency', $this->formTokens->issueIdempotencyKey()),
        ];

        return $this->page($template, $request, $data);
    }

    /** @param array<string, mixed> $data */
    private function page(string $template, Request $request, array $data, int $status = 200): Response
    {
        $path = $request->path();
        $base = rtrim($this->config->string('app.url'), '/');
        $data += [
            'canonical' => $base . ($path === '/' ? '/' : $path),
            'currentPath' => $path,
            'lang' => $this->language,
            'nonce' => (string) $request->attribute('csp_nonce', ''),
            'flash' => $this->session->isStarted() ? $this->session->pull('flash', []) : [],
        ];

        return $this->view->response($template, $data, 'layouts/public', $status);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withCmsPage(string $slug, array $data): array
    {
        $stableKey = self::PAGE_CONTENT_KEYS[$slug] ?? 'page.' . $slug;
        $row = null;
        foreach ($this->content->paginate('pages', 'fr', 'published', 1, 100) as $candidate) {
            if (($candidate['content_key'] ?? null) === $stableKey) {
                $row = $candidate;
                break;
            }
        }
        if ($row === null) {
            return $data;
        }
        $page = $this->contentMapper->page($row);
        $data['title'] = $this->seoTitle($page, (string) ($data['title'] ?? 'SCTECH'));
        $data['description'] = $this->seoDescription($page, (string) ($data['description'] ?? ''));
        $data['cmsPage'] = $page;
        $data['cmsBlocks'] = $page['blocks'];
        return $data;
    }

    /** @return array<string, array<string, mixed>> */
    private function publishedSolutions(): array
    {
        $fallbacks = is_array($this->language['financial_solutions'] ?? null)
            ? $this->language['financial_solutions']
            : [];
        $solutions = $fallbacks;
        foreach ($this->content->publishedByKeyPrefix('expertises', 'fr', 'solution.', 20) as $row) {
            $slug = is_string($row['slug'] ?? null) ? $row['slug'] : '';
            if ($slug === '' || !isset($fallbacks[$slug])) {
                continue;
            }
            $mapped = $fallbacks[$slug];
            foreach (
                [
                    'title' => 'title',
                    'name' => 'title',
                    'summary' => 'summary',
                    'eyebrow' => 'eyebrow',
                    'problem_text' => 'problem_text',
                    'positioning_text' => 'positioning_text',
                    'seo_title' => 'seo_title',
                    'seo_description' => 'seo_description',
                ] as $target => $source
            ) {
                $value = trim((string) ($row[$source] ?? ''));
                if ($value !== '') {
                    $mapped[$target] = $value;
                }
            }
            $mapped['blocks'] = $this->contentMapper->blocks((string) ($row['blocks_json'] ?? ''));
            $mapped['status'] = (string) ($row['status'] ?? 'published');
            $solutions[$slug] = $mapped;
        }
        return $solutions;
    }

    /** @return list<array<string, mixed>> */
    private function publishedArticles(int $limit, bool $financialFirst): array
    {
        $fetchLimit = $financialFirst ? max(100, $limit) : $limit;
        $articles = array_map(
            fn (array $row): array => $this->mapArticle($row),
            $this->content->paginate('articles', 'fr', 'published', 1, $fetchLimit)
        );
        if ($financialFirst) {
            usort($articles, static fn (array $left, array $right): int =>
                (isset(self::ARTICLE_CATEGORIES[(string) ($right['category_key'] ?? '')]) ? 1 : 0)
                <=> (isset(self::ARTICLE_CATEGORIES[(string) ($left['category_key'] ?? '')]) ? 1 : 0));
        }
        return array_slice($articles, 0, $limit);
    }

    /** @return list<array<string, mixed>> */
    private function publishedCases(int $limit): array
    {
        return array_map(
            fn (array $row): array => $this->mapCase($row),
            $this->content->paginate('realisations', 'fr', 'published', 1, $limit)
        );
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapArticle(array $row): array
    {
        $published = !empty($row['published_at'])
            ? new DateTimeImmutable((string) $row['published_at'], new DateTimeZone('UTC'))
            : null;
        $categoryKey = (string) ($row['category_key'] ?? '');
        $category = self::ARTICLE_CATEGORIES[$categoryKey]
            ?? (trim((string) ($row['eyebrow'] ?? '')) ?: 'Bibliothèque technique');
        return $row + [
            'category_key' => $categoryKey,
            'category' => $category,
            'excerpt' => (string) ($row['summary'] ?? ''),
            'reading_time' => (string) ($row['reading_minutes'] ?? 6) . ' min',
            'blocks' => $this->contentMapper->blocks((string) ($row['blocks_json'] ?? '')),
            'published_label' => $published?->format('d.m.Y'),
            'published_iso' => $published?->format(DATE_ATOM),
        ];
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapCase(array $row): array
    {
        return $row + ['blocks' => $this->contentMapper->blocks((string) ($row['blocks_json'] ?? ''))];
    }

    private function legalPage(string $template, string $title, Request $request): Response
    {
        $ready = $this->legalProfile->isPublicReady();
        return $this->page($template, $request, [
            'title' => $title,
            'description' => 'Informations légales et conditions de traitement des données du site SCTECH.',
            'robots' => $ready ? 'index,follow' : 'noindex,nofollow',
            'legal' => $ready ? $this->legalProfile->data() : [],
            'legalReady' => $ready,
            'pageClass' => 'page-legal',
        ]);
    }

    /** @param array<string, mixed> $content */
    private function seoTitle(array $content, string $fallback): string
    {
        $seo = trim((string) ($content['seo_title'] ?? ''));
        return $seo !== '' ? $seo : $fallback;
    }

    /** @param array<string, mixed> $content */
    private function seoDescription(array $content, string $fallback): string
    {
        $seo = trim((string) ($content['seo_description'] ?? ''));
        return $seo !== '' ? $seo : $fallback;
    }

    /** @return array<string, mixed> */
    private function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org', '@type' => 'Organization',
            'name' => 'SCTECH', 'url' => rtrim($this->config->string('app.url'), '/'),
            'email' => 'contact@sctech.ma',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Casablanca', 'addressCountry' => 'MA'],
            'areaServed' => ['Maroc', 'Afrique', 'Europe'],
        ];
    }

    /** @return array<string, mixed> */
    private function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org', '@type' => 'WebSite',
            'name' => 'SCTECH', 'url' => rtrim($this->config->string('app.url'), '/') . '/',
            'inLanguage' => 'fr',
        ];
    }

    /** @param array<string, mixed> $article
     * @return array<string, mixed>
     */
    private function articleSchema(array $article): array
    {
        $schema = [
            '@context' => 'https://schema.org', '@type' => 'Article',
            'headline' => (string) ($article['title'] ?? ''),
            'description' => (string) ($article['excerpt'] ?? ''),
            'inLanguage' => 'fr',
            'author' => ['@type' => 'Organization', 'name' => 'SCTECH'],
            'publisher' => ['@type' => 'Organization', 'name' => 'SCTECH'],
        ];
        if (!empty($article['published_iso'])) {
            $schema['datePublished'] = $article['published_iso'];
        }
        return $schema;
    }
}
