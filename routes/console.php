<?php

use Illuminate\Support\Facades\Schedule;

// Nécessite la tâche cron système : * * * * * php artisan schedule:run
Schedule::command('jnuaf:send-reminders')->dailyAt('09:00')->withoutOverlapping();

// Durées de conservation annoncées dans la politique de confidentialité.
Schedule::command('jnuaf:purge-data')->dailyAt('03:00')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('partners:maintain')->everyFiveMinutes()
    ->withoutOverlapping(10);
\Illuminate\Support\Facades\Schedule::command('jnuaf:backup')->dailyAt('02:30')->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::call(function () { $f = storage_path('logs/jnuaf-cron.log'); if (is_file($f) && filesize($f) > 2097152) { @rename($f, $f . '.1'); } })->dailyAt('04:00')->name('rotation-journal-cron');

\Illuminate\Support\Facades\Schedule::command('jnuaf:purge-chat-attachments')->dailyAt('03:10')->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('cards:purge')->dailyAt('03:20')->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('bounces:process --days=2')->everyFiveMinutes()->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::command('trash:purge')->dailyAt('03:35')->withoutOverlapping();



\Illuminate\Support\Facades\Schedule::command('cards:auto-issue')->everyFiveMinutes()->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::command('cards:sync-links')->everyMinute()->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::command('accounts:purge-deleted')->dailyAt('03:40')->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('cron_heartbeat', time(), 3600))->everyMinute()->name('cron-heartbeat');

\Illuminate\Support\Facades\Schedule::command('committee:expire-pole-proposals')->dailyAt('03:50')->withoutOverlapping(10);

\Illuminate\Support\Facades\Schedule::call(fn () => \Illuminate\Support\Facades\DB::table('account_login_links')->where('created_at', '<', now()->subDay())->delete())->dailyAt('03:55')->name('purge-account-login-links');

\Illuminate\Support\Facades\Schedule::command('access:sync-committee')->everyFiveMinutes()->withoutOverlapping(10);
