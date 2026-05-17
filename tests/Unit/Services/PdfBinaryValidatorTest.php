<?php

use App\Services\Resume\PdfBinaryValidator;

test('valid pdf magic bytes pass validation', function () {
    $result = app(PdfBinaryValidator::class)->validate("%PDF-1.4\n1 0 obj\n%%EOF");

    expect($result['valid'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});

test('html response disguised as pdf is rejected', function () {
    $result = app(PdfBinaryValidator::class)->validate("<!DOCTYPE html><html><body>Server error</body></html>");

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toBe('html_or_exception_payload_detected');
});

test('pdf body containing exception text is rejected', function () {
    $result = app(PdfBinaryValidator::class)->validate("%PDF-1.4\nSymfony\\Component\\ErrorHandler\\Error\\FatalError");

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toBe('html_or_exception_payload_detected');
});

test('json error payload is rejected', function () {
    $result = app(PdfBinaryValidator::class)->validate('{"error":"renderer failed"}');

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toBe('json_error_payload_detected');
});

test('leading whitespace before pdf magic bytes is rejected', function () {
    $result = app(PdfBinaryValidator::class)->validate(" \n%PDF-1.4\n%%EOF");

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toBe('missing_pdf_magic_bytes');
});
