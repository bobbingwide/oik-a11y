<?php

class oik_a11y_reports
{

    private $sites;
    private $sites_reports = [];
    private $site_report = [];

    /** @var array Category counts from the site reports for reporttype=2
     * associative array.
     */
    private $sites_categories = [];
    private $sites_categories_summary = [];

    private $nameshame = [];

    private $all_reports = [];

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
            if( $this->site_report ) {
                $this->append_site_report($position, $domain);
            }
        }
        //print_r( $this->sites_reports );
    }

    function load_site_report( $position, $domain ) {
        a11y::echo( "Loading site report $position $domain" );
        $file = $this->find_site_report( $position, $domain);
        if ( $file ) {
            $json = file_get_contents( $file );
            $site_report = json_decode( $json, true );
            //print_r( $json );
            $this->site_report = $site_report;
            //print_r( $stats );
        } else {
            a11y::echo( "No site report for: $position $domain" );
            $this->site_report = null;
        }
    }

    function append_site_report( $position, $domain ) {
        if ( $this->site_report['status']['success'] ) {
            $statistics = $this->site_report['statistics'];
            a11y::echo( "Report for $position $domain," . $statistics['creditsremaining']);
            $this->sites_reports["{$position}-{$domain}"] = $statistics;
            if ( isset( $this->site_report['categories'])) {
                $this->sites_categories_summary["{$position}-{$domain}"] = $this->extract_categories();
                $this->sites_categories["{$position}-{$domain}"] = $this->site_report['categories'];

            }
        } else {
            a11y::echo( "Report for $position $domain failed: " );
            a11y::echo( $this->site_report['status']['error'] );
        }
    }

    function find_site_report( $position, $domain ) {
        $date = date("Ym");
        $files = glob( "reports/$position-$domain-$date*.json");
        //print_r( $files );
        $file = array_last( $files);
        a11y::echo( $file );
        return $file;
    }

    function report() {
        echo "oik-a11y reports" . PHP_EOL;
        echo count( $this->sites_reports) ,PHP_EOL;
        echo '<br />';

        $counts = [ 0,0,0,0,0,0,0,0,0,0,0];
        $this->nameshame = [];
        foreach ( $this->sites_reports as $key => $statistics ) {
            $AIMscore = $statistics['AIMscore'];
            $this->nameshame[ $key ] = $AIMscore;
            $index = floor( $AIMscore );
            $counts[$index]++;

            a11y::echo( "$AIMscore $index," );
        }
        echo PHP_EOL;
        echo '<hr />';
        echo "Score,Count" . PHP_EOL;

        foreach ( $counts as $index => $count ) {
            if ( $index ) {
                echo "$index,$count" . PHP_EOL;
            }
        }
        echo PHP_EOL;
        echo '<hr />';
        echo "Score,%WordPress" . PHP_EOL;
        $percentages = [];
        foreach ( $counts as $index => $count ) {
            if ( $index ) {
                $percentage = ( $count / count( $this->sites_reports) ) * 100;
                $percentage = number_format( $percentage, 1 );
                echo "$index,$percentage" . PHP_EOL;
            }
        }
        echo '<hr />';


    }

    function extract_categories() {
        $categories = [ 'error' => $this->site_report['categories']['error']['count'],
            'contrast' => $this->site_report['categories']['contrast']['count'],
            'alert' => $this->site_report['categories']['alert']['count'],
            'feature' => $this->site_report['categories']['feature']['count'],
            'structure' => $this->site_report['categories']['structure']['count'],
            'aria' => $this->site_report['categories']['aria']['count']
            ];
        return $categories;

    }

    function report_errors() {
        echo "oik-a11y report errors" . PHP_EOL;
            echo count( $this->sites_categories_summary) ,PHP_EOL;
            $counts = [ 0,0,0,0,0,0,0,0,0,0,0];
            $error_counts = [ 0,0,0,0,0,0,0,0,0,0,0];
            $contrast_counts = [ 0,0,0,0,0,0,0,0,0,0,0];
            $alert_counts = [ 0,0,0,0,0,0,0,0,0,0,0];
            foreach ( $this->sites_categories_summary as $key => $categories ) {
                //$AIMscore = $statistics['AIMscore'];
                $AIMscore = $this->nameshame[ $key ];
                $index = floor( $AIMscore );
                $counts[$index]++;
                $error_counts[$index] += $categories['error'];
                $contrast_counts[$index] += $categories['contrast'];
                $alert_counts[$index] += $categories['alert'];

            }
            $this->report_counts( $error_counts, 'Error' );
            $this->report_percentages( $error_counts, 'Error' );
            $this->report_averages( $error_counts, 'Errors', $counts );
            $this->report_counts( $contrast_counts, 'Contrast' );
            $this->report_percentages( $contrast_counts, 'Contrast' );
            $this->report_averages( $contrast_counts, 'Contrast', $counts );
            $this->report_counts( $alert_counts, 'Alert' );
            $this->report_percentages( $alert_counts, 'Alert' );
            $this->report_averages( $alert_counts, 'Alerts', $counts );
            $this->report_all_three( $error_counts, $contrast_counts, $alert_counts, 'Avg Error,Avg Contrast,Avg Alert', $counts );

    }

    function report_counts( $counts, $label ) {
        echo PHP_EOL;
        echo "Score,#$label" . PHP_EOL;

        foreach ( $counts as $index => $count ) {
            if ( $index ) {
                echo "$index,$count" . PHP_EOL;
            }
        }
        echo PHP_EOL;

    }


    function report_averages( $counts, $label, $sites) {
        echo "Score,Average $label " . PHP_EOL;
        foreach ( $counts as $index => $count ) {
            if ( $index ) {

                $number_sites = $sites[$index];
                $average = ( $count / $number_sites ) ;
                $average = round( $average, 0 );
                echo "$index,$average" . PHP_EOL;
            }
        }

    }

    function report_percentages( $counts, $label ) {
        echo "Score,%$label" . PHP_EOL;
        $total = array_sum( $counts);
        foreach ( $counts as $index => $count ) {
            if ( $index ) {
                $percentage = ( $count / $total ) * 100;
                $percentage = number_format_i18n( $percentage, 1 );
                echo "$index,$percentage" . PHP_EOL;
            }
        }
    }

    function report_all_three( $error_counts, $contrast_counts, $alert_counts, $labels, $counts ) {
        bw_trace2();

        $total_error = array_sum( $error_counts );
        $total_contrast = array_sum( $contrast_counts );
        $total_alert = array_sum( $alert_counts );
        echo "Total Errors: $total_error" ,PHP_EOL;
        echo "Total Contrast: $total_contrast" , PHP_EOL;
        echo "Total Alerts: $total_alert", PHP_EOL;
        echo "Score,$labels" . PHP_EOL;
        foreach ( $error_counts as $index => $count  ) {
            if ( $index ) {
                $average_error = round( ( $count / $counts[$index]), 0 );
                $average_contrast = round( ( $contrast_counts[ $index ] / $counts[$index ]),0 );
                $average_alert = round( ( $alert_counts[ $index ] / $counts[$index] ),0 );
                echo "$index,$average_error,$average_contrast,$average_alert" , PHP_EOL;
            }
        }

    }

    function report_detailed_errors()
    {
        echo "<h2>Detailed errors</h2>";
        asort($this->nameshame);
        foreach ($this->nameshame as $key => $AIMscore) {
            $index = floor($AIMscore);
            $removed_vowels = $this->remove_vowels( $key );
            echo "<br />$removed_vowels,$AIMscore,$index";
            if ( isset( $this->sites_categories_summary[ $key ])) {
                $categories = $this->sites_categories_summary[ $key ];
                echo ",{$categories['error']},{$categories['contrast']},{$categories['alert']},";
                echo $categories['error']+$categories['contrast']+$categories['alert'];
            } else {
                echo "No categories";
            }
        }


    }

    function remove_vowels( $key) {
        $parts = explode( '.' ,$key);
        $removed = str_replace(['a','e','i','o','u'], '?', $parts[0]);
        $parts[0] = $removed;
        return implode( '.', $parts );
    }

    /**
     * Counts each particular error by AIM score.
     * @TODO Index 0 is used to count the total number of errors
     * to enable us to find the top errors reported for UK WordPress sites.
     * We do this because WordPress sites exhibit different problems from the worldwide report.
     * eg. "Missing doc tag" isn't really a problem.
     * @return void
     */
    function count_each_category() {
        //$error_counts = [ [0],[0],[0],[0],[0],[0],[0],[0],[0],[0],[0]];
        $error_cats = ['error', 'contrast', 'alert'];
        foreach ($this->nameshame as $key => $AIMscore) {
            $index = floor($AIMscore);
            //echo "<br />$key,$AIMscore,$index";

            if (isset($this->sites_categories[$key])) {
                $categories = $this->sites_categories[$key];
                foreach ( $error_cats as $error_cat ) {
                    $items = $categories[$error_cat]['items'];
                    //echo '<br />';
                    //echo "$error_cat," . count( $items );
                    foreach ( $items as $itemkey => $item ) {
                        //echo '<br />' . $item['id'] . ',' . $item['description'] . ','  . $item['count'];
                        if ( isset($error_counts[ 0][$error_cat][$item['description']] ) ) {
                            $error_counts[0][$error_cat][$item['description']] += $item['count'];
                        } else {
                            $error_counts[0][$error_cat][$item['description']] = $item['count'];
                        }
                        if ( isset($error_counts[ $index][$error_cat][$item['description']] ) ) {
                            $error_counts[$index][$error_cat][$item['description']] += $item['count'];
                        }
                        else {
                            $error_counts[$index][$error_cat][$item['description']] = $item['count'];
                        }

                    }
                }

            }
        }
        //print_r( $error_counts );
        echo '<h3>Errors</h3>';
        foreach ( $error_counts as $index => $error_cats ) {
            echo "<h4>AIM Score $index</h4>";
            echo '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr;">';
            foreach ( $error_cats as $cat => $error_cat ) {
                echo '<div>';
                echo "<h5>$cat</h5>";
                echo "<br />Description,Count";
                arsort( $error_cat );
                //print_r( $error_cat );
                foreach ( $error_cat as $description => $count ) {
                    echo "<br />$description,$count";
                }
                echo '</div>';
            }
            echo '</div>';

        }
        oik_require( "classes/class_oik_a11y_wave_error_breakdown.php", "oik-a11y");
        $oik_a11y_wave_error_breakdown = new oik_a11y_wave_error_breakdown( $error_counts );
        $oik_a11y_wave_error_breakdown->reports();
        $oik_a11y_wave_error_breakdown->reports('alert');
        //$oik_a11y_wave_error_breakdown->reports('contrast');

    }



}