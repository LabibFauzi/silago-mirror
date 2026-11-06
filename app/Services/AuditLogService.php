<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Layanan untuk mencatat setiap tindakan staf ke audit log.
 * Dipanggil dalam transaksi database yang sama dengan aksi staf.
 */
class AuditLogService
{
    public function log(
        string $category,
        string $action,
        ?string $target = null,
        ?string $note = null,
        ?array $meta = null,
        ?int $adminId = null
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'admin_id' => $adminId ?? Auth::id(),
            'category' => $category,
            'action'   => $action,
            'target'   => $target,
            'note'     => $note,
            'meta'     => $meta,
        ]);
    }

    // ---- Shortcut per kategori ----

    public function logUser(string $action, string $target, ?string $note = null, ?array $meta = null): AdminAuditLog
    {
        return $this->log('pengguna', $action, $target, $note, $meta);
    }

    public function logProduct(string $action, string $target, ?int $productId = null, ?array $meta = null): AdminAuditLog
    {
        return $this->log('barang', $action, $target, $productId ? "#$productId" : null, $meta);
    }

    public function logReport(string $action, string $target, ?int $reportId = null, ?array $meta = null): AdminAuditLog
    {
        return $this->log('laporan', $action, $target, $reportId ? "#$reportId" : null, $meta);
    }

    public function logTransaction(string $action, string $target, ?int $dealId = null, ?array $meta = null): AdminAuditLog
    {
        return $this->log('transaksi', $action, $target, $dealId ? "#$dealId" : null, $meta);
    }

    public function logSetting(string $action, string $target, ?string $note = null, ?array $meta = null): AdminAuditLog
    {
        return $this->log('pengaturan', $action, $target, $note, $meta);
    }

    public function logSystem(string $action, string $target, ?string $note = null, ?array $meta = null): AdminAuditLog
    {
        return $this->log('sistem', $action, $target, $note, $meta);
    }
}
