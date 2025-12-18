<?php
/**
 * @package oik-a11y
 * @copyright (C) Copyright Bobbing Wide 2026
 *
 * To help understand and identify the most common WAVE error types by AIM score band.
 *
 *
 *
 */

class oik_a11y_wave_error_breakdown
{
    /** @var array
     * $error_counts is a multi dimensional array:
     *
     * $errors_counts[$index][$error_cat][$item_description] = $total_count
     *
     * Field | Values
     * ----- | --------
     * $index | 0 key for the total for the other AIM scores, individually stored in key 1 to 10
     * $error_cat | 'error', 'contrast', 'alert'
     * $item_description | the WAVE item description eg 'Empty link' for error, 'Redundant link' for alert
     */
    private $error_counts = [];

    private $pie_array = [];

    function __construct( $error_counts ) {
        $this->error_counts = $error_counts;
        echo '<h2>Pie charts</h2>';

    }

    function reports( $error_cat='error') {
        $this->report_percentage_pie_for_index( 0, $error_cat,10);
        for ( $index = 1; $index <= 10; $index++ ) {
            $this->report_percentage_pie_for_index( $index, $error_cat, 5 );

        }
    }

    function get_pie_for_index( $index, $error_cat ) {
        $this->pie_array = [];
        if ( isset( $this->error_counts[$index][$error_cat])) {
            foreach ($this->error_counts[$index][$error_cat] as $item_description => $total) {
                $this->pie_array[$item_description] = $total;

            }
        }
        //print_r( $this->pie_array );

        return $this->pie_array;
    }

    function calculate_percentages( $pie_array ) {
        $percentages_array = [];
        if ( count( $pie_array) ) {
            $total = array_sum($pie_array);
            foreach ($pie_array as $item => $count) {
                $percentage = ($count / $total) * 100;
                $percentages_array[$item] = $percentage;
            }
        }
        //echo "%[]: " .  count( $percentages_array);
        return $percentages_array;
    }

    /**
     * Returns array as percentages.
     *
     * @param $pie_array Associative array of key => number
     * @return array Associative array of key => percentage
     */
    function get_percentage_pie( $pie_array ) {
        //echo "<br />Producing percentage pie";
        //print_r( $pie_array );
        arsort( $pie_array );
        $percentages_array = $this->calculate_percentages( $pie_array );
        return $percentages_array;
    }

    function report_percentage_pie_for_index( $index, $error_cat, $slices ) {
        echo "<h3>Producing percentage pie. Index: $index Category: $error_cat Slices: $slices</h3>";
        $this->get_pie_for_index( $index, $error_cat );
        $percentages_array = $this->get_percentage_pie( $this->pie_array );
        echo '<div style="display:grid; grid-template-columns: 1fr 1fr">';
        echo '<div>';
        $content = $this->report_pie( $percentages_array, $slices);
        echo '</div>';
        $this->chart_pie( $content );
        echo '</div>';

    }

    function report_pie( $percentage_array, $slices ) {
        echo '<br />';
        $count = count( $percentage_array);
        echo "Description,%";
        $content = "Description,%\n";
        $total = 0;
        $sliced = 0;
        foreach ( $percentage_array as $item_description => $percentage ) {
            $total += $percentage;

            if ( $sliced < $slices) {
                echo '<br />';
                $percentage = number_format_i18n($percentage, 1);
                $line = "$item_description,$percentage\n";
                $content .= $line;
                echo $line;
            } elseif ( $sliced === $slices) {
                $percentage = 100 - $total;
                $percentage = number_format_i18n($percentage, 1);
                $others = $count - $sliced;
                echo '<br />';
                $line = "Others: $others,$percentage\n";
                $content .= $line;
                echo $line;
            }
            $sliced++;
        }
        //echo '<br />';
        //echo "<br />Total $total";
        //echo '<hr/>';
        return $content;
    }

    function chart_pie( $content ) {
       echo '<div>';
        $atts = [ 'type'=> 'pie',
            'height'=> 250,
            ];
        $atts['max'] = 100;
        $atts['backgroundColors'] = ['#ee82ee', '#4b0082', '#0000ff', '#008000', '#ffff00', '#ffa500', '#ff0000'];
        $atts['borderColors'] = [];
        $atts['showLine'] = false;
        require_once '../../../../bw/access/sb-chart-nowp.php';
        sb_chart_nowp( $atts, $content );
        echo '</div>';

    }

    /*
"Rainbow": [
{"color": "#ee82ee"},
{"color": "#4b0082"},
{"color": "#0000ff"},
{"color": "#008000"},
{"color": "#ffff00"},
{"color": "#ffa500"},
{"color": "#ff0000"}
]
    */

}