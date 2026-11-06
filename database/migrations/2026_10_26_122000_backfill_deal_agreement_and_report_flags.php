<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Deal lama yang sudah agreed/completed/cancelled dianggap disetujui
        // kedua pihak jika sudah memiliki harga kesepakatan.
        DB::table('marketplace_deals')
            ->whereIn('status', ['agreed', 'completed', 'cancelled'])
            ->whereNotNull('agreed_price')
            ->update([
                'current_offer_price' => DB::raw('COALESCE(current_offer_price, agreed_price)'),
                'buyer_agreed' => true,
                'seller_agreed' => true,
                'buyer_agreed_at' => DB::raw('COALESCE(buyer_agreed_at, agreed_at, created_at)'),
                'seller_agreed_at' => DB::raw('COALESCE(seller_agreed_at, agreed_at, created_at)'),
            ]);

        // Deal yang sudah pernah dilaporkan tidak boleh dianggap bersih.
        DB::table('marketplace_deals')
            ->whereNull('reported_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('reports')
                    ->whereColumn('reports.marketplace_deal_id', 'marketplace_deals.id');
            })
            ->update(['reported_at' => DB::raw('CURRENT_TIMESTAMP')]);
    }

    public function down(): void
    {
        // Backfill data historis tidak dikembalikan.
    }
};
