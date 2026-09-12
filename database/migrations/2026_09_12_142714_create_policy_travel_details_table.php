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
        Schema::create('policy_travel_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('destination');
            $table->date('trip_start_date');
            $table->date('trip_end_date');
            $table->text('travelers');
            $table->string('coverage_tier', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_travel_details');
    }
};
