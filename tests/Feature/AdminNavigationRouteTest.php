<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

uses(RefreshDatabase::class);

/**
 * Bug Condition Exploration Test
 * 
 * This test confirms the bug exists by testing admin users accessing pages with navigation.
 * EXPECTED OUTCOME: This test FAILS on unfixed code (proving the bug exists).
 * 
 * Validates: Requirements 1.1, 1.2, 2.1, 2.2
 */

beforeEach(function () {
    // Create an admin user for testing
    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'role' => 'admin',
    ]);
});

test('admin users can access dashboard without RouteNotFoundException', function () {
    // **Property 1: Bug Condition** - Admin Navigation Route Exception
    // This test encodes the expected behavior: admin users should be able to access
    // pages with navigation without encountering RouteNotFoundException
    
    // Act - Admin accesses dashboard which renders app.blade.php layout
    $response = $this->actingAs($this->admin)
        ->get(route('admin.dashboard'));

    // Assert - Expected behavior: page renders successfully with status 200
    $response->assertStatus(200);
    
    // Verify navigation contains the correct admin internships URL
    $response->assertSee('/admin/internships', false);
});

test('admin users can access internships page without RouteNotFoundException', function () {
    // Act - Admin accesses internships page which renders app.blade.php layout
    $response = $this->actingAs($this->admin)
        ->get(route('admin.internships.index'));

    // Assert - Expected behavior: page renders successfully with status 200
    $response->assertStatus(200);
    
    // Verify navigation contains the correct admin internships URL
    $response->assertSee('/admin/internships', false);
});

test('admin users can access applications page without RouteNotFoundException', function () {
    // Act - Admin accesses applications page which renders app.blade.php layout
    $response = $this->actingAs($this->admin)
        ->get(route('admin.applications.index'));

    // Assert - Expected behavior: page renders successfully with status 200
    $response->assertStatus(200);
    
    // Verify navigation contains the correct admin internships URL
    $response->assertSee('/admin/internships', false);
});

test('route helper for internships.index throws exception while admin.internships.index succeeds', function () {
    // This test directly verifies the root cause: 'internships.index' route doesn't exist
    // while 'admin.internships.index' does exist
    
    // Assert - 'internships.index' route should not exist (throws exception)
    expect(fn() => route('internships.index'))
        ->toThrow(RouteNotFoundException::class);
    
    // Assert - 'admin.internships.index' route should exist and generate URL successfully
    $adminInternshipsUrl = route('admin.internships.index');
    expect($adminInternshipsUrl)->toBe(url('/admin/internships'));
});
