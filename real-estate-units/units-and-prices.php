<?php

/**
 * Builds a title and a price for a property unit.
 *
 * By default, the title is generated from the unit type and bedroom count.
 * A custom title template may be provided to override the default format.
 *
 * Available placeholders:
 * {br}     - unit bedroom count, e.g. "1 BR", "2 BR", etc.
 * {type}   - unit type, e.g. "Apartment", "Villa", etc.
 * {layout} - unit layout label: "", "Duplex", or "Triplex".
 * {join}   - attribute separator, currently "+".
 *
 * Unit attributes: {maid}, {study}, {pool}
 * The list of supported attributes is defined in $unit_attribute_labels.
 *
 * {!...} - Preserves the enclosed text exactly as written.
 *          For example, French sentence-case normalization won't have any effect.
 *          How to use: {!XL}, {!TypeA}, {!M1Elite}, etc.
 */

add_shortcode( 'units_and_prices', function() {
  global $post;

  $is_french = strpos( $_SERVER[ 'REQUEST_URI' ], '/fr/' ) !== false;

  $post_id = isset( $post->ID ) ? $post->ID : get_the_ID();
  if ( ! $post_id ) return '';

  $units = get_post_meta( $post_id, 'property_units', true );
  if ( ! is_array( $units ) ) return '';


  // extracts protected parts wrapped in {!...}
  $store_protected_parts = function( string $text ): array {
    if ( $text === '' ) return [];

    preg_match_all( '/\{!([^}]+)\}/', $text, $matches );
    return $matches[ 1 ];
  };


  // restores protected parts and removes {!...} wrappers
  $restore_protected_parts = function( string $text, array $protected ): string {
    if ( $text === '' ) return '';

    return preg_replace_callback(
      '/\{![^}]+\}/',
      function() use ( &$protected ) {
        // returns the first element and removes it from the original array
        return array_shift( $protected ) ?? '';
      },
      $text
    );
  };


  // ensures sentence case for French titles
  $french_sentence_case = function( string $text ): string {
    if ( $text === '' ) return '';

    // make the whole text lowercase, leaving the first letter uppercase
    $text = mb_strtolower( $text, 'UTF-8' );
    $first_letter = mb_substr( $text, 0, 1, 'UTF-8' );
    $rest_of_text = mb_substr( $text, 1, null, 'UTF-8' );

    return mb_strtoupper( $first_letter, 'UTF-8' ) . $rest_of_text;
  };


  $unit_type_labels = [
    'en' => [
      0 => 'Studio',
      1 => 'Apartment',
      2 => 'Penthouse',
      3 => 'Townhouse',
      4 => 'Villa',
    ],
    'fr' => [
      0 => 'Studio',
      1 => 'Appartement',
      2 => 'Penthouse',
      3 => 'Maison de ville',
      4 => 'Villa',
    ],
  ];

  $unit_types = $unit_type_labels[ $is_french ? 'fr' : 'en' ];


  $unit_layouts = [
    1 => '', // Standard - no need to display it in the unit name
    2 => 'Duplex',
    3 => 'Triplex',
  ];


  $unit_attribute_labels = [
    'en' => [
      '{maid}'  => 'Maid',
      '{study}' => 'Study',
      '{pool}'  => 'Pool',
    ],
    'fr' => [
      '{maid}'  => 'ch. de service',
      '{study}' => 'bureau',
      '{pool}'  => 'piscine',
    ],
  ];

  $unit_attributes = $unit_attribute_labels[ $is_french ? 'fr' : 'en' ];


  // how attributes in the template are joined with the main part of the title
  $attribute_separator = '+';


  // unit defaults
  $default_bedrooms   = 1; // 1 bedroom
  $default_type_idx   = 1; // Apartment
  $default_layout_idx = 1; // Standard
  $default_price      = 0; // Price upon request


  $output = '<div class="units-and-prices">';

  foreach ( $units as $item ) {
    $unit_bedrooms = (int) ( $item[ 'unit_bedroom_count' ] ?? $default_bedrooms );

    $unit_type_idx = (int) ( $item[ 'unit_type' ] ?? $default_type_idx );
    $unit_type     = $unit_types[ $unit_type_idx ] ?? $unit_types[ $default_type_idx ];

    $unit_layout_idx = (int) ( $item[ 'unit_layout' ] ?? $default_layout_idx );
    $unit_layout     = $unit_layouts[ $unit_layout_idx ] ?? $unit_layouts[ $default_layout_idx ];

    $unit_price = (int) ( $item[ 'unit_price' ] ?? $default_price );

    $unit_type_template = trim(
      (string) ( $item[ 'unit_type_template' ] ?? '' )
    );


    // bedroom part with a non-breaking space
    $br_part = $unit_bedrooms > 0
      ? $unit_bedrooms . "\u{00A0}" . ( $is_french ? 'ch.' : 'BR' )
      : ''; // studios must always appear without bedroom number


    // default title
    $unit_title = $unit_type;


    // provided template overwrites the title
    if ( $unit_type_template ) {
      $replacements = [
        '{br}'     => $br_part,
        '{type}'   => $unit_type,
        '{layout}' => $unit_layout,
        '{join}'   => $attribute_separator,
      ];

      // add all attributes to the array
      foreach ( $unit_attributes as $key => $value ) {
        $replacements[ $key ] = $value;
      }

      $unit_title = trim(
        strtr( $unit_type_template, $replacements )
      );
    }
    else {
      // we omit this part for studios as they have no bedrooms
      if ( $unit_bedrooms > 0 ) {

        // for Duplex and Triplex layouts, remove the Apartment part
        $is_complex_apartment = $unit_layout !== '' && $unit_type_idx === 1;
        if ( $is_complex_apartment ) {
          $unit_type = '';
        }

        // standard format
        $unit_title = $is_french
          ? [ $unit_type, $unit_layout, $br_part ]
          : [ $br_part, $unit_layout, $unit_type ];

        $unit_title = implode( ' ', array_filter( $unit_title ) );
      }
    }

    // the title is composed - post-processing starts

    // store templates parts that must not be changed
    $protected = $store_protected_parts( $unit_title );

    // ensure sentence case for French titles
    if ( $is_french ) {
      $unit_title = $french_sentence_case( $unit_title );
    }

    // post-processing ends - the title is complete
    $unit_title = $restore_protected_parts( $unit_title, $protected );


    // if the price is 0, that means the price is unknown
    if ( $unit_price === 0 ) {
      $unit_price = $is_french ? 'Prix sur demande' : 'Price upon request';
    }
    else {
      $from = $is_french ? 'À partir de' : 'From';
      $formatted = number_format( $unit_price, 0, '', ( $is_french ? ' ' : ',' ) );

      $formatted_price = $is_french
        ? $formatted . ' AED'
        : 'AED ' . $formatted;

      $unit_price = '<b>' . $from . '</b> <span class="property-price">' . $formatted_price . '</span>';
    }

    $output .= '
			<div class="unit">
        <div class="unit-title">' . esc_html( $unit_title ) . '</div>
        <div class="unit-price">' . wp_kses_post( $unit_price ) . '</div>
      </div>';
  }

  $output .= '</div>';

  return $output;
});