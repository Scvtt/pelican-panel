<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the column as nullable
        Schema::table('feature_flags', function (Blueprint $table) {
            $table->string('flag')->nullable()->after('name');
        });
        
        // Update existing records with a unique value based on name
        $featureFlags = DB::table('feature_flags')->get();
        foreach ($featureFlags as $flag) {
            // Create a unique string based on the name
            $flagValue = Str::upper(Str::snake($flag->name));
            
            // Ensure uniqueness by adding a unique identifier if needed
            while (DB::table('feature_flags')->where('flag', $flagValue)->exists()) {
                $flagValue = $flagValue . '_' . Str::random(4);
            }
            
            DB::table('feature_flags')
                ->where('id', $flag->id)
                ->update(['flag' => $flagValue]);
        }
        
        // Make the column required and unique
        Schema::table('feature_flags', function (Blueprint $table) {
            $table->string('flag')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            $table->dropColumn('flag');
        });
    }
}; 