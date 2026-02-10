<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider', 30)->default('upload');
            $table->string('title')->nullable();
            $table->string('artist')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['is_public', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
