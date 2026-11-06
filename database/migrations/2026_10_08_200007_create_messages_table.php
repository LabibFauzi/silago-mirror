<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // text/offer/location/system
            $table->text('message')->nullable();
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->string('offer_status')->nullable(); // pending/accepted/rejected/countered
            $table->foreignId('parent_offer_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->string('location_name')->nullable();
            $table->string('location_address')->nullable();
            $table->integer('location_accuracy')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
