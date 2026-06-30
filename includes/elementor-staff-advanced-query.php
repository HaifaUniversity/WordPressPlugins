<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Advanced query params for Elementor Loop Grid
 * Query ID: staff_advanced
 * Copied from v1 for backward compatibility with existing Elementor Loop Grid widgets.
 */

if ( ! function_exists( 'haifa_add_advanced_query_params_control' ) ) :
function haifa_add_advanced_query_params_control( $element, $args ) {
    $element->add_control(
        'advanced_query_params',
        [
            'label'       => __( 'Advanced Query Params', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'staff_type=administrative|is_featured=1|orderby=is_featured',
            'description' => __( 'Use | to separate params. Format: key=value|key2=value2', 'haifa-staff' ),
        ]
    );
}
endif;

add_action(
    'elementor/element/loop-grid/section_query/before_section_end',
    'haifa_add_advanced_query_params_control',
    10,
    2
);

if ( ! function_exists( 'haifa_staff_advanced_query' ) ) :
function haifa_staff_advanced_query( \WP_Query $query, \Elementor\Widget_Base $widget ) {

    $settings = $widget->get_settings_for_display();

    if ( empty( $settings['advanced_query_params'] ) ) {
        return;
    }

    $raw   = $settings['advanced_query_params'];
    $parts = array_map( 'trim', explode( '|', $raw ) );

    $meta_query = (array) $query->get( 'meta_query' );
    $orderby    = $query->get( 'orderby' );
    $order      = $query->get( 'order' ) ?: 'DESC';

    foreach ( $parts as $part ) {

        if ( ! $part || strpos( $part, '=' ) === false ) {
            continue;
        }

        list( $key, $value ) = array_map( 'trim', explode( '=', $part, 2 ) );
        $value = trim( $value, " \t\n\r\0\x0B\"'" );

        if ( $key === 'orderby' ) {
            $orderby = 'meta_value';
            $query->set( 'meta_key', $value );
        } elseif ( $key === 'order' ) {
            $order = strtoupper( $value );
        } elseif ( $key === 'post_status' ) {
            $query->set( 'post_status', $value );
        } else {
            $meta_query[] = [
                'key'     => $key,
                'value'   => $value,
                'compare' => 'LIKE',
            ];
        }
    }

    if ( ! empty( $meta_query ) ) {
        $query->set( 'meta_query', $meta_query );
    }
    if ( ! empty( $orderby ) ) {
        $query->set( 'orderby', $orderby );
    }
    if ( ! empty( $order ) ) {
        $query->set( 'order', $order );
    }
}
endif;

add_action( 'elementor/query/staff_advanced', 'haifa_staff_advanced_query', 10, 2 );
