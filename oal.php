<?php
/*
Plugin Name: Owned Auto Listing Dashboard
Description: Adds a custom car dashboard for managing cars.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Create a menu item in the dashboard
function oal_create_menu() {
    add_menu_page(
        'Owned Auto Listing Dashboard',
        'Owned Auto Listing',
        'activate_plugins',
        'owned-auto-listing-dashboard',
        'oal_display_page'
    );
}

add_action('admin_menu', 'oal_create_menu');

// Custom Dashboard UI
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
        <h1>Owned Auto Listing Dashboard</h1>
        <form method="POST" enctype="multipart/form-data">
        <?php wp_nonce_field('save_car', '_wpnonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="car_brand">Car Brand</label></th>
                    <td>
                        <select name="car_brand" id="car_brand">
                            <option value="Mercedes">Mercedes</option>

                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_brand">Car Brand</label></th>
                    <td><input name="car_brand" type="text" id="car_brand" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_image">Car Image</label></th>
                    <td><input name="car_image" type="file" id="car_image" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_description">Car Description</label></th>
                    <td><textarea name="car_description" id="car_description" class="regular-text" rows="5"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_price">Car Price</label></th>
                    <td><input name="car_price" type="text" id="car_price" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_ydelse">Ydelse</label></th>
                    <td><input name="car_ydelse" type="text" id="car_ydelse" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_restvaerdi">Restværdi</label></th>
                    <td><input name="car_restvaerdi" type="text" id="car_restvaerdi" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button('Save Car'); ?>
        </form>

        <!-- Cars table to edit and delete -->
        <h2>Cars</h2>
        <table class="wp-list-table widefat fixed striped" id="oal-cars-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Model</th>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" id="oal-loading-message">Loading cars...</td>
                </tr>
            </tbody>
        </table>
        <br>
        <button class="button button-secondary" id="oal-purge-data">Purge All Data</button>
    </div>
    <?php
}

function oal_save_car() {
    if (!empty($_POST) && check_admin_referer('save_car', '_wpnonce')) {
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;
        
        $car_data = array(
            'id' => time(),
            'model' => sanitize_text_field($_POST['car_name']),
            'description' => sanitize_text_field($_POST['car_description']),
            'price' => floatval($_POST['car_price']),
            'ydelse' => floatval($_POST['car_ydelse']),
            'restvaerdi' => floatval($_POST['car_restvaerdi']),
        );

        // Handle file upload
        if (!empty($_FILES['car_image']['tmp_name'])) {
            $file = wp_upload_bits($_FILES['car_image']['name'], null, file_get_contents($_FILES['car_image']['tmp_name']));
            if (!$file['error']) {
                $car_data['image'] = $file['url'];
            } else {
                wp_die('Error uploading image: ' . $file['error']);
            }
        }

        $all_users_data = get_option('oal_users_data', array());

        if (!isset($all_users_data[$username])) {
            $all_users_data[$username] = array(
                'subscription' => 'Auto Listings Dealer',
                'role' => current($current_user->roles),
                'cars' => array()
            );
        }

        $brand_exists = false;
        foreach ($all_users_data[$username]['cars'] as &$brand) {
            if ($brand['brand'] === $_POST['car_brand']) {
                $brand['vehicles'][] = $car_data;
                $brand_exists = true;
                break;
            }
        }

        if (!$brand_exists) {
            $all_users_data[$username]['cars'][] = array(
                'brand' => $_POST['car_brand'],
                'vehicles' => array($car_data)
            );
        }

        update_option('oal_users_data', $all_users_data);

        wp_redirect(admin_url('admin.php?page=owned-auto-listing-dashboard&success=1'));
        exit;
    }
}

add_action('admin_init', 'oal_save_car');

// Create custom REST API endpoint
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
    
    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    
    $processed_cars = [
        $username => [
            'subscription' => 'Auto Listings Dealer',
            'role' => current($current_user->roles),
            'cars' => $cars
        ]
    ];
    return new WP_REST_Response($processed_cars, 200);
}

function oal_enqueue_scripts($hook) {
    if ('toplevel_page_owned-auto-listing-dashboard' !== $hook) {
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
    $car_index = array_search($id, array_column($cars, 'id'));

    if ($car_index === false) {
        wp_send_json_error('Car not found');
    }

    array_splice($cars, $car_index, 1);
    update_option('oal_cars', $cars);

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
