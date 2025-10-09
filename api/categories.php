<?php
/**
 * Categories API
 * 
 * Returns list of Wikimedia Commons categories for the dashboard
 * Add or remove categories by editing the $categories array below
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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
        'id' => 'wlb-india-2024',
        'name' => 'Wiki Loves Birds India 2024',
        'slug' => 'wiki-loves-birds-india-2024',
        'description' => 'Photography contest celebrating Indian bird diversity',
        'categoryName' => 'Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)',
        'icon' => '🦅',
        'year' => '2024',
        'color1' => '#3B82F6',
        'color2' => '#1D4ED8'
    ],
    [
        'id' => 'wlm-india-2023',
        'name' => 'Wiki Loves Monuments India 2023',
        'slug' => 'wiki-loves-monuments-india-2023',
        'description' => 'Documenting India\'s architectural heritage',
        'categoryName' => 'Images_from_Wiki_Loves_Monuments_2023_in_India',
        'icon' => '🏛️',
        'year' => '2023',
        'color1' => '#F59E0B',
        'color2' => '#D97706'
    ],
    [
        'id' => 'wle-india-2023',
        'name' => 'Wiki Loves Earth India 2023',
        'slug' => 'wiki-loves-earth-india-2023',
        'description' => 'Capturing natural heritage and protected areas',
        'categoryName' => 'Images_from_Wiki_Loves_Earth_2023_in_India',
        'icon' => '🌍',
        'year' => '2023',
        'color1' => '#10B981',
        'color2' => '#059669'
    ],
    [
        'id' => 'wla-2023',
        'name' => 'Wiki Loves Africa 2023',
        'slug' => 'wiki-loves-africa-2023',
        'description' => 'Celebrating African culture and heritage',
        'categoryName' => 'Images_from_Wiki_Loves_Africa_2023',
        'icon' => '🌍',
        'year' => '2023',
        'color1' => '#EF4444',
        'color2' => '#DC2626'
    ],
    [
        'id' => 'wlf-2023',
        'name' => 'Wiki Loves Folklore 2023',
        'slug' => 'wiki-loves-folklore-2023',
        'description' => 'Traditional cultural expressions and practices',
        'categoryName' => 'Images_from_Wiki_Loves_Folklore_2023',
        'icon' => '🎭',
        'year' => '2023',
        'color1' => '#8B5CF6',
        'color2' => '#7C3AED'
    ],
    [
        'id' => 'ceebies-2022',
        'name' => 'CEE Spring 2022',
        'slug' => 'cee-spring-2022',
        'description' => 'Central and Eastern European photo contest',
        'categoryName' => 'Images_from_CEE_Spring_2022',
        'icon' => '🌸',
        'year' => '2022',
        'color1' => '#EC4899',
        'color2' => '#DB2777'
    ]
];

/**
 * Load categories from external JSON file (optional)
 * Uncomment this section if you want to load from a JSON file instead
 */
/*
$jsonFile = __DIR__ . '/categories.json';
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $categoriesFromFile = json_decode($jsonContent, true);
    if ($categoriesFromFile && isset($categoriesFromFile['categories'])) {
        $categories = $categoriesFromFile['categories'];
    }
}
*/

// Return the categories
echo json_encode([
    'success' => true,
    'categories' => $categories,
    'count' => count($categories),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>