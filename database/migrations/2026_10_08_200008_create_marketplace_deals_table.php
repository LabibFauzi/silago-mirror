<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('accepted_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('agreed_price', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('agreed');
            $table->timestamp('agreed_at')->nullable();
            $table->string('completion_token_hash', 64)->unique()->nullable();
            $table->string('completion_code_hash', 64)->nullable();
            $table->timestamp('completion_token_expires_at')->nullable();
            $table->smallInteger('completion_attempts')->default(0);
            $table->timestamp('completion_locked_until')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('completed_lat', 10, 7)->nullable();
            $table->decimal('completed_lng', 10, 7)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->text('cancel_note')->nullable();
            $table->timestamps();
            
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_deals');
    }
};
