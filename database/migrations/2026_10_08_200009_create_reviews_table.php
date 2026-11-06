<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_deal_id')->constrained('marketplace_deals')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['marketplace_deal_id', 'reviewer_id']);
        });

        DB::statement('ALTER TABLE reviews ADD CONSTRAINT check_rating_range CHECK (rating >= 1 AND rating <= 5)');
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT check_reviewer_reviewee_different CHECK (reviewer_id != reviewee_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
