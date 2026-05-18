<?php

namespace App\Services\Resume;

/**
 * Classifies candidate technical domain from parsed resume content (not JD).
 */
class ResumeDomainClassifier
{
    private const DOMAIN_SIGNALS = [
        'design'    => ['ui designer', 'ux designer', 'figma', 'sketch', 'wireframe', 'visual design', 'graphic design', 'prototyping'],
        'backend'   => ['backend', 'laravel', 'django', 'spring', 'node.js', 'api', 'rest api', 'mysql', 'postgresql', 'microservices', 'server-side'],
        'frontend'  => ['frontend', 'react', 'vue', 'angular', 'css', 'html', 'javascript', 'typescript', 'responsive'],
        'fullstack' => ['full stack', 'fullstack', 'full-stack', 'mern', 'mean'],
        'mobile'    => ['flutter', 'react native', 'android', 'ios', 'kotlin', 'swift', 'mobile'],
        'devops'    => ['devops', 'docker', 'kubernetes', 'terraform', 'ci/cd', 'aws', 'infrastructure'],
        'data'      => ['machine learning', 'data science', 'tensorflow', 'pytorch', 'pandas', 'nlp'],
        'hr'        => ['human resources', 'recruitment', 'talent acquisition', 'hr '],
    ];

    private const DOMAIN_LABELS = [
        'backend'   => 'Backend Engineer',
        'frontend'  => 'Frontend Engineer',
        'fullstack' => 'Full Stack Engineer',
        'mobile'    => 'Mobile Engineer',
        'devops'    => 'DevOps Engineer',
        'data'      => 'Data / ML Engineer',
        'design'    => 'UI/UX Designer',
        'hr'        => 'HR Professional',
        'general'   => 'Software Engineer',
    ];

    private const FORBIDDEN_BY_DOMAIN = [
        'backend'   => ['UI Designer', 'UX Designer', 'Graphic Designer', 'Marketing', 'Visual Designer'],
        'frontend'  => ['Graphic Designer', 'Marketing', 'HR Manager'],
        'fullstack' => ['Graphic Designer', 'Marketing', 'HR Manager'],
        'mobile'    => ['Graphic Designer', 'Marketing'],
        'devops'    => ['UI Designer', 'Graphic Designer', 'Marketing'],
        'data'      => ['UI Designer', 'Graphic Designer', 'Marketing'],
        'design'    => ['Backend Engineer', 'DevOps Engineer'],
        'general'   => ['Graphic Designer', 'Marketing'],
    ];

    /**
     * @return array{
     *   role_category: string,
     *   candidate_type: string,
     *   primary_domain: string,
     *   core_stack: array<int, string>,
     *   experience_level: string,
     *   architecture_depth: string,
     *   forbidden_domains: array<int, string>,
     *   confidence: int
     * }
     */
    public function classify(array $parsedResume): array
    {
        $corpus = $this->buildCorpus($parsedResume);
        $scores = [];

        foreach (self::DOMAIN_SIGNALS as $domain => $signals) {
            $hits = 0;
            foreach ($signals as $signal) {
                if (str_contains($corpus, strtolower($signal))) {
                    $hits++;
                }
            }
            $scores[$domain] = $hits;
        }

        arsort($scores);
        $topDomain = array_key_first($scores) ?: 'general';
        $topScore  = $scores[$topDomain] ?? 0;

        if ($topScore < 2) {
            $topDomain = $this->inferFromTitles($parsedResume) ?? 'general';
        }

        $coreStack = $this->extractCoreStack($parsedResume);
        $archDepth = $this->architectureDepth($corpus);

        return [
            'role_category'      => $topDomain,
            'candidate_type'     => self::DOMAIN_LABELS[$topDomain] ?? self::DOMAIN_LABELS['general'],
            'primary_domain'     => $this->primaryDomainLabel($topDomain),
            'core_stack'         => $coreStack,
            'experience_level'   => $this->experienceLevel($parsedResume),
            'architecture_depth' => $archDepth,
            'forbidden_domains'  => self::FORBIDDEN_BY_DOMAIN[$topDomain] ?? self::FORBIDDEN_BY_DOMAIN['general'],
            'confidence'         => min(100, max(20, $topScore * 15)),
        ];
    }

    public function isCrossDomain(array $identity, array $jdAnalysis): bool
    {
        $resumeDomain = strtolower((string) ($identity['role_category'] ?? 'general'));
        $jdDomain     = strtolower((string) ($jdAnalysis['role_category'] ?? 'general'));

        if ($resumeDomain === $jdDomain) {
            return false;
        }

        $compatible = [
            'fullstack' => ['backend', 'frontend', 'fullstack', 'internship', 'general'],
            'backend'   => ['fullstack', 'devops', 'data', 'internship', 'general'],
            'frontend'  => ['fullstack', 'design', 'internship', 'general'],
            'internship'=> ['backend', 'frontend', 'fullstack', 'mobile', 'data', 'design', 'general'],
            'general'   => ['internship', 'general'],
        ];

        $allowed = $compatible[$resumeDomain] ?? [];

        return !in_array($jdDomain, $allowed, true);
    }

    private function buildCorpus(array $parsed): string
    {
        $parts = [
            $parsed['summary'] ?? '',
            $parsed['raw_text'] ?? '',
            implode(' ', $parsed['skills'] ?? []),
        ];

        foreach ($parsed['experience'] ?? [] as $exp) {
            $parts[] = ($exp['title'] ?? '') . ' ' . ($exp['org'] ?? '');
            foreach ($exp['bullets'] ?? [] as $b) {
                $parts[] = $b;
            }
        }

        foreach ($parsed['projects'] ?? [] as $proj) {
            $parts[] = ($proj['title'] ?? '') . ' ' . ($proj['tech'] ?? '');
            foreach ($proj['bullets'] ?? [] as $b) {
                $parts[] = $b;
            }
        }

        return strtolower(implode(' ', $parts));
    }

  /**
     * @return array<int, string>
     */
    private function extractCoreStack(array $parsed): array
    {
        $known = [
            'laravel', 'php', 'node.js', 'nodejs', 'react', 'vue', 'python', 'java',
            'mysql', 'postgresql', 'mongodb', 'redis', 'docker', 'aws', 'typescript',
            'javascript', 'go', 'rust', 'kubernetes', 'figma',
        ];

        $corpus = $this->buildCorpus($parsed);
        $found  = [];

        foreach ($known as $tech) {
            if (str_contains($corpus, $tech)) {
                $found[] = ucfirst(str_replace(['node.js', 'nodejs'], 'Node.js', $tech));
            }
        }

        return array_slice(array_unique($found), 0, 8);
    }

    private function architectureDepth(string $corpus): string
    {
        $signals = ['architected', 'scalable', 'microservices', 'system design', 'distributed', 'architecture'];
        $hits = 0;
        foreach ($signals as $s) {
            if (str_contains($corpus, $s)) {
                $hits++;
            }
        }

        if ($hits >= 3) {
            return 'high';
        }
        if ($hits >= 1) {
            return 'medium';
        }

        return 'low';
    }

    private function experienceLevel(array $parsed): string
    {
        $count = count($parsed['experience'] ?? []);
        $text  = strtolower($parsed['raw_text'] ?? '');

        if (preg_match('/\b(senior|lead|principal|staff)\b/i', $text)) {
            return 'senior';
        }
        if ($count >= 3) {
            return 'mid';
        }
        if ($count >= 1) {
            return 'junior';
        }

        return 'entry';
    }

    private function primaryDomainLabel(string $domain): string
    {
        return match ($domain) {
            'backend'   => 'Backend Engineering',
            'frontend'  => 'Frontend Engineering',
            'fullstack' => 'Full Stack Engineering',
            'mobile'    => 'Mobile Engineering',
            'devops'    => 'DevOps / Infrastructure',
            'data'      => 'Data & Machine Learning',
            'design'    => 'Product & Visual Design',
            'hr'        => 'Human Resources',
            default     => 'Software Engineering',
        };
    }

    private function inferFromTitles(array $parsed): ?string
    {
        foreach ($parsed['experience'] ?? [] as $exp) {
            $title = strtolower((string) ($exp['title'] ?? ''));
            if (preg_match('/\b(backend|back-end)\b/', $title)) {
                return 'backend';
            }
            if (preg_match('/\b(frontend|front-end)\b/', $title)) {
                return 'frontend';
            }
            if (preg_match('/\bfull[\s-]?stack\b/', $title)) {
                return 'fullstack';
            }
            if (preg_match('/\b(ui|ux|designer)\b/', $title)) {
                return 'design';
            }
        }

        return null;
    }
}
