<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            // Listing ownership
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('dealer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Vehicle identification
            $table->foreignId('make_id')
                ->constrained('makes')
                ->restrictOnDelete();

            $table->foreignId('vehicle_model_id')
                ->constrained('vehicle_models')
                ->restrictOnDelete();

            // Listing information
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Vehicle specifications
            $table->unsignedSmallInteger('year');
            $table->decimal('price', 15, 2);
            $table->unsignedBigInteger('mileage')->default(0);

            $table->string('condition');
            $table->string('transmission');
            $table->string('fuel_type');
            $table->string('body_type')->nullable();
            $table->string('drive_type')->nullable();
            $table->decimal('engine_size', 4, 1)->nullable();

            $table->string('exterior_color')->nullable();
            $table->string('interior_color')->nullable();
            $table->string('vin')->nullable()->unique();

            // Marketplace information
            $table->string('location');
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Search and filtering indexes
            $table->index(['status', 'published_at']);
            $table->index(['make_id', 'vehicle_model_id']);
            $table->index('price');
            $table->index('year');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
