<?php

namespace App\Services\Resume;

use App\Models\Internship;

/**
 * JobDescriptionAnalyzer — Step 3 of the Resume Intelligence Pipeline.
 *
 * Extracts intelligence from job descriptions:
 *   - Role classification (Backend, Frontend, FullStack, HR, AI/ML, Internship)
 *   - Required skill extraction
 *   - Keyword density analysis
 *   - Action verb extraction
 *   - Experience level expectations
 *   - Recruiter terminology
 */
class JobDescriptionAnalyzer
{
    use \App\Traits\ResilientJsonTrait;

    public function __construct(
        private ?N8nOrchestrator $n8n = null,
    ) {}
    // ── Role Classification Signals ───────────────────────────────────────

    private const ROLE_SIGNALS = [
        'design'     => ['figma', 'sketch', 'adobe xd', 'wireframe', 'prototyping', 'ui designer', 'ux designer', 'visual design', 'user interface', 'user experience', 'design system', 'typography', 'design thinking', 'interaction design', 'ui/ux'],
        'backend'    => ['laravel', 'django', 'spring', 'node', 'api', 'rest', 'graphql', 'microservices', 'mysql', 'postgresql', 'redis', 'php', 'python', 'java', 'golang', 'backend', 'server-side'],
        'frontend'   => ['react', 'vue', 'angular', 'next', 'nuxt', 'svelte', 'tailwind', 'css', 'html', 'javascript', 'typescript', 'responsive', 'frontend', 'web development'],
        'fullstack'  => ['full stack', 'fullstack', 'full-stack', 'mern', 'mean', 'lamp', 'end-to-end', 'both frontend and backend', 'both back-end and front-end'],
        'mobile'     => ['android', 'ios', 'flutter', 'react native', 'kotlin', 'swift', 'mobile app', 'play store', 'app store'],
        'devops'     => ['docker', 'kubernetes', 'ci/cd', 'jenkins', 'terraform', 'aws', 'azure', 'gcp', 'cloud', 'devops', 'deployment', 'infrastructure'],
        'data'       => ['machine learning', 'ml', 'deep learning', 'nlp', 'data science', 'pandas', 'numpy', 'tensorflow', 'pytorch', 'sklearn', 'analytics', 'data pipeline'],
        'hr'         => ['recruitment', 'talent acquisition', 'onboarding', 'hr', 'human resources', 'hiring', 'payroll', 'employee relations'],
        'internship' => ['intern', 'internship', 'fresher', 'entry-level', '0-1 year', 'student', 'graduate'],
    ];

    // ── Strong Action Verbs by Category ───────────────────────────────────

    private const ACTION_VERBS = [
        'technical'  => ['developed', 'built', 'implemented', 'architected', 'deployed', 'integrated', 'optimized', 'engineered', 'designed', 'migrated', 'refactored', 'automated', 'created', 'established'],
        'leadership' => ['led', 'managed', 'directed', 'coordinated', 'spearheaded', 'mentored', 'supervised', 'orchestrated', 'championed'],
        'impact'     => ['improved', 'increased', 'reduced', 'enhanced', 'accelerated', 'streamlined', 'transformed', 'boosted', 'scaled'],
        'analytical' => ['analyzed', 'researched', 'evaluated', 'identified', 'diagnosed', 'assessed', 'monitored', 'measured'],
    ];

    // ── Stop Words for Keyword Extraction ─────────────────────────────────

    private const STOP_WORDS = [
        'the','a','an','and','or','in','at','for','to','of','is','are','will','be',
        'with','as','on','this','that','our','your','we','you','have','has','can',
        'must','should','would','able','work','team','good','strong','knowledge',
        'experience','required','preferred','looking','join','role','position',
        'using','develop','build','manage','ensure','provide','support','maintain',
        'create','make','help','need','new','also','well','both','all','any',
        'other','more','into','from','about','over','such','like','than','its',
        'please','candidate','responsibilities','requirements','qualifications',
    ];

    /* ------------------------------------------------------------------ */
    /*  Public API                                                          */
    /* ------------------------------------------------------------------ */

    public function analyze(Internship $internship): array
    {
        $baseAnalysis = $this->ruleBasedAnalyze($internship);

        $n8nResult = ($this->n8n ?? new N8nOrchestrator())->dispatch(
            $this->buildAiSystemPrompt(),
            "Analyze the following Job Description:\nTitle: {$baseAnalysis['job_title']}\nDescription: {$baseAnalysis['description_raw']}"
        );

        if ($n8nResult['success'] && !empty($n8nResult['data'])) {
            \Illuminate\Support\Facades\Log::info('JdAnalyzer: AI Analysis success', [
                'role' => $n8nResult['data']['role_category'] ?? 'unknown',
                'attempts' => $n8nResult['attempts'],
            ]);

            return $this->parseAiJdReport($n8nResult['data'], $baseAnalysis);
        }

        \Illuminate\Support\Facades\Log::warning('JdAnalyzer: Falling back to rule-based analysis');
        return $baseAnalysis;
    }

    private function buildAiSystemPrompt(): string
    {
        return <<<PROMPT
You are a Senior Job Description Intelligence Engineer, ATS Semantic Matching Specialist, Recruiter Psychology Analyst, and NLP Resume Strategist.

PRIMARY OBJECTIVE:
Deeply understand the job description before ANY resume optimization begins. You must infer the hidden recruiter intent, true technical stack, and semantic expectations.

CRITICAL RULES:
- Determine what the recruiter ACTUALLY wants, not just repeated keywords.
- Classify the role (Frontend, Backend, Full Stack, DevOps, Data, etc.).
- Extract frameworks, databases, APIs, deployment tools.
- Infer experience level (intern, junior, mid-level, senior).
- Extract hidden recruiter priorities (e.g. if they say "scalable UI", they want "UI scalability" and "component architecture").
- Build semantic clusters to prevent keyword stuffing.
- NEVER force identity corruption. Prioritize natural alignment.

Return STRICT structured JSON ONLY. NO markdown, NO explanations.
Example format:
{
  "role_type": "Frontend Developer",
  "experience_level": "Intern",
  "core_skills": ["React.js", "JavaScript", "Responsive Design"],
  "preferred_skills": ["TailwindCSS", "Node.js"],
  "recruiter_focus": ["UI scalability", "component architecture", "frontend performance"],
  "semantic_clusters": {
    "frontend_engineering": ["React.js", "Responsive Design", "Component Architecture"]
  }
}
PROMPT;
    }

    private function parseAiJdReport(array $aiData, array $baseAnalysis): array
    {
        $baseAnalysis['role_category']     = $aiData['role_type'] ?? $baseAnalysis['role_category'];
        $baseAnalysis['experience_level']  = $aiData['experience_level'] ?? $baseAnalysis['experience_level'];
        
        if (!empty($aiData['core_skills'])) {
            $baseAnalysis['required_skills'] = $aiData['core_skills'];
        }
        
        $baseAnalysis['keywords']          = array_merge($baseAnalysis['keywords'], $aiData['preferred_skills'] ?? []);
        $baseAnalysis['recruiter_terms']   = $aiData['recruiter_focus'] ?? $baseAnalysis['recruiter_terms'];
        $baseAnalysis['semantic_clusters'] = $aiData['semantic_clusters'] ?? [];
        
        return $baseAnalysis;
    }

    public function ruleBasedAnalyze(Internship $internship): array
    {
        $title       = $internship->title ?? '';
        $description = $internship->description ?? '';
        $skills      = $internship->required_skills ?? [];
        $fullText    = strtolower($title . ' ' . $description . ' ' . implode(' ', $skills));

        $roleCategory     = $this->classifyRole($fullText, $skills);
        $requiredSkills   = $this->extractRequiredSkills($skills, $description);
        $keywords         = $this->extractKeywords($title, $description, $skills);
        $actionVerbs      = $this->extractRequiredActionVerbs($description);
        $expLevel         = $this->detectExperienceLevel($description);
        $techFrameworks   = $this->extractTechFrameworks($description, $skills);
        $recruiterTerms   = $this->extractRecruiterTerminology($description);

        return [
            'role_category'      => $roleCategory,
            'required_skills'    => $requiredSkills,
            'keywords'           => $keywords,
            'action_verbs'       => $actionVerbs,
            'experience_level'   => $expLevel,
            'tech_frameworks'    => $techFrameworks,
            'recruiter_terms'    => $recruiterTerms,
            'job_title'          => $title,
            'organization'       => $internship->organization ?? '',
            'location'           => $internship->location ?? '',
            'description_raw'    => $description,
            'semantic_clusters'  => [], // Default for rule-based
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Role Classification                                                 */
    /* ------------------------------------------------------------------ */

    private function classifyRole(string $fullText, array $skills): string
    {
        if (preg_match('/\b(ui|ux|product|graphic|visual|interaction)\s*design/i', $fullText)) {
            return 'design';
        }

        $scores = [];
        $skillsLower = array_map('strtolower', $skills);

        foreach (self::ROLE_SIGNALS as $role => $signals) {
            $score = 0;
            foreach ($signals as $signal) {
                if (str_contains($fullText, $signal)) {
                    $score += 2;
                }
                // Extra weight for exact skill match
                foreach ($skillsLower as $skill) {
                    if (str_contains($skill, $signal) || str_contains($signal, $skill)) {
                        $score += 3;
                    }
                }
            }
            $scores[$role] = $score;
        }

        arsort($scores);
        $top = array_key_first($scores);

        return $scores[$top] > 0 ? $top : 'general';
    }

    /* ------------------------------------------------------------------ */
    /*  Skill Extraction                                                    */
    /* ------------------------------------------------------------------ */

    private function extractRequiredSkills(array $declaredSkills, string $description): array
    {
        // Start with explicitly declared skills
        $skills = array_map('trim', $declaredSkills);

        // Also extract tech terms from description
        $techPattern = '/\b(PHP|Python|Java|JavaScript|TypeScript|Go|Ruby|Swift|Kotlin|Rust|C\+\+|C#|Scala|R\b|MATLAB|SQL|HTML|CSS|Laravel|Django|Flask|FastAPI|Spring|Rails|Express|Node\.?js|React\.?js|Vue\.?js|Angular|Next\.?js|Nuxt|Svelte|Redux|GraphQL|REST|RESTful|MySQL|PostgreSQL|MongoDB|Redis|SQLite|Firebase|Supabase|ElasticSearch|Docker|Kubernetes|AWS|Azure|GCP|Git|GitHub|GitLab|Linux|Nginx|Apache|Terraform|Ansible|Jenkins|CI\/CD|Figma|Postman|Jira|Webpack|Vite|TailwindCSS|Bootstrap|jQuery|Pandas|NumPy|TensorFlow|PyTorch|scikit-learn|OpenCV|Keras|LangChain|HuggingFace|Flutter|React Native|Android|iOS)\b/i';

        if (preg_match_all($techPattern, $description, $matches)) {
            $extracted = array_unique($matches[1]);
            foreach ($extracted as $tech) {
                // Add if not already in skills list (case-insensitive)
                $alreadyIn = false;
                foreach ($skills as $s) {
                    if (strtolower($s) === strtolower($tech)) { $alreadyIn = true; break; }
                }
                if (!$alreadyIn) $skills[] = $tech;
            }
        }

        return array_values(array_unique(array_filter($skills)));
    }

    /* ------------------------------------------------------------------ */
    /*  Keyword Extraction                                                  */
    /* ------------------------------------------------------------------ */

    private function extractKeywords(string $title, string $description, array $skills): array
    {
        $text  = strtolower($title . ' ' . $description . ' ' . implode(' ', $skills));
        $words = preg_split('/[\W_]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $keywords = [];
        foreach ($words as $w) {
            if (strlen($w) > 2 && !in_array($w, self::STOP_WORDS) && !is_numeric($w)) {
                $keywords[$w] = ($keywords[$w] ?? 0) + 1;
            }
        }

        // Sort by frequency (higher frequency = more important for ATS)
        arsort($keywords);

        // Return top keywords, excluding those already in required skills list
        $skillsLower = array_map('strtolower', $skills);
        $result = [];
        foreach (array_keys($keywords) as $kw) {
            if (!in_array($kw, $skillsLower)) {
                $result[] = $kw;
            }
        }

        return array_slice($result, 0, 40);
    }

    /* ------------------------------------------------------------------ */
    /*  Action Verb Extraction                                              */
    /* ------------------------------------------------------------------ */

    private function extractRequiredActionVerbs(string $description): array
    {
        $lower = strtolower($description);
        $found = [];

        foreach (self::ACTION_VERBS as $category => $verbs) {
            foreach ($verbs as $verb) {
                if (str_contains($lower, $verb)) {
                    $found[$category][] = $verb;
                }
            }
        }

        return $found;
    }

    /* ------------------------------------------------------------------ */
    /*  Experience Level Detection                                          */
    /* ------------------------------------------------------------------ */

    private function detectExperienceLevel(string $description): string
    {
        $lower = strtolower($description);

        if (preg_match('/\b(0[-–]1|0 to 1|fresher|no experience|entry.?level|new graduate)\b/i', $lower)) {
            return 'entry';
        }
        if (preg_match('/\b(1[-–]2|1 to 2|junior|beginner)\b/i', $lower)) {
            return 'junior';
        }
        if (preg_match('/\b(3[-–]5|2[-–]4|mid.?level|intermediate)\b/i', $lower)) {
            return 'mid';
        }
        if (preg_match('/\b(5\+|5 or more|senior|lead|principal|staff)\b/i', $lower)) {
            return 'senior';
        }

        return 'entry'; // Default for internship platform
    }

    /* ------------------------------------------------------------------ */
    /*  Tech Framework Extraction                                           */
    /* ------------------------------------------------------------------ */

    private function extractTechFrameworks(string $description, array $skills): array
    {
        $frameworks = [
            'languages'  => [],
            'frameworks' => [],
            'tools'      => [],
            'databases'  => [],
            'cloud'      => [],
        ];

        $langKeywords  = ['php','python','java','javascript','typescript','c++','c#','ruby','go','rust','swift','kotlin','dart','r','scala','html','css','sql'];
        $fwKeywords    = ['laravel','django','flask','react','vue','angular','next','nuxt','express','spring','rails','fastapi','node','nodejs','bootstrap','tailwind','jquery','redux','graphql','nestjs','svelte'];
        $toolKeywords  = ['git','docker','kubernetes','jenkins','aws','azure','gcp','linux','nginx','apache','postman','jira','figma','webpack','vite','terraform','ansible','github','gitlab'];
        $dbKeywords    = ['mysql','postgresql','mongodb','redis','sqlite','oracle','mssql','elasticsearch','cassandra','dynamodb','firebase','supabase'];
        $cloudKeywords = ['aws','azure','gcp','heroku','vercel','netlify','cloudflare','digitalocean','render'];

        $allTerms = array_merge(array_map('strtolower', $skills), [strtolower($description)]);
        $fullText = implode(' ', $allTerms);

        foreach (array_map('strtolower', $skills) as $skill) {
            if (in_array($skill, $langKeywords))  $frameworks['languages'][]  = $skill;
            elseif (in_array($skill, $fwKeywords)) $frameworks['frameworks'][] = $skill;
            elseif (in_array($skill, $toolKeywords)) $frameworks['tools'][]   = $skill;
            elseif (in_array($skill, $dbKeywords)) $frameworks['databases'][]  = $skill;
            elseif (in_array($skill, $cloudKeywords)) $frameworks['cloud'][]   = $skill;
        }

        return $frameworks;
    }

    /* ------------------------------------------------------------------ */
    /*  Recruiter Terminology Extraction                                    */
    /* ------------------------------------------------------------------ */

    private function extractRecruiterTerminology(string $description): array
    {
        $lower = strtolower($description);
        $terms = [];

        $recruiterTerms = [
            'scalable', 'maintainable', 'production-ready', 'cross-functional',
            'agile', 'scrum', 'sprint', 'code review', 'tdd', 'unit testing',
            'best practices', 'clean code', 'design patterns', 'solid principles',
            'microservices', 'monolith', 'soa', 'event-driven', 'asynchronous',
            'real-time', 'high-performance', 'low-latency', 'fault-tolerant',
            'distributed systems', 'restful', 'soap', 'authentication', 'authorization',
            'oauth', 'jwt', 'security', 'encryption', 'performance optimization',
        ];

        foreach ($recruiterTerms as $term) {
            if (str_contains($lower, $term)) {
                $terms[] = $term;
            }
        }

        return $terms;
    }
}
