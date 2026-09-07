<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirebaseController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Get Firebase connection status and health.
     */
    public function status(): JsonResponse
    {
        $status = $this->firebaseService->testConnection();
        return response()->json($status);
    }

    /**
     * Trigger database sync to Firebase Firestore.
     */
    public function sync(): JsonResponse
    {
        $counts = $this->firebaseService->syncAll();

        return response()->json([
            'success' => true,
            'message' => 'Successfully synchronized records to Firebase Firestore.',
            'counts' => $counts,
            'total' => array_sum($counts),
        ]);
    }

    /**
     * Get database snapshot payload for client-side Firebase batch storing.
     */
    public function exportPayload(): JsonResponse
    {
        $payload = $this->firebaseService->getExportPayload();
        return response()->json($payload);
    }
}
