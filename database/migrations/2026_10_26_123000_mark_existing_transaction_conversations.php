<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Percakapan lama yang sudah punya offer/deal harus ditandai
        // transaksi agar konsisten dengan state machine baru.
        DB::table('conversations')
            ->where(function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('messages')
                        ->whereColumn('messages.conversation_id', 'conversations.id')
                        ->where('messages.type', 'offer');
                })->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('marketplace_deals')
                        ->whereColumn('marketplace_deals.conversation_id', 'conversations.id');
                });
            })
            ->update(['type' => 'transaction']);
    }

    public function down(): void
    {
        // Penanda transaksi historis tidak dikembalikan.
    }
};
