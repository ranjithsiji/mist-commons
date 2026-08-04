<?php
/**
 * Commons API helpers.
 *
 * Checking a category against the Commons web API before touching the replica
 * turns a slow, pointless database scan into a fast, clear failure: typos and
 * non-existent categories are rejected in well under a second instead of
 * running a multi-second query that can only ever return zero rows.
 */

const COMMONS_API_ENDPOINT = 'https://commons.wikimedia.org/w/api.php';
const COMMONS_API_TIMEOUT = 8;
// Toolforge asks tools to identify themselves; see
// https://meta.wikimedia.org/wiki/User-Agent_policy
const COMMONS_USER_AGENT = 'MIST-Commons-Dashboard/1.0 (https://mist.toolforge.org/)';

/**
 * Look up a category on Commons.
 *
 * Returns:
 *   ['ok' => true,  'exists' => bool, 'title' => string, 'pages' => int, 'files' => int]
 *   ['ok' => false, 'error' => string]   when Commons could not be reached
 *
 * The 'ok' flag separates "Commons says this category does not exist" from
 * "we could not ask Commons". Only the former should block a query: if the API
 * is unreachable we must not lock users out of a category that is perfectly
 * fine, so callers are expected to fall through to the database in that case.
 */
function commonsCategoryInfo($category)
{
    // Normalise to the title form Commons expects: underscores, no namespace
    // prefix. The caller may pass either "Foo bar" or "Foo_bar".
    $title = preg_replace('/^Category:/i', '', trim($category));
    $title = str_replace(' ', '_', $title);
    if ($title === '') {
        return ['ok' => true, 'exists' => false, 'title' => '', 'pages' => 0, 'files' => 0];
    }

    $query = http_build_query([
        'action' => 'query',
        'format' => 'json',
        'formatversion' => '2',
        'prop' => 'categoryinfo',
        'titles' => 'Category:' . $title,
    ]);

    $response = commonsApiGet(COMMONS_API_ENDPOINT . '?' . $query);
    if ($response === null) {
        return ['ok' => false, 'error' => 'Could not reach the Wikimedia Commons API'];
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data['query']['pages'][0])) {
        return ['ok' => false, 'error' => 'Unexpected response from the Wikimedia Commons API'];
    }

    $page = $data['query']['pages'][0];
    // A category page that was never created still holds files if something
    // links to it, and those are legitimate to analyse. Treat the category as
    // existing when either the page exists or categoryinfo reports members.
    $info = $page['categoryinfo'] ?? [];
    $members = (int) ($info['pages'] ?? 0) + (int) ($info['files'] ?? 0) + (int) ($info['subcats'] ?? 0);
    $exists = empty($page['missing']) || $members > 0;

    return [
        'ok' => true,
        'exists' => $exists,
        'title' => $page['title'] ?? ('Category:' . $title),
        'pages' => (int) ($info['pages'] ?? 0),
        'files' => (int) ($info['files'] ?? 0),
    ];
}

/**
 * GET a URL, returning the body or null on any failure.
 */
function commonsApiGet($url)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => COMMONS_API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => COMMONS_API_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => COMMONS_USER_AGENT,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            error_log('Commons API request failed (' . $status . '): ' . $error);
            return null;
        }
        return $body;
    }

    // Fallback for hosts without the cURL extension
    $context = stream_context_create([
        'http' => [
            'timeout' => COMMONS_API_TIMEOUT,
            'header' => 'User-Agent: ' . COMMONS_USER_AGENT . "\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        error_log('Commons API request failed: file_get_contents returned false');
        return null;
    }
    return $body;
}
