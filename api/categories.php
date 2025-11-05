<?php
/**
 * Categories API
 * 
 * Returns list of Wikimedia Commons categories for the dashboard
 * Add or remove categories by editing the $categories array below
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

/**
 * Categories Configuration
 * 
 * Each category should have:
 * - id: Unique identifier
 * - name: Display name
 * - slug: URL-friendly identifier
 * - description: Brief description
 * - categoryName: Exact category name from Wikimedia Commons
 * - icon: Emoji icon
 * - year: Year or time period
 * - color1, color2: Gradient colors for the card
 * - startDate: Campaign start date (YYYY-MM-DD)
 * - endDate: Campaign end date (YYYY-MM-DD) or TODAY to use current date
 */
$categories = [
    // Fallback examples only; will be overridden by categories.json if present
];

try {
    // Load categories from JSON file if present
    $jsonFile = __DIR__ . '/categories.json';
    if (file_exists($jsonFile)) {
        $jsonContent = file_get_contents($jsonFile);
        $categoriesFromFile = json_decode($jsonContent, true);
        
        if (json_last_error() === JSON_ERROR_NONE && 
            $categoriesFromFile && 
            isset($categoriesFromFile['categories']) && 
            is_array($categoriesFromFile['categories'])) {
            $categories = $categoriesFromFile['categories'];
        }
    }
    
    // Validate and enrich categories
    $validatedCategories = [];
    $todayStr = date('Y-m-d');
    foreach ($categories as $category) {
        if (isset($category['id'], $category['name'], $category['categoryName'])) {
            $startDate = $category['startDate'] ?? null;
            $endDateRaw = $category['endDate'] ?? null;
            $endDate = $endDateRaw === 'TODAY' ? $todayStr : $endDateRaw;

            $validatedCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'] ?? strtolower(str_replace(' ', '-', $category['name'])),
                'description' => $category['description'] ?? '',
                'categoryName' => $category['categoryName'],
                'icon' => $category['icon'] ?? '📊',
                'year' => $category['year'] ?? '',
                'color1' => $category['color1'] ?? '#3B82F6',
                'color2' => $category['color2'] ?? '#1D4ED8',
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $validatedCategories,
        'count' => count($validatedCategories),
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.1.0'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    error_log('Categories API Error: ' . $e->getMessage());
}
?>
