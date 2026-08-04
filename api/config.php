<?php
/**
 * Database Configuration for Wikimedia Toolforge
 * 
 * For Wikimedia Toolforge deployment, use replica database credentials
 * Documentation: https://wikitech.wikimedia.org/wiki/Help:Toolforge/Database
 */

// Configuration array
return [
    // Toolforge replica database configuration
    'toolforge' => [
        'host' => 'commonswiki.analytics.db.svc.wikimedia.cloud',
        'dbname' => 'commonswiki_p',
        'username' => getenv('MYSQL_USERNAME') ?: 's12345', // Your tool username
        'password' => getenv('MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    
    // Local development configuration
    'local' => [
        'host' => 'localhost',
        'dbname' => 'wikidata',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    
    // Cache settings
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'path' => __DIR__ . '/../cache/'
    ],
    
    // API settings
    'api' => [
        'rate_limit' => 100, // requests per minute
        'timeout' => 30 // seconds
    ],

    // Category query settings
    'query' => [
        // Rows fetched per statement. Large categories are read in batches so
        // that peak memory stays flat instead of growing with the category,
        // and each statement stays short enough to avoid replica timeouts.
        // Lower this if the tool hits memory limits; raise it to trade memory
        // for fewer round-trips.
        'batch_size' => 10000,

        // Above this many files a category cannot be assembled into a single
        // response and is refused with an explanation rather than failing
        // partway through.
        'max_files' => 120000
    ]
];
?>