<?php
/**
 * JWT Configuration Example
 * 
 * Copy this file to jwt.php and update with a strong secret key
 */

return [
    'secret_key' => 'CHANGE_THIS_TO_A_STRONG_RANDOM_SECRET_KEY',
    'algorithm' => 'HS256',
    'expiration' => 86400 // 24 hours in seconds
];

