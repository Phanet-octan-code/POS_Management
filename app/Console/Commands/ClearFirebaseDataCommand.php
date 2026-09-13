<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class ClearFirebaseDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:clear {--collection= : Specific collection to clear (e.g., sales, products)} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete documents from Google Firebase Firestore collections';

    /**
     * Execute the console command.
     */
    public function handle(FirebaseService $firebaseService): int
    {
        $targetCollection = $this->option('collection');
        $projectId = config('firebase.project_id');

        $this->info("Connecting to Firebase Firestore (Project: {$projectId})...");

        $test = $firebaseService->testConnection();
        if (!$test['success']) {
            $this->warn('Firebase connection note: ' . $test['message']);
        } else {
            $this->info('Connection verified: ' . $test['message']);
        }

        if ($targetCollection) {
            $this->warn("Clearing collection '{$targetCollection}' in Firestore...");
            $deleted = $firebaseService->clearCollection($targetCollection);
            $this->info("Successfully deleted {$deleted} documents from collection '{$targetCollection}'.");
            return Command::SUCCESS;
        }

        $this->warn('Clearing all collections in Firebase Firestore...');
        $results = $firebaseService->clearAll();

        $rows = [];
        $total = 0;
        foreach ($results as $collection => $count) {
            $rows[] = [$collection, $count];
            $total += $count;
        }

        $this->newLine();
        $this->table(['Firestore Collection', 'Documents Deleted'], $rows);
        $this->info("Firebase cleanup complete! Total documents deleted: {$total}");

        return Command::SUCCESS;
    }
}
