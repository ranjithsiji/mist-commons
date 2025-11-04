<?php
/**
 * Wikimedia Commons Dashboard API
 * 
 * This API queries the Wikimedia Commons replica database using the Database class
 * and returns category statistics in JSON format for the Vue.js dashboard.
 * 
 * @author Ranjith Siji
 * @version 2.0
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
 */
function queryCommonsDatabase($category) {
    try {
        $startTime = microtime(true);
        $db = Database::getInstance();
        
        // SQL Query to get category statistics
        // This query retrieves comprehensive image data from categorylinks, page, and image tables
        $sql = "
            SELECT 
                cl.cl_from,
                cl.cl_to,
                img.img_name as filename,
                DATE_FORMAT(img.img_timestamp, '%Y-%m-%d') as upload_date,
                DATE_FORMAT(img.img_timestamp, '%Y%m%d') as imgdate,
                img.img_timestamp,
                img.img_size,
                img.img_width,
                img.img_height,
                img.img_bits,
                img.img_media_type,
                img.img_major_mime,
                img.img_minor_mime,
                COALESCE(img.img_metadata, '{}') as img_metadata,
                COALESCE(actor.actor_name, 'Unknown') as uploader,
                page.page_title,
                page.page_len
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
        
        // Process and enhance the results
        $processedRows = [];
        $statistics = [
            'total_files' => 0,
            'total_size_bytes' => 0,
            'total_size_mb' => 0,
            'uploaders' => [],
            'file_types' => [],
            'upload_timeline' => [],
            'gps_enabled_count' => 0
        ];
        
        foreach ($results as $row) {
            // Parse metadata for additional insights
            $metadata = [];
            $hasGPS = false;
            
            if (!empty($row['img_metadata']) && $row['img_metadata'] !== '{}') {
                $decodedMetadata = json_decode($row['img_metadata'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMetadata)) {
                    $metadata = $decodedMetadata;
                    
                    // Check for GPS coordinates
                    if (isset($metadata['GPSLatitude']) || isset($metadata['GPSLongitude']) ||
                        (isset($metadata['data']) && (isset($metadata['data']['GPSLatitude']) || isset($metadata['data']['GPSLongitude'])))) {
                        $hasGPS = true;
                        $statistics['gps_enabled_count']++;
                    }
                }
            }
            
            // Collect statistics
            $statistics['total_files']++;
            $statistics['total_size_bytes'] += (int)$row['img_size'];
            
            // Track uploaders
            $uploader = $row['uploader'];
            if (!isset($statistics['uploaders'][$uploader])) {
                $statistics['uploaders'][$uploader] = 0;
            }
            $statistics['uploaders'][$uploader]++;
            
            // Track file types
            $mimeType = $row['img_major_mime'] . '/' . $row['img_minor_mime'];
            if (!isset($statistics['file_types'][$mimeType])) {
                $statistics['file_types'][$mimeType] = 0;
            }
            $statistics['file_types'][$mimeType]++;
            
            // Track upload timeline (by month)
            $uploadMonth = date('Y-m', strtotime($row['img_timestamp']));
            if (!isset($statistics['upload_timeline'][$uploadMonth])) {
                $statistics['upload_timeline'][$uploadMonth] = 0;
            }
            $statistics['upload_timeline'][$uploadMonth]++;
            
            // Format row data
            $processedRows[] = [
                'id' => (int)$row['cl_from'],
                'category' => $row['cl_to'],
                'filename' => $row['filename'],
                'page_title' => $row['page_title'],
                'upload_date' => $row['upload_date'],
                'imgdate' => $row['imgdate'],
                'timestamp' => $row['img_timestamp'],
                'size_bytes' => (int)$row['img_size'],
                'size_mb' => round((int)$row['img_size'] / 1024 / 1024, 2),
                'dimensions' => [
                    'width' => (int)$row['img_width'],
                    'height' => (int)$row['img_height']
                ],
                'media_type' => $row['img_media_type'],
                'mime_type' => $mimeType,
                'uploader' => $uploader,
                'has_gps' => $hasGPS,
                'metadata_available' => !empty($metadata)
            ];
        }
        
        // Calculate final statistics
        $statistics['total_size_mb'] = round($statistics['total_size_bytes'] / 1024 / 1024, 2);
        $statistics['unique_uploaders'] = count($statistics['uploaders']);
        $statistics['gps_percentage'] = $statistics['total_files'] > 0 ? 
            round(($statistics['gps_enabled_count'] / $statistics['total_files']) * 100, 2) : 0;
        
        // Sort uploaders by contribution count
        arsort($statistics['uploaders']);
        $statistics['top_uploaders'] = array_slice($statistics['uploaders'], 0, 10, true);
        
        // Sort file types
        arsort($statistics['file_types']);
        
        // Sort timeline
        ksort($statistics['upload_timeline']);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'success' => true,
            'data' => $processedRows,
            'statistics' => $statistics,
            'meta' => [
                'category' => $category,
                'query_time_ms' => $executionTime,
                'total_records' => count($processedRows),
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => 'commonswiki.analytics.db.svc.wikimedia.cloud'
            ]
        ];

    } catch (Exception $e) {
        error_log('Database query failed for category ' . $category . ': ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Database query failed: ' . $e->getMessage(),
            'category' => $category,
            'timestamp' => date('Y-m-d H:i:s')
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
        
        // Validate structure and transform to new format
        if (!isset($sampleData['rows']) || !is_array($sampleData['rows'])) {
            return [
                'success' => false,
                'error' => 'Sample data file must contain a "rows" array',
                'category' => $category
            ];
        }
        
        // Transform old format to new format
        $transformedData = [];
        $statistics = [
            'total_files' => count($sampleData['rows']),
            'total_size_bytes' => 0,
            'total_size_mb' => 0,
            'uploaders' => [],
            'file_types' => [],
            'upload_timeline' => [],
            'gps_enabled_count' => 0
        ];
        
        foreach ($sampleData['rows'] as $index => $row) {
            if (is_array($row) && count($row) >= 8) {
                $sizeBytes = (int)($row[5] ?? 0);
                $statistics['total_size_bytes'] += $sizeBytes;
                
                $uploader = $row[7] ?? 'Unknown';
                if (!isset($statistics['uploaders'][$uploader])) {
                    $statistics['uploaders'][$uploader] = 0;
                }
                $statistics['uploaders'][$uploader]++;
                
                $transformedData[] = [
                    'id' => (int)($row[0] ?? $index),
                    'category' => $row[1] ?? $category,
                    'filename' => $row[2] ?? 'unknown.jpg',
                    'page_title' => $row[2] ?? 'unknown.jpg',
                    'upload_date' => isset($row[4]) ? date('Y-m-d', strtotime($row[4])) : date('Y-m-d'),
                    'imgdate' => $row[3] ?? date('Ymd'),
                    'timestamp' => $row[4] ?? date('YmdHis'),
                    'size_bytes' => $sizeBytes,
                    'size_mb' => round($sizeBytes / 1024 / 1024, 2),
                    'dimensions' => ['width' => 0, 'height' => 0],
                    'media_type' => 'BITMAP',
                    'mime_type' => 'image/jpeg',
                    'uploader' => $uploader,
                    'has_gps' => false,
                    'metadata_available' => !empty($row[6] ?? '')
                ];
            }
        }
        
        $statistics['total_size_mb'] = round($statistics['total_size_bytes'] / 1024 / 1024, 2);
        $statistics['unique_uploaders'] = count($statistics['uploaders']);
        arsort($statistics['uploaders']);
        $statistics['top_uploaders'] = array_slice($statistics['uploaders'], 0, 10, true);
        
        return [
            'success' => true,
            'data' => $transformedData,
            'statistics' => $statistics,
            'meta' => [
                'category' => $category,
                'query_time_ms' => 0,
                'total_records' => count($transformedData),
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => 'sample_data',
                'sample_data' => true
            ]
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
 * Generate mock data for development/testing
 */
function generateMockData($category) {
    $cameras = ['Canon EOS 5D Mark IV', 'Nikon D850', 'Sony Alpha 7R IV', 'Fujifilm X-T4', 'Olympus OM-D E-M1'];
    $users = ['NaturePhotographer123', 'BirdWatcher_India', 'WildlifeExplorer', 'PhotographyLover', 'IndiaHeritage'];
    $fileTypes = ['image/jpeg', 'image/png', 'image/tiff'];
    
    $mockData = [];
    $statistics = [
        'total_files' => 50,
        'total_size_bytes' => 0,
        'uploaders' => [],
        'file_types' => [],
        'upload_timeline' => [],
        'gps_enabled_count' => 0
    ];
    
    for ($i = 1; $i <= 50; $i++) {
        $daysAgo = rand(1, 365);
        $timestamp = date('YmdHis', strtotime("-{$daysAgo} days"));
        $uploadDate = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $uploadMonth = date('Y-m', strtotime("-{$daysAgo} days"));
        $sizeBytes = rand(500000, 5000000);
        $uploader = $users[array_rand($users)];
        $mimeType = $fileTypes[array_rand($fileTypes)];
        $hasGPS = rand(1, 3) === 1; // 33% chance of GPS data
        
        // Update statistics
        $statistics['total_size_bytes'] += $sizeBytes;
        if (!isset($statistics['uploaders'][$uploader])) {
            $statistics['uploaders'][$uploader] = 0;
        }
        $statistics['uploaders'][$uploader]++;
        
        if (!isset($statistics['file_types'][$mimeType])) {
            $statistics['file_types'][$mimeType] = 0;
        }
        $statistics['file_types'][$mimeType]++;
        
        if (!isset($statistics['upload_timeline'][$uploadMonth])) {
            $statistics['upload_timeline'][$uploadMonth] = 0;
        }
        $statistics['upload_timeline'][$uploadMonth]++;
        
        if ($hasGPS) {
            $statistics['gps_enabled_count']++;
        }
        
        $mockData[] = [
            'id' => $i,
            'category' => $category,
            'filename' => "Sample_Photo_{$i}.jpg",
            'page_title' => "Sample_Photo_{$i}.jpg",
            'upload_date' => $uploadDate,
            'imgdate' => date('Ymd', strtotime("-{$daysAgo} days")),
            'timestamp' => $timestamp,
            'size_bytes' => $sizeBytes,
            'size_mb' => round($sizeBytes / 1024 / 1024, 2),
            'dimensions' => [
                'width' => rand(1920, 6000),
                'height' => rand(1080, 4000)
            ],
            'media_type' => 'BITMAP',
            'mime_type' => $mimeType,
            'uploader' => $uploader,
            'has_gps' => $hasGPS,
            'metadata_available' => true
        ];
    }
    
    // Finalize statistics
    $statistics['total_size_mb'] = round($statistics['total_size_bytes'] / 1024 / 1024, 2);
    $statistics['unique_uploaders'] = count($statistics['uploaders']);
    $statistics['gps_percentage'] = round(($statistics['gps_enabled_count'] / 50) * 100, 2);
    arsort($statistics['uploaders']);
    $statistics['top_uploaders'] = array_slice($statistics['uploaders'], 0, 10, true);
    arsort($statistics['file_types']);
    ksort($statistics['upload_timeline']);
    
    return [
        'success' => true,
        'data' => $mockData,
        'statistics' => $statistics,
        'meta' => [
            'category' => $category,
            'query_time_ms' => rand(100, 500),
            'total_records' => count($mockData),
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => 'mock_data',
            'mock_data' => true
        ]
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
            'timestamp' => date('Y-m-d H:i:s'),
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
    
    // Validate category name (enhanced security)
    if (!preg_match('/^[a-zA-Z0-9_\-().\s\/]+$/', $category) || strlen($category) > 255) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid category name. Only alphanumeric characters, spaces, hyphens, underscores, periods, forward slashes and parentheses are allowed. Maximum 255 characters.',
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
            $data['meta']['cached'] = true;
            $data['meta']['cache_age_seconds'] = time() - filemtime($cacheFile);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Determine data source
    $useMockData = isset($_GET['mock']) && $_GET['mock'] === '1';
   // $useSampleData = isset($_GET['sample']) && $_GET['sample'] === '1';
   $useSampleData =1 ;
    
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
        if (!isset($data['meta']['mock_data']) && !isset($data['meta']['sample_data'])) {
            saveCacheData($cacheFile, $data);
        }
        $data['meta']['cached'] = false;
    }

    // Set appropriate HTTP status code
    http_response_code($data['success'] ? 200 : 500);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    
    // Log error for debugging
    error_log('Dashboard API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
?>