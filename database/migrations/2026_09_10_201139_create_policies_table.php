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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('slug');
            $table->string('policy_number', 50);
            $table->string('class', 20);
            $table->string('subclass', 50);
            $table->string('type', 10);
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('carrier_id')->constrained()->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->date('effective_date');
            $table->date('expiry_date');
            $table->date('bound_at')->nullable();
            $table->decimal('premium_amount', 12);
            $table->decimal('discount_amount', 12)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('source', 20);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
            $table->unique(['organization_id', 'policy_number']);
            $table->index('organization_id');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
