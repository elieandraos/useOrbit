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
        Schema::create('policy_medical_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('coverage_scope', 20);
            $table->string('class_tier', 20);
            $table->boolean('co_insurance')->default(false);
            $table->decimal('co_insurance_share', 5, 2)->nullable();
            $table->boolean('guaranteed_renewable')->default(false);
            $table->string('insured_full_name')->nullable();
            $table->date('insured_date_of_birth')->nullable();
            $table->string('insured_gender', 30)->nullable();
            $table->boolean('insured_smoker')->nullable();
            $table->text('insured_medical_history')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_medical_details');
    }
};
