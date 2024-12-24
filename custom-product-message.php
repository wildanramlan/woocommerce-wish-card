<?php
/*
Plugin Name: Custom Product Message
Description: Allows customers to add a personalized message to their orders.
Version: 1.1
Author: Wildan Ramlan
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add the custom message field to the product page
add_action( 'woocommerce_before_add_to_cart_button', 'add_custom_message_field' );

function add_custom_message_field() {
    global $product;

    $enable_custom_message = get_post_meta( $product->get_id(), 'enable_custom_message', true );

    if ( $enable_custom_message === 'yes' ) {
        echo '<div class="custom-message">';
        echo '<label for="wish_card_checkbox">';
        echo '<input type="checkbox" id="wish_card_checkbox" name="wish_card_checkbox" /> ';
        echo __('Add a Wish Card', 'woocommerce') . '</label>';
        echo '<div id="wish_card_message" style="display:none;">';
        echo '<label for="custom_message">' . __('Custom Message', 'woocommerce') . '</label>';
        echo '<textarea id="custom_message" name="custom_message" rows="4" cols="50"></textarea>';
        echo '</div>';
        echo '</div>';
    }
}

// Enqueue scripts for handling the checkbox
add_action( 'wp_enqueue_scripts', 'enqueue_custom_scripts' );

function enqueue_custom_scripts() {
    wp_enqueue_script( 'custom-product-message-script', plugins_url( 'custom-script.js', __FILE__ ), array('jquery'), null, true );
}

// Save the custom message when the product is added to the cart
add_action( 'woocommerce_add_cart_item_data', 'save_custom_message', 10, 2 );

function save_custom_message( $cart_item_data, $product_id ) {
    if ( isset( $_POST['wish_card_checkbox'] ) ) {
        $cart_item_data['wish_card'] = 'yes';

        if ( isset( $_POST['custom_message'] ) ) {
            $cart_item_data['custom_message'] = sanitize_textarea_field( $_POST['custom_message'] );

            // Get the custom message price
            $custom_message_price = get_post_meta( $product_id, 'custom_message_price', true );
            if ( ! empty( $custom_message_price ) ) {
                $cart_item_data['custom_message_price'] = floatval( $custom_message_price );
            }
        }
    }
    return $cart_item_data;
}

// Display the custom message in the cart
add_filter( 'woocommerce_get_item_data', 'display_custom_message_in_cart', 10, 2 );

function display_custom_message_in_cart( $item_data, $cart_item ) {
    if ( isset( $cart_item['custom_message'] ) ) {
        $item_data[] = array(
            'name' => __('Custom Message', 'woocommerce'),
            'value' => esc_html( $cart_item['custom_message'] ),
        );
    }
    if ( isset( $cart_item['wish_card'] ) ) {
        $item_data[] = array(
            'name' => __('Wish Card', 'woocommerce'),
            'value' => __('Yes', 'woocommerce'),
        );
    }
    return $item_data;
}

// Save the custom message to the order
add_action( 'woocommerce_checkout_create_order_line_item', 'add_custom_message_to_order', 10, 4 );

function add_custom_message_to_order( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['custom_message'] ) ) {
        $item->add_meta_data( __('Custom Message', 'woocommerce'), $values['custom_message'] );
    }
    if ( isset( $values['wish_card'] ) ) {
        $item->add_meta_data( __('Wish Card', 'woocommerce'), 'Yes' );
    }
}

// Validate the custom message
add_action( 'woocommerce_check_cart_items', 'validate_custom_message' );

function validate_custom_message() {
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( empty( $cart_item['custom_message'] ) && isset( $cart_item['wish_card'] ) ) {
            wc_add_notice( __('Please enter a custom message for your Wish Card.', 'woocommerce'), 'error' );
        }
    }
}

// Enqueue styles
add_action( 'wp_enqueue_scripts', 'enqueue_custom_styles' );

function enqueue_custom_styles() {
    wp_enqueue_style( 'custom-product-message-style', plugins_url( 'style.css', __FILE__ ) );
}

// Add custom fields to the product data panel
add_action( 'woocommerce_product_options_general_product_data', 'add_custom_message_settings' );

function add_custom_message_settings() {
    woocommerce_wp_checkbox( array(
        'id'            => 'enable_custom_message',
        'label'         => __( 'Enable Custom Message', 'woocommerce' ),
        'description'   => __( 'Enable this option to allow customers to add a custom message to their order.', 'woocommerce' ),
    ) );

    woocommerce_wp_text_input( array(
        'id'            => 'custom_message_price',
        'label'         => __( 'Custom Message Price', 'woocommerce' ),
        'description'   => __( 'Set the price for the custom message.', 'woocommerce' ),
        'desc_tip'      => true,
        'type'          => 'number',
        'custom_attributes' => array(
            'step' => '0.01',
            'min'  => '0',
        ),
    ) );
}

// Save custom fields
add_action( 'woocommerce_process_product_meta', 'save_custom_message_settings' );

function save_custom_message_settings( $post_id ) {
    $enable_custom_message = isset( $_POST['enable_custom_message'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, 'enable_custom_message', $enable_custom_message );

    $custom_message_price = isset( $_POST['custom_message_price'] ) ? sanitize_text_field( $_POST['custom_message_price'] ) : '';
    update_post_meta( $post_id, 'custom_message_price', $custom_message_price );
}

// Calculate the custom message price in the cart
add_action( 'woocommerce_cart_calculate_fees', 'add_custom_message_price_to_cart' );

function add_custom_message_price_to_cart() {
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( isset( $cart_item['custom_message_price'] ) && isset( $cart_item['wish_card'] ) ) {
            $custom_message_price = $cart_item['custom_message_price'];
            WC()->cart->add_fee( __( 'Custom Message', 'woocommerce' ), $custom_message_price );
        }
    }
}