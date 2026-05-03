<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('bill_number')->index();
            $table->enum('bill_type', ['retail', 'wholesale', 'credit'])->default('retail');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('extra_charges', 10, 2)->default(0);
            $table->string('extra_charges_label')->nullable();
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'due'])->default('paid');
            $table->enum('payment_method', ['cash', 'upi', 'card', 'credit', 'mixed'])->default('cash');
            $table->text('notes')->nullable();
            $table->timestamp('billed_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'bill_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
