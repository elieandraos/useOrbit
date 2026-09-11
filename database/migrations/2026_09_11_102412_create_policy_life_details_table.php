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
        Schema::create('policy_life_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('sum_assured', 12);
            $table->unsignedSmallInteger('term_years');
            $table->boolean('smoker');
            $table->text('beneficiaries');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_life_details');
    }
};
