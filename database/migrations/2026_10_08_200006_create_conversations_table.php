<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['product_id', 'buyer_id', 'seller_id']);
        });

        // Add check constraint so buyer and seller are not the same
        DB::statement('ALTER TABLE conversations ADD CONSTRAINT check_buyer_seller_different CHECK (buyer_id != seller_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
