<?php
// app/Console/Commands/CleanupOldData.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AiConversation;
use App\Models\Notification;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOldData extends Command
{
    protected $signature = 'medical:cleanup {--days=30}';
    protected $description = 'Nettoyer les anciennes données';

    public function handle()
    {
        $days = $this->option('days');
        $this->info("Nettoyage des données de plus de {$days} jours...");

        $this->cleanupOldConversations($days);
        $this->cleanupOldNotifications($days);
        $this->cleanupOldFiles($days);
        $this->cleanupOldLogs($days);

        $this->info('Nettoyage terminé !');
    }

    private function cleanupOldConversations($days)
    {
        $deleted = AiConversation::where('created_at', '<', Carbon::now()->subDays($days))->delete();
        $this->line("Conversations IA supprimées : {$deleted}");
    }

    private function cleanupOldNotifications($days)
    {
        $deleted = Notification::where('created_at', '<', Carbon::now()->subDays($days))
            ->where('is_read', true)
            ->delete();
        $this->line("Notifications lues supprimées : {$deleted}");
    }

    private function cleanupOldFiles($days)
    {
        $files = Storage::disk('public')->files('receipts');
        $deletedCount = 0;

        foreach ($files as $file) {
            if (Storage::disk('public')->lastModified($file) < Carbon::now()->subDays($days)->timestamp) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        $this->line("Fichiers PDF supprimés : {$deletedCount}");
    }

    private function cleanupOldLogs($days)
    {
        $logFiles = glob(storage_path('logs/*.log'));
        $deletedCount = 0;

        foreach ($logFiles as $file) {
            if (filemtime($file) < Carbon::now()->subDays($days)->timestamp) {
                unlink($file);
                $deletedCount++;
            }
        }

        $this->line("Fichiers logs supprimés : {$deletedCount}");
    }
}