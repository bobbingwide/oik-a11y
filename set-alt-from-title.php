<?php
/**
 * Set alt text for image attachments where alt is missing.
 */

$args = [
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
];

$attachments = get_posts( $args );

foreach ( $attachments as $attachment ) {

    // Existing alt text?
    $alt = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

    if ( ! empty( $alt ) ) {
        // Skip if alt already set.
        continue;
    }

    // Use the attachment title.
    $title = get_the_title( $attachment->ID );

    // Update meta.
    update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $title );

    // Output progress to WP-CLI.
    WP_CLI::log( "Set alt for ID {$attachment->ID} → '{$title}'" );
}

WP_CLI::success( "Done! Alt text has been set where missing." );
