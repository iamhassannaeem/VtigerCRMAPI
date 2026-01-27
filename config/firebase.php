<?php
/**
 * Firebase Configuration Example
 * 
 * Copy this file to firebase.php and update with your Firebase credentials
 * 
 * IMPORTANT: Google is deprecating the legacy FCM API (server key).
 * This configuration uses HTTP v1 API with service account authentication.
 * 
 * To get your service account JSON file:
 * 1. Go to Firebase Console > Project Settings > Service Accounts
 * 2. Click "Generate New Private Key"
 * 3. Save the JSON file as firebase-service-account.json in the project root
 */

return [
    // Path to your Firebase service account JSON file
    // Download from: Firebase Console > Project Settings > Service Accounts > Generate New Private Key
    'service_account_path' => __DIR__ . '/../firebase-service-account.json',
    
    // Legacy server key (deprecated, kept for backward compatibility)
    // 'server_key' => 'YOUR_FIREBASE_SERVER_KEY_HERE',
];
