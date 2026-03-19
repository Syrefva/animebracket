<?php

// Database credentials (omit DB_DISABLE to enable DB; define it to disable)
define('DB_HOST', $_SERVER['DB_HOST'] ?? 'localhost'); // Set by nginx in Docker; 'db' = Compose service
define('DB_USER', 'animebracket');
define('DB_PASS', 'thisisatestpassword');
define('DB_NAME', 'anime_bracket');

// Whether to enable the global exception handler. Generally
// enable for production
define('HANDLE_EXCEPTIONS', true);

// Dev login: enable the bottom-right overlay to create/switch dev users (no Reddit). Remove for production.
define('DEV_LOGIN', true);
