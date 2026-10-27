<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('object_description')->nullable();
            $table->text('symptoms')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default('investigating');
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('repair_session_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->string('kind')->default('message');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_observations');
        Schema::dropIfExists('repair_sessions');
    }
};
