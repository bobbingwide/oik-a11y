<?php

/**
 * Bits of wp-includes/compat.php needed when not WordPress
 * @TODO Shouldn't these go in bobbnotwp.inc_ ?
 */

if ( ! function_exists( 'array_last' ) ) {
    /**
     * Polyfill for `array_last()` function added in PHP 8.5.
     *
     * Returns the last element of an array.
     *
     * @since 6.9.0
     *
     * @param array $array The array to get the last element from.
     * @return mixed|null The last element of the array, or null if the array is empty.
     */
    function array_last( array $array ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound
        if ( empty( $array ) ) {
            return null;
        }

        return $array[ array_key_last( $array ) ];
    }


}

function number_format_i18n( $number, $decimals = 2 ) {
    return number_format( $number, $decimals);
}

