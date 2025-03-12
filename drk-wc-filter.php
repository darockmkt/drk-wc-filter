<?php

/**
 * Plugin Name: daRock WooCommerce Filter
 * Plugin URI: https://darock.com.br/
 * Description: Create custom search filter from Attributes for your WooCommerce products.
 * Version: 1.0.0
 * Author: daRock
 * Author URI: https://darock.com.br
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * I18n.
 */
function drk_wc_filter_load_textdomain() {
    load_plugin_textdomain('drk-wc-filter', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'drk_wc_filter_load_textdomain');

/**
 * Configurations' Menu.
 */
function drk_wc_filter_add_admin_menu() {
    add_options_page(
        __('daRock WooCommerce Filter Configurations', 'drk-wc-filter'),
        'daRock WooCommerce Filter',
        'manage_options',
        'drk-wc-filter',
        'drk_wc_filter_options_page'
    );
}
add_action('admin_menu', 'drk_wc_filter_add_admin_menu');

/**
 * Plugins' Menu.
 */
function drk_wc_filter_plugin_action_link($links) {
    $settings_link = '<a href="options-general.php?page=drk-wc-filter">' . __('Configurations', 'drk-wc-filter') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'drk_wc_filter_plugin_action_link');

/**
 * Options' Page.
 */
function drk_wc_filter_options_page() {
    // Checks if user submitted the form and processes data.
    if (isset($_POST['drk_wc_filter_order_submit'])) {
        check_admin_referer('drk_wc_filter_save_order');

        $order = array_map('sanitize_text_field', $_POST['drk_wc_filter_order']);
        update_option('drk_wc_filter_order', $order);

        echo '<div class="updated"><p>' . __('Filter Order saved successfully!', 'drk-wc-filter') . '</p></div>';
    }

    // Get Products Attributes and sorts based on saved order.
    $attributes = wc_get_attribute_taxonomies();
    $saved_order = get_option('drk_wc_filter_order', []);

    usort($attributes, function($a, $b) use ($saved_order) {
        $pos_a = array_search($a->attribute_name, $saved_order);
        $pos_b = array_search($b->attribute_name, $saved_order);

        if ($pos_a === false) $pos_a = PHP_INT_MAX;
        if ($pos_b === false) $pos_b = PHP_INT_MAX;

        return $pos_a - $pos_b;
    });
    ?>
    <div class="wrap">
        <h1><?php echo get_admin_page_title(); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('drk_wc_filter_save_order'); ?>
            <h2><?php _e('Attributes Order', 'drk-wc-filter'); ?></h2>
            <p><?php _e('Drag and drop attributes to define the order in which they will appear in the filter.', 'drk-wc-filter'); ?></p>
            <ul id="drk-wc-filter-sortable">
                <?php foreach ($attributes as $attribute) : ?>
                <li class="ui-state-default">
                    <input type="hidden" name="drk_wc_filter_order[]" value="<?php esc_attr_e($attribute->attribute_name); ?>">
                    <?php esc_html_e($attribute->attribute_label); ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <p><input type="submit" name="drk_wc_filter_order_submit" class="button-primary" value="<?php _e('Save Order', 'drk-wc-filter'); ?>"></p>
        </form>
    </div>
    <?php
}

function drk_wc_filter_enqueue_admin_scripts($hook) {
    if ($hook != 'settings_page_drk-wc-filter') {
        return;
    }

    // Add Admin CSS.
    wp_enqueue_style('drk-wc-filter-admin', plugins_url('/assets/css/admin.css', __FILE__));

    // Add Admin JS with jQuery UI Sortable dependency.
    wp_enqueue_script('drk-wc-filter-script', plugins_url('/assets/js/script.js', __FILE__), array('jquery', 'jquery-ui-sortable'), null, true);
}
add_action('admin_enqueue_scripts', 'drk_wc_filter_enqueue_admin_scripts');

/**
 * Shortcode.
 */
function drk_wc_filter_shortcode() {
    ob_start();

    // Get Products Attributes and sorts based on saved order.
    $attributes = wc_get_attribute_taxonomies();
    $saved_order = get_option('drk_wc_filter_order', []);

    usort($attributes, function($a, $b) use ($saved_order) {
        $pos_a = array_search($a->attribute_name, $saved_order);
        $pos_b = array_search($b->attribute_name, $saved_order);

        if ($pos_a === false) $pos_a = PHP_INT_MAX;
        if ($pos_b === false) $pos_b = PHP_INT_MAX;

        return $pos_a - $pos_b;
    });

    if ($attributes) {
        echo '<form method="GET" action="' . esc_url(get_permalink(woocommerce_get_page_id('shop'))) . '">';
        
        foreach ($attributes as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
            $terms = get_terms($taxonomy);

            if ($terms && !is_wp_error($terms)) {
                echo '<div class="drk-wc-filter">';
                echo '<label for="' . esc_attr($taxonomy) . '">' . esc_html($attribute->attribute_label) . '</label>';
                echo '<select name="' . esc_attr($taxonomy) . '" id="' . esc_attr($taxonomy) . '">';
                echo '<option value="">' . __('Select', 'drk-wc-filter') . ' ' . esc_html($attribute->attribute_label) . '</option>';

                foreach ($terms as $term) {
                    echo '<option value="' . esc_attr($term->slug) . '" ' . selected(isset($_GET[$taxonomy]) && $_GET[$taxonomy] === $term->slug, true, false) . '>';
                    echo esc_html($term->name);
                    echo '</option>';
                }

                echo '</select>';
                echo '</div>';
            }
        }

        echo '<button type="submit">' . __('Filter', 'drk-wc-filter') . '</button>';
        echo '</form>';
    }

    return ob_get_clean();
}
add_shortcode('drk_wc_filter', 'drk_wc_filter_shortcode');

function drk_wc_filter_styles() {
    wp_enqueue_style('drk-wc-filter-site', plugin_dir_url(__FILE__) . 'assets/css/site.css');
}
add_action('wp_enqueue_scripts', 'drk_wc_filter_styles');

/**
 * Block.
 */
function drk_wc_filter_block_init() {
    // Register script.
    wp_register_script(
        'drk-wc-filter-block',
        plugins_url('assets/js/block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/block.js')
    );

    // Register block.
    register_block_type('wc-custom/filters-block', array(
        'editor_script' => 'drk-wc-filter-block',
        'render_callback' => 'drk_wc_filter_block_render',
    ));
}
add_action('init', 'drk_wc_filter_block_init');

function drk_wc_filter_block_render() {
    // Do shortcode previously created.
    return do_shortcode('[drk_wc_filter]');
}

function drk_wc_filter_block_editor_assets() {
    wp_enqueue_style(
        'drk-wc-filter-block-editor',
        plugins_url('assets/css/editor.css', __FILE__),
        array('wp-edit-blocks'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/css/editor.css')
    );
}
add_action('enqueue_block_editor_assets', 'drk_wc_filter_block_editor_assets');

/**
 * Query.
 */
function drk_wc_filter_query($query) {
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category() || is_product_tag())) {
        $tax_query = array();

        // Get Products Attributes.
        $attributes = wc_get_attribute_taxonomies();

        foreach ($attributes as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

            if (isset($_GET[$taxonomy]) && !empty($_GET[$taxonomy])) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_GET[$taxonomy]),
                    'operator' => 'IN',
                );
            }
        }

        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
    }
}
add_action('pre_get_posts', 'drk_wc_filter_query');
