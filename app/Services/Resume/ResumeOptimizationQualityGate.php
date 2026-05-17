<?php

namespace App\Services\Resume;

class ResumeOptimizationQualityGate
{
    public function evaluate(
        array $originalResume,
        array $optimizedResume,
        array $jdAnalysis,
        int $beforeScore,
        int $afterScore
    ): array {
        $originalText = $this->canonicalRawText($originalResume);
        $optimizedText = $this->canonicalRawText($optimizedResume);

        $changedSections = $this->changedSections($originalResume, $optimizedResume);
        $addedKeywords = $this->addedKeywords($originalText, $optimizedText, $jdAnalysis);
        $rewrittenBulletCount = $this->rewrittenBulletCount($originalResume, $optimizedResume);
        $semanticDelta = $this->semanticDelta($originalText, $optimizedText);
        $scoreDelta = $afterScore - $beforeScore;

        $minScoreDelta = (int) config('services.resume_optimizer.min_score_delta', 1);
        $minTextDelta = (float) config('services.resume_optimizer.min_text_delta', 0.08);

        $materialContentChange = $semanticDelta >= $minTextDelta
            || count($changedSections) >= 2
            || $rewrittenBulletCount >= 1
            || count($addedKeywords) >= 2;

        $passed = $materialContentChange && $scoreDelta >= $minScoreDelta;

        return [
            'passed' => $passed,
            'changed_sections' => $changedSections,
            'added_keywords' => $addedKeywords,
            'rewritten_bullet_count' => $rewrittenBulletCount,
            'semantic_delta' => round($semanticDelta, 4),
            'score_delta' => $scoreDelta,
            'min_score_delta' => $minScoreDelta,
            'min_text_delta' => $minTextDelta,
            'reason' => $passed ? null : $this->failureReason($materialContentChange, $scoreDelta, $minScoreDelta),
        ];
    }

    public function canonicalRawText(array $resume): string
    {
        $parts = [];

        foreach (['name', 'contact', 'email', 'phone'] as $key) {
            if (!empty($resume[$key]) && is_string($resume[$key])) {
                $parts[] = $resume[$key];
            }
        }

        if (!empty($resume['summary']) && is_string($resume['summary'])) {
            $parts[] = "PROFESSIONAL SUMMARY\n" . $resume['summary'];
        }

        if (!empty($resume['skills'])) {
            $parts[] = "TECHNICAL SKILLS\n" . $this->sectionToText($resume['skills']);
        }

        if (!empty($resume['experience'])) {
            $parts[] = "EXPERIENCE\n" . $this->sectionToText($resume['experience']);
        }

        if (!empty($resume['projects'])) {
            $parts[] = "PROJECTS\n" . $this->sectionToText($resume['projects']);
        }

        if (!empty($resume['education'])) {
            $parts[] = "EDUCATION\n" . $this->sectionToText($resume['education']);
        }

        if (!empty($resume['certifications'])) {
            $parts[] = "CERTIFICATIONS\n" . $this->sectionToText($resume['certifications']);
        }

        $canonical = trim(implode("\n\n", array_filter($parts)));

        return $canonical !== '' ? $canonical : trim((string) ($resume['raw_text'] ?? ''));
    }

    private function changedSections(array $original, array $optimized): array
    {
        $changed = [];

        foreach (['summary', 'skills', 'experience', 'projects', 'education', 'certifications'] as $section) {
            $before = $this->normalizeText($this->sectionToText($original[$section] ?? ''));
            $after = $this->normalizeText($this->sectionToText($optimized[$section] ?? ''));

            if ($before !== $after && $after !== '') {
                $changed[] = $section;
            }
        }

        return $changed;
    }

    private function addedKeywords(string $originalText, string $optimizedText, array $jdAnalysis): array
    {
        $original = strtolower($originalText);
        $optimized = strtolower($optimizedText);
        $keywords = array_values(array_unique(array_filter(array_merge(
            $jdAnalysis['required_skills'] ?? [],
            $jdAnalysis['keywords'] ?? [],
            $jdAnalysis['recruiter_terms'] ?? []
        ))));

        $added = [];
        foreach ($keywords as $keyword) {
            $needle = strtolower((string) $keyword);
            if ($needle === '') {
                continue;
            }

            if (!str_contains($original, $needle) && str_contains($optimized, $needle)) {
                $added[] = (string) $keyword;
            }
        }

        return array_values(array_unique($added));
    }

    private function rewrittenBulletCount(array $original, array $optimized): int
    {
        $beforeBullets = $this->flattenBullets($original);
        $afterBullets = $this->flattenBullets($optimized);
        $count = 0;
        $limit = min(count($beforeBullets), count($afterBullets));

        for ($i = 0; $i < $limit; $i++) {
            if ($this->normalizeText($beforeBullets[$i]) !== $this->normalizeText($afterBullets[$i])) {
                $count++;
            }
        }

        return $count + max(0, count($afterBullets) - count($beforeBullets));
    }

    private function semanticDelta(string $originalText, string $optimizedText): float
    {
        $original = $this->normalizeText($originalText);
        $optimized = $this->normalizeText($optimizedText);

        if ($original === '' || $optimized === '') {
            return 0.0;
        }

        if ($original === $optimized) {
            return 0.0;
        }

        similar_text($original, $optimized, $similarity);

        return max(0.0, min(1.0, 1.0 - ($similarity / 100.0)));
    }

    private function sectionToText(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (!is_array($value)) {
            return '';
        }

        $lines = [];
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $lines[] = $this->sectionToText($item);
                continue;
            }

            if (is_string($key)) {
                $lines[] = $key . ': ' . (string) $item;
            } else {
                $lines[] = (string) $item;
            }
        }

        return trim(implode("\n", array_filter($lines)));
    }

    private function flattenBullets(array $resume): array
    {
        $bullets = [];

        foreach (['experience', 'projects'] as $section) {
            foreach (($resume[$section] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                foreach (($entry['bullets'] ?? []) as $bullet) {
                    if (is_string($bullet) && trim($bullet) !== '') {
                        $bullets[] = $bullet;
                    }
                }
            }
        }

        return $bullets;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function failureReason(bool $materialContentChange, int $scoreDelta, int $minScoreDelta): string
    {
        if (!$materialContentChange) {
            return 'optimized_resume_not_materially_different';
        }

        if ($scoreDelta < $minScoreDelta) {
            return 'ats_score_did_not_improve';
        }

        return 'quality_gate_failed';
    }
}
