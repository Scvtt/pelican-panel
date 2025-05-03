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
        Schema::create('ar_mods', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->index();
            $table->string('name');
            $table->string('author')->nullable();
            $table->string('version')->nullable();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            $table->text('preview_url')->nullable();
            $table->json('tags')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
            
            // Make sure UUID and server_id combination is unique
            $table->unique(['uuid', 'server_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_mods');
    }
}; 