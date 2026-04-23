<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrates resume files from local disk (storage/app/resumes/) 
     * to public disk (storage/app/public/resumes/) so they can be 
     * accessed via public URLs.
     */
    public function up(): void
    {
        $profiles = DB::table('profiles')
            ->whereNotNull('resume_path')
            ->get();

        foreach ($profiles as $profile) {
            $oldPath = $profile->resume_path;
            
            // Check if file exists on local disk
            if (Storage::disk('local')->exists($oldPath)) {
                // Copy file to public disk
                $fileContents = Storage::disk('local')->get($oldPath);
                Storage::disk('public')->put($oldPath, $fileContents);
                
                // Delete from local disk
                Storage::disk('local')->delete($oldPath);
                
                echo "Migrated resume for profile {$profile->id}: {$oldPath}\n";
            } else {
                echo "Resume file not found for profile {$profile->id}: {$oldPath}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $profiles = DB::table('profiles')
            ->whereNotNull('resume_path')
            ->get();

        foreach ($profiles as $profile) {
            $path = $profile->resume_path;
            
            // Check if file exists on public disk
            if (Storage::disk('public')->exists($path)) {
                // Copy file back to local disk
                $fileContents = Storage::disk('public')->get($path);
                Storage::disk('local')->put($path, $fileContents);
                
                // Delete from public disk
                Storage::disk('public')->delete($path);
                
                echo "Reverted resume for profile {$profile->id}: {$path}\n";
            }
        }
    }
};
