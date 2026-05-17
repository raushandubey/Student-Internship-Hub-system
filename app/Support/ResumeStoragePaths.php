<?php

namespace App\Support;

class ResumeStoragePaths
{
    /**
     * Return safe storage-key candidates for legacy and current resume paths.
     *
     * Current uploads store paths like "resumes/file.pdf", but older records may
     * contain "/storage/resumes/file.pdf" or a full public URL.
     *
     * @return array<int, string>
     */
    public static function candidates(?string $path): array
    {
        if (!is_string($path) || trim($path) === '') {
            return [];
        }

        $raw = trim($path);
        $urlPath = parse_url($raw, PHP_URL_PATH);
        $pathOnly = $urlPath ?: $raw;

        $normalized = str_replace('\\', '/', rawurldecode($pathOnly));
        $normalized = ltrim($normalized, '/');

        $candidates = [$normalized];

        foreach (['storage/', 'public/', 'app/public/', 'app/private/'] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $candidates[] = substr($normalized, strlen($prefix));
            }
        }

        if (preg_match('#(?:^|/)(resumes/.+)$#', $normalized, $matches)) {
            $candidates[] = $matches[1];
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    public static function encode(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
    }
}
