<?php

declare(strict_types=1);

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
        Schema::create('policy_fire_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('property_type', 50);
            $table->unsignedSmallInteger('floor_area');
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->string('street');
            $table->string('building_floor')->nullable();
            $table->string('city', 100);
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('sum_insured', 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_fire_details');
    }
};
