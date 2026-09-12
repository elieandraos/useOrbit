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
        Schema::create('policy_expat_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('coverage_zone', 20);
            $table->string('travel_scope')->nullable();
            $table->string('full_name');
            $table->string('gender', 30);
            $table->string('nationality');
            $table->date('date_of_birth');
            $table->string('phone', 30);
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->date('visa_expiry_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_expat_details');
    }
};
