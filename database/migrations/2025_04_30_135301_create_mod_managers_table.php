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
        Schema::create('mod_managers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('egg_mod_manager', function (Blueprint $table) {
            $table->foreignId('egg_id')->constrained()->onDelete('cascade');
            $table->foreignId('mod_manager_id')->constrained()->onDelete('cascade');
            $table->primary(['egg_id', 'mod_manager_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_mod_manager');
        Schema::dropIfExists('mod_managers');
    }
};
