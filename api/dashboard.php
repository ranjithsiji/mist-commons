<?php
/**
 * Wikimedia Commons Dashboard API
 * Adds optional date range filtering via start/end (YYYY-MM-DD). End may be TODAY (handled client-side categories API).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Max-Age: 3600');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Only GET requests are supported.', 'timestamp' => date('Y-m-d H:i:s')]);
    exit();
}
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/commons.php';
$cacheDir = __DIR__ . '/../cache';
$cacheTime = 3600;

// Query tuning lives in config.php so it can be adjusted per deployment.
$appConfig = @include __DIR__ . '/config.php';
$queryConfig = is_array($appConfig) && isset($appConfig['query']) ? $appConfig['query'] : [];

// Rows fetched per statement when reading a category.
define('CATEGORY_BATCH_SIZE', max(100, (int) ($queryConfig['batch_size'] ?? 10000)));

// Above this many files a category cannot be assembled into a single response.
// Also the backstop when Commons is unreachable and its count is unavailable.
define('MAX_CATEGORY_FILES', max(CATEGORY_BATCH_SIZE, (int) ($queryConfig['max_files'] ?? 120000)));
function isCacheValid($f, $t)
{
    return file_exists($f) && (time() - filemtime($f)) < $t;
}
function getCachedData($f)
{
    return file_exists($f) ? json_decode(file_get_contents($f), true) : null;
}
function saveCacheData($f, $d)
{
    $dir = dirname($f);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    file_put_contents($f, json_encode($d));
}

/**
 * Reduce an img_metadata blob to the few fields the dashboard actually uses.
 *
 * Full EXIF runs to several KB per file, so shipping it verbatim produces a
 * response of well over a hundred megabytes for a large campaign category —
 * enough to exhaust PHP's memory limit before the response is even sent.
 * Only the coordinates and camera model are ever read by the client.
 *
 * MediaWiki writes this column as JSON on current wikis and as a PHP
 * serialize() string on older rows, so both are accepted.
 */
function compactImageMetadata($blob)
{
    if (!is_string($blob) || $blob === '' || $blob === '0' || $blob === '{}') {
        return '{}';
    }

    $data = null;
    if ($blob[0] === '{' || $blob[0] === '[') {
        $decoded = json_decode($blob, true);
        if (is_array($decoded)) {
            // Some rows nest the fields under "data", others are already flat
            $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
        }
    } else {
        // Legacy PHP-serialised metadata. Guard against objects in the payload.
        $decoded = @unserialize($blob, ['allowed_classes' => false]);
        if (is_array($decoded)) {
            $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
        }
    }

    if (!is_array($data)) {
        // The blob is fetched truncated, so a large EXIF payload can arrive as
        // invalid JSON. Pull the handful of fields we need straight out of the
        // text rather than discarding the row's metadata entirely.
        return recoverTruncatedMetadata($blob);
    }

    $kept = [];
    foreach (['GPSLatitude', 'GPSLongitude', 'Model'] as $field) {
        if (!isset($data[$field])) {
            continue;
        }
        $value = $data[$field];
        // EXIF values are occasionally arrays or rationals; keep only scalars
        if (is_scalar($value) && $value !== '') {
            $kept[$field] = $value;
        }
    }

    return $kept ? json_encode(['data' => $kept], JSON_UNESCAPED_UNICODE) : '{}';
}

/**
 * Salvage coordinates and camera model from a blob that could not be decoded,
 * typically because it was cut off by the LEFT() in the query.
 */
function recoverTruncatedMetadata($blob)
{
    $kept = [];

    foreach (['GPSLatitude', 'GPSLongitude'] as $field) {
        // JSON: "GPSLatitude":10.5   |   serialized: "GPSLatitude";d:10.5;
        if (preg_match('/"' . $field . '"\s*[:;]\s*(?:d:)?"?(-?\d+(?:\.\d+)?)"?/', $blob, $m)) {
            $kept[$field] = (float) $m[1];
        }
    }

    // JSON: "Model":"Canon"   |   serialized: "Model";s:5:"Canon";
    if (preg_match('/"Model"\s*[:;]\s*(?:s:\d+:)?"((?:[^"\\\\]|\\\\.)*)"/', $blob, $m)) {
        $model = stripcslashes($m[1]);
        if ($model !== '') {
            $kept['Model'] = $model;
        }
    }

    return $kept ? json_encode(['data' => $kept], JSON_UNESCAPED_UNICODE) : '{}';
}

function queryCommonsDatabase($category, $startDate = null, $endDate = null)
{
    try {
        $startTime = microtime(true);
        $db = Database::getInstance();

        $dateFilter = '';
        $dateParams = [];
        // img.img_timestamp is in MediaWiki format YYYYMMDDHHMMSS
        if ($startDate) {
            $dateFilter .= " AND img.img_timestamp >= ?";
            $dateParams[] = str_replace(['-', 'T', ':'], '', $startDate . '000000');
        }
        if ($endDate) {
            $dateFilter .= " AND img.img_timestamp <= ?";
            $dateParams[] = str_replace(['-', 'T', ':'], '', $endDate . '235959');
        }

        // Fetch in batches rather than in one statement. mysqli buffers a whole
        // result set in memory, so a 46k-file category would otherwise hold
        // every row — metadata blobs included — before a single one is reduced.
        // Batching keeps peak memory flat no matter how large the category is,
        // and keeps each statement short enough to avoid replica timeouts.
        //
        // Paging is by cl_from rather than OFFSET: MariaDB re-scans and discards
        // every skipped row for a large OFFSET, so the last batches of a big
        // category would cost far more than the first.
        $sqlTemplate = "
            SELECT
                cl.cl_from,
                lt.lt_title as cl_to,
                img.img_name as filename,
                DATE_FORMAT(img.img_timestamp, '%Y%m%d') as imgdate,
                img.img_timestamp,
                img.img_size,
                -- Only the head of the blob is needed: the fields the
                -- dashboard reads sit near the front, and transferring full
                -- EXIF for every file is what exhausts memory on large
                -- categories. Truncated rows are parsed leniently below.
                LEFT(COALESCE(img.img_metadata, ''), 4096) as img_metadata,
                COALESCE(actor.actor_name, 'Unknown') as uploader
            FROM
                categorylinks cl
            INNER JOIN
                linktarget lt ON cl.cl_target_id = lt.lt_id
            INNER JOIN
                page ON cl.cl_from = page.page_id
            INNER JOIN
                image img ON page.page_title = img.img_name
            LEFT JOIN
                actor ON img.img_actor = actor.actor_id
            WHERE
                lt.lt_title = ?
                AND lt.lt_namespace = 14
                AND page.page_namespace = 6
                AND cl.cl_from > ?
                {$dateFilter}
            ORDER BY
                cl.cl_from ASC
            LIMIT " . CATEGORY_BATCH_SIZE . "
        ";

        $rows = [];
        $lastId = 0;
        $batches = 0;

        while (count($rows) < MAX_CATEGORY_FILES) {
            $params = array_merge([$category, $lastId], $dateParams);
            $results = $db->executeQuery($sqlTemplate, $params);
            $batches++;

            if (empty($results)) {
                break;
            }

            // Never exceed the cap: the final batch may run past it
            $remaining = MAX_CATEGORY_FILES - count($rows);
            if (count($results) > $remaining) {
                $results = array_slice($results, 0, $remaining);
            }

            foreach ($results as $row) {
                $lastId = (int) $row['cl_from'];
                $rows[] = [
                    $lastId,
                    $row['cl_to'],
                    $row['filename'],
                    $row['imgdate'],
                    $row['img_timestamp'],
                    (int) $row['img_size'],
                    compactImageMetadata($row['img_metadata']),
                    $row['uploader']
                ];
            }

            $fetched = count($results);
            // Release the batch before requesting the next one
            unset($results);

            // A short batch means the category is exhausted
            if ($fetched < CATEGORY_BATCH_SIZE) {
                break;
            }
        }

        // Rows are paged by cl_from for efficiency, but the dashboard expects
        // them newest first, as the single-statement query used to return them.
        usort($rows, function ($a, $b) {
            return strcmp((string) $b[4], (string) $a[4]);
        });

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        return ['success' => true, 'rows' => $rows, 'count' => count($rows), 'timestamp' => date('c'), 'category' => $category, 'cached' => false, 'query_time_ms' => $executionTime, 'batches' => $batches];
    } catch (Exception $e) {
        error_log('Database query failed for category ' . $category . ': ' . $e->getMessage());
        return ['success' => false, 'error' => 'Database query failed: ' . $e->getMessage(), 'category' => $category, 'timestamp' => date('c')];
    }
}

function loadSampleData($category = 'sample')
{
    $sampleFile = __DIR__ . '/sample-data.json';
    if (!file_exists($sampleFile))
        return ['success' => false, 'error' => 'Sample data file not found: ' . $sampleFile, 'category' => $category];
    try {
        $data = json_decode(file_get_contents($sampleFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON in sample data file: ' . json_last_error_msg(), 'category' => $category];
        }
        return ['success' => true, 'rows' => $data['rows'] ?? [], 'count' => count($data['rows'] ?? []), 'timestamp' => date('c'), 'category' => $category, 'cached' => false, 'sample_data' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error reading sample data: ' . $e->getMessage(), 'category' => $category];
    }
}

function generateMockData($category)
{
    $users = ['NaturePhotographer123', 'BirdWatcher_India', 'WildlifeExplorer', 'PhotographyLover', 'IndiaHeritage'];
    $mockRows = [];
    for ($i = 1; $i <= 50; $i++) {
        $daysAgo = rand(1, 365);
        $timestamp = date('YmdHis', strtotime("-{$daysAgo} days"));
        $imgdate = date('Ymd', strtotime("-{$daysAgo} days"));
        $sizeBytes = rand(500000, 15000000);
        $uploader = $users[array_rand($users)];
        $metadata = '{}';
        if (rand(1, 4) === 1) {
            $lat = 8 + rand(0, 2800) / 100;
            $lon = 76 + rand(0, 600) / 100;
            $metadata = json_encode(['data' => ['GPSLatitude' => $lat, 'GPSLongitude' => $lon, 'Model' => 'Canon EOS 5D Mark IV']]);
        } else if (rand(1, 3) === 1) {
            $cameras = ['Canon EOS 5D Mark IV', 'Nikon D850', 'Sony Alpha 7R IV'];
            $metadata = json_encode(['data' => ['Model' => $cameras[array_rand($cameras)]]]);
        }
        $mockRows[] = [$i, $category, "Sample_Photo_{$i}.jpg", $imgdate, $timestamp, $sizeBytes, $metadata, $uploader];
    }
    return ['success' => true, 'rows' => $mockRows, 'count' => count($mockRows), 'timestamp' => date('c'), 'category' => $category, 'cached' => false, 'mock_data' => true, 'query_time_ms' => rand(50, 200)];
}

try {
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    if (empty($category)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Category parameter is required. Please provide ?category=CategoryName', 'timestamp' => date('c')]);
        exit;
    }
    // Accept the characters Commons category titles actually use: Unicode
    // letters and combining marks (Indic scripts write vowel signs as marks,
    // not letters), plus colons for campaign categories such as
    // "Uploaded via Campaign:vaz-2026". The query is parameterised, so this
    // check rejects nonsense input rather than escaping anything.
    if (!preg_match('/^[\p{L}\p{M}\p{N}_\-().,:\'!\s\/]+$/u', $category) || mb_strlen($category) > 255) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category name. Letters, numbers, spaces and the characters _-().,:\'!/ are allowed. Maximum 255 characters.', 'timestamp' => date('c')]);
        exit;
    }
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    // Date filters (YYYY-MM-DD)
    $startDate = isset($_GET['start']) ? $_GET['start'] : null;
    $endDate = isset($_GET['end']) ? $_GET['end'] : null;
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate))
        $startDate = null;
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))
        $endDate = null;

    $cacheKey = md5($category . '|' . $startDate . '|' . $endDate);
    $cacheFile = $cacheDir . '/dashboard_' . $cacheKey . '.json';

    if (!$forceRefresh && isCacheValid($cacheFile, $cacheTime)) {
        $data = getCachedData($cacheFile);
        if ($data) {
            $data['cached'] = true;
            $data['cache_age_seconds'] = time() - filemtime($cacheFile);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $useMockData = isset($_GET['mock']) && $_GET['mock'] === '1';
    $useSampleData = isset($_GET['sample']) && $_GET['sample'] === '1';
    if ($useMockData) {
        $data = generateMockData($category);
    } elseif ($useSampleData) {
        $data = loadSampleData($category);
    } else {
        // Confirm the category exists before scanning the replica: a typo or a
        // deleted category otherwise costs a slow query that can only return
        // nothing. If Commons itself cannot be reached we fall through and
        // query anyway, so an API outage never blocks a valid category.
        $check = commonsCategoryInfo($category);
        if ($check['ok'] && !$check['exists']) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Category not found on Wikimedia Commons: ' . $category,
                'category' => $category,
                'category_exists' => false,
                'timestamp' => date('c')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Commons has already told us how big this category is. Refuse the
        // ones that cannot be assembled into a response, with a message that
        // says so, rather than dying halfway through with an empty 500.
        if ($check['ok'] && $check['files'] > MAX_CATEGORY_FILES) {
            http_response_code(413);
            echo json_encode([
                'success' => false,
                'error' => 'This category has ' . number_format($check['files']) . ' files, which is too many to analyse in one request (the limit is ' . number_format(MAX_CATEGORY_FILES) . '). Use the start and end date parameters to analyse a shorter period.',
                'category' => $category,
                'file_count' => $check['files'],
                'limit' => MAX_CATEGORY_FILES,
                'timestamp' => date('c')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = queryCommonsDatabase($category, $startDate, $endDate);
    }

    if ($data['success']) {
        if (!isset($data['mock_data']) && !isset($data['sample_data']))
            saveCacheData($cacheFile, $data);
        $data['cached'] = false;
    }

    http_response_code($data['success'] ? 200 : 500);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage(), 'timestamp' => date('c'), 'debug' => ['file' => $e->getFile(), 'line' => $e->getLine()]]);
    error_log('Dashboard API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
?>