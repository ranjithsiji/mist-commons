<?php
/**
 * Wiki Loves Birds India 2024 - Dashboard API
 * 
 * This API queries the Wikimedia database and returns data in JSON format
 * for the Vue.js dashboard to consume.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
// Update these with your Wikimedia database credentials
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'commonswiki_p',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];

// Cache configuration
$cacheFile = __DIR__ . '/../cache/dashboard_data.json';
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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
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
                img.img_metadata,
                actor.actor_name as actor_name
            FROM 
                categorylinks cl
            INNER JOIN 
                page ON cl.cl_from = page.page_id
            INNER JOIN 
                image img ON page.page_title = img.img_name
            INNER JOIN
                actor ON img.img_actor = actor.actor_id
            WHERE 
                cl.cl_to = :category
                AND page.page_namespace = 6
            ORDER BY 
                img.img_timestamp DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['category' => $category]);
        $results = $stmt->fetchAll();

        // Format the data to match the expected structure
        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                $row['cl_from'],
                $row['cl_to'],
                $row['File'],
                $row['imgdate'],
                $row['img_timestamp'],
                $row['img_size'],
                $row['img_metadata'],
                $row['actor_name']
            ];
        }

        return [
            'success' => true,
            'rows' => $rows,
            'count' => count($rows),
            'timestamp' => date('Y-m-d H:i:s')
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

// Main execution
try {
    // Get category from query parameter
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    
    if (empty($category)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Category parameter is required'
        ]);
        exit;
    }
    
    // Check if we should force refresh (bypass cache)
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    
    // Create unique cache file for each category
    $cacheFile = __DIR__ . '/../cache/dashboard_' . md5($category) . '.json';

    if (!$forceRefresh && isCacheValid($cacheFile, $cacheTime)) {
        // Return cached data
        $data = getCachedData($cacheFile);
        if ($data) {
            $data['cached'] = true;
            $data['cache_age'] = time() - filemtime($cacheFile);
            $data['category'] = $category;
            echo json_encode($data);
            exit;
        }
    }

    // Query fresh data from database
    $data = queryDatabase($dbConfig, $category);
    
    if ($data['success']) {
        // Save to cache
        saveCacheData($cacheFile, $data);
        $data['cached'] = false;
        $data['category'] = $category;
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>