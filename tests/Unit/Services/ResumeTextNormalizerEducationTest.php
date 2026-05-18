<?php

use App\Services\Resume\ResumeTextNormalizer;

test('sanitizeEducationEntries removes person name used as degree', function () {
    $clean = ResumeTextNormalizer::sanitizeEducationEntries([
        ['degree' => 'R A U S H A N D U B E Y', 'school' => ''],
        ['degree' => 'B.Tech Computer Science', 'school' => 'ABC University', 'year' => '2024'],
    ], 'Raushan Dubey');

    expect($clean)->toHaveCount(1)
        ->and($clean[0]['degree'])->toBe('B.Tech Computer Science');
});
