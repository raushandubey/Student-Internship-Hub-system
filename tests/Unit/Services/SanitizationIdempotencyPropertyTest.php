<?php

/**
 * Property-Based Test for Sanitization Idempotency
 *
 * **Validates: Requirements 14.8**
 *
 * This test validates Property 7: Sanitization Idempotency
 * - sanitizeForStorage(sanitizeForStorage(text)) = sanitizeForStorage(text)
 * - Applying the sanitizer twice must produce the same result as applying it once.
 */

use App\Services\ResumeOptimizationService;

/**
 * Helper: invoke the private sanitizeForStorage method via reflection.
 */
function callSanitize(ResumeOptimizationService $service, string $text): string
{
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('sanitizeForStorage');
    $method->setAccessible(true);
    return $method->invoke($service, $text);
}

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for plain text (no artifacts)
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for plain text', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for plain text");
})->with([
    'simple sentence'        => ['Hello, this is a plain resume text.'],
    'multi-line text'        => ["Name: John Doe\nEmail: john@example.com\nSkills: PHP, Laravel"],
    'already clean resume'   => [
        "PROFESSIONAL SUMMARY\nExperienced developer with 5 years in PHP.\n\nTECHNICAL SKILLS\nPHP, Laravel, MySQL\n\nEXPERIENCE\nSoftware Engineer | Acme Corp | 2019-2024\n• Built REST APIs\n\nEDUCATION\nBachelor of Computer Science | State University | 2019",
    ],
    'empty string'           => [''],
    'only whitespace'        => ['   '],
    'tabs and newlines'      => ["\t\tName\n\nSummary\n"],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for text with PDF header artifacts
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for text with PDF header artifacts', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for PDF header artifacts");
})->with([
    'pdf header before summary' => [
        "%PDF-1.4\n%âãÏÓ\nsome binary data\nPROFESSIONAL SUMMARY\nExperienced developer.",
    ],
    'pdf header before skills' => [
        "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\nSKILLS\nPHP, Laravel",
    ],
    'pdf header before experience' => [
        "%PDF-1.5 binary garbage here\nEXPERIENCE\nSoftware Engineer | Corp | 2020-2023",
    ],
    'pdf header before education' => [
        "%PDF-1.4\nEDUCATION\nBachelor of CS | University | 2020",
    ],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for text with endobj markers
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for text with endobj markers', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for endobj markers");
})->with([
    'single obj block' => [
        "1 0 obj\n<< /Type /Page >>\nendobj\nPROFESSIONAL SUMMARY\nDeveloper.",
    ],
    'multiple obj blocks' => [
        "1 0 obj\n<< /Type /Catalog >>\nendobj\n2 0 obj\n<< /Type /Pages >>\nendobj\nSKILLS\nPHP",
    ],
    'obj block with content' => [
        "5 0 obj\n<< /Length 100 >>\nstream\nsome stream data\nendstream\nendobj\nEXPERIENCE\nEngineer",
    ],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for text with xref/%%EOF blocks
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for text with xref and %%EOF blocks', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for xref/%%EOF blocks");
})->with([
    'xref block' => [
        "xref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n%%EOF\nEDUCATION\nBachelor of CS",
    ],
    'xref with trailer' => [
        "xref\n0 1\n0000000000 65535 f\ntrailer\n<< /Size 1 >>\nstartxref\n9\n%%EOF\nSKILLS\nPHP",
    ],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for text with hex strings
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for text with hex strings', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for hex strings");
})->with([
    'short hex string'  => ["<0a1b2c3d> PROFESSIONAL SUMMARY\nDeveloper."],
    'long hex string'   => ["<0a1b2c3d4e5f6a7b8c9d0e1f> SKILLS\nPHP, Laravel"],
    'multiple hex'      => ["<aabbccdd> some text <11223344> more text EXPERIENCE\nEngineer"],
    'hex with spaces'   => ["<0a 1b 2c 3d 4e 5f> EDUCATION\nBachelor of CS"],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for text with non-printable characters
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for text with non-printable characters', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for non-printable characters");
})->with([
    'null bytes'          => ["Name\x00John\x00Doe\nSKILLS\nPHP"],
    'control characters'  => ["Name\x01\x02\x03John\nEXPERIENCE\nEngineer"],
    'bell and backspace'  => ["Summary\x07\x08text\nEDUCATION\nBachelor"],
    'mixed non-printable' => ["\x01\x02Hello\x1F World\x7F\nSKILLS\nPHP, Laravel"],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for text with excessive whitespace
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for text with excessive whitespace', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for excessive whitespace");
})->with([
    'multiple spaces'       => ["Name:    John    Doe\nSKILLS\nPHP,   Laravel"],
    'multiple blank lines'  => ["SUMMARY\n\n\n\nDeveloper.\n\n\n\nSKILLS\nPHP"],
    'tabs and spaces mixed' => ["Name:\t\t John\t Doe\nEXPERIENCE\nEngineer"],
    'trailing spaces'       => ["Line one   \nLine two   \nSKILLS\nPHP   "],
    'leading and trailing'  => ["   PROFESSIONAL SUMMARY\nDeveloper.   "],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for mixed artifacts
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for mixed PDF artifacts', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for mixed artifacts");
})->with([
    'pdf header + endobj + hex' => [
        "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n<0a1b2c3d>\nPROFESSIONAL SUMMARY\nDeveloper.",
    ],
    'xref + non-printable + whitespace' => [
        "xref\n0 1\n0000000000 65535 f\n%%EOF\n\x01\x02\nSKILLS\n   PHP,   Laravel   ",
    ],
    'all artifact types combined' => [
        "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\nxref\n0 1\n0000000000 65535 f\n%%EOF\n<0a1b2c3d>\n\x00\x01\x02\n\n\n\nPROFESSIONAL SUMMARY\nExperienced developer.\n\nTECHNICAL SKILLS\nPHP, Laravel, MySQL\n\nEXPERIENCE\nEngineer | Corp | 2020-2024\n• Built APIs\n\nEDUCATION\nBachelor of CS | University | 2020",
    ],
    'realistic corrupted resume' => [
        "%PDF-1.4\n%âãÏÓ\n3 0 obj\n<< /Length 200 >>\nstream\nbinary data here\nendstream\nendobj\n<deadbeef1234>\n\x00\x1F\nPROFESSIONAL SUMMARY\nMotivated software developer.\n\nTECHNICAL SKILLS\nPHP, Laravel, MySQL, JavaScript\n\nEXPERIENCE\nSoftware Engineer | Acme Corp | 2021-2024\n• Developed REST APIs\n• Improved performance\n\nEDUCATION\nBachelor of Computer Science | State University | 2021",
    ],
]);

// ---------------------------------------------------------------------------
// Property Test: idempotency holds for already-clean resume text
// (Uses ASCII-only bullets "-" since sanitizeForStorage strips non-ASCII chars
//  such as the UTF-8 bullet "•". The idempotency property still holds.)
// ---------------------------------------------------------------------------
test('property: sanitizeForStorage is idempotent for already-clean resume text', function (string $text) {
    $service = new ResumeOptimizationService();

    $once  = callSanitize($service, $text);
    $twice = callSanitize($service, $once);

    expect($twice)->toBe($once, "sanitizeForStorage should be idempotent for already-clean resume text");

    // For ASCII-only clean text, the sanitizer should be a no-op (output equals trimmed input)
    expect($once)->toBe(trim($text), "ASCII-only clean resume text should be unchanged by sanitization");
})->with([
    'minimal clean resume' => [
        "PROFESSIONAL SUMMARY\nExperienced PHP developer.\n\nTECHNICAL SKILLS\nPHP, Laravel, MySQL\n\nEXPERIENCE\nSoftware Engineer | Acme Corp | 2021-2024\n- Built REST APIs\n\nEDUCATION\nBachelor of Computer Science | State University | 2021",
    ],
    'clean resume with contact info' => [
        "John Doe\njohn@example.com | +1234567890 | City, Country\n\nPROFESSIONAL SUMMARY\nMotivated developer with 3 years experience.\n\nTECHNICAL SKILLS\nPHP, Laravel, Vue.js, MySQL\n\nEXPERIENCE\nBackend Developer | Tech Corp | 2022-2024\n- Developed microservices\n- Optimized database queries\n\nEDUCATION\nBachelor of Computer Science | University | 2022",
    ],
]);
