<?php

return [
    'api_key' => env('FIREBASE_API_KEY', 'AIzaSyBc5n8S7mzPA99K6TmuKVT7n7whLRYCFKg'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'pos-management-88866.firebaseapp.com'),
    'project_id' => env('FIREBASE_PROJECT_ID', 'pos-management-88866'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'pos-management-88866.firebasestorage.app'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '1089593768258'),
    'app_id' => env('FIREBASE_APP_ID', '1:1089593768258:web:7a6428fcb624a812e530db'),
    'measurement_id' => env('FIREBASE_MEASUREMENT_ID', 'G-Q021Y54GX7'),

    /*
    |--------------------------------------------------------------------------
    | Firestore REST API Endpoint
    |--------------------------------------------------------------------------
    */
    'firestore_base_url' => 'https://firestore.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID', 'pos-management-88866') . '/databases/(default)/documents',
];
