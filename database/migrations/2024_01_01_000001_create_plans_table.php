<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('price_monthly'); // in paise (INR)
            $table->integer('max_devices')->default(1);
            $table->integer('max_products')->default(500); // -1 = unlimited
            $table->integer('max_staff')->default(1);
            $table->boolean('whatsapp_receipt')->default(false);
            $table->boolean('multi_branch')->default(false);
            $table->boolean('api_access')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
