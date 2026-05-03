<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->enum('unit', ['piece', 'kg', 'gram', 'litre', 'ml', 'box', 'pack', 'dozen'])->default('piece');
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2);
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->enum('gst_type', ['inclusive', 'exclusive'])->default('exclusive');
            $table->integer('stock_quantity')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->date('expiry_date')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
