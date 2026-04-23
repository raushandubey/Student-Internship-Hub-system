<?php

namespace Tests\Unit;

use App\Models\Internship;
use Tests\TestCase;

class InternshipDeactivationTest extends TestCase
{
    public function test_internship_has_deactivation_fillable_fields()
    {
        $internship = new Internship();
        
        $fillable = $internship->getFillable();
        
        $this->assertContains('deactivation_reason', $fillable);
        $this->assertContains('deactivated_by', $fillable);
        $this->assertContains('deactivated_at', $fillable);
    }

    public function test_internship_casts_deactivated_at_to_datetime()
    {
        $internship = new Internship();
        
        $casts = $internship->getCasts();
        
        $this->assertEquals('datetime', $casts['deactivated_at']);
    }

    public function test_internship_has_deactivated_by_relationship()
    {
        $internship = new Internship();
        
        $relation = $internship->deactivatedBy();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        $this->assertEquals('deactivated_by', $relation->getForeignKeyName());
    }

    public function test_is_deactivated_returns_true_when_inactive_and_has_deactivated_at()
    {
        $internship = new Internship();
        $internship->is_active = false;
        $internship->deactivated_at = now();
        
        $this->assertTrue($internship->isDeactivated());
    }

    public function test_is_deactivated_returns_false_when_active()
    {
        $internship = new Internship();
        $internship->is_active = true;
        $internship->deactivated_at = now();
        
        $this->assertFalse($internship->isDeactivated());
    }

    public function test_is_deactivated_returns_false_when_inactive_but_no_deactivated_at()
    {
        $internship = new Internship();
        $internship->is_active = false;
        $internship->deactivated_at = null;
        
        $this->assertFalse($internship->isDeactivated());
    }
}
