<?php

class oik_a11y_check_site_status
{

    private $domain = null;
    private $url = null;
    private $extract_from;
    private $extract_to;
    private $contents;
    private $extract;
    private $version;
    private $length;

    public $fix_crlf = false;
    public $fix_lf = false;

    function __construct($domain) {
        $this->domain = $domain;
        $this->url = 'https://' . $this->domain;
    }

    function check_site_status() {
        if ( !$this->gethostbyname() ) {
            return "N/A";
        }
        $this->fetchdomain();
        /*
        if ( $this->get_contents() < 0 ) {
            return "n/a";
        */
        if ( $this->length < 1 ) {
            return "n/a";
        }
        $is_WordPress = $this->isWordPress();
        if ( $is_WordPress ) {
            return $is_WordPress;
        } else {
            //$this->whatcms();
        }
        return "OK";
    }

    function fetchdomain() {
        $urlToCheck = "https://{$this->domain}/";
        $this->contents = file_get_contents($urlToCheck);
        if ( $this->contents ) {
            $this->length = strlen($this->contents);
        } else {
            $this->length = 0;

        }
        //echo "Response: $response";
        return $this->length;
    }

/**
* Gets the URLs contents
*
* Uses bw_remote_get() rather than file_get_contents() for a number of reasons
* - Caters better for SSL in local installs
* - Less chance of Warning message for not found
* - etc. tbc
* - @TODO But we need to be able to detect WordPress errors!
*/
    function get_contents() {
        $this->contents = '';

        //$this->contents = file_get_contents( $this->url );
        $this->contents = oik_remote::bw_remote_get( $this->url, false );
        if ( $this->contents ) {
            $len = strlen($this->contents);
        } else {
            $response_code = oik_remote::bw_retrieve_response_code();
            //$this->echo( "Response code: $response_code" );
            //$len = '0.' . $response_code;
            if ( '' === $response_code) {
                $response_code = 999;
            }
            $this->echo( "Response code: $response_code" );
            $this->echo( "Response message: " . oik_remote::bw_retrieve_response_message() );
            return( - $response_code );

        }
        //bw_trace2( $this->contents, "contents");
        if ( $this->fix_crlf ) {
            //echo "Length was:" . strlen( $this->contents );
            $this->contents = str_replace( PHP_EOL, "\n", $this->contents );
        }
        if ( $this->fix_lf ) {
            $this->contents = str_replace( "\n", "", $this->contents );
        }
        if ( $this->fix_lf ) {
            $this->contents = str_replace( "\r", "", $this->contents );
        }
        $this->length = strlen( $this->contents );
        if ( $len <> $this->length ) {
            $this->echo( $len );
            $this->echo( $this->length );
        }
        //echo $this->url . ": " . $this->length;
        //echo PHP_EOL;
        return( $len );
    }


    function gethostbyname() {
        $ip = gethostbyname( $this->domain );
        return ( $ip !== $this->domain);
    }

    function isWordPress() {
        $this->detect_wp_version();
        if ( !$this->version ) {
            $this->detect_wordpress();
        }
        return $this->version;
    }

    /**
     * Gets the home page.
     *
     * We expect it to finish with end tags for body and html
     * which may be followed by HTML comments from oik-bwtrace
     *
     * @TODO Cater for new lines and stuff
     *
     */
    function home() {
        $this->url = $this->domain;
        $this->fix_lf = true;
        $this->set_expected_content( '</body></html>' );
        $this->fetch_url();
        //$this->verbose = true;
        //$this->maybe_verbose();
        $this->detect_wp_version();
        $this->detect_php_version();
        $this->detect_theme();
    }

    /**
     * Attempts to detect the WordPress version from the extracted contents
     * twemoji.js?ver=5.0.1"}}
     */
    function detect_wp_version() {
        $this->echo( "Detecting..." );
        $this->echo();
        $version = $this->maybe_extract( '<meta name="generator" content="WordPress ', '" />' );
        if ( !$version ) {
            $version = $this->maybe_extract( '<metaname="generator" content="WordPress ', '" />' );
        }
        if ( !$version ) {
            $version = $this->maybe_extract( "twemoji.js?ver=", '"}};' );
        }
        if ( !$version ) {
            $version = $this->maybe_extract( "wp-emoji-release.min.js?ver=", '"}};' );
        }
        $this->version = $version;
        $this->echo( "Version:" . $this->version );

    }

    /**
     * Attempts to detect WordPress folders.
     *
     * We can't just look for wp-content since a website may link to an image from a WordPress site
     * so wp-content/uploads would be a false positive.
     * @returns false | 'WP' if we find a plugin or theme
     */
    function detect_wordpress() {
        $wordpress = null;
        // Would this not be better in a loop?
        $pos = strpos( $this->contents, "wp-content/plugins" );
        if ( false === $pos ) {
            $pos = strpos($this->contents, "wp-content/plugins");
            $wordpress = (false === $pos) ? false : 'WP';
        } else {

            $wordpress = (false === $pos) ? false: 'WP';
        }
        $this->version = $wordpress;
        return $wordpress;
    }

    /**
     * Safely echo in batch environment
     *
     * Stores echoed output for JSON requests
     * @param $string
     */
    function echo( $string=PHP_EOL ) {
        if ( "cli" === php_sapi_name() ) {
            echo $string;
            if ( $string != PHP_EOL ) {
                echo PHP_EOL;
            }
        } else {
            $this->message .= $string;
        }
    }

    /**  */
    function maybe_extract( $from, $to ) {
        $this->echo( "Trying:" . $from . " " . $to );
        $this->echo();
        $this->set_extract_from( $from );
        $this->set_extract_to( $to );
        $this->extract();
        $len = ( $this->extract ) ? strlen( $this->extract ) : 0;
        $this->echo( "Extract length: " . $len );
        if ( $len > 0 && $len < $this->length ) {
            $version = $this->extract;
        } else {
            $version = null;
        }
        return $version;

    }

    function set_extract_from( $from ) {
        $this->extract_from = $from;
    }
    function set_extract_to( $to ) {
        $this->extract_to = $to;
    }

    /**
     * Extracts the string between extract_from and extract_to
     */
    function extract() {
        $this->extract = $this->contents;
        //bw_trace2( $this->extract, "this extract");
        if ( $this->extract_from ) {
            $this->extract_from();
            if ( $this->extract_to ) {
                $this->extract_to();
            } else {
                //$this->extract_to();

            }
        } else { // from is null
            if ( $this->extract_to && $this->extract ) {
                $this->extract_to();
            } else {
                //$found = strpos( $this->extract, $this->expected_content );
            }
        }
        //bw_trace2( $this->extract, "this extract after");


    }

    /**
     * Extracts the string after extract_from
     */
    function extract_from() {
        $from = strpos(  $this->extract, $this->extract_from );
        if ( $from !== false ) {
            $from += strlen( $this->extract_from );
            $this->extract = substr( $this->extract, $from );
            $this->echo( "From:");
            $this->echo( substr( $this->extract, 0, 80 ) );

        } else {
            $this->echo( "Can't find from string: " . $this->extract_from );
            $this->echo();
            $this->extract = null;
        }
    }

    /**
     * Extracts the string before extract_to
     */
    function extract_to()
    {
        if ( !$this->extract )
            return;
        $to = strpos($this->extract, $this->extract_to);
        $this->echo("To:" . $to);
        $this->echo(substr($this->extract, 0, 80));
        if (false !== $to) {
            $this->extract = substr($this->extract, 0, $to);
            $this->echo($this->extract);
        } else {
            $this->echo("Can't find to string: " . $this->extract_to);
            $this->extract = null;
        }

    }

    function whatcms() {
        // https://whatcms.org/?s=www.bbc.com
        $urlToCheck = "https://whatcms.org/?s={$this->domain}";
        $response = file_get_contents( $urlToCheck );
        $this->length = strlen( $response );
        echo $response;

    }




    }