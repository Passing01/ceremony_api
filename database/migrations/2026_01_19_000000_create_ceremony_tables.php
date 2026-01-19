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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 50);
            $table->decimal('price_per_pack', 10, 2);
            $table->text('preview_image')->nullable();
            $table->json('config_schema')->nullable(); // Config definis champs requis
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->integer('remaining_uses')->default(2);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null'); // nullable if template deleted? Spec says references templates.
            $table->string('title');
            $table->timestamp('event_date');
            $table->json('location')->nullable(); // { "name": "Palais", "lat": 0.0, "lng": 0.0 }
            $table->json('custom_data')->nullable(); // { "groom": "Paul", "bride": "Marie", "music": "url" }
            $table->string('short_link', 100)->unique()->nullable();
            $table->boolean('is_souvenir_enabled')->default(false);
            $table->string('slug')->unique()->nullable(); // Added slug as per specs mentions "GET /api/events/{slug}/stats"
            $table->timestamps();
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->onDelete('cascade');
            $table->string('whatsapp_number', 20);
            $table->uuid('invitation_token')->unique(); // default gen_random_uuid handled by DB or App
            $table->string('status', 20)->default('pending'); // pending, sent, read
            $table->string('rsvp', 20)->default('waiting'); // waiting, confirmed, declined
            $table->timestamp('check_in_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
        Schema::dropIfExists('events');
        Schema::dropIfExists('user_credits');
        Schema::dropIfExists('templates');
    }
};
