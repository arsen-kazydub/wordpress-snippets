<?php

/* Returns the lowest price among all unit types for the current property. */

add_shortcode( 'lowest_unit_price', function() {
  global $post;

  $is_french = strpos( $_SERVER[ 'REQUEST_URI' ], '/fr/' ) !== false;

  $post_id = isset( $post->ID ) ? $post->ID : get_the_ID();
  if ( ! $post_id ) return '';

  $units = get_post_meta( $post_id, 'property_units', true );
  if ( ! is_array( $units ) ) return '';


  // create an array of prices
  $prices = [];

  foreach ( $units as $item ) {
    if ( ! isset( $item[ 'unit_price' ] ) ) continue;

    $price = (int) $item[ 'unit_price' ];

    if ( $price > 0 ) {
      $prices[] = $price;
    }
  }

  if ( empty( $prices ) ) {
    return $is_french ? 'Prix sur demande' : 'Price upon request';
  }

  $lowest    = min( $prices );
  $from      = $is_french ? 'À partir de' : 'From';
  $formatted = number_format( $lowest, 0, '', ( $is_french ? ' ' : ',' ) );

  $formatted_price = $is_french
    ? $formatted . ' AED'
    : 'AED ' . $formatted;

  return $from . ' <span class="property-price">' . $formatted_price . '</span>';
});