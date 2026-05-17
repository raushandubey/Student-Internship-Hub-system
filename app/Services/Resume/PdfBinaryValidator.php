<?php

namespace App\Services\Resume;

class PdfBinaryValidator
{
    public function validate(mixed $binary): array
    {
        if (!is_string($binary) || $binary === '') {
            return $this->result(false, 'empty_or_non_string_pdf_body', 0, '');
        }

        $size = strlen($binary);
        $firstBytes = substr($binary, 0, 20);
        $firstBytesHex = bin2hex($firstBytes);
        $probe = strtolower(substr($binary, 0, min($size, 4096)));
        $wholeProbe = strtolower(substr($binary, 0, min($size, 65536)));
        $trimmedProbe = ltrim($probe);

        if (str_starts_with($trimmedProbe, '{') || str_starts_with($trimmedProbe, '[')) {
            return $this->result(false, 'json_error_payload_detected', $size, $firstBytesHex);
        }

        if (str_contains($wholeProbe, '<html') || str_contains($wholeProbe, '<!doctype')) {
            return $this->result(false, 'html_or_exception_payload_detected', $size, $firstBytesHex);
        }

        if (!str_starts_with($binary, '%PDF-')) {
            return $this->result(false, 'missing_pdf_magic_bytes', $size, $firstBytesHex);
        }

        foreach (['laravel\\', 'symfony\\component', 'whoops\\', 'stack trace'] as $needle) {
            if (str_contains($wholeProbe, $needle)) {
                return $this->result(false, 'html_or_exception_payload_detected', $size, $firstBytesHex);
            }
        }

        return $this->result(true, null, $size, $firstBytesHex);
    }

    public function isValid(mixed $binary): bool
    {
        return $this->validate($binary)['valid'] === true;
    }

    private function result(bool $valid, ?string $reason, int $size, string $firstBytesHex): array
    {
        return [
            'valid' => $valid,
            'reason' => $reason,
            'size' => $size,
            'first_bytes_hex' => $firstBytesHex,
        ];
    }
}
