<?php
/*
Plugin Name: Oal
Description: Adds a car to the api endpoint
Version: 1.0.1
Author: aeeriksen
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//create a menu item in the dashboard
function oal_create_menu() {
    add_menu_page(
        'Oal Dashboard',
        'Oal Dashboard',
        'activate_plugins',
        'oal-dashboard',
        'oal_display_page'
    );
}

add_action('admin_menu', 'oal_create_menu');

//custom Dashboard UI
function oal_display_page() {
    // Fetch the cars data from the WordPress option
    $cars = get_option('oal_cars', array());
    
    ?>
    <div class="wrap">
        <h1>Custom Car Dashboard</h1>
        <form method="POST" enctype="multipart/form-data">
        <?php wp_nonce_field('save_car', '_wpnonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="brand_name">Brand Name</label></th>
                    <td><input name="brand_name" type="text" id="brand_name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="model_name">Model Name</label></th>
                    <td><input name="model_name" type="text" id="model_name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="description">Description</label></th>
                    <td><textarea name="description" id="description" class="large-text" rows="4" required></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="price">Price</label></th>
                    <td><input name="price" type="number" step="0.01" id="price" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ydelse">Ydelse</label></th>
                    <td><input name="ydelse" type="text" id="ydelse" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="restværdi">Restværdi</label></th>
                    <td><input name="restværdi" type="text" id="restværdi" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="car_image">Image</label></th>
                    <td><input type="file" name="car_image" id="car_image" required></td>
                </tr>
            </table>
            <?php submit_button('Save Car'); ?>
        </form>

        <h2>All Cars</h2>
        <div id="oal-display-area">
            <?php
            if (empty($cars)) {
                echo '<p>No cars found.</p>';
            } else {
                foreach ($cars as $username => $brands) {
                    echo '<div class="user-card">';
                    echo '<h3>' . esc_html($username) . '</h3>';

                    foreach ($brands as $brand => $models) {
                        echo '<div class="brand-card">';
                        echo '<h4>' . esc_html($brand) . '</h4>';

                        foreach ($models as $model => $car) {
                            echo '<div class="model-card">';
                            echo '<h5>' . esc_html($model) . '</h5>';
                            echo '<p>Description: ' . esc_html($car['description']) . '</p>';
                            echo '<p>Price: $' . esc_html(number_format($car['price'], 2)) . '</p>';
                            echo '<p>Image: <img src="' . esc_url($car['image']) . '" width="50" height="50" /></p>';
                            echo '<button class="button delete-car" data-id="' . esc_attr($car['id']) . '">Delete</button>';
                            echo '</div>';  // End of model card
                        }

                        echo '</div>';  // End of brand card
                    }

                    echo '</div>';  // End of user card
                }
            }
            ?>
        </div>

        <form method="POST">
            <?php wp_nonce_field('oal_purge_data_action', 'oal_purge_data_nonce'); ?>
            <button type="submit" name="oal_purge_data" class="button button-primary">Purge All Data</button>
        </form>



        <style>
            #oal-display-area {
                display: flex;
                flex-wrap: wrap;
            }

            .user-card, .brand-card, .model-card {
                padding: 15px;
                border: 1px solid #ddd;
                margin: 10px;
                border-radius: 5px;
                box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            }

            .user-card {
                background-color: #f3f4f6;
                width: 100%;
            }

            .brand-card {
                background-color: #e2e2e2;
                margin-left: 20px;
            }

            .model-card {
                background-color: #d1d1d1;
                margin-left: 40px;
            }
        </style>
        <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var brandCards = document.querySelectorAll('.brand-card h4');
                    brandCards.forEach(function(brandCard) {
                        brandCard.addEventListener('click', function() {
                            var modelCards = this.parentElement.querySelectorAll('.model-card');
                            modelCards.forEach(function(modelCard) {
                                if (modelCard.style.display === 'none' || modelCard.style.display === '') {
                                    modelCard.style.display = 'block';
                                } else {
                                    modelCard.style.display = 'none';
                                }
                            });
                        });
                    });
                });
        </script>
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

        echo "<script>alert('Car saved successfully!');window.location='" . admin_url('admin.php?page=oal-dashboard') . "';</script>";
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
    if ('toplevel_page_oal-dashboard' !== $hook) {
        return;
    }
    
    wp_enqueue_script('oal-admin', plugins_url('oal-admin.js', __FILE__), array('jquery'), '1.0.0', true);
    wp_localize_script('oal-admin', 'oal_ajax', array('url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('oal_nonce'), 'capability' => current_user_can('activate_plugins') ? 'yes' : 'no'));
}
add_action('admin_enqueue_scripts', 'oal_enqueue_scripts');



function oal_delete_car() {
    if (!wp_verify_nonce($_POST['nonce'], 'oal_nonce')) {
        wp_send_json_error('Forbidden');
    }

    $id = intval($_POST['id']);
    $cars = get_option('oal_cars', array());
    
    // Traversing the hierarchy to find the car to delete
    foreach ($cars as $username => $brands) {
        foreach ($brands as $brand => $models) {
            foreach ($models as $model => $car) {
                if ($car['id'] == $id) {  // Changed to non-strict comparison
                    unset($cars[$username][$brand][$model]);
                    
                    // If no more models for the brand, remove the brand.
                    if (empty($cars[$username][$brand])) {
                        unset($cars[$username][$brand]);
                    }
                    
                    // If the user has no more brands, remove the user entry.
                    if (empty($cars[$username])) {
                        unset($cars[$username]);
                    }
                    
                    update_option('oal_cars', $cars);
                    wp_send_json_success();
                    return; // Exit the function
                }
            }
        }
    }
    
    wp_send_json_error('Car not found');
}


add_action('wp_ajax_oal_delete_car', 'oal_delete_car');


if (isset($_POST['oal_purge_data'])) {
    $deleted = delete_option('oal_cars');
    if ($deleted) {
        add_action('admin_notices', 'oal_purge_data_notice');
    } else {
        add_action('admin_notices', 'oal_purge_data_error_notice');
    }
}


function oal_purge_data_error_notice() {
    echo '<div class="notice notice-error is-dismissible">
        <p>Error purging the data. Please try again.</p>
    </div>';
}
