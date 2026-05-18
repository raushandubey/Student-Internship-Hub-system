<?php

namespace App\Services\Resume;

use App\Traits\ResilientJsonTrait;
use Illuminate\Support\Facades\Log;

/**
 * Strict JSON sanitation and schema validation for AI resume outputs.
 */
class AiOutputSanitizer
{
    use ResilientJsonTrait;

    private const ALLOWED_TOP_KEYS = [
        'summary', 'skills', 'experience', 'projects', 'education', 'certifications',
        'name', 'email', 'phone', 'contact', 'raw_text', 'optimization_meta',
        'optimized_sections',
    ];

    private const FORBIDDEN_KEYS = [
        'latex', 'formatting', 'layout', 'spacing', 'template', 'tex', 'pdf_layout',
    ];

    /**
     * @return array{valid: bool, data: ?array, issues: array<int, string>, json: ?string}
     */
    public function sanitizeAndValidate(string $rawContent): array
    {
        $cleaned = $this->stripArtifacts($rawContent);
        $decoded = $this->safeJsonDecode($cleaned);

        if (!is_array($decoded)) {
            return [
                'valid' => false,
                'data' => null,
                'issues' => ['Invalid or missing JSON structure'],
                'json' => null,
            ];
        }

        $forbiddenFound = $this->findForbiddenKeys($decoded);
        if (!empty($forbiddenFound)) {
            return [
                'valid' => false,
                'data' => $decoded,
                'issues' => array_map(fn ($k) => "Forbidden key present: {$k}", $forbiddenFound),
                'json' => null,
            ];
        }

        $decoded = $this->recursiveSanitize($decoded);

        if (isset($decoded['optimized_sections']) && is_array($decoded['optimized_sections'])) {
            $decoded = array_merge($decoded, $decoded['optimized_sections']);
            unset($decoded['optimized_sections']);
        }

        $issues = $this->validateSchema($decoded);

        if (!empty($issues)) {
            return [
                'valid' => false,
                'data' => $decoded,
                'issues' => $issues,
                'json' => null,
            ];
        }

        $json = json_encode($decoded);

        return [
            'valid' => $json !== false,
            'data' => $decoded,
            'issues' => $json === false ? ['Failed to encode sanitized JSON'] : [],
            'json' => $json !== false ? $json : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function validateSchema(array $data): array
    {
        $issues = [];

        foreach (self::FORBIDDEN_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $issues[] = "Forbidden key present: {$key}";
            }
        }

        $summary = trim((string) ($data['summary'] ?? ''));
        $skills  = $data['skills'] ?? [];

        if ($summary === '') {
            $issues[] = 'Summary is empty';
        }

        if (!is_array($skills) || count(array_filter($skills, fn ($s) => is_string($s) && trim($s) !== '')) === 0) {
            $issues[] = 'Skills array is empty or invalid';
        }

        $hasBody = !empty($data['experience']) || !empty($data['projects']) || !empty($data['education']);
        if (!$hasBody) {
            $issues[] = 'Must include at least one of experience, projects, or education';
        }

        foreach ($data['experience'] ?? [] as $i => $exp) {
            if (!is_array($exp)) {
                $issues[] = "Experience entry {$i} is not an object";
                continue;
            }
            if (!isset($exp['bullets']) || !is_array($exp['bullets'])) {
                $issues[] = "Experience entry {$i} missing bullets array";
            }
        }

        return $issues;
    }

    private function stripArtifacts(string $text): string
    {
        // Remove Unicode BOM marker
        $text = str_replace("\xEF\xBB\xBF", '', $text);

        // Strip PDF artifacts
        $text = preg_replace('/%PDF-[\s\S]*?%%EOF/i', '', $text) ?? $text;

        // Strip markdown code block wrappers ONLY — do NOT strip backticks inside JSON string values.
        // This regex removes the ```json ... ``` wrapper but preserves the JSON content within,
        // including any backtick characters that appear inside JSON string values
        // (e.g., bullet points like "Built REST API using `Laravel` framework").
        // We apply this in a loop to handle nested code blocks.
        $previous = null;
        while ($previous !== $text) {
            $previous = $text;
            $text = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/i', '$1', $text) ?? $text;
        }

        // Clean non-printable characters (but preserve printable ASCII including backtick 0x60)
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array<int, string>
     */
    private function findForbiddenKeys(array $data, string $prefix = ''): array
    {
        $found = [];
        foreach ($data as $key => $value) {
            $keyStr = (string) $key;
            if (in_array(strtolower($keyStr), self::FORBIDDEN_KEYS, true)) {
                $found[] = $prefix . $keyStr;
            }
            if (is_array($value)) {
                $found = array_merge($found, $this->findForbiddenKeys($value, $prefix . $keyStr . '.'));
            }
        }

        return $found;
    }

    private function removeForbiddenKeys(array $data): array
    {
        foreach (self::FORBIDDEN_KEYS as $key) {
            unset($data[$key]);
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, self::ALLOWED_TOP_KEYS, true) && !is_numeric($key)) {
                Log::warning('AiOutputSanitizer: stripping unknown key', ['key' => $key]);
                continue;
            }
            $result[$key] = is_array($value) ? $this->removeForbiddenKeysRecursive($value) : $value;
        }

        return $result;
    }

    private function removeForbiddenKeysRecursive(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (in_array((string) $key, self::FORBIDDEN_KEYS, true)) {
                continue;
            }
            $clean[$key] = is_array($value) ? $this->removeForbiddenKeysRecursive($value) : $value;
        }

        return $clean;
    }
}
