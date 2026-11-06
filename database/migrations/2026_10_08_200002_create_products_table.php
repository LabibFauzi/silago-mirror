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
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('condition')->nullable();
            $table->text('specification')->nullable();
            $table->string('completeness')->nullable();
            $table->text('sell_reason')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock')->default(1);
            $table->string('status')->default('available');
            $table->string('verification_status')->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->string('scan_result')->nullable();
            $table->string('scan_note')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['seller_id', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
