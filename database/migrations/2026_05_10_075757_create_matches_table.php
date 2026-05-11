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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('week');
            $table->foreignId('home_team_id');
            $table->foreignId('guest_team_id');
            $table->boolean('is_played')->default(false);
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('guest_score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
