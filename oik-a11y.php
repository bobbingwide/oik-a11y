<?php
/**
 * @package oik-a11y
 * @copyright (C) Copyright Bobbing Wide 2025
 */

//oik_require_lib( "class-oik-remote" );
//$domain = "dzen.ru";

$start_position = 2001;
$end_position = 10000;
$url = 'https://www.isitwp.com/';
//$endpoint = 'https://wave.webaim.org/report#/google.com';
$endpoint = 'https://wave.webaim.org/api/request';

oik_require_lib( 'class-oik-remote');

// @TODO Hide this apikey
$apikey = "3X9jVFwv6059";
//$result = oik_remote::bw_remote_get( $url );


$domains = file( 'tranco-1t400.csv');
//echo $result;
$site_statuses = [];

for ( $position = $start_position; $position <= $end_position; $position++ ) {
    $csv = $domains[$position-1];
    //echo $csv;
    $data = str_getcsv( $csv,',', '"', '');
    if ( $data[0] != $position) {
        echo "Something's wrong with the input file" . $position . $data[0];
        die();
    } else {
        //echo "$position $data[0] $data[1]";
        $domain = $data[1];
        if ( false !== strpos(  $domain, '.uk') ) {
            $version = oik_a11y_check_site_status( $position, $domain );
            $site_statuses[] = "$position,$domain,$version\n";
        }
    }

}
file_put_contents( "wp-versions/oik-a11y-wp-versions-$start_position-$end_position.csv", $site_statuses );
//print_r( $site_statuses);
return;


$wave_report = oik_a11y_get_wave_report( $domain, $position, $endpoint, $apikey );
echo $wave_report;
oik_a11y_write_wave_report( $domain, $position, $wave_report );

function oik_a11y_write_wave_report( $domain, $position, $wave_report ) {
    $date = date("Ymd");
    $written = file_put_contents("reports/$position-$domain-$date.json", $wave_report);
    if (false === $written) {
        echo "Couldn't write to $position-$domain-$date.json";
    } else {
        echo "Wrote $written to $position-$domain-$date.json";
    }
}

function oik_a11y_get_wave_report( $domain, $position, $endpoint, $apikey ) {
    $urlToCheck = "https://$domain/";

    // Build query string. WAVE will default to JSON if format not given,
    // but we’ll be explicit and use reporttype=2 for item breakdown.
    // See https://wave.webaim.org/api/
    // https://wave.webaim.org/api/details
    $query = http_build_query([
        'key' => $apikey,
        'url' => $urlToCheck,
        'format' => 'json',
        'reporttype' => 1,   // 1=summary only, 2=summary + item types, 3/4 add XPaths/CSS selectors
    ]);
    $requestUrl = $endpoint . '?' . $query;
    // Make the HTTP request
    $response = file_get_contents($requestUrl);

    return $response;
}

function oik_a11y_check_site_status( $position, $domain ) {
    require_once "classes/class_oik_a11y_check_site_status.php";
    $check_site_status = new oik_a11y_check_site_status( $domain );
    echo "Checking site status for $position $domain\n";
    $status = $check_site_status->check_site_status();
    echo $status;
    return $status;
}


/**
// Decode JSON
$data = json_decode($response, true);
if ($data === null) {
    die("Error: unable to decode WAVE JSON.\n");
}

// Check status
if (empty($data['status']['success']) || $data['status']['success'] !== true) {
    $msg = isset($data['status']['error'])
        ? $data['status']['error']
        : 'Unknown error from WAVE API.';
    die("WAVE API returned an error: {$msg}\n");
}

// Basic stats
$stats = $data['statistics'];
echo "WAVE report for: {$stats['pageurl']}\n";
echo "Title: {$stats['pagetitle']}\n";
echo "Analysis time: {$stats['time']} seconds\n";
echo "Total DOM elements: {$stats['totalelements']}\n";
echo "WAVE online report URL: {$stats['waveurl']}\n\n";

// Categories summary
echo "=== Category summary ===\n";
foreach ($data['categories'] as $catKey => $catData) {
    $desc = $catData['description'] ?? $catKey;
    $count = $catData['count'] ?? 0;
    echo ucfirst($catKey) . " ({$desc}): {$count}\n";
}
echo "\n";

// Item-level details (because reporttype=2)
echo "=== Item details ===\n";
foreach ($data['categories'] as $catKey => $catData) {
    if (empty($catData['items'])) {
        continue;
    }

    echo strtoupper($catKey) . " items:\n";
    foreach ($catData['items'] as $itemId => $item) {
        $desc = $item['description'] ?? $itemId;
        $count = $item['count'] ?? 0;
        echo "  - {$itemId} ({$desc}): {$count}\n";
    }
    echo "\n";
}
 *
 */
