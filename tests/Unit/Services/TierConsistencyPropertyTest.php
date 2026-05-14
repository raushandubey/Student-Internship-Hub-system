<?php

/**
 * Property-Based Test for ResumeScore Tier Consistency
 * 
 * **Validates: Requirements 1.9, 1.10, 1.11**
 * 
 * This test validates Property 3: Tier Consistency
 * - scoreTier(score) = "high" ↔ score ≥ 70
 * - scoreTier(score) = "medium" ↔ 60 ≤ score < 70
 * - scoreTier(score) = "low" ↔ score < 60
 */

use App\Models\ResumeScore;

/**
 * Property Test: Tier Consistency for High Tier
 * 
 * Tests that for any score >= 70, scoreTier returns "high"
 */
test('property: scores >= 70 always return high tier', function (int $score) {
    // Act
    $tier = ResumeScore::scoreTier($score);
    
    // Assert
    expect($tier)
        ->toBe('high', "Score {$score} should be classified as 'high' tier");
        
})->with(function () {
    $testCases = [];
    
    // Boundary value: exactly 70
    $testCases[] = [70];
    
    // Just above boundary
    $testCases[] = [71];
    
    // Mid-range high scores
    $testCases[] = [75];
    $testCases[] = [80];
    $testCases[] = [85];
    $testCases[] = [90];
    $testCases[] = [95];
    
    // Maximum score
    $testCases[] = [100];
    
    // All values from 70 to 100 (comprehensive coverage)
    for ($i = 70; $i <= 100; $i++) {
        $testCases[] = [$i];
    }
    
    return $testCases;
});

/**
 * Property Test: Tier Consistency for Medium Tier
 * 
 * Tests that for any score where 60 <= score < 70, scoreTier returns "medium"
 */
test('property: scores between 60 and 69 always return medium tier', function (int $score) {
    // Act
    $tier = ResumeScore::scoreTier($score);
    
    // Assert
    expect($tier)
        ->toBe('medium', "Score {$score} should be classified as 'medium' tier");
        
})->with(function () {
    $testCases = [];
    
    // Lower boundary: exactly 60
    $testCases[] = [60];
    
    // Just above lower boundary
    $testCases[] = [61];
    
    // Mid-range medium scores
    $testCases[] = [62];
    $testCases[] = [63];
    $testCases[] = [64];
    $testCases[] = [65];
    $testCases[] = [66];
    $testCases[] = [67];
    $testCases[] = [68];
    
    // Upper boundary: exactly 69 (just below 70)
    $testCases[] = [69];
    
    // All values from 60 to 69 (comprehensive coverage)
    for ($i = 60; $i < 70; $i++) {
        $testCases[] = [$i];
    }
    
    return $testCases;
});

/**
 * Property Test: Tier Consistency for Low Tier
 * 
 * Tests that for any score < 60, scoreTier returns "low"
 */
test('property: scores below 60 always return low tier', function (int $score) {
    // Act
    $tier = ResumeScore::scoreTier($score);
    
    // Assert
    expect($tier)
        ->toBe('low', "Score {$score} should be classified as 'low' tier");
        
})->with(function () {
    $testCases = [];
    
    // Minimum score
    $testCases[] = [0];
    
    // Just above minimum
    $testCases[] = [1];
    
    // Low range scores
    $testCases[] = [5];
    $testCases[] = [10];
    $testCases[] = [15];
    $testCases[] = [20];
    $testCases[] = [25];
    $testCases[] = [30];
    $testCases[] = [35];
    $testCases[] = [40];
    $testCases[] = [45];
    $testCases[] = [50];
    $testCases[] = [55];
    
    // Just below boundary: 59 (just below 60)
    $testCases[] = [59];
    
    // All values from 0 to 59 (comprehensive coverage)
    for ($i = 0; $i < 60; $i++) {
        $testCases[] = [$i];
    }
    
    return $testCases;
});

/**
 * Property Test: Tier Boundaries are Mutually Exclusive
 * 
 * Tests that every score in [0, 100] maps to exactly one tier
 */
test('property: every score maps to exactly one tier', function (int $score) {
    // Act
    $tier = ResumeScore::scoreTier($score);
    
    // Assert: tier must be one of the three valid values
    expect($tier)
        ->toBeIn(['low', 'medium', 'high'], "Score {$score} must map to a valid tier");
        
    // Assert: tier matches expected value based on score
    if ($score >= 70) {
        expect($tier)->toBe('high', "Score {$score} >= 70 should be 'high'");
    } elseif ($score >= 60) {
        expect($tier)->toBe('medium', "Score {$score} in [60, 70) should be 'medium'");
    } else {
        expect($tier)->toBe('low', "Score {$score} < 60 should be 'low'");
    }
    
})->with(function () {
    // Test all possible scores from 0 to 100
    $testCases = [];
    for ($i = 0; $i <= 100; $i++) {
        $testCases[] = [$i];
    }
    return $testCases;
});

/**
 * Property Test: Tier Boundary Transitions
 * 
 * Tests that tier changes occur exactly at the boundary values (60 and 70)
 */
test('property: tier transitions occur at exact boundaries', function () {
    // Test transition from low to medium at score 60
    expect(ResumeScore::scoreTier(59))->toBe('low', 'Score 59 should be low');
    expect(ResumeScore::scoreTier(60))->toBe('medium', 'Score 60 should be medium');
    
    // Test transition from medium to high at score 70
    expect(ResumeScore::scoreTier(69))->toBe('medium', 'Score 69 should be medium');
    expect(ResumeScore::scoreTier(70))->toBe('high', 'Score 70 should be high');
    
    // Verify no other transitions exist
    for ($i = 0; $i < 59; $i++) {
        expect(ResumeScore::scoreTier($i))->toBe('low', "Score {$i} should remain low");
    }
    
    for ($i = 60; $i < 69; $i++) {
        expect(ResumeScore::scoreTier($i))->toBe('medium', "Score {$i} should remain medium");
    }
    
    for ($i = 70; $i <= 100; $i++) {
        expect(ResumeScore::scoreTier($i))->toBe('high', "Score {$i} should remain high");
    }
});

/**
 * Property Test: Tier Consistency is Deterministic
 * 
 * Tests that calling scoreTier multiple times with the same score always returns the same tier
 */
test('property: tier classification is deterministic', function (int $score) {
    // Act: Call scoreTier multiple times
    $tier1 = ResumeScore::scoreTier($score);
    $tier2 = ResumeScore::scoreTier($score);
    $tier3 = ResumeScore::scoreTier($score);
    
    // Assert: All results are identical
    expect($tier1)
        ->toBe($tier2, "Score {$score} should produce consistent tier on second call")
        ->and($tier2)
        ->toBe($tier3, "Score {$score} should produce consistent tier on third call");
        
})->with(function () {
    // Test representative scores from each tier
    return [
        [0],    // low
        [30],   // low
        [59],   // low boundary
        [60],   // medium boundary
        [65],   // medium
        [69],   // medium boundary
        [70],   // high boundary
        [85],   // high
        [100],  // high
    ];
});

/**
 * Property Test: Tier Monotonicity
 * 
 * Tests that tier classification never decreases as score increases
 * (low < medium < high in ordering)
 */
test('property: tier classification is monotonic with score', function () {
    $tierOrder = ['low' => 0, 'medium' => 1, 'high' => 2];
    
    $previousTierValue = -1;
    
    for ($score = 0; $score <= 100; $score++) {
        $tier = ResumeScore::scoreTier($score);
        $currentTierValue = $tierOrder[$tier];
        
        // Assert: tier value never decreases
        expect($currentTierValue)
            ->toBeGreaterThanOrEqual($previousTierValue, 
                "Tier should not decrease: score {$score} has tier '{$tier}' (value {$currentTierValue}) but previous was {$previousTierValue}");
        
        $previousTierValue = $currentTierValue;
    }
});

/**
 * Property Test: Comprehensive Range Coverage
 * 
 * Tests that all scores in [0, 100] are correctly classified
 * This is a comprehensive test covering all possible integer scores
 */
test('property: all scores in valid range are correctly classified', function () {
    $lowCount = 0;
    $mediumCount = 0;
    $highCount = 0;
    
    for ($score = 0; $score <= 100; $score++) {
        $tier = ResumeScore::scoreTier($score);
        
        // Count tiers
        match ($tier) {
            'low' => $lowCount++,
            'medium' => $mediumCount++,
            'high' => $highCount++,
            default => throw new Exception("Invalid tier '{$tier}' for score {$score}"),
        };
        
        // Verify correct classification
        if ($score < 60) {
            expect($tier)->toBe('low', "Score {$score} should be low");
        } elseif ($score < 70) {
            expect($tier)->toBe('medium', "Score {$score} should be medium");
        } else {
            expect($tier)->toBe('high', "Score {$score} should be high");
        }
    }
    
    // Verify expected counts
    expect($lowCount)->toBe(60, 'Should have 60 low tier scores (0-59)');
    expect($mediumCount)->toBe(10, 'Should have 10 medium tier scores (60-69)');
    expect($highCount)->toBe(31, 'Should have 31 high tier scores (70-100)');
});
