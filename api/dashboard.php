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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed. Only GET requests are supported.','timestamp'=>date('Y-m-d H:i:s')]); exit(); }
require_once __DIR__ . '/database.php';
$cacheDir = __DIR__ . '/../cache';
$cacheTime = 3600;
function isCacheValid($f,$t){ return file_exists($f) && (time()-filemtime($f))<$t; }
function getCachedData($f){ return file_exists($f)? json_decode(file_get_contents($f), true): null; }
function saveCacheData($f,$d){ $dir=dirname($f); if(!is_dir($dir)) mkdir($dir,0755,true); file_put_contents($f,json_encode($d)); }

function queryCommonsDatabase($category, $startDate = null, $endDate = null) {
    try {
        $startTime = microtime(true);
        $db = Database::getInstance();
        $params = [$category];
        $dateFilter = '';
        // img.img_timestamp is in MediaWiki format YYYYMMDDHHMMSS
        if ($startDate) {
            $dateFilter .= " AND img.img_timestamp >= ?";
            $params[] = str_replace(['-','T',':'],'', $startDate . '000000');
        }
        if ($endDate) {
            $dateFilter .= " AND img.img_timestamp <= ?";
            $params[] = str_replace(['-','T',':'],'', $endDate . '235959');
        }
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
                {$dateFilter}
            ORDER BY 
                img.img_timestamp DESC
            LIMIT 100000
        ";
        $results = $db->executeQuery($sql, $params);
        $rows = [];
        foreach ($results as $row) {
            $rows[] = [
                (int)$row['cl_from'],
                $row['cl_to'],
                $row['filename'],
                $row['imgdate'],
                $row['img_timestamp'],
                (int)$row['img_size'],
                $row['img_metadata'] ?: '{}',
                $row['uploader']
            ];
        }
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        return [ 'success'=>true,'rows'=>$rows,'count'=>count($rows),'timestamp'=>date('c'),'category'=>$category,'cached'=>false,'query_time_ms'=>$executionTime ];
    } catch (Exception $e) {
        error_log('Database query failed for category ' . $category . ': ' . $e->getMessage());
        return [ 'success'=>false,'error'=>'Database query failed: ' . $e->getMessage(),'category'=>$category,'timestamp'=>date('c') ];
    }
}

function loadSampleData($category = 'sample') {
    $sampleFile = __DIR__ . '/sample-data.json';
    if (!file_exists($sampleFile)) return ['success'=>false,'error'=>'Sample data file not found: '.$sampleFile,'category'=>$category];
    try { $data=json_decode(file_get_contents($sampleFile), true); if(json_last_error()!==JSON_ERROR_NONE){ return ['success'=>false,'error'=>'Invalid JSON in sample data file: '.json_last_error_msg(),'category'=>$category]; }
        return ['success'=>true,'rows'=>$data['rows']??[],'count'=>count($data['rows']??[]),'timestamp'=>date('c'),'category'=>$category,'cached'=>false,'sample_data'=>true];
    } catch (Exception $e) { return ['success'=>false,'error'=>'Error reading sample data: '.$e->getMessage(),'category'=>$category]; }
}

function generateMockData($category) {
    $users=['NaturePhotographer123','BirdWatcher_India','WildlifeExplorer','PhotographyLover','IndiaHeritage'];
    $mockRows=[]; for($i=1;$i<=50;$i++){ $daysAgo=rand(1,365); $timestamp=date('YmdHis', strtotime("-{$daysAgo} days")); $imgdate=date('Ymd', strtotime("-{$daysAgo} days")); $sizeBytes=rand(500000,15000000); $uploader=$users[array_rand($users)]; $metadata='{}'; if(rand(1,4)===1){ $lat=8+rand(0,2800)/100; $lon=76+rand(0,600)/100; $metadata=json_encode(['data'=>['GPSLatitude'=>$lat,'GPSLongitude'=>$lon,'Model'=>'Canon EOS 5D Mark IV']]); } else if(rand(1,3)===1){ $cameras=['Canon EOS 5D Mark IV','Nikon D850','Sony Alpha 7R IV']; $metadata=json_encode(['data'=>['Model'=>$cameras[array_rand($cameras)]]]); }
        $mockRows[]=[ $i,$category,"Sample_Photo_{$i}.jpg",$imgdate,$timestamp,$sizeBytes,$metadata,$uploader ]; }
    return ['success'=>true,'rows'=>$mockRows,'count'=>count($mockRows),'timestamp'=>date('c'),'category'=>$category,'cached'=>false,'mock_data'=>true,'query_time_ms'=>rand(50,200)]; }

try {
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    if (empty($category)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Category parameter is required. Please provide ?category=CategoryName','timestamp'=>date('c')]); exit; }
    if (!preg_match('/^[a-zA-Z0-9_\-().\s\/]+$/', $category) || strlen($category) > 255) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid category name. Only alphanumeric characters, spaces, hyphens, underscores, periods, forward slashes and parentheses are allowed. Maximum 255 characters.','timestamp'=>date('c')]); exit; }
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
    // Date filters (YYYY-MM-DD)
    $startDate = isset($_GET['start']) ? $_GET['start'] : null;
    $endDate = isset($_GET['end']) ? $_GET['end'] : null;
    if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$startDate)) $startDate = null;
    if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$endDate)) $endDate = null;

    $cacheKey = md5($category.'|'.$startDate.'|'.$endDate);
    $cacheFile = $cacheDir . '/dashboard_' . $cacheKey . '.json';

    if (!$forceRefresh && isCacheValid($cacheFile, $cacheTime)) {
        $data = getCachedData($cacheFile);
        if ($data) { $data['cached']=true; $data['cache_age_seconds']=time()-filemtime($cacheFile); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
    }

    $useMockData = isset($_GET['mock']) && $_GET['mock'] === '1';
    $useSampleData = isset($_GET['sample']) && $_GET['sample'] === '1';
    if ($useMockData) { $data = generateMockData($category); }
    elseif ($useSampleData) { $data = loadSampleData($category); }
    else { $data = queryCommonsDatabase($category, $startDate, $endDate); }

    if ($data['success']) { if (!isset($data['mock_data']) && !isset($data['sample_data'])) saveCacheData($cacheFile, $data); $data['cached']=false; }

    http_response_code($data['success'] ? 200 : 500);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Server error: '.$e->getMessage(),'timestamp'=>date('c'),'debug'=>['file'=>$e->getFile(),'line'=>$e->getLine()]]);
    error_log('Dashboard API Error: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
}
?>
