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
    protected $signature = 'firebase:sync {--type=all : The type of data to sync (all, products, sales, customers)} {--force : Force sync even if ping reports permissions issues}';

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
            $isPermission = str_contains($test['message'], '403') || str_contains($test['message'], 'PERMISSION_DENIED');
            if ($isPermission && !$this->option('force')) {
                $this->error('Cloud Firestore security rules currently deny write access (403 PERMISSION_DENIED).');
                $this->newLine();
                $this->line('To enable data storage in Firebase:');
                $this->line('1. Open Firebase Console (https://console.firebase.google.com/) -> project ' . config('firebase.project_id'));
                $this->line('2. Navigate to: Firestore Database -> Rules tab');
                $this->line('3. Update rules to:');
                $this->line("   rules_version = '2';");
                $this->line("   service cloud.firestore {");
                $this->line("     match /databases/{database}/documents {");
                $this->line("       match /{document=**} { allow read, write: if true; }");
                $this->line("     }");
                $this->line("   }");
                $this->line('4. Click Publish, then re-run: php artisan firebase:sync');
                $this->newLine();
                $this->line('Tip: You can pass --force to attempt document sync anyway.');
                return Command::FAILURE;
            }
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
                ['Products', $counts['products'] ?? 0],
                ['Sales / Orders', $counts['sales'] ?? 0],
                ['Customers', $counts['customers'] ?? 0],
                ['Suppliers', $counts['suppliers'] ?? 0],
                ['Categories', $counts['categories'] ?? 0],
                ['Brands', $counts['brands'] ?? 0],
                ['Purchases', $counts['purchases'] ?? 0],
                ['Expenses', $counts['expenses'] ?? 0],
                ['Returns', $counts['returns'] ?? 0],
                ['Settings', $counts['settings'] ?? 0],
                ['Users', $counts['users'] ?? 0],
            ]
        );

        $total = array_sum($counts);
        $this->info("Database sync completed! Total records synchronized to Firebase Firestore: {$total}");
        return Command::SUCCESS;
    }
}
