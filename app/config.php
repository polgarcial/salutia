<?php
/**
 * General Configuration
 * 
 * This file contains general configuration settings for Salutia.
 */

// Application settings
define('APP_NAME', 'Salutia');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/salutia');
define('API_URL', APP_URL . '/backend');

// JWT Authentication settings
define('JWT_SECRET', 'your_jwt_secret_key_here'); // Change this in production
define('JWT_EXPIRATION', 3600); // Token expiration time in seconds (1 hour)

// File upload settings
define('UPLOAD_DIR', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// AI Integration settings
define('AI_ENABLED', true);
define('AI_API_KEY', 'your_ai_api_key_here'); // Replace with your actual API key
define('AI_MODEL', 'gpt-3.5-turbo'); // AI model to use

// Email settings
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_smtp_username');
define('SMTP_PASSWORD', 'your_smtp_password');
define('SMTP_FROM_EMAIL', 'noreply@salutia.com');
define('SMTP_FROM_NAME', 'Salutia');

// Logging settings
define('LOG_DIR', ROOT_PATH . '/logs');
define('LOG_LEVEL', 'debug'); // Options: debug, info, warning, error

// Timezone settings
date_default_timezone_set('Europe/Madrid');

// Security settings
define('ENABLE_CSRF', true);
define('SESSION_LIFETIME', 7200); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);

// Create necessary directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}
