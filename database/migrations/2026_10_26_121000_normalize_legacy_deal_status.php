<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Versi lama memakai status "active" untuk deal yang sudah disepakati.
        // Samakan dengan state machine baru agar QR, detail, dan transaksi
        // tidak dianggap sebagai chat biasa.
        DB::table('marketplace_deals')
            ->where('status', 'active')
            ->update([
                'status' => 'agreed',
                'current_offer_price' => DB::raw('COALESCE(current_offer_price, agreed_price)'),
                'buyer_agreed' => true,
                'seller_agreed' => true,
                'buyer_agreed_at' => DB::raw('COALESCE(buyer_agreed_at, agreed_at, created_at)'),
                'seller_agreed_at' => DB::raw('COALESCE(seller_agreed_at, agreed_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke "active": status lama tidak lagi
        // dikenali oleh state machine.
    }
};
