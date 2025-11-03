<?php
/**
 * Wikimedia Commons Dashboard API
 * 
 * This API queries the Wikimedia database and returns data in JSON format
 * for the Vue.js dashboard to consume.
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

// Load configuration
require_once __DIR__ . '/config.php';

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
 * Query the database
 */
function queryDatabase($dbConfig, $category) {
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30
        ]);

        // SQL Query with parameterized category
        // This query retrieves image data from the categorylinks and image tables
        $sql = "
            SELECT 
                cl.cl_from,
                cl.cl_to,
                img.img_name as File,
                DATE_FORMAT(img.img_timestamp, '%Y%m%d') as imgdate,
                img.img_timestamp,
                img.img_size,
                COALESCE(img.img_metadata, '{}') as img_metadata,
                COALESCE(actor.actor_name, 'Unknown') as actor_name
            FROM 
                categorylinks cl
            INNER JOIN 
                page ON cl.cl_from = page.page_id
            INNER JOIN 
                image img ON page.page_title = img.img_name
            LEFT JOIN
                actor ON img.img_actor = actor.actor_id
            WHERE 
                cl.cl_to = :category
                AND page.page_namespace = 6
            ORDER BY 
                img.img_timestamp DESC
            LIMIT 10000
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['category' => $category]);
        $results = $stmt->fetchAll();

        // Format the data to match the expected structure
        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                (int)$row['cl_from'],
                $row['cl_to'],
                $row['File'],
                $row['imgdate'],
                $row['img_timestamp'],
                (int)$row['img_size'],
                $row['img_metadata'],
                $row['actor_name']
            ];
        }

        return [
            'success' => true,
            'rows' => $rows,
            'count' => count($rows),
            'timestamp' => date('Y-m-d H:i:s'),
            'query_time' => $stmt->rowCount(),
            'category' => $category
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'category' => $category
        ];
    }
}

/**
 * Generate mock data for development/testing
 */
function generateMockData($category) {
    $mockRows = [];
    $cameras = ['Canon EOS 5D Mark IV', 'Nikon D850', 'Sony Alpha 7R IV', 'Fujifilm X-T4'];
    $users = ['NaturePhotographer123', 'BirdWatcher_India', 'WildlifeExplorer', 'PhotographyLover'];
    
    for ($i = 1; $i <= 50; $i++) {
        $date = date('Ymd', strtotime("-{$i} days"));
        $timestamp = date('YmdHis', strtotime("-{$i} days"));
        $size = rand(500000, 5000000);
        
        $hasGPS = rand(1, 3) === 1; // 33% chance of GPS data
        $metadata = [
            'data' => [
                'Model' => $cameras[array_rand($cameras)]
            ]
        ];
        
        if ($hasGPS) {
            $metadata['data']['GPSLatitude'] = rand(800, 3500) / 100; // India latitude range
            $metadata['data']['GPSLongitude'] = rand(6800, 9700) / 100; // India longitude range
        }
        
        $mockRows[] = [
            $i,
            $category,
            "Sample_Bird_Photo_{$i}.jpg",
            $date,
            $timestamp,
            $size,
            json_encode($metadata),
            $users[array_rand($users)]
        ];
    }
    
    return [
        'success' => true,
        'rows' => $mockRows,
        'count' => count($mockRows),
        'timestamp' => date('Y-m-d H:i:s'),
        'category' => $category,
        'mock_data' => true
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
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Validate category name (basic security)
    if (!preg_match('/^[a-zA-Z0-9_\-()\s]+$/', $category)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid category name. Only alphanumeric characters, spaces, hyphens, underscores and parentheses are allowed.',
            'timestamp' => date('Y-m-d H:i:s')
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
            $data['cache_age'] = time() - filemtime($cacheFile);
            $data['category'] = $category;
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Check if we should use mock data (for development)
    // Load data for wiki-loves-birds-india-2024 from a json file to test the output
    $useMockData = isset($_GET['mock']) && $_GET['mock'] === '1';
    $useCatData = isset($_GET['cat']) && $_GET['cat'] === '1';
    if ($useMockData) {
        // Generate mock data
        $data = generateMockData($category);
    } elseif ($useCatData){
        $jsonData = __DIR__ . '/sample-data.json';
    } else {
        // Query fresh data from database
        $data = queryDatabase($dbConfig, $category);
    }
    
    if ($data['success']) {
        // Save to cache (only if not mock data)
        if (!isset($data['mock_data'])) {
            saveCacheData($cacheFile, $data);
        }
        $data['cached'] = false;
        $data['category'] = $category;
    }

    if ($useCatData){
        echo json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log error for debugging
    error_log('Dashboard API Error: ' . $e->getMessage());
}
?>