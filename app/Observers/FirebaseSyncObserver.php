<?php

namespace App\Observers;

use App\Services\FirebaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseSyncObserver
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Determine whether Firebase synchronization should execute.
     */
    protected function shouldSync(): bool
    {
        return (bool) config('firebase.enabled', true);
    }

    /**
     * Handle the Model "saved" event (after created or updated).
     */
    public function saved(Model $model): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        try {
            $this->firebase->storeModel($model);
        } catch (Throwable $e) {
            Log::warning("[FirebaseSyncObserver] Error syncing saved " . get_class($model) . " #{$model->getKey()}: " . $e->getMessage());
        }
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (!$this->shouldSync()) {
            return;
        }

        try {
            $this->firebase->deleteModel($model);
        } catch (Throwable $e) {
            Log::warning("[FirebaseSyncObserver] Error syncing deleted " . get_class($model) . " #{$model->getKey()}: " . $e->getMessage());
        }
    }
}
