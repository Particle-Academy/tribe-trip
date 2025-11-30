<?php

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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('adjustments', 10, 2)->default(0);
            $table->string('adjustment_reason')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('draft');
            $table->date('due_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index for efficient queries
            $table->index(['user_id', 'status']);
            $table->index(['billing_period_start', 'billing_period_end']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
