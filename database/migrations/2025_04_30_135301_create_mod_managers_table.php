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
            $table->unsignedBigInteger('egg_id');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_managers');
    }
};
