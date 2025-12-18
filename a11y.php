<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2025
 * @package bw/a11y
 *
 * Analyse WAVE reports for the top 200 WordPress .uk sites
 */

if ( defined( 'ABSPATH') ) {
    echo "This has been loaded by WordPress";
} else {
	require_once("inc/bobbset.php");
}

oik_require( "classes/class_a11y.php", "oik-a11y" );
oik_require( "classes/class_oik_a11y_reports.php", "oik-a11y" );
oik_require( 'includes/oik-a11y-compat.php', 'oik-a11y' );



echo '<html lang="en-GB">';
echo '<title>OIK a11y - WAVE Accessibility reports analysis</title>';
echo '<main class="main">';
echo '<body>';
echo '<head>';
echo '</head>';
echo '<h1>WAVE Accessibility reports analysis</h1>';

$a11y = new a11y();
$a11y->load_reports('wp-sites.csv');
$a11y->load_reports( 'hm-sites.csv' );
//$a11y->load_reports('other-sites.csv');
$a11y->report();
//a11y->maybe_perform_action();

//$a11y->process_form();

//$a11y->form();

//$a11y->previous_results();
echo '</main>';
echo '</body>';
echo '</html>';
