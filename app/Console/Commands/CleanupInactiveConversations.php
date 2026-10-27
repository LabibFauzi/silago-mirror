<?php

namespace App\Console\Commands;

use App\Services\ChatPurgeService;
use Illuminate\Console\Command;

class CleanupInactiveConversations extends Command
{
    protected $signature = 'chat:purge {--hours=24 : Batas jam aktivitas terakhir}';

    protected $description = 'Hapus chat biasa tanpa offer/deal yang tidak aktif lebih dari 24 jam';

    protected $aliases = ['conversations:cleanup-inactive'];

    public function handle(ChatPurgeService $purgeService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $result = $purgeService->purge($hours);

        $this->info("Batas aktivitas: {$result['threshold']}");
        $this->info("Menghapus {$result['conversations']} percakapan, {$result['messages']} pesan, {$result['files']} file.");

        return Command::SUCCESS;
    }
}
