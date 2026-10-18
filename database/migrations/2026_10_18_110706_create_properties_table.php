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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('slug')->unique();
            $table->foreignId('property_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('transaction');
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);

            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->longText('description_ar');
            $table->longText('description_en')->nullable();

            $table->decimal('price', 12, 2);
            $table->boolean('price_negotiable')->default(false);
            $table->decimal('surface_m2', 10, 2);
            $table->decimal('built_area_m2', 10, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('legal_title')->nullable();
            $table->string('video_url')->nullable();
            $table->json('details')->nullable();

            $table->foreignId('owner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'transaction']);
            $table->index(['status', 'featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
