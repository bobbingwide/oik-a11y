<?php
/**
 * @package oik-a11y
 *
 * A11y to help anaylse WAVE reports for .uk WordPress websites
 */

class a11y
{
    private $oik_a11y_reports = null;

    private $reports;

    private $sites;
    
    static $message = null;

    function __construct() {
        self::$message = '';
    }
    function load_reports( $sites='wp-sites.csv') {
        $this->oik_a11y_reports = new oik_a11y_reports( $sites );
        $this->oik_a11y_reports->load_sites_reports();
    }

    function report() {
        $this->oik_a11y_reports->report();
        //$this->oik_a11y_reports->report_errors();
        $this->oik_a11y_reports->report_detailed_errors();
        $this->oik_a11y_reports->count_each_category();
    }

    function load_sites() {

    }
    /**
     * Safely echo in batch environment
     *
     * Stores echoed output for JSON requests
     * @param $string
     */
    static function echo( $string=PHP_EOL ) {
        if ( "cli" === php_sapi_name() ) {
            echo $string;
            if ( $string != PHP_EOL ) {
                echo PHP_EOL;
            }
        } else {
            self::$message .= $string;
        }
    }
    

}