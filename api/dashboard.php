<?php
/**
 * Wikimedia Commons Dashboard API
 * 
 * This API queries the Wikimedia Commons replica database using the Database class
 * and returns category statistics in JSON format for the Vue.js dashboard.
 * 
 * Rewritten to match the original working Quarry SQL output format
 * 
 * @author Ranjith Siji
 * @version 3.0
 */

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Only GET requests are supported.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Load database class
require_once __DIR__ . '/database.php';

// Cache configuration
$cacheDir = __DIR__ . '/../cache';
$cacheTime = 3600; // Cache for 1 hour (3600 seconds)

/**
 * Check if cache is valid
 */
function isCacheValid($cacheFile, $cacheTime) {
    if (!file_exists($cacheFile)) {
        return false;
    }
    return (time() - filemtime($cacheFile)) < $cacheTime;
}

/**
 * Get data from cache
 */
function getCachedData($cacheFile) {
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        return json_decode($data, true);
    }
    return null;
}

/**
 * Save data to cache
 */
function saveCacheData($cacheFile, $data) {
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    file_put_contents($cacheFile, json_encode($data));
}

/**
 * Query the Wikimedia Commons database using the Database class
 * Returns data in the original working format that matches Quarry output
 */
function queryCommonsDatabase($category) {
    try {
        $startTime = microtime(true);
        $db = Database::getInstance();
        
        // Original SQL Query that matches the working Quarry format
        $sql = "
            SELECT 
                cl.cl_from,
                cl.cl_to,
                img.img_name as filename,
                DATE_FORMAT(img.img_timestamp, '%Y%m%d') as imgdate,
                img.img_timestamp,
                img.img_size,
                COALESCE(img.img_metadata, '') as img_metadata,
                COALESCE(actor.actor_name, 'Unknown') as uploader
            FROM 
                categorylinks cl
            INNER JOIN 
                page ON cl.cl_from = page.page_id
            INNER JOIN 
                image img ON page.page_title = img.img_name
            LEFT JOIN
                actor ON img.img_actor = actor.actor_id
            WHERE 
                cl.cl_to = ?
                AND page.page_namespace = 6
            ORDER BY 
                img.img_timestamp DESC
            LIMIT 10000
        ";

        $results = $db->executeQuery($sql, [$category]);
        
        // Convert to the original "rows" format that was working
        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                (int)$row['cl_from'],           // 0: cl_from (page id)
                $row['cl_to'],                  // 1: category name
                $row['filename'],               // 2: filename
                $row['imgdate'],                // 3: image date (YYYYMMDD)
                $row['img_timestamp'],          // 4: full timestamp
                (int)$row['img_size'],          // 5: file size in bytes
                $row['img_metadata'] ?: '{}',   // 6: metadata (empty object if null)
                $row['uploader']                // 7: uploader username
            ];
        }
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Return in the original format
        return [
            'success' => true,
            'rows' => $rows,
            'count' => count($rows),
            'timestamp' => date('c'),
            'category' => $category,
            'cached' => false,
            'query_time_ms' => $executionTime
        ];

    } catch (Exception $e) {
        error_log('Database query failed for category ' . $category . ': ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database query failed: ' . $e->getMessage(),
            'category' => $category,
            'timestamp' => date('c')
        ];
    }
}

/**
 * Load sample data from JSON file for development/testing
 */
function loadSampleData($category = 'sample') {
    $sampleFile = __DIR__ . '/sample-data.json';
    
    if (!file_exists($sampleFile)) {
        return [
            'success' => false,
            'error' => 'Sample data file not found: ' . $sampleFile,
            'category' => $category
        ];
    }
    
    try {
        $jsonContent = file_get_contents($sampleFile);
        $sampleData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON in sample data file: ' . json_last_error_msg(),
                'category' => $category
            ];
        }
        
        // Return in the original format
        return [
            'success' => true,
            'rows' => $sampleData['rows'] ?? [],
            'count' => count($sampleData['rows'] ?? []),
            'timestamp' => date('c'),
            'category' => $category,
            'cached' => false,
            'sample_data' => true
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Error reading sample data: ' . $e->getMessage(),
            'category' => $category
        ];
    }
}

/**
 * Generate mock data for development/testing in original format
 */
function generateMockData($category) {
    $users = ['NaturePhotographer123', 'BirdWatcher_India', 'WildlifeExplorer', 'PhotographyLover', 'IndiaHeritage'];
    
    $mockRows = [];
    
    for ($i = 1; $i <= 50; $i++) {
        $daysAgo = rand(1, 365);
        $timestamp = date('YmdHis', strtotime("-{$daysAgo} days"));
        $imgdate = date('Ymd', strtotime("-{$daysAgo} days"));
        $sizeBytes = rand(500000, 15000000); // 0.5MB to 15MB
        $uploader = $users[array_rand($users)];
        
        // Generate some mock metadata (some with GPS, some without)
        $hasGPS = rand(1, 4) === 1; // 25% chance
        $metadata = '{}';
        if ($hasGPS) {
            $lat = 8 + rand(0, 2800) / 100; // Kerala area roughly
            $lon = 76 + rand(0, 600) / 100;
            $metadata = json_encode([
                'data' => [
                    'GPSLatitude' => $lat,
                    'GPSLongitude' => $lon,
                    'Model' => 'Canon EOS 5D Mark IV'
                ]
            ]);
        } else {
            // Some files have camera info but no GPS
            if (rand(1, 3) === 1) {
                $cameras = ['Canon EOS 5D Mark IV', 'Nikon D850', 'Sony Alpha 7R IV'];
                $metadata = json_encode([
                    'data' => [
                        'Model' => $cameras[array_rand($cameras)]
                    ]
                ]);
            }
        }
        
        $mockRows[] = [
            $i,                                    // 0: cl_from
            $category,                             // 1: category
            "Sample_Photo_{$i}.jpg",               // 2: filename
            $imgdate,                              // 3: imgdate (YYYYMMDD)
            $timestamp,                            // 4: timestamp
            $sizeBytes,                            // 5: size in bytes
            $metadata,                             // 6: metadata
            $uploader                              // 7: uploader
        ];
    }
    
    return [
        'success' => true,
        'rows' => $mockRows,
        'count' => count($mockRows),
        'timestamp' => date('c'),
        'category' => $category,
        'cached' => false,
        'mock_data' => true,
        'query_time_ms' => rand(50, 200)
    ];
}

// Main execution
try {
    // Get category from query parameter
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    
    if (empty($category)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Category parameter is required. Please provide ?category=CategoryName',
            'timestamp' => date('c'),
            'usage' => [
                'endpoint' => $_SERVER['REQUEST_URI'],
                'parameters' => [
                    'category' => 'Required - Category name from Wikimedia Commons',
                    'refresh' => 'Optional - Set to "1" to bypass cache',
                    'mock' => 'Optional - Set to "1" to use mock data',
                    'sample' => 'Optional - Set to "1" to use sample data'
                ]
            ]
        ]);
        exit;
    }
    
    // Validate category name
    if (!preg_match('/^[a-zA-Z0-9_\-().\s\/]+$/', $category) || strlen($category) > 255) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid category name. Only alphanumeric characters, spaces, hyphens, underscores, periods, forward slashes and parentheses are allowed. Maximum 255 characters.',
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    // Check if we should force refresh (bypass cache)
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    
    // Create unique cache file for each category
    $cacheFile = $cacheDir . '/dashboard_' . md5($category) . '.json';

    if (!$forceRefresh && isCacheValid($cacheFile, $cacheTime)) {
        // Return cached data
        $data = getCachedData($cacheFile);
        if ($data) {
            $data['cached'] = true;
            $data['cache_age_seconds'] = time() - filemtime($cacheFile);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Determine data source based on URL parameters
    $useMockData = isset($_GET['mock']) && $_GET['mock'] === '1';
    $useSampleData = isset($_GET['sample']) && $_GET['sample'] === '1';
    
    if ($useMockData) {
        // Generate mock data for development
        $data = generateMockData($category);
    } elseif ($useSampleData) {
        // Load sample data from JSON file
        $data = loadSampleData($category);
    } else {
        // Query fresh data from Wikimedia Commons database
        $data = queryCommonsDatabase($category);
    }
    
    if ($data['success']) {
        // Save to cache (only if real database data)
        if (!isset($data['mock_data']) && !isset($data['sample_data'])) {
            saveCacheData($cacheFile, $data);
        }
        $data['cached'] = false;
    }

    // Set appropriate HTTP status code
    http_response_code($data['success'] ? 200 : 500);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'timestamp' => date('c'),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    
    // Log error for debugging
    error_log('Dashboard API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
?>