<?php

/* Returns the bedroom count or range for the current property. */

add_shortcode( 'bedroom_range', function() {
  global $post;

  $is_french = strpos( $_SERVER[ 'REQUEST_URI' ], '/fr/' ) !== false;

  $post_id = isset( $post->ID ) ? $post->ID : get_the_ID();
  if ( ! $post_id ) return '';

  $units = get_post_meta( $post_id, 'property_units', true );
  if ( ! is_array( $units ) ) return '';


  // create an array of bedroom count
  $bedrooms = [];

  foreach ( $units as $item ) {
    if ( isset( $item[ 'unit_bedroom_count' ] ) ) {
      $bedrooms[] = (int) $item[ 'unit_bedroom_count' ];
    }
  }

  if ( empty( $bedrooms ) ) return '';

  // get min and max values
  $lowest  = min( $bedrooms );
  $highest = max( $bedrooms );


  $get_range_start = function( $bedroom_count ) {
    if ( $bedroom_count <= 0 ) {
      return 'Studio';
    }
    return $bedroom_count;
  };


  $get_range_end = function( $bedroom_count, $is_french ) {
    if ( $bedroom_count <= 0 ) {
      return 'Studio';
    }

    $label = $bedroom_count === 1
      ? ( $is_french ? 'chambre' : 'Bedroom' )
      : ( $is_french ? 'chambres' : 'Bedrooms' );

    return $bedroom_count . ' ' . $label;
  };


  $str_to = $is_french ? ' à ' : ' to ';

  $start = $get_range_start( $lowest );
  $end   = $get_range_end( $highest, $is_french );

  // single value or range
  $result = $lowest === $highest
    ? $end
    : $start . $str_to . $end;

  return '<div class="property-bedroom-range">' . $result . '</div>';
});