<?php

namespace App\Services\Resume;

/**
 * Synonym-aware semantic skill matching for ATS scoring and weakness detection.
 *
 * Replaces literal-only keyword checks with recruiter-term abstraction:
 * e.g. "debugging" satisfies "problem solving", "architecture" satisfies "system design".
 */
class SemanticSkillMatcher
{
    /** @var array<string, array<int, string>>|null */
    private static ?array $mergedMap = null;

    /**
     * Default recruiter-term → evidence clusters (config can extend/override).
     *
     * Configurable via config/services.php:
     *   'resume_optimizer' => [
     *       'semantic_skill_map' => [
     *           'my custom skill' => ['synonym1', 'synonym2'],
     *       ],
     *   ]
     *
     * @var array<string, array<int, string>>
     */
    private const DEFAULT_SEMANTIC_SKILL_MAP = [
        // ── Problem Solving / Analytical ─────────────────────────────────
        'problem solving' => [
            'debugging', 'troubleshooting', 'root cause', 'root-cause', 'incident',
            'resolved production', 'diagnosed', 'fixed critical', 'issue resolution',
            'problem-solving', 'analytical', 'investigated', 'analysis', 'investigate',
            'triage', 'triaged', 'resolved', 'debugged', 'traced', 'profiled',
        ],
        'analytical thinking' => [
            'analytical', 'analysis', 'analytical approach', 'data-driven', 'data driven',
            'debugging', 'troubleshooting', 'root cause', 'investigated', 'diagnosed',
            'problem solving', 'problem-solving', 'critical thinking', 'evaluated',
            'assessed', 'researched', 'metrics', 'measured', 'profiling',
        ],
        'critical thinking' => [
            'analytical', 'analysis', 'evaluated', 'assessed', 'researched',
            'problem solving', 'debugging', 'root cause', 'data-driven',
        ],

        // ── Software Craftsmanship / Code Quality ─────────────────────────
        'software craftsmanship' => [
            'clean code', 'solid', 'solid principles', 'design patterns', 'refactoring',
            'refactored', 'code review', 'code reviews', 'tdd', 'test-driven',
            'test driven development', 'software craftsmanship', 'craftsmanship',
            'best practices', 'maintainable', 'readable code', 'code quality',
            'clean architecture', 'dry principle', 'kiss principle', 'yagni',
        ],
        'code quality' => [
            'code quality', 'code quality metrics', 'clean code', 'solid', 'refactoring',
            'refactored', 'code review', 'code reviews', 'linting', 'static analysis',
            'sonarqube', 'phpcs', 'phpstan', 'psalm', 'eslint', 'test coverage',
            'maintainable', 'readable', 'best practices', 'software craftsmanship',
            'design patterns', 'tdd', 'bdd',
        ],
        'clean code' => [
            'clean code', 'solid', 'refactoring', 'refactored', 'code review',
            'design patterns', 'maintainable', 'readable', 'best practices',
            'software craftsmanship', 'code quality',
        ],

        // ── System Design / Architecture ──────────────────────────────────
        'system design' => [
            'architecture', 'architected', 'system design', 'scalable', 'scalable systems',
            'distributed', 'microservices', 'high availability', 'fault tolerant',
            'api design', 'infrastructure design', 'system architecture', 'designed system',
            'event-driven', 'event driven', 'domain-driven', 'ddd', 'cqrs', 'saga',
            'service mesh', 'load balancing', 'sharding', 'replication',
        ],
        'architecture' => [
            'architecture', 'architected', 'system design', 'scalable', 'distributed',
            'microservices', 'high availability', 'api design', 'infrastructure design',
            'system architecture', 'designed system', 'event-driven', 'ddd',
        ],
        'software architecture' => [
            'architecture', 'architected', 'system design', 'scalable', 'distributed',
            'microservices', 'api design', 'infrastructure design', 'system architecture',
            'clean architecture', 'hexagonal', 'layered architecture', 'mvc', 'mvvm',
        ],

        // ── Backend Engineering ───────────────────────────────────────────
        'backend engineering' => [
            'rest api', 'rest apis', 'restful', 'backend', 'server-side', 'laravel',
            'node.js', 'nodejs', 'api development', 'database', 'mysql', 'postgresql',
            'endpoint', 'microservice', 'production engineering', 'php', 'python',
            'java', 'golang', 'go', 'ruby', 'rails', 'django', 'flask', 'spring',
            'express', 'fastapi', 'graphql', 'grpc', 'message queue', 'rabbitmq',
            'kafka', 'redis', 'caching', 'orm', 'eloquent', 'prisma',
        ],
        'backend development' => [
            'rest api', 'rest apis', 'restful', 'backend', 'server-side', 'laravel',
            'node.js', 'nodejs', 'api development', 'database', 'mysql', 'postgresql',
            'endpoint', 'microservice', 'php', 'python', 'java', 'golang', 'express',
            'graphql', 'grpc', 'redis', 'caching', 'orm',
        ],
        'api development' => [
            'rest api', 'rest apis', 'restful', 'graphql', 'grpc', 'api design',
            'endpoint', 'api development', 'openapi', 'swagger', 'postman',
            'api gateway', 'webhook', 'oauth', 'jwt', 'authentication',
        ],
        'server-side development' => [
            'backend', 'server-side', 'rest api', 'laravel', 'node.js', 'php',
            'python', 'java', 'golang', 'express', 'django', 'flask', 'spring',
        ],

        // ── Frontend Engineering ──────────────────────────────────────────
        'frontend engineering' => [
            'react', 'vue', 'angular', 'javascript', 'typescript', 'css', 'html',
            'responsive', 'ui development', 'component', 'frontend', 'front-end',
            'next.js', 'nextjs', 'nuxt', 'svelte', 'tailwind', 'bootstrap',
            'webpack', 'vite', 'sass', 'scss', 'accessibility', 'wcag',
            'spa', 'pwa', 'web components', 'dom', 'browser api',
        ],
        'frontend development' => [
            'react', 'vue', 'angular', 'javascript', 'typescript', 'css', 'html',
            'responsive', 'ui development', 'component', 'frontend', 'front-end',
            'next.js', 'nextjs', 'nuxt', 'svelte', 'tailwind', 'bootstrap',
        ],
        'ui development' => [
            'react', 'vue', 'angular', 'javascript', 'typescript', 'css', 'html',
            'responsive', 'component', 'frontend', 'front-end', 'ui', 'ux',
            'tailwind', 'bootstrap', 'sass', 'scss', 'design system',
        ],

        // ── Full Stack ────────────────────────────────────────────────────
        'full stack' => [
            'full stack', 'full-stack', 'fullstack', 'end-to-end', 'mern', 'mean',
            'full stack development', 'full-stack development', 'fullstack development',
            'frontend and backend', 'backend and frontend', 'client and server',
        ],
        'full stack development' => [
            'full stack', 'full-stack', 'fullstack', 'end-to-end', 'mern', 'mean',
            'full stack development', 'full-stack development', 'fullstack development',
            'frontend and backend', 'backend and frontend',
        ],

        // ── DevOps / Infrastructure ───────────────────────────────────────
        'devops' => [
            'docker', 'kubernetes', 'ci/cd', 'aws', 'deployment', 'jenkins',
            'terraform', 'infrastructure', 'devops', 'github actions', 'gitlab ci',
            'circleci', 'ansible', 'helm', 'argocd', 'monitoring', 'observability',
            'prometheus', 'grafana', 'elk', 'datadog', 'infrastructure as code',
            'iac', 'cloud', 'azure', 'gcp', 'containerization', 'orchestration',
        ],
        'cloud engineering' => [
            'aws', 'azure', 'gcp', 'cloud', 'terraform', 'kubernetes', 'docker',
            'infrastructure as code', 'iac', 'serverless', 'lambda', 'cloud functions',
            's3', 'ec2', 'rds', 'cloudformation', 'pulumi',
        ],
        'infrastructure' => [
            'docker', 'kubernetes', 'terraform', 'ansible', 'aws', 'azure', 'gcp',
            'infrastructure', 'infrastructure as code', 'iac', 'cloud', 'deployment',
            'ci/cd', 'jenkins', 'github actions', 'helm', 'argocd',
        ],
        'continuous integration' => [
            'ci/cd', 'jenkins', 'github actions', 'gitlab ci', 'circleci', 'travis',
            'continuous integration', 'continuous deployment', 'pipeline', 'automated deployment',
        ],
        'continuous deployment' => [
            'ci/cd', 'jenkins', 'github actions', 'gitlab ci', 'circleci',
            'continuous deployment', 'continuous delivery', 'automated deployment',
            'deployment pipeline', 'blue-green', 'canary deployment',
        ],

        // ── Data Engineering ──────────────────────────────────────────────
        'data engineering' => [
            'etl', 'data pipeline', 'data warehouse', 'spark', 'hadoop', 'kafka',
            'airflow', 'dbt', 'snowflake', 'bigquery', 'redshift', 'databricks',
            'data lake', 'data modeling', 'sql', 'postgresql', 'mysql', 'nosql',
            'mongodb', 'elasticsearch', 'data engineering', 'batch processing',
            'stream processing', 'flink', 'beam',
        ],
        'data analysis' => [
            'data analysis', 'analytics', 'sql', 'python', 'pandas', 'numpy',
            'tableau', 'power bi', 'excel', 'data visualization', 'reporting',
            'metrics', 'kpi', 'dashboard', 'statistical analysis', 'r',
        ],
        'machine learning' => [
            'machine learning', 'ml', 'deep learning', 'neural network', 'tensorflow',
            'pytorch', 'scikit-learn', 'sklearn', 'nlp', 'computer vision',
            'model training', 'feature engineering', 'data science', 'ai',
        ],

        // ── Communication / Collaboration ─────────────────────────────────
        'communication' => [
            'collaborated', 'presented', 'documented', 'stakeholder', 'cross-functional',
            'mentored', 'facilitated', 'communicated', 'written communication',
            'verbal communication', 'technical writing', 'documentation',
        ],
        'teamwork' => [
            'team', 'collaborated', 'paired', 'cross-functional', 'agile', 'scrum',
            'pair programming', 'mob programming', 'team player', 'cooperative',
        ],
        'collaboration' => [
            'collaborated', 'team', 'cross-functional', 'agile', 'scrum', 'paired',
            'stakeholder', 'cross-team', 'partnership', 'coordinated',
        ],

        // ── Leadership / Management ───────────────────────────────────────
        'leadership' => [
            'led', 'managed', 'mentored', 'spearheaded', 'directed', 'owned',
            'team lead', 'tech lead', 'technical lead', 'engineering manager',
            'supervised', 'guided', 'coached', 'drove', 'championed',
        ],
        'project management' => [
            'managed', 'led', 'delivered', 'planned', 'coordinated', 'agile',
            'scrum', 'kanban', 'sprint', 'roadmap', 'milestone', 'stakeholder',
            'project management', 'pm', 'jira', 'confluence', 'trello',
        ],
        'mentoring' => [
            'mentored', 'coached', 'guided', 'trained', 'onboarded', 'supervised',
            'knowledge transfer', 'pair programming', 'code review',
        ],

        // ── Optimization / Performance ────────────────────────────────────
        'optimization' => [
            'optimized', 'performance', 'latency', 'throughput', 'efficiency',
            'query optimization', 'caching', 'reduced load', 'improved performance',
            'profiling', 'benchmarking', 'reduced latency', 'increased throughput',
        ],
        'performance optimization' => [
            'optimized', 'performance', 'latency', 'throughput', 'efficiency',
            'query optimization', 'caching', 'reduced load', 'profiling',
            'benchmarking', 'reduced latency', 'increased throughput', 'load testing',
        ],

        // ── Testing / Quality Assurance ───────────────────────────────────
        'testing' => [
            'unit test', 'integration test', 'phpunit', 'jest', 'tdd', 'qa',
            'test coverage', 'automated test', 'e2e', 'end-to-end test', 'cypress',
            'playwright', 'selenium', 'bdd', 'cucumber', 'mocha', 'vitest',
        ],
        'test-driven development' => [
            'tdd', 'test-driven', 'test driven development', 'unit test', 'phpunit',
            'jest', 'vitest', 'mocha', 'test coverage', 'red-green-refactor',
        ],
        'quality assurance' => [
            'qa', 'quality assurance', 'testing', 'unit test', 'integration test',
            'e2e', 'automated test', 'test coverage', 'regression testing',
            'manual testing', 'test plan', 'bug tracking',
        ],

        // ── Data Structures / Algorithms ──────────────────────────────────
        'data structures' => [
            'algorithm', 'data structure', 'complexity', 'oop', 'recursion',
            'binary tree', 'graph', 'hash map', 'linked list', 'sorting',
            'searching', 'dynamic programming', 'big o',
        ],
        'algorithms' => [
            'algorithm', 'data structure', 'complexity', 'recursion', 'sorting',
            'searching', 'dynamic programming', 'big o', 'leetcode', 'competitive programming',
        ],

        // ── Security ─────────────────────────────────────────────────────
        'security' => [
            'authentication', 'authorization', 'oauth', 'jwt', 'ssl', 'tls',
            'encryption', 'hashing', 'xss', 'csrf', 'sql injection', 'owasp',
            'penetration testing', 'security audit', 'vulnerability', 'firewall',
        ],
        'cybersecurity' => [
            'security', 'authentication', 'authorization', 'oauth', 'jwt', 'ssl',
            'encryption', 'hashing', 'xss', 'csrf', 'owasp', 'penetration testing',
            'vulnerability', 'firewall', 'intrusion detection',
        ],

        // ── Database ──────────────────────────────────────────────────────
        'database management' => [
            'mysql', 'postgresql', 'sqlite', 'mongodb', 'redis', 'elasticsearch',
            'database', 'sql', 'nosql', 'orm', 'eloquent', 'prisma', 'sequelize',
            'query optimization', 'indexing', 'migration', 'schema design',
        ],
        'sql' => [
            'sql', 'mysql', 'postgresql', 'sqlite', 'oracle', 'mssql',
            'database', 'query', 'stored procedure', 'trigger', 'indexing',
            'join', 'transaction', 'normalization',
        ],

        // ── Agile / Methodologies ─────────────────────────────────────────
        'agile' => [
            'agile', 'scrum', 'kanban', 'sprint', 'standup', 'retrospective',
            'backlog', 'user story', 'velocity', 'iteration', 'lean',
        ],
        'scrum' => [
            'scrum', 'agile', 'sprint', 'standup', 'retrospective', 'backlog',
            'user story', 'velocity', 'scrum master', 'product owner',
        ],
    ];

    /**
     * @return array{matched: bool, via: string, evidence: array<int, string>}
     */
    public function evaluate(string $requiredSkill, string $resumeText, array $jdAnalysis = []): array
    {
        $skill = $this->normalizeToken($requiredSkill);
        $text  = strtolower($resumeText);

        if ($skill === '' || $text === '') {
            return ['matched' => false, 'via' => 'none', 'evidence' => []];
        }

        if ($this->literalMatch($skill, $text)) {
            return ['matched' => true, 'via' => 'literal', 'evidence' => [$skill]];
        }

        $aliases = $this->aliasesForSkill($skill);
        foreach ($aliases as $alias) {
            if ($this->literalMatch($alias, $text)) {
                return ['matched' => true, 'via' => 'alias', 'evidence' => [$alias]];
            }
        }

        $map = $this->buildMergedMap($jdAnalysis);
        foreach ($map as $recruiterTerm => $evidenceList) {
            if (!$this->termsRelated($skill, $recruiterTerm)) {
                continue;
            }

            foreach ($evidenceList as $evidence) {
                $normalized = $this->normalizeToken($evidence);
                if ($normalized !== '' && $this->literalMatch($normalized, $text)) {
                    return ['matched' => true, 'via' => 'cluster', 'evidence' => [$evidence]];
                }
            }
        }

        $inferred = $this->inferFromEngineeringSignals($skill, $text);
        if ($inferred['matched']) {
            return $inferred;
        }

        return ['matched' => false, 'via' => 'none', 'evidence' => []];
    }

    public function isSatisfied(string $requiredSkill, string $resumeText, array $jdAnalysis = []): bool
    {
        return $this->evaluate($requiredSkill, $resumeText, $jdAnalysis)['matched'];
    }

    /**
     * @param  array<int, string>  $requiredSkills
     * @return array{score: int, matching: array<int, string>, missing: array<int, string>, explanations: array<string, array{via: string, evidence: array<int, string>}>}
     */
    public function scoreSkills(array $requiredSkills, string $resumeText, array $jdAnalysis = []): array
    {
        if (empty($requiredSkills)) {
            return ['score' => 50, 'matching' => [], 'missing' => [], 'explanations' => []];
        }

        $matching = [];
        $missing  = [];
        $explanations = [];

        foreach ($requiredSkills as $skill) {
            $result = $this->evaluate((string) $skill, $resumeText, $jdAnalysis);
            if ($result['matched']) {
                $matching[] = $skill;
                if ($result['via'] !== 'literal') {
                    $explanations[strtolower((string) $skill)] = [
                        'via' => $result['via'],
                        'evidence' => $result['evidence'],
                    ];
                }
            } else {
                $missing[] = $skill;
            }
        }

        $score = (int) round((count($matching) / count($requiredSkills)) * 100);

        return [
            'score' => $score,
            'matching' => $matching,
            'missing' => $missing,
            'explanations' => $explanations,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildMergedMap(array $jdAnalysis): array
    {
        if (self::$mergedMap !== null && empty($jdAnalysis['semantic_clusters'])) {
            return self::$mergedMap;
        }

        $map = self::DEFAULT_SEMANTIC_SKILL_MAP;

        $configMap = config('services.resume_optimizer.semantic_skill_map', []);
        if (is_array($configMap)) {
            foreach ($configMap as $term => $evidence) {
                if (!is_array($evidence)) {
                    continue;
                }
                $key = $this->normalizeToken((string) $term);
                $map[$key] = array_values(array_unique(array_merge(
                    $map[$key] ?? [],
                    array_map(fn ($e) => $this->normalizeToken((string) $e), $evidence)
                )));
            }
        }

        $clusters = $jdAnalysis['semantic_clusters'] ?? [];
        if (is_array($clusters)) {
            foreach ($clusters as $clusterName => $clusterSkills) {
                if (!is_array($clusterSkills)) {
                    continue;
                }
                $key = $this->normalizeToken((string) $clusterName);
                $evidence = array_map(fn ($s) => $this->normalizeToken((string) $s), $clusterSkills);
                $map[$key] = array_values(array_unique(array_merge($map[$key] ?? [], $evidence)));
            }
        }

        self::$mergedMap = $map;

        return $map;
    }

    /**
     * @return array<int, string>
     */
    private function aliasesForSkill(string $skill): array
    {
        $aliases = [];
        $compact = str_replace([' ', '-', '_'], '', $skill);

        if (str_contains($skill, ' ')) {
            $aliases[] = str_replace(' ', '-', $skill);
            $aliases[] = str_replace(' ', '', $skill);
        }

        if (str_contains($skill, '-')) {
            $aliases[] = str_replace('-', ' ', $skill);
        }

        if ($compact !== $skill) {
            $aliases[] = $compact;
        }

        return array_unique(array_filter($aliases));
    }

    /**
     * @return array{matched: bool, via: string, evidence: array<int, string>}
     */
    private function inferFromEngineeringSignals(string $requiredSkill, string $text): array
    {
        $architectureSignals = [
            'architecture', 'architected', 'scalable', 'distributed', 'microservices',
            'system design', 'api design', 'production engineering', 'designed system',
            'event-driven', 'domain-driven', 'ddd', 'cqrs', 'service mesh',
        ];
        $engineeringSignals = [
            'debugging', 'optimized', 'performance', 'rest api', 'rest apis',
            'troubleshooting', 'latency', 'deployment', 'profiling', 'benchmarking',
        ];
        $analyticalSignals = [
            'analytical', 'analysis', 'data-driven', 'data driven', 'debugging',
            'troubleshooting', 'root cause', 'investigated', 'diagnosed', 'metrics',
            'measured', 'evaluated', 'assessed', 'researched',
        ];
        $craftsmanshipSignals = [
            'clean code', 'solid', 'design patterns', 'refactoring', 'refactored',
            'code review', 'tdd', 'test-driven', 'best practices', 'maintainable',
            'code quality', 'readable', 'software craftsmanship',
        ];
        $backendSignals = [
            'rest api', 'rest apis', 'laravel', 'node.js', 'api', 'mysql', 'backend',
            'php', 'python', 'java', 'golang', 'express', 'django', 'flask', 'spring',
            'graphql', 'grpc', 'redis', 'postgresql', 'endpoint', 'server-side',
        ];
        $frontendSignals = [
            'react', 'vue', 'angular', 'javascript', 'typescript', 'css', 'html',
            'frontend', 'front-end', 'component', 'responsive', 'next.js', 'nuxt',
            'svelte', 'tailwind', 'bootstrap', 'ui', 'spa', 'pwa',
        ];
        $devopsSignals = [
            'docker', 'kubernetes', 'ci/cd', 'aws', 'deployment', 'jenkins',
            'terraform', 'infrastructure', 'github actions', 'gitlab ci', 'helm',
            'argocd', 'ansible', 'cloud', 'azure', 'gcp', 'containerization',
        ];
        $dataSignals = [
            'etl', 'data pipeline', 'spark', 'kafka', 'airflow', 'dbt', 'snowflake',
            'bigquery', 'redshift', 'data lake', 'data modeling', 'sql', 'nosql',
            'mongodb', 'elasticsearch', 'batch processing', 'stream processing',
        ];

        $skillNorm = $this->normalizeToken($requiredSkill);

        // ── Compound skill normalization ──────────────────────────────────
        // Normalize compound forms: "full stack development" → "full stack"
        $compoundMap = [
            'full stack development'    => 'full stack',
            'full-stack development'    => 'full stack',
            'fullstack development'     => 'full stack',
            'backend development'       => 'backend engineering',
            'frontend development'      => 'frontend engineering',
            'software development'      => 'backend engineering',
            'web development'           => 'full stack',
            'api development'           => 'backend engineering',
            'server-side development'   => 'backend engineering',
            'cloud infrastructure'      => 'devops',
            'infrastructure engineering'=> 'devops',
            'data pipeline engineering' => 'data engineering',
        ];
        if (isset($compoundMap[$skillNorm])) {
            $skillNorm = $compoundMap[$skillNorm];
        }

        // ── Fuzzy matching: partial token overlap ─────────────────────────
        // If the required skill contains a known root term, infer from signals
        $fuzzyRoots = [
            'analytic'    => $analyticalSignals,
            'craft'       => $craftsmanshipSignals,
            'quality'     => $craftsmanshipSignals,
            'architect'   => $architectureSignals,
            'design'      => $architectureSignals,
            'backend'     => $backendSignals,
            'server'      => $backendSignals,
            'frontend'    => $frontendSignals,
            'front-end'   => $frontendSignals,
            'devops'      => $devopsSignals,
            'cloud'       => $devopsSignals,
            'data'        => $dataSignals,
            'pipeline'    => $dataSignals,
            'debug'       => $engineeringSignals,
            'troubleshoot'=> $engineeringSignals,
            'problem'     => $engineeringSignals,
        ];
        foreach ($fuzzyRoots as $root => $signals) {
            if (str_contains($skillNorm, $root)) {
                foreach ($signals as $sig) {
                    if ($this->literalMatch($sig, $text)) {
                        return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                    }
                }
            }
        }

        // ── Explicit term-to-signal mappings ──────────────────────────────
        if ($this->termsRelated($skillNorm, 'problem solving')) {
            foreach (array_merge($engineeringSignals, ['resolved', 'fixed', 'diagnosed']) as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'analytical thinking') || $this->termsRelated($skillNorm, 'analytical')) {
            foreach ($analyticalSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'software craftsmanship') || $this->termsRelated($skillNorm, 'code quality')) {
            foreach ($craftsmanshipSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'system design') || $this->termsRelated($skillNorm, 'architecture')) {
            foreach ($architectureSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'backend engineering') || $this->termsRelated($skillNorm, 'backend')) {
            foreach ($backendSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'frontend engineering') || $this->termsRelated($skillNorm, 'frontend')) {
            foreach ($frontendSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'full stack') || $this->termsRelated($skillNorm, 'fullstack')) {
            foreach (array_merge($backendSignals, $frontendSignals) as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'devops') || $this->termsRelated($skillNorm, 'infrastructure')) {
            foreach ($devopsSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        if ($this->termsRelated($skillNorm, 'data engineering') || $this->termsRelated($skillNorm, 'data pipeline')) {
            foreach ($dataSignals as $sig) {
                if ($this->literalMatch($sig, $text)) {
                    return ['matched' => true, 'via' => 'inferred', 'evidence' => [$sig]];
                }
            }
        }

        return ['matched' => false, 'via' => 'none', 'evidence' => []];
    }

    private function termsRelated(string $a, string $b): bool
    {
        $a = $this->normalizeToken($a);
        $b = $this->normalizeToken($b);

        if ($a === $b) {
            return true;
        }

        if (str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }

        $aTokens = explode(' ', $a);
        $bTokens = explode(' ', $b);
        $overlap = count(array_intersect($aTokens, $bTokens));

        return $overlap >= min(count($aTokens), count($bTokens)) && $overlap > 0;
    }

    private function literalMatch(string $needle, string $haystack): bool
    {
        $needle = $this->normalizeToken($needle);
        if ($needle === '') {
            return false;
        }

        // Lowercase haystack for case-insensitive comparison
        $haystack = strtolower($haystack);

        // Special characters: C++, C#, .NET, Node.js, etc.
        // Use delimiter-aware matching instead of \b (which doesn't work with non-word chars)
        if (preg_match('/[^\w\s]/', $needle)) {
            $escaped = preg_quote($needle, '/');
            // Match at word/delimiter boundaries: start/end of string, whitespace, punctuation
            if (preg_match('/(^|[\s,;|()\[\]\/])' . $escaped . '($|[\s,;|()\[\]\/])/i', $haystack)) {
                return true;
            }
            // Also try substring match for special-char skills (e.g., "node.js" in "using node.js framework")
            if (str_contains($haystack, $needle)) {
                return true;
            }
            return false;
        }

        // Multi-word phrases: use word boundary matching
        if (str_word_count($needle) > 1) {
            // Primary: word boundary regex
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/i', $haystack)) {
                return true;
            }
            // Fallback: direct substring (handles hyphenated or concatenated variants)
            if (str_contains($haystack, $needle)) {
                return true;
            }
            return false;
        }

        // Single word: standard word boundary match
        if (preg_match('/\b' . preg_quote($needle, '/') . '\b/i', $haystack)) {
            return true;
        }

        return false;
    }

    private function normalizeToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        // Normalize common special-character skill name variants
        $value = str_replace(['c plus plus', 'cplusplus', 'c plus+'], 'c++', $value);
        $value = str_replace(['node js', 'nodejs', 'node_js'], 'node.js', $value);
        $value = str_replace(['dot net', 'dotnet', 'dot_net'], '.net', $value);
        $value = str_replace(['c sharp', 'csharp', 'c_sharp'], 'c#', $value);
        $value = str_replace(['asp net', 'aspnet', 'asp.net'], 'asp.net', $value);
        $value = str_replace(['vue js', 'vuejs', 'vue_js'], 'vue', $value);
        $value = str_replace(['react js', 'reactjs', 'react_js'], 'react', $value);
        $value = str_replace(['next js', 'nextjs', 'next_js'], 'next.js', $value);
        $value = str_replace(['angular js', 'angularjs'], 'angular', $value);

        return $value;
    }

    /** Reset cached map (for tests). */
    public static function resetCache(): void
    {
        self::$mergedMap = null;
    }
}
