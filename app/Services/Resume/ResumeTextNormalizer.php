<?php

namespace App\Services\Resume;

/**
 * Shared normalization for resume text extracted from PDFs and rule-based rewrites.
 */
class ResumeTextNormalizer
{
    /**
     * Collapse per-character PDF spacing (e.g. "R A U S H A N" → "Raushan").
     */
    public static function normalizePersonName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return '';
        }

        $tokens = preg_split('/\s+/', $name);
        if ($tokens === false || count($tokens) < 2) {
            return $name;
        }

        $singleCharCount = count(array_filter($tokens, fn (string $t) => mb_strlen($t) === 1 && preg_match('/^[A-Za-z]$/', $t)));
        $ratio = $singleCharCount / count($tokens);

        if ($ratio < 0.6) {
            return $name;
        }

        $parts = [];
        $buffer = '';

        foreach ($tokens as $token) {
            if (mb_strlen($token) === 1 && preg_match('/^[A-Za-z]$/', $token)) {
                $buffer .= $token;
                continue;
            }

            if ($buffer !== '') {
                $parts[] = $buffer;
                $buffer = '';
            }
            $parts[] = $token;
        }

        if ($buffer !== '') {
            $parts[] = $buffer;
        }

        $normalized = array_map(function (string $part): string {
            if (!str_contains($part, ' ') && strlen($part) >= 8) {
                return self::splitJoinedName($part);
            }

            return ucwords(strtolower($part));
        }, $parts);

        return trim(implode(' ', $normalized));
    }

    private static function splitJoinedName(string $joined): string
    {
        $joined = strtolower(preg_replace('/[^a-z]/i', '', $joined));
        $len    = strlen($joined);

        if ($len < 8) {
            return ucwords($joined);
        }

        $bestSplit = null;
        $bestScore   = -1;

        for ($i = 4; $i <= $len - 3; $i++) {
            $first  = substr($joined, 0, $i);
            $second = substr($joined, $i);
            $score  = self::scoreNameFragment($first) + self::scoreNameFragment($second);

            if (strlen($first) >= 5 && strlen($first) <= 9 && strlen($second) >= 4 && strlen($second) <= 8) {
                $score += 6;
            }

            if (strlen($second) >= 4 && strlen($second) <= 6) {
                $score += 5;
            }

            if (strlen($first) < 5 && strlen($second) > 6) {
                $score -= 8;
            }

            if (strlen($second) > 7) {
                $score -= 6;
            }

            $idealSplit = (int) round($len * 0.58);
            $score += max(0, 10 - abs($i - $idealSplit) * 2);

            if ($score > $bestScore) {
                $bestScore   = $score;
                $bestSplit   = ucwords($first) . ' ' . ucwords($second);
            }
        }

        return $bestSplit ?? ucwords($joined);
    }

    private static function scoreNameFragment(string $fragment): int
    {
        $length = strlen($fragment);
        if ($length < 3 || $length > 14) {
            return -5;
        }

        if (!preg_match('/^[a-z]+$/', $fragment)) {
            return -5;
        }

        $vowels = preg_match_all('/[aeiou]/', $fragment);

        return 5 + min(5, $vowels) + ($length >= 4 && $length <= 10 ? 3 : 0);
    }

    public static function formatDisplayName(string $name): string
    {
        $normalized = self::normalizePersonName($name);

        return strtoupper($normalized !== '' ? $normalized : 'CANDIDATE');
    }

    public static function namesMatch(string $a, string $b): bool
    {
        $normalize = static fn (string $s): string => preg_replace('/[^a-z0-9]/i', '', strtolower(self::normalizePersonName($s)));

        $na = $normalize($a);
        $nb = $normalize($b);

        return $na !== '' && $nb !== '' && ($na === $nb || str_contains($na, $nb) || str_contains($nb, $na));
    }

    public static function isLikelyPersonName(string $line, ?string $candidateName = null): bool
    {
        $line = trim($line);
        if ($line === '' || strlen($line) > 55) {
            return false;
        }

        if ($candidateName !== null && $candidateName !== '' && self::namesMatch($line, $candidateName)) {
            return true;
        }

        if (preg_match('/[@\d]{4,}|https?:\/\//i', $line)) {
            return false;
        }

        if (!preg_match('/\b(b\.?\s*tech|b\.?\s*e\.?|bachelor|master|mba|diploma|university|college|institute|school)\b/i', $line)
            && preg_match('/^[A-Za-z][A-Za-z\s.\'-]{1,48}$/', $line)
            && str_word_count($line) <= 5
            && !preg_match('/\b(20\d{2}|present|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\b/i', $line)) {
            return true;
        }

        return false;
    }

    public static function isValidDegreeLine(string $line): bool
    {
        $line = trim($line);
        if ($line === '' || strlen($line) > 120) {
            return false;
        }

        return (bool) preg_match(
            '/\b(b\.?\s*tech|b\.?\s*e\.?|b\.?\s*sc|m\.?\s*tech|m\.?\s*sc|bachelor|master|mba|ph\.?d|diploma|associate|bca|mca|bcom|bcom|bba|b\.?a\.?|m\.?a\.?)\b/i',
            $line
        ) || (
            preg_match('/\b(university|college|institute|school|iit|nit)\b/i', $line)
            && preg_match('/\b(20\d{2}|cgpa|gpa|%)\b/i', $line)
        );
    }

    /**
     * Profession label for rule-based summaries from JD analysis.
     */
    public static function professionLabelFromJd(array $jd): string
    {
        $category = strtolower((string) ($jd['role_category'] ?? 'general'));
        $title    = strtolower((string) ($jd['job_title'] ?? ''));

        if ($category === 'design' || preg_match('/\b(ui|ux|product|graphic|visual|interaction)\s*design/i', $title)) {
            return 'UI/UX designer';
        }

        if ($category === 'frontend' || preg_match('/\b(frontend|front-end|front end)\b/i', $title)) {
            return 'frontend developer';
        }

        if ($category === 'backend' || preg_match('/\b(backend|back-end|back end)\b/i', $title)) {
            return 'backend developer';
        }

        if ($category === 'fullstack' || preg_match('/\bfull[\s-]?stack\b/i', $title)) {
            return 'full-stack developer';
        }

        if ($category === 'mobile' || preg_match('/\bmobile\b/i', $title)) {
            return 'mobile developer';
        }

        if ($category === 'data' || preg_match('/\b(data|ml|machine learning|ai)\b/i', $title)) {
            return 'data and ML engineer';
        }

        if ($category === 'devops' || preg_match('/\bdevops\b/i', $title)) {
            return 'DevOps engineer';
        }

        if ($category === 'hr' || preg_match('/\b(hr|human resources|recruiter)\b/i', $title)) {
            return 'HR professional';
        }

        if ($category === 'internship' || preg_match('/\bintern\b/i', $title)) {
            if (preg_match('/\bdesign/i', $title)) {
                return 'design intern';
            }

            return 'intern';
        }

        if (preg_match('/\bengineer/i', $title)) {
            return 'software engineer';
        }

        if (preg_match('/\bdeveloper/i', $title)) {
            return 'developer';
        }

        if (preg_match('/\bdesigner/i', $title)) {
            return 'designer';
        }

        return 'professional';
    }

    /**
     * @param  array<int, array<string, mixed>>  $education
     * @return array<int, array<string, mixed>>
     */
    public static function sanitizeEducationEntries(array $education, string $candidateName = ''): array
    {
        $clean = [];

        foreach ($education as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $degree = trim((string) ($entry['degree'] ?? ''));
            $school = trim((string) ($entry['school'] ?? ''));

            if ($degree !== '' && self::isLikelyPersonName($degree, $candidateName) && !self::isValidDegreeLine($degree)) {
                $degree = '';
            }

            if ($degree !== '' && preg_match('/\b([A-Za-z])\s+(?=[A-Za-z]\b)/', $degree)) {
                $normalized = self::normalizePersonName($degree);
                $degree = self::isLikelyPersonName($normalized, $candidateName) && !self::isValidDegreeLine($normalized)
                    ? ''
                    : $normalized;
            }

            if ($school !== '' && self::isLikelyPersonName($school, $candidateName) && !self::isValidDegreeLine($school)) {
                $school = '';
            }

            if ($degree === '' && $school === '') {
                continue;
            }

            if ($degree === '' && !self::isValidDegreeLine($school)) {
                continue;
            }

            $entry['degree'] = $degree;
            $entry['school'] = $school;
            $clean[] = $entry;
        }

        return $clean;
    }

    public static function buildRoleTargetedSummary(array $jd, array $weakness = [], ?array $candidateIdentity = null): string
    {
        $jobTitle   = trim((string) ($jd['job_title'] ?? 'the position'));
        $org        = trim((string) ($jd['organization'] ?? ''));
        $profession = trim((string) ($candidateIdentity['candidate_type'] ?? ''));
        if ($profession === '') {
            $profession = self::professionLabelFromJd($jd);
        }

        $topSkills  = implode(', ', array_slice($jd['required_skills'] ?? [], 0, 3));
        $missing    = implode(', ', array_slice($weakness['missing_skills'] ?? [], 0, 3));
        $stackHint  = implode(', ', array_slice($candidateIdentity['core_stack'] ?? [], 0, 4));

        $summary = "{$profession} applying for the {$jobTitle} role"
            . ($org !== '' ? " at {$org}" : '') . '. ';

        if ($stackHint !== '') {
            $summary .= "Core stack: {$stackHint}. ";
        }

        if ($topSkills !== '') {
            $summary .= "Proficient in {$topSkills}. ";
        }

        if ($missing !== '' && !str_contains(strtolower($topSkills), strtolower(explode(',', $missing)[0] ?? ''))) {
            $summary .= "Eager to apply strengths in {$missing}. ";
        }

        $resumeCategory = strtolower((string) ($candidateIdentity['role_category'] ?? ''));
        $category = $resumeCategory !== '' ? $resumeCategory : strtolower((string) ($jd['role_category'] ?? ''));

        $summary .= match ($category) {
            'design'     => 'Focused on user-centered design, visual consistency, and accessible interfaces.',
            'frontend'   => 'Experienced in responsive interfaces, component architecture, and performance-minded UI delivery.',
            'backend'    => 'Experienced in building reliable APIs, data models, and production-grade backend systems.',
            'fullstack'  => 'Experienced delivering end-to-end features across APIs, data layers, and user-facing systems.',
            'data'       => 'Experienced in data-driven problem solving, modeling, and analytical delivery.',
            'hr'         => 'Experienced in stakeholder communication, hiring workflows, and people operations.',
            'internship' => 'Ready to contribute quickly, learn fast, and deliver measurable outcomes in a team environment.',
            default      => 'Committed to delivering measurable impact through strong collaboration and execution.',
        };

        return trim($summary);
    }
}
