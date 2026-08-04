<?php
/**
 * New Users API
 *
 * Reports the uploaders to a category whose Commons account was registered
 * around the campaign period, i.e. contributors the campaign recruited rather
 * than existing Commons users who joined in.
 *
 * A user counts as "new" when user_registration falls between
 * (start - grace days) and end. The grace window catches people who signed up
 * shortly before the contest opened specifically to take part.
 *
 * Loaded on demand by the dashboard's New Contributors popup, so the main
 * dashboard never pays for this extra query.
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
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Only GET requests are supported.', 'timestamp' => date('c')]);
    exit();
}
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/commons.php';

$cacheDir = __DIR__ . '/../cache';
$cacheTime = 3600;
// Users registered within this many days before the campaign start still count
// as new: many participants sign up a little ahead of the contest.
const DEFAULT_GRACE_DAYS = 30;

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
 * Fetch uploaders to $category along with their registration date, first and
 * last upload to the category, and their file/byte totals.
 *
 * Registration is compared in MediaWiki timestamp format (YYYYMMDDHHMMSS).
 * user_registration is NULL for very old accounts (pre-2005-ish), which are by
 * definition not new, so those are reported with registration = null and
 * excluded from the new-user set.
 */
function queryNewUsers($category, $startDate, $endDate, $graceDays)
{
    $startTime = microtime(true);
    $db = Database::getInstance();

    // Registration window: campaign start minus the grace period, to campaign end.
    $windowStart = date('YmdHis', strtotime($startDate . ' -' . $graceDays . ' days'));
    $windowEnd = date('YmdHis', strtotime($endDate . ' +1 day')); // exclusive upper bound

    $sql = "
        SELECT
            actor.actor_name AS username,
            u.user_registration AS registration,
            COUNT(*) AS file_count,
            SUM(img.img_size) AS total_size,
            MIN(img.img_timestamp) AS first_upload,
            MAX(img.img_timestamp) AS last_upload
        FROM
            categorylinks cl
        INNER JOIN
            linktarget lt ON cl.cl_target_id = lt.lt_id
        INNER JOIN
            page ON cl.cl_from = page.page_id
        INNER JOIN
            image img ON page.page_title = img.img_name
        INNER JOIN
            actor ON img.img_actor = actor.actor_id
        LEFT JOIN
            user u ON actor.actor_user = u.user_id
        WHERE
            lt.lt_title = ?
            AND lt.lt_namespace = 14
            AND page.page_namespace = 6
        GROUP BY
            actor.actor_name, u.user_registration
        ORDER BY
            file_count DESC
        LIMIT 50000
    ";

    $results = $db->executeQuery($sql, [$category]);

    $newUsers = [];
    $totalUploaders = 0;
    foreach ($results as $row) {
        $totalUploaders++;
        $registration = $row['registration'] ?: null;
        // No registration timestamp means a legacy account, not a new one.
        if ($registration === null || $registration < $windowStart || $registration >= $windowEnd) {
            continue;
        }
        $newUsers[] = [
            'username' => $row['username'],
            'registration' => $registration,
            'files' => (int) $row['file_count'],
            'size' => (int) $row['total_size'],
            'firstUpload' => $row['first_upload'],
            'lastUpload' => $row['last_upload'],
        ];
    }

    // Most recently registered first: the freshest recruits.
    usort($newUsers, function ($a, $b) {
        return strcmp($b['registration'], $a['registration']);
    });

    return [
        'success' => true,
        'category' => $category,
        'newUsers' => $newUsers,
        'newUserCount' => count($newUsers),
        'totalUploaders' => $totalUploaders,
        'window' => [
            'start' => $windowStart,
            'end' => $windowEnd,
            'campaignStart' => $startDate,
            'campaignEnd' => $endDate,
            'graceDays' => (int) $graceDays,
        ],
        'timestamp' => date('c'),
        'cached' => false,
        'query_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
    ];
}

try {
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    if ($category === '') {
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

    $startDate = isset($_GET['start']) ? $_GET['start'] : null;
    $endDate = isset($_GET['end']) ? $_GET['end'] : null;
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate))
        $startDate = null;
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))
        $endDate = null;

    // "New" is only meaningful relative to a campaign period.
    if (!$startDate) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'A campaign start date is required to identify new users. Please provide ?start=YYYY-MM-DD',
            'timestamp' => date('c')
        ]);
        exit;
    }
    if (!$endDate) {
        $endDate = date('Y-m-d');
    }
    if ($endDate < $startDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'End date must not be earlier than start date.', 'timestamp' => date('c')]);
        exit;
    }

    $graceDays = isset($_GET['grace']) && ctype_digit((string) $_GET['grace'])
        ? min((int) $_GET['grace'], 365)
        : DEFAULT_GRACE_DAYS;

    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    $cacheKey = md5($category . '|' . $startDate . '|' . $endDate . '|' . $graceDays);
    $cacheFile = $cacheDir . '/newusers_' . $cacheKey . '.json';

    if (!$forceRefresh && isCacheValid($cacheFile, $cacheTime)) {
        $data = getCachedData($cacheFile);
        if ($data) {
            $data['cached'] = true;
            $data['cache_age_seconds'] = time() - filemtime($cacheFile);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Same guard as the dashboard: don't scan the replica for a category that
    // Commons says does not exist. An unreachable API falls through.
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

    $data = queryNewUsers($category, $startDate, $endDate, $graceDays);
    if ($data['success']) {
        saveCacheData($cacheFile, $data);
        $data['cached'] = false;
    }

    http_response_code(200);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('New Users API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage(), 'timestamp' => date('c')]);
}
?>
