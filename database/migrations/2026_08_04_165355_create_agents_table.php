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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('slug');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth');
            $table->string('phone', 30);
            $table->string('email');
            $table->string('street')->nullable();
            $table->string('building_floor')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
            $table->index('organization_id');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
