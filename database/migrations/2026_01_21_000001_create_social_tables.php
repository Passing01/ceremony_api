<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('caption')->nullable();
            $table->string('visibility', 20)->default('public'); // public, private (future)
            $table->timestamps();
        });

        Schema::create('post_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->onDelete('cascade');
            $table->string('type', 20); // image, video
            $table->text('path');
            $table->text('thumbnail')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('post_likes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('post_bookmarks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->foreignUuid('parent_id')->nullable()->constrained('post_comments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_bookmarks');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('posts');
    }
};
