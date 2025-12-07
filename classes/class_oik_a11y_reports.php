<?php

class oik_a11y_reports
{

    private $sites;
    private $sites_reports = [];
    private $site_report = [];
    function __construct( $sites ) {
        $this->sites = $sites;
        $this->sites_reports = [];

    }
    function load_sites_reports() {
        $lines = file( $this->sites );
        //print_r( $lines );
        foreach ( $lines as $line ) {
            $data = str_getcsv($line, ',', '"');
            $position = $data[0];
            $domain = $data[1];
            $this->load_site_report($position, $domain);
            $this->append_site_report( $position, $domain );
        }
        //print_r( $this->sites_reports );
    }

    function load_site_report( $position, $domain ) {
        echo "Loading site report $position $domain", PHP_EOL;
        $file = $this->find_site_report( $position, $domain);
        if ( $file ) {
            $json = file_get_contents( $file );
            $site_report = json_decode( $json, true );
            //print_r( $json );
            $this->site_report = $site_report;
            //print_r( $stats );
        }
    }

    function append_site_report( $position, $domain ) {
        if ( $this->site_report['status']['success'] ) {
            $statistics = $this->site_report['statistics'];
            echo "Report for $position $domain" . $statistics['creditsremaining'], PHP_EOL;
            $this->sites_reports["{$position}-{$domain}"] = $statistics;
        } else {
            echo "Report for $position $domain failed";
            echo $this->site_report['status']['error'] . PHP_EOL;
        }
    }

    function find_site_report( $position, $domain ) {
        $date = date("Ym");
        $files = glob( "reports/$position-$domain-$date*.json");
        //print_r( $files );
        $file = array_last( $files);
        echo $file , PHP_EOL;
        return $file;
    }

    function report() {
        echo "oik-a11y reports" . PHP_EOL;
        echo count( $this->sites_reports) ,PHP_EOL;

        $counts = [ 0,0,0,0,0,0,0,0,0,0,0];
        $nameshame = [];
        foreach ( $this->sites_reports as $key => $statistics ) {
            $AIMscore = $statistics['AIMscore'];
            $nameshame[ $key ] = $AIMscore;
            $index = floor( $AIMscore );
            $counts[$index]++;

            echo "$AIMscore $index,";
        }
        echo PHP_EOL;
        echo "Score,#WordPress" . PHP_EOL;

        foreach ( $counts as $index => $count ) {
            if ( $index ) {
                echo "$index,$count" . PHP_EOL;
            }
        }
        echo PHP_EOL;
        echo "Score,%WordPress" . PHP_EOL;
        $percentages = [];
        foreach ( $counts as $index => $count ) {
            if ( $index ) {
                $percentage = ( $count / count( $this->sites_reports) ) * 100;
                $percentage = number_format_i18n( $percentage, 1 );
                echo "$index,$percentage" . PHP_EOL;
            }
        }
        asort( $nameshame );
        foreach ($nameshame as $key => $value ) {

            echo "$key,$value" , PHP_EOL;
        }
        //print_r( $nameshame);

    }

}