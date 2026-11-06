<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService: menyimpan notifikasi di database dan
 * mengantre push FCM (dijalankan setelah commit transaksi).
 *
 * Kegagalan notifikasi TIDAK membatalkan aksi utama.
 */
class NotificationService
{
    public function send(
        int $userId,
        string $title,
        string $body,
        string $type,
        ?int $referenceId = null
    ): void {
        try {
            Notification::create([
                'user_id'      => $userId,
                'title'        => $title,
                'body'         => $body,
                'type'         => $type,
                'reference_id' => $referenceId,
            ]);

            // TODO: dispatch SendPushNotification::class ke queue setelah FCM dikonfigurasi
            // dispatch(new \App\Jobs\SendPushNotification($userId, $title, $body));
        } catch (\Throwable $e) {
            // Kegagalan notifikasi dicatat tapi tidak disebarkan
            Log::warning('Gagal mengirim notifikasi', [
                'user_id' => $userId,
                'type'    => $type,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ---- Shortcut per tipe notifikasi ----

    public function offerBaru(int $userId, int $conversationId): void
    {
        $this->send($userId, 'Penawaran Baru', 'Ada penawaran baru untuk barang kamu.', 'offer_baru', $conversationId);
    }

    public function offerDisetujui(int $userId, int $dealId): void
    {
        $this->send($userId, 'Penawaran Diterima', 'Penjual menerima penawaran kamu. Deal telah terbentuk!', 'offer_disetujui', $dealId);
    }

    public function offerDitolak(int $userId, int $conversationId): void
    {
        $this->send($userId, 'Penawaran Ditolak', 'Penjual menolak penawaran kamu.', 'offer_ditolak', $conversationId);
    }

    public function offerDibalas(int $userId, int $conversationId): void
    {
        $this->send($userId, 'Penawaran Dibalas', 'Ada balasan penawaran dari lawan bicaramu.', 'offer_dibalas', $conversationId);
    }

    public function pesanBaru(int $userId, int $conversationId): void
    {
        $this->send($userId, 'Pesan Baru', 'Kamu mendapat pesan baru.', 'pesan_baru', $conversationId);
    }

    public function transaksiSelesai(int $userId, int $dealId): void
    {
        $this->send($userId, 'Transaksi Selesai', 'Transaksi telah berhasil diselesaikan.', 'transaksi_selesai', $dealId);
    }

    public function transaksiDibatalkan(int $userId, int $dealId): void
    {
        $this->send($userId, 'Transaksi Dibatalkan', 'Kesepakatan deal telah dibatalkan.', 'transaksi_dibatalkan', $dealId);
    }

    public function postDisetujui(int $userId, int $productId): void
    {
        $this->send($userId, 'Barang Disetujui', 'Barang kamu telah disetujui dan kini tayang di katalog.', 'post_disetujui', $productId);
    }

    public function postDitolak(int $userId, int $productId, string $alasan): void
    {
        $this->send($userId, 'Barang Ditolak', "Barang kamu ditolak. Alasan: {$alasan}", 'post_ditolak', $productId);
    }

    public function stokMenipis(int $userId, int $productId): void
    {
        $this->send($userId, 'Stok Barang Menipis', 'Stok salah satu barangmu hampir habis.', 'stok_menipis', $productId);
    }

    public function barangDiarsipkan(int $userId, int $productId): void
    {
        $this->send($userId, 'Barang Diarsipkan', 'Barang kamu telah melewati masa tayang dan diarsipkan.', 'barang_diarsipkan', $productId);
    }

    public function laporanDiproses(int $userId, int $reportId): void
    {
        $this->send($userId, 'Laporan Diproses', 'Laporan kamu sedang ditinjau oleh staf.', 'laporan_diproses', $reportId);
    }

    public function laporanSelesai(int $userId, int $reportId): void
    {
        $this->send($userId, 'Laporan Selesai', 'Laporan kamu telah diselesaikan. Terima kasih.', 'laporan_selesai', $reportId);
    }

    public function akunDisuspend(int $userId): void
    {
        $this->send($userId, 'Akun Ditangguhkan', 'Akun kamu telah ditangguhkan. Hubungi dukungan untuk informasi lebih lanjut.', 'akun_disuspend', null);
    }
}
