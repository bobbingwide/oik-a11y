<?php
/**
 * @package oik-a11y
 * @copyright (C) Copyright Bobbing Wide 2025
 */

//oik_require_lib( "class-oik-remote" );
//$domain = "dzen.ru";
$process = oik_batch_query_value_from_argv( 1, 'UK' );
$process = strtolower( trim( $process ));
switch ( $process ) {
    /* Step 1 */
    case 'uk':
    case 'wp':
        $start_position = oik_batch_query_value_from_argv( 2, 140001 );
        $end_position = oik_batch_query_value_from_argv( 3, $start_position + 9999 );
        oik_a11y_versionise_uk_domains( $start_position, $end_position );
        break;
    /* Step 2 */
    case 'enough':
        $enough = oik_batch_query_value_from_argv( 2, 200 );
        oik_a11y_enough_versionised( $enough);
        break;
    /* Step 3 */
    case 'wave':
       $sites = oik_batch_query_value_from_argv( 2, 'wp-sites.csv' );
       oik_a11y_wave_domains( $sites );
       break;
    /* Step 4 */
    case 'reports':
    default:
        // Other values: other_sites.csv
        $sites = oik_batch_query_value_from_argv( 2, 'wp-sites.csv' );
        oik_a11y_reports( $sites );

}

//$start_position = oik_batch_query_value_from_argv( 2, 20001 );
//$end_position = oik_batch_query_value_from_argv( 3, 30000 );

function oik_a11y_enough_versionised( $enough ) {
    echo "Checking we have enough .UK sites using WordPress: $enough.";
    $files = glob( 'wp-versions/*.csv', GLOB_NOSORT);
    //print_r( $files );
    $count_WP = 0;
    $count_OK = 0;
    $count_na = 0;
    $wp_sites = [];
    $other_sites = [];
    foreach ( $files as $file ) {
        echo $file , PHP_EOL;
        $records = file( $file );
        // print_r($records);

        foreach ( $records as $record ) {
            echo $record, PHP_EOL;
            $record = trim( $record);
            if ( empty( $record )) continue;
            $data = str_getcsv( $record, ',', '"');
            if ( false === strpos( $data[1], '.uk')) {
                continue;
            }
            switch ( $data[2] ) {
                case 'n/a':
                case 'N/A':
                    $count_na++;
                    break;
                case 'OK':
                    $count_OK++;
                    $other_sites[] = $record . PHP_EOL;
                    break;
                case 'WP':
                default:
                    $count_WP++;
                    $wp_sites[] = $record . PHP_EOL;
                    if ( $count_WP >= $enough) {
                        echo "Enough: ". $enough;
                        echo "Count WP: ".  $count_WP;
                        echo "OK: " . $count_OK;
                        echo "N/A: " . $count_na;
                        break 3;
                    }

            }
        }
    }
    echo PHP_EOL;
    echo "Enough?: ".  $count_WP, PHP_EOL;
    echo "OK: " . $count_OK, PHP_EOL;
    echo "N/A: " . $count_na,PHP_EOL;
    file_put_contents( 'wp-sites.csv', $wp_sites );
    file_put_contents( 'other_sites.csv', $other_sites );
}


function oik_a11y_versionise_uk_domains( $start_position, $end_position )
{

    $domains = file('tranco-1t400.csv');
//echo $result;
    $site_statuses = [];

    for ($position = $start_position; $position <= $end_position; $position++) {
        $csv = $domains[$position - 1];
        //echo $csv;
        $data = str_getcsv($csv, ',', '"', '');
        if ($data[0] != $position) {
            echo "Something's wrong with the input file" . $position . $data[0];
            die();
        } else {
            //echo "$position $data[0] $data[1]";
            $domain = $data[1];
            if (false !== strpos($domain, '.uk')) {
                $version = oik_a11y_check_site_status($position, $domain);
                $site_statuses[] = "$position,$domain,$version\n";
            }
        }

    }
    file_put_contents("wp-versions/oik-a11y-wp-versions-$start_position-$end_position.csv", $site_statuses);
//print_r( $site_statuses);
    return;
}

function oik_a11y_wave_domains( $sites='wp-sites.csv' )
{
    // This is the manual URL
    // 'https://wave.webaim.org/report#/google.com'

    // This is the URL for the API
    $endpoint = 'https://wave.webaim.org/api/request';
    // Define your WebAIM APIKEY in wp-config.php
    $apikey = WEBAIM_APIKEY;

    oik_require_lib('class-oik-remote');
    $lines = file( $sites );
    //print_r( $lines );
    foreach ( $lines as $line ) {
        $data = str_getcsv( $line, ',', '"' );
        $position = $data[0];
        $domain = $data[1];

        $done = oik_a11y_is_wave_report_done( $domain, $position );
        if ( !$done ) {
            $wave_report = oik_a11y_get_wave_report($domain, $position, $endpoint, $apikey);
            echo $wave_report;
            echo  PHP_EOL;
            if ( oik_a11y_valid_wave_report( $wave_report )) {
                oik_a11y_write_wave_report($domain, $position, $wave_report);
            } else {
                echo "Invalid WAVE report for $position $domain" , PHP_EOL;
            }
        }
    }

}

function oik_a11y_is_wave_report_done( $domain, $position ) {
    $date = date("Ym");
    $files = glob( "reports/$position-$domain-$date*.json");
    //print_r( $files );
    $done = count( $files) > 0;
    return $done;
}

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
        'reporttype' => 2,   // 1=summary only, 2=summary + item types, 3/4 add XPaths/CSS selectors
    ]);
    $requestUrl = $endpoint . '?' . $query;
    // Make the HTTP request
    $response = file_get_contents($requestUrl);

    return $response;
}

function oik_a11y_valid_wave_report( $wave_report ) {
    $json = json_decode( $wave_report, true );
    $valid = $json['status']['success'];
    return $valid;
}

function oik_a11y_check_site_status( $position, $domain ) {
    require_once "classes/class_oik_a11y_check_site_status.php";
    $check_site_status = new oik_a11y_check_site_status( $domain );
    echo "Checking site status for $position $domain\n";
    $status = $check_site_status->check_site_status();
    echo $status;
    return $status;
}

function oik_a11y_reports( $sites ) {
    require_once "classes/class_oik_a11y_reports.php";
    $reports = new oik_a11y_reports( $sites );
    $reports->load_sites_reports();
    $reports->report();
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
