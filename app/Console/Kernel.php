<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
{
    // Envoyer les rappels de rendez-vous toutes les heures
    $schedule->command('appointments:send-reminders')
             ->hourly()
             ->withoutOverlapping();

    // Nettoyer les anciennes données tous les jours à 2h du matin
    $schedule->command('medical:cleanup --days=30')
             ->dailyAt('02:00');

    // Générer un rapport système tous les lundis
    $schedule->command('medical:generate-report')
             ->weeklyOn(1, '08:00');

    // Traiter les paiements échoués toutes les 4 heures
    $schedule->command('payments:process-failed')
             ->everyFourHours();

    // Sauvegarde quotidienne
    $schedule->command('backup:run')
             ->dailyAt('03:00');
}

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
