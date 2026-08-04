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
        'max_files' => 120000,

        // Bytes of img_metadata read per file, so that full EXIF is not
        // transferred for every row.
        //
        // Do not lower this casually. GPS coordinates are not near the front of
        // the blob: measured on Wiki Loves Africa 2026, files whose metadata
        // exceeds 4 KB carry GPSLatitude at byte offset 25,000 on average and
        // 28,264 at worst, so a 4 KB or 8 KB cut silently dropped every one of
        // them from the map. Blobs there average only ~1.5 KB and top out at
        // ~28.4 KB, so this ceiling costs almost nothing while keeping every
        // coordinate. Rows still cut short have their fields recovered from the
        // text, but recovery cannot invent what was never fetched.
        'metadata_head_bytes' => 32768
    ]
];
?>