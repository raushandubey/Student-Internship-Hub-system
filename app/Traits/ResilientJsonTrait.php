<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ResilientJsonTrait
{
    /**
     * Resiliently extract and decode JSON from AI/Webhook responses.
     */
    protected function safeJsonDecode($data): ?array
    {
        if (is_array($data)) {
            return $data;
        }

        if (!is_string($data)) {
            return null;
        }

        $json = $this->extractJson($data);
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('ResilientJson: Failed to decode extracted JSON', [
                'error' => json_last_error_msg(),
                'raw_snippet' => substr($json, 0, 200)
            ]);
            return null;
        }

        // Guard: decoded value must be an array (not a scalar string/int)
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Resiliently extract JSON from a string (handles markdown wrappers).
     */
    protected function extractJson(string $text): string
    {
        // 1. Content between ```json and ```
        if (preg_match('/```json\s*([\s\S]*?)\s*```/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // 2. Content between ``` and ```
        if (preg_match('/```\s*([\s\S]*?)\s*```/i', $text, $matches)) {
            return trim($matches[1]);
        }

        // 3. Find first { and last }
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start !== false && $end !== false) {
            return substr($text, $start, $end - $start + 1);
        }

        return $text;
    }

    /**
     * Recursively purge control characters and non-printable UTF-8.
     */
    protected function recursiveSanitize(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->recursiveSanitize($value);
            } elseif (is_string($value)) {
                $clean = str_replace(["\u{2022}", "\u{25CF}", "\u{25E6}", "\u{2013}", "\u{2014}"], ['-', '-', '-', '-', '-'], $value);
                $clean = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $clean);
                $result[$key] = trim(preg_replace('/[ \t]+/', ' ', $clean));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
