<?php
add_action( 'add_meta_boxes', 'add_orders_details_to_admin' );
add_action( 'save_post', 'update_order_details' );

/**
 * Add metabox to order for tracking code
 */
if (!function_exists('add_orders_details_to_admin')) {
    function add_orders_details_to_admin()
    {
        add_meta_box('idx_order_details', 'Código', 'render_order_details', 'shop_order', 'side', 'core');
    }
}


/**
 * Render field of tracking code
 *
 * @param $post
 *
 * @return mixed
 */
function render_order_details($post)
{
    $tracking_code_frenet = get_post_meta($post->ID, 'tracking_code_frenet', true);

    render_nonce_field('orders-details');
    require 'partials/admin/tracking-field.php';
}

/**
 * Update tracking metadata.
 *
 * @param $post_id
 *
 * @return mixed
 */
function update_order_details($post_id)
{
    if (!is_nonce_ok('orders-details')) {
        return $post_id;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    update_post_meta($post_id, 'tracking_code_frenet', $_POST['tracking_code_frenet']);
}