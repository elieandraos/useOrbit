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
        Schema::create('policy_automotive_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('plate_number', 20);
            $table->string('make', 50);
            $table->string('model', 50);
            $table->unsignedSmallInteger('year');
            $table->string('vin', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->decimal('valuation_amount', 12)->nullable();
            $table->string('valuation_source', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_automotive_details');
    }
};
