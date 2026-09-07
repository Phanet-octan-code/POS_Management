<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class SyncToFirebaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:sync {--type=all : The type of data to sync (all, products, sales, customers)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize POS Management database records with Google Firebase Firestore';

    /**
     * Execute the console command.
     */
    public function handle(FirebaseService $firebaseService): int
    {
        $this->info('Connecting to Firebase Firestore (Project: ' . config('firebase.project_id') . ')...');

        $test = $firebaseService->testConnection();
        if (!$test['success']) {
            $this->warn('Firebase ping note: ' . $test['message']);
            $this->line('Proceeding with document sync...');
        } else {
            $this->info('Connection verified: ' . $test['message']);
        }

        $this->info('Starting database sync to Firebase Firestore...');

        $counts = $firebaseService->syncAll();

        $this->newLine();
        $this->table(
            ['Collection', 'Records Synced to Firebase'],
            [
                ['Products', $counts['products']],
                ['Sales / Orders', $counts['sales']],
                ['Customers', $counts['customers']],
                ['Categories', $counts['categories']],
            ]
        );

        $this->info('Database sync to Firebase Firestore completed successfully!');
        return Command::SUCCESS;
    }
}
