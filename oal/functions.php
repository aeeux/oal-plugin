<?php
/*
Plugin Name: Oal
Description: Adds a car to the api endpoint
Version: 1.0.0
Author: aeeriksen
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//create a menu item in the dashboard
function cpd_create_menu() {
    add_menu_page(
        'Custom Product Dashboard',
        'Custom Product Dashboard',
        'activate_plugins',
        'custom-product-dashboard',
        'cpd_display_page'
    );
}

add_action('admin_menu', 'cpd_create_menu');

//custom Dashboard UI
function oal_display_page() {
    if (isset($_GET['success'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Car saved successfully!</p>
        </div>
        <?php
    }
    ?>
    <div class="wrap">
        <h1>Custom Car Dashboard</h1>
        <form method="POST" enctype="multipart/form-data">
        <?php wp_nonce_field('save_car', '_wpnonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="username">Username</label></th>
                    <td><input name="username" type="text" id="username" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="brand_name">Brand Name</label></th>
                    <td><input name="brand_name" type="text" id="brand_name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="model_name">Model Name</label></th>
                    <td><input name="model_name" type="text" id="model_name" class="regular-text" required /></td>
                </tr>
                <!-- ... (other fields similar to before, e.g., description, price, ydelse, restværdi, image) ... -->
            </table>
            <?php submit_button('Save Car'); ?>
        </form>
    </div>
    <?php
}


function oal_save_car() {
    if (!empty($_POST) && check_admin_referer('save_car', '_wpnonce')) {
        $file = array();
        
        // Handle the uploaded image, if available
        if (!empty($_FILES['car_image']['name'])) {
            // Setup the array of supported file types
            $supported_types = array('image/jpg', 'image/jpeg', 'image/gif', 'image/png');

            // Get the file type of the upload
            $arr_file_type = wp_check_filetype(basename($_FILES['car_image']['name']));
            $uploaded_type = $arr_file_type['type'];

            // Check if the type is supported. If not, throw an error.
            if (in_array($uploaded_type, $supported_types)) {
                $upload_overrides = array('test_form' => false);
                $file = wp_handle_upload($_FILES['car_image'], $upload_overrides);

                // Check if there was an error with the upload
                if (isset($file['error'])) {
                    // Handle the error here
                }
            } else {
                // File type not supported
            }
        }

        $cars = get_option('oal_cars', array());

        // Get the current user's username
        $current_user = _wp_get_current_user();
        $username = $current_user->user_login; // Get the username from the user object

        $brandName = sanitize_text_field($_POST['brand_name']);
        $modelName = sanitize_text_field($_POST['model_name']);

        // Set data hierarchy
        if (!isset($cars[$username])) {
            $cars[$username] = array();
        }
        if (!isset($cars[$username][$brandName])) {
            $cars[$username][$brandName] = array();
        }

        $cars[$username][$brandName][$modelName] = array(
            'id' => time(),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'ydelse' => sanitize_text_field($_POST['ydelse']),
            'restværdi' => sanitize_text_field($_POST['restværdi']),
            'image' => $file['url'] ?? ''
        );

        update_option('oal_cars', $cars);

        wp_redirect(admin_url('admin.php?page=custom-car-dashboard&success=1'));
        exit;
    }
}


add_action('admin_init', 'oal_save_car');

// Register custom REST API endpoint
function oal_register_api_endpoints() {
    register_rest_route('oal/v1', '/cars', array(
        'methods' => 'GET',
        'callback' => 'oal_get_cars',
    ));
}
add_action('rest_api_init', 'oal_register_api_endpoints');


function oal_get_cars() {
    $cars = get_option('oal_cars', array());
    if (empty($cars)) {
        return new WP_REST_Response(array(), 200);
    }
    return new WP_REST_Response($cars, 200);
}


function oal_enqueue_scripts($hook) {
    if ('toplevel_page_custom-car-dashboard' !== $hook) {
        return;
    }
    wp_enqueue_script('oal-admin', plugins_url('oal-admin.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_localize_script('oal-admin', 'oal_ajax', array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('oal_nonce'), 'capability' => current_user_can('activate_plugins') ? 'yes' : 'no'));
}
add_action('admin_enqueue_scripts', 'oal_enqueue_scripts');



function oal_delete_car() {
    if (!wp_verify_nonce($_POST['nonce'], 'oal_nonce') || !current_user_can('activate_plugins')) {
        wp_send_json_error('Forbidden');
    }

    $id = intval($_POST['id']);
    $cars = get_option('oal_cars', array());
    
    // You'll need to modify this logic since cars are nested under users -> brands -> models
    // ... Your deletion logic here, which would be different due to hierarchy ...

    wp_send_json_success();
}
add_action('wp_ajax_oal_delete_car', 'oal_delete_car');



function oal_purge_data() {
    check_ajax_referer('oal_nonce', 'nonce');
    if (!current_user_can('activate_plugins')) {
        wp_send_json_error('Forbidden');
    }

    update_option('oal_cars', array());
    wp_send_json_success();
}


add_action('wp_ajax_oal_purge_data', 'oal_purge_data');
