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
        Schema::create('policy_insureds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->string('member_code', 20);
            $table->string('full_name');
            $table->string('relationship', 20);
            $table->date('date_of_birth');
            $table->string('gender', 30)->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->unique(['policy_id', 'member_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_insureds');
    }
};
