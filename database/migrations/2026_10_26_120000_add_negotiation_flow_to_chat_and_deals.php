<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type')->default('chat')->after('seller_id')->index();
        });

        Schema::table('marketplace_deals', function (Blueprint $table) {
            $table->decimal('agreed_price', 12, 2)->nullable()->change();
            $table->string('status')->default('negotiating')->change();

            $table->decimal('current_offer_price', 12, 2)->nullable()->after('agreed_price');
            $table->boolean('buyer_agreed')->default(false)->after('current_offer_price');
            $table->boolean('seller_agreed')->default(false)->after('buyer_agreed');
            $table->timestamp('buyer_agreed_at')->nullable()->after('seller_agreed');
            $table->timestamp('seller_agreed_at')->nullable()->after('buyer_agreed_at');
            $table->timestamp('reported_at')->nullable()->after('cancelled_at');
        });

        // Data lama: deal yang sudah agreed dianggap disetujui kedua pihak.
        DB::table('marketplace_deals')
            ->whereNull('current_offer_price')
            ->update(['current_offer_price' => DB::raw('agreed_price')]);

        DB::table('marketplace_deals')
            ->where('status', 'agreed')
            ->update([
                'buyer_agreed' => true,
                'seller_agreed' => true,
                'buyer_agreed_at' => DB::raw('COALESCE(agreed_at, created_at)'),
                'seller_agreed_at' => DB::raw('COALESCE(agreed_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('marketplace_deals', function (Blueprint $table) {
            $table->dropColumn([
                'current_offer_price',
                'buyer_agreed',
                'seller_agreed',
                'buyer_agreed_at',
                'seller_agreed_at',
                'reported_at',
            ]);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
