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
 */
$categories = [
    [
        'id' => 'wle-india-2023',
        'name' => 'Wiki Loves Earth India 2025',
        'slug' => 'wiki-loves-earth-india-2025',
        'description' => 'Capturing natural heritage including national parks, wildlife sanctuaries, and protected areas across India.',
        'categoryName' => 'Images_from_Wiki_Loves_Earth_2025_in_India',
        'icon' => '🌍',
        'year' => '2023',
        'color1' => '#10B981',
        'color2' => '#059669'
    ],
    [
        'id' => 'wlb-india-2024',
        'name' => 'Wiki Loves Birds India 2024',
        'slug' => 'wiki-loves-birds-india-2024',
        'description' => 'Photography contest celebrating Indian bird diversity with thousands of contributions from nature photographers across India.',
        'categoryName' => 'Images_from_Wiki_Loves_Birds_India_2024',
        'icon' => '🦅',
        'year' => '2024',
        'color1' => '#3B82F6',
        'color2' => '#1D4ED8'
    ],
    [
        'id' => 'wlm-india-2023',
        'name' => 'Wiki Loves Monuments India 2023',
        'slug' => 'wiki-loves-monuments-india-2023',
        'description' => 'Documenting India\'s rich architectural heritage including temples, forts, palaces, and historical monuments.',
        'categoryName' => 'Images_from_Wiki_Loves_Monuments_2023_in_India',
        'icon' => '🏛️',
        'year' => '2023',
        'color1' => '#F59E0B',
        'color2' => '#D97706'
    ],
    [
        'id' => 'wla-2023',
        'name' => 'Wiki Loves Africa 2025',
        'slug' => 'wiki-loves-africa-2025',
        'description' => 'Celebrating African culture, heritage, and natural beauty through collaborative photography efforts.',
        'categoryName' => 'Images_from_Wiki_Loves_Africa_2025',
        'icon' => '🌍',
        'year' => '2023',
        'color1' => '#EF4444',
        'color2' => '#DC2626'
    ],
    [
        'id' => 'wlf-2023',
        'name' => 'Wiki Loves Folklore 2025',
        'slug' => 'wiki-loves-folklore-2025',
        'description' => 'Traditional cultural expressions, practices, festivals, and folklore from around the world.',
        'categoryName' => 'Images_from_Wiki_Loves_Folklore_2025',
        'icon' => '🎭',
        'year' => '2023',
        'color1' => '#8B5CF6',
        'color2' => '#7C3AED'
    ],
    [
        'id' => 'wsc-2024-ukraine',
        'name' => 'Wiki Science Competition 2024 in Ukraine',
        'slug' => 'wiki-science-competition-2024-in-ukraine',
        'description' => 'Wiki Science Competiton photography contest to capture  scientific images and innovation in Ukraine.',
        'categoryName' => 'Images_from_Wiki_Science_Competition_2024_in_Ukraine',
        'icon' => '🌸',
        'year' => '2022',
        'color1' => '#EC4899',
        'color2' => '#DB2777'
    ]
];

try {
    /**
     * Load categories from external JSON file (optional)
     * Uncomment this section if you want to load from a JSON file instead
     */
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
    
    // Validate categories data
    $validatedCategories = [];
    foreach ($categories as $category) {
        if (isset($category['id'], $category['name'], $category['categoryName'])) {
            // Ensure all required fields have default values
            $validatedCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'] ?? strtolower(str_replace(' ', '-', $category['name'])),
                'description' => $category['description'] ?? '',
                'categoryName' => $category['categoryName'],
                'icon' => $category['icon'] ?? '📊',
                'year' => $category['year'] ?? '',
                'color1' => $category['color1'] ?? '#3B82F6',
                'color2' => $category['color2'] ?? '#1D4ED8'
            ];
        }
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'categories' => $validatedCategories,
        'count' => count($validatedCategories),
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log error for debugging
    error_log('Categories API Error: ' . $e->getMessage());
}
?>