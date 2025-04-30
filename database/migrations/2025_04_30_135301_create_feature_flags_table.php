<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('egg_feature_flag', function (Blueprint $table) {
            $table->unsignedBigInteger('egg_id');
            $table->unsignedBigInteger('feature_flag_id');
            
            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('cascade');
            $table->foreign('feature_flag_id')->references('id')->on('feature_flags')->onDelete('cascade');
            
            $table->primary(['egg_id', 'feature_flag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_feature_flag');
        Schema::dropIfExists('feature_flags');
    }
};
