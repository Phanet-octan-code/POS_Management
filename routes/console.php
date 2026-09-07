<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('firebase:sync {--type=all}', function () {
    $this->call(\App\Console\Commands\SyncToFirebaseCommand::class, [
        '--type' => $this->option('type') ?: 'all'
    ]);
})->purpose('Synchronize POS Management database records with Google Firebase Firestore');

Artisan::command('pos:clear-data {--all}', function () {
    $this->call(\App\Console\Commands\ClearPosDataCommand::class, [
        '--all' => (bool) $this->option('all')
    ]);
})->purpose('Clear transactional records (sales, purchases, payments, returns, expenses, logs, alerts) from POS system');
