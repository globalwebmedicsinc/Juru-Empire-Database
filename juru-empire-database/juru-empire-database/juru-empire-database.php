<?php
/*
Plugin Name: Juru Empire Database
Description: Manages Systems, Planets, Points of Interest, and Fauna for the Juru Empire in WordPress with import/export and API functionality.
Version: 2.4.1
Author: Justin Lavecchia
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize WP_Filesystem with error handling
function juru_init_filesystem() {
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $method = defined('FS_METHOD') ? FS_METHOD : 'direct';
    if ($method !== 'direct' && !WP_Filesystem()) {
        $creds = request_filesystem_credentials('', '', false, false, null);
        if (!$creds) {
            error_log('Juru Empire Database: Failed to obtain filesystem credentials.');
            return false;
        }
        if (!WP_Filesystem($creds)) {
            error_log('Juru Empire Database: Failed to initialize WP_Filesystem with credentials.');
            return false;
        }
    } elseif (!WP_Filesystem()) {
        error_log('Juru Empire Database: Failed to initialize WP_Filesystem with direct method.');
        return false;
    }
    return true;
}

// Register Custom Post Types
function juru_register_post_types() {
    register_post_type('juru_system', [
        'labels' => [
            'name' => 'Systems',
            'singular_name' => 'System',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-admin-site',
        'supports' => ['title', 'thumbnail'],
        'show_in_menu' => 'juru_empire_menu',
    ]);

    register_post_type('juru_planet', [
        'labels' => [
            'name' => 'Planets',
            'singular_name' => 'Planet',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-admin-site-alt',
        'supports' => ['title', 'thumbnail'],
        'show_in_menu' => 'juru_empire_menu',
    ]);

    register_post_type('juru_poi', [
        'labels' => [
            'name' => 'Points of Interest',
            'singular_name' => 'Point of Interest',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'thumbnail'],
        'show_in_menu' => 'juru_empire_menu',
    ]);

    register_post_type('juru_player', [
        'labels' => [
            'name' => 'Players',
            'singular_name' => 'Player',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title'],
        'show_in_menu' => 'juru_empire_menu',
    ]);

    register_post_type('juru_fauna', [
        'labels' => [
            'name' => 'Fauna',
            'singular_name' => 'Fauna',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-pets',
        'supports' => ['title', 'thumbnail'],
        'show_in_menu' => 'juru_empire_menu',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
}
add_action('init', 'juru_register_post_types');

// Create Admin Menu
function juru_admin_menu() {
    add_menu_page(
        'Juru Empire',
        'Juru Empire',
        'manage_options',
        'juru_empire_menu',
        null,
        'dashicons-star-filled',
        20
    );

    add_submenu_page(
        'juru_empire_menu',
        'Ships',
        'Ships',
        'manage_options',
        'edit.php?post_type=juru_poi&poi_type=ships'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Trading Outpost',
        'Trading Outpost',
        'manage_options',
        'edit.php?post_type=juru_poi&poi_type=trading_outpost'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Archives Outpost',
        'Archives Outpost',
        'manage_options',
        'edit.php?post_type=juru_poi&poi_type=archives_outpost'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Minor Settlement',
        'Minor Settlement',
        'manage_options',
        'edit.php?post_type=juru_poi&poi_type=minor_settlement'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Crashed Freighter',
        'Crashed Freighter',
        'manage_options',
        'edit.php?post_type=juru_poi&poi_type=crashed_freighter'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Settlements',
        'Settlements',
        'manage_options',
        'edit.php?post_type=juru_poi&poi_type=settlements'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Fauna',
        'Fauna',
        'manage_options',
        'edit.php?post_type=juru_fauna'
    );

    add_submenu_page(
        'juru_empire_menu',
        'Juru Settings',
        'Settings',
        'manage_options',
        'juru_settings',
        'juru_settings_page'
    );
}
add_action('admin_menu', 'juru_admin_menu');

// Settings Page Callback
function juru_settings_page() {
    ?>
    <div class="wrap">
        <h1>Juru Empire Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('juru_settings_group');
            do_settings_sections('juru_settings');
            submit_button();
            ?>
        </form>
        <h2>Import/Export Data</h2>
        <form method="post" action="">
            <?php wp_nonce_field('juru_export_action', 'juru_export_nonce'); ?>
            <h3>Export Data</h3>
            <p>
                <label for="juru_export_type">Select Data Type to Export:</label>
                <select name="juru_export_type" id="juru_export_type">
                    <option value="juru_system">Systems</option>
                    <option value="juru_planet">Planets</option>
                    <option value="juru_poi">Points of Interest</option>
                    <option value="juru_fauna">Fauna</option>
                </select>
            </p>
            <p>
                <?php submit_button('Export to CSV', 'secondary', 'juru_export_submit', false); ?>
            </p>
        </form>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('juru_import_action', 'juru_import_nonce'); ?>
            <h3>Import Data</h3>
            <p>
                <label for="juru_import_file">Upload CSV File:</label>
                <input type="file" name="juru_import_file" id="juru_import_file" accept=".csv" required />
            </p>
            <p>
                <label for="juru_import_type">Select Data Type to Import:</label>
                <select name="juru_import_type" id="juru_import_type">
                    <option value="juru_system">Systems</option>
                    <option value="juru_planet">Planets</option>
                    <option value="juru_poi">Points of Interest</option>
                    <option value="juru_fauna">Fauna</option>
                </select>
            </p>
            <p>
                <?php submit_button('Import CSV', 'primary', 'juru_import_submit', false); ?>
            </p>
        </form>
        <h2>API Settings</h2>
        <form method="post" action="">
            <?php wp_nonce_field('juru_api_action', 'juru_api_nonce'); ?>
            <h3>Sync Data with Another Server</h3>
            <p>
                <label for="juru_api_url">Remote Server API URL:</label>
                <input type="url" name="juru_api_url" id="juru_api_url" value="<?php echo esc_attr(get_option('juru_api_url', '')); ?>" placeholder="https://example.com/wp-json/juru/v1/sync" />
                <p class="description">Enter the remote server's API endpoint for data syncing.</p>
            </p>
            <p>
                <label for="juru_api_key">API Key:</label>
                <input type="text" name="juru_api_key" id="juru_api_key" value="<?php echo esc_attr(get_option('juru_api_key', '')); ?>" />
                <p class="description">Enter the API key for authentication with the remote server.</p>
            </p>
            <p>
                <label for="juru_api_overwrite"><input type="checkbox" name="juru_api_overwrite" id="juru_api_overwrite" value="1" <?php checked(get_option('juru_api_overwrite', false)); ?> /> Overwrite Local Data</label>
                <p class="description">If checked, local data will be overwritten with remote data during sync.</p>
            </p>
            <p>
                <?php submit_button('Sync Data', 'primary', 'juru_api_sync', false); ?>
            </p>
        </form>
    </div>
    <?php
}

// Register Settings
function juru_register_settings() {
    register_setting('juru_settings_group', 'juru_max_coord_x', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1000,
    ]);

    register_setting('juru_settings_group', 'juru_max_coord_y', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1000,
    ]);

    register_setting('juru_settings_group', 'juru_display_map', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true,
    ]);

    register_setting('juru_settings_group', 'juru_api_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => '',
    ]);

    register_setting('juru_settings_group', 'juru_api_key', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);

    register_setting('juru_settings_group', 'juru_api_overwrite', [
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => false,
    ]);

    add_settings_section(
        'juru_main_settings',
        'General Settings',
        function() { echo '<p>Configure general settings for the Juru Empire Database.</p>'; },
        'juru_settings'
    );

    add_settings_field(
        'juru_max_coord_x',
        'Maximum X Coordinate',
        function() {
            $value = get_option('juru_max_coord_x', 1000);
            echo '<input type="number" name="juru_max_coord_x" value="' . esc_attr($value) . '" min="500" max="10000" />';
            echo '<p class="description">Set the maximum X coordinate for Points of Interest (500-10000).</p>';
        },
        'juru_settings',
        'juru_main_settings'
    );

    add_settings_field(
        'juru_max_coord_y',
        'Maximum Y Coordinate',
        function() {
            $value = get_option('juru_max_coord_y', 1000);
            echo '<input type="number" name="juru_max_coord_y" value="' . esc_attr($value) . '" min="500" max="10000" />';
            echo '<p class="description">Set the maximum Y coordinate for Points of Interest (500-10000).</p>';
        },
        'juru_settings',
        'juru_main_settings'
    );

    add_settings_field(
        'juru_display_map',
        'Display POI Map',
        function() {
            $value = get_option('juru_display_map', true);
            echo '<input type="checkbox" name="juru_display_map" value="1" ' . checked($value, true, false) . ' />';
            echo '<p class="description">Enable or disable the display of the POI map on the frontend.</p>';
        },
        'juru_settings',
        'juru_main_settings'
    );
}
add_action('admin_init', 'juru_register_settings');

// Migrate Existing Z Coordinates to Y Coordinates
function juru_migrate_coordinates() {
    $version = get_option('juru_plugin_version', '1.8');
    if (version_compare($version, '2.0', '<')) {
        $pois = get_posts([
            'post_type' => 'juru_poi',
            'numberposts' => -1,
            'post_status' => 'any',
        ]);
        foreach ($pois as $poi) {
            $z_coord = get_post_meta($poi->ID, '_juru_coord_z', true);
            if ($z_coord !== '') {
                update_post_meta($poi->ID, '_juru_coord_y', $z_coord);
                delete_post_meta($poi->ID, '_juru_coord_z');
            }
        }
        update_option('juru_plugin_version', '2.4.0');
    }
}
add_action('init', 'juru_migrate_coordinates');

// Filter Search Queries for POIs and Fauna
function juru_filter_poi_search($query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $post_type = $query->get('post_type');
        $post_types = is_array($post_type) ? $post_type : [$post_type];
        if (in_array('juru_poi', $post_types)) {
            $poi_type = get_query_var('poi_type');
            if ($poi_type) {
                $meta_query = $query->get('meta_query') ?: [];
                $meta_query[] = [
                    'key' => '_juru_poi_type',
                    'value' => sanitize_text_field($poi_type),
                    'compare' => '=',
                ];
                $query->set('meta_query', $meta_query);
            }
        } elseif (empty($post_type)) {
            $post_types = $query->get('post_type') ?: ['post', 'page'];
            if (!is_array($post_types)) {
                $post_types = [$post_types];
            }
            $post_types = array_diff($post_types, ['juru_poi', 'juru_fauna']);
            $query->set('post_type', $post_types);
        }
    }
}
add_action('pre_get_posts', 'juru_filter_poi_search');

// Register Custom Meta Boxes
function juru_add_meta_boxes() {
    add_meta_box('juru_system_details', 'System Details', 'juru_system_meta_callback', 'juru_system', 'normal', 'high');
    add_meta_box('juru_planet_details', 'Planet Details', 'juru_planet_meta_callback', 'juru_planet', 'normal', 'high');
    add_meta_box('juru_poi_details', 'Point of Interest Details', 'juru_poi_meta_callback', 'juru_poi', 'normal', 'high');
    add_meta_box('juru_fauna_details', 'Fauna Details', 'juru_fauna_meta_callback', 'juru_fauna', 'normal', 'high');
}
add_action('add_meta_boxes', 'juru_add_meta_boxes');

// System Meta Callback
function juru_system_meta_callback($post) {
    wp_nonce_field('juru_system_meta', 'juru_system_nonce');
    $economy_type = get_post_meta($post->ID, '_juru_economy_type', true);
    $economy_strength = get_post_meta($post->ID, '_juru_economy_strength', true);
    $orbital_bodies = get_post_meta($post->ID, '_juru_orbital_bodies', true);
    ?>
    <p><label>Economy Type: <input type="text" name="juru_economy_type" value="<?php echo esc_attr($economy_type); ?>" /></label></p>
    <p><label>Economy Strength: <input type="text" name="juru_economy_strength" value="<?php echo esc_attr($economy_strength); ?>" /></label></p>
    <p><label>Orbital Bodies (1-6): <input type="number" name="juru_orbital_bodies" min="1" max="6" value="<?php echo esc_attr($orbital_bodies); ?>" /></label></p>
    <?php
}

// Planet Meta Callback
function juru_planet_meta_callback($post) {
    wp_nonce_field('juru_planet_meta', 'juru_planet_nonce');
    $system = get_post_meta($post->ID, '_juru_system', true);
    $portal_address = get_post_meta($post->ID, '_juru_portal_address', true);
    ?>
    <p><label>System:
        <select name="juru_system">
            <option value="">Select a System</option>
            <?php
            $systems = get_posts(['post_type' => 'juru_system', 'numberposts' => -1, 'post_status' => 'publish']);
            foreach ($systems as $sys) {
                echo '<option value="' . esc_attr($sys->ID) . '" ' . selected($system, $sys->ID, false) . '>' . esc_html($sys->post_title) . '</option>';
            }
            ?>
        </select>
    </label></p>
    <p><label>Portal Address: <input type="text" name="juru_portal_address" value="<?php echo esc_attr($portal_address); ?>" /></label></p>
    <?php
}

// POI Meta Callback
function juru_poi_meta_callback($post) {
    wp_nonce_field('juru_poi_meta', 'juru_poi_nonce');
    $planet = get_post_meta($post->ID, '_juru_planet', true);
    $poi_type = get_post_meta($post->ID, '_juru_poi_type', true);
    $coord_x = get_post_meta($post->ID, '_juru_coord_x', true);
    $coord_y = get_post_meta($post->ID, '_juru_coord_y', true);
    $max_coord_x = get_option('juru_max_coord_x', 1000);
    $max_coord_y = get_option('juru_max_coord_y', 1000);
    ?>
    <p><label>Planet:
        <select name="juru_planet">
            <option value="">Select a Planet</option>
            <?php
            $planets = get_posts(['post_type' => 'juru_planet', 'numberposts' => -1, 'post_status' => 'publish']);
            foreach ($planets as $pl) {
                echo '<option value="' . esc_attr($pl->ID) . '" ' . selected($planet, $pl->ID, false) . '>' . esc_html($pl->post_title) . '</option>';
            }
            ?>
        </select>
    </label></p>
    <p><label>POI Type:
        <select name="juru_poi_type">
            <option value="ships" <?php selected($poi_type, 'ships'); ?>>Ships</option>
            <option value="trading_outpost" <?php selected($poi_type, 'trading_outpost'); ?>>Trading Outpost</option>
            <option value="archives_outpost" <?php selected($poi_type, 'archives_outpost'); ?>>Archives Outpost</option>
            <option value="minor_settlement" <?php selected($poi_type, 'minor_settlement'); ?>>Minor Settlement</option>
            <option value="crashed_freighter" <?php selected($poi_type, 'crashed_freighter'); ?>>Crashed Freighter</option>
            <option value="settlements" <?php selected($poi_type, 'settlements'); ?>>Settlements</option>
        </select>
    </label></p>
    <p><label>X Coordinate: <input type="number" name="juru_coord_x" step="0.01" min="0" max="<?php echo esc_attr($max_coord_x); ?>" value="<?php echo esc_attr($coord_x); ?>" /></label></p>
    <p><label>Y Coordinate: <input type="number" name="juru_coord_y" step="0.01" min="0" max="<?php echo esc_attr($max_coord_y); ?>" value="<?php echo esc_attr($coord_y); ?>" /></label></p>
    <?php
}

// Fauna Meta Callback
function juru_fauna_meta_callback($post) {
    wp_nonce_field('juru_fauna_meta', 'juru_fauna_nonce');
    $description = get_post_meta($post->ID, '_juru_fauna_description', true);
    $planet = get_post_meta($post->ID, '_juru_planet', true);
    $diet = get_post_meta($post->ID, '_juru_fauna_diet', true);
    $produces = get_post_meta($post->ID, '_juru_fauna_produces', true);
    $image_2 = get_post_meta($post->ID, '_juru_fauna_image_2', true);
    $image_3 = get_post_meta($post->ID, '_juru_fauna_image_3', true);
    ?>
    <p><label>Description: <textarea name="juru_fauna_description" rows="4" cols="50"><?php echo esc_textarea($description); ?></textarea></label></p>
    <p><label>Planet:
        <select name="juru_planet">
            <option value="">Select a Planet</option>
            <?php
            $planets = get_posts(['post_type' => 'juru_planet', 'numberposts' => -1, 'post_status' => 'publish']);
            foreach ($planets as $pl) {
                echo '<option value="' . esc_attr($pl->ID) . '" ' . selected($planet, $pl->ID, false) . '>' . esc_html($pl->post_title) . '</option>';
            }
            ?>
        </select>
    </label></p>
    <p><label>Diet: <input type="text" name="juru_fauna_diet" value="<?php echo esc_attr($diet); ?>" /></label></p>
    <p><label>Produces: <input type="text" name="juru_fauna_produces" value="<?php echo esc_attr($produces); ?>" /></label></p>
    <p><label>Additional Image 1:
        <input type="hidden" name="juru_fauna_image_2" id="juru_fauna_image_2" value="<?php echo esc_attr($image_2); ?>" />
        <img id="juru_fauna_image_2_preview" src="<?php echo $image_2 ? wp_get_attachment_url($image_2) : ''; ?>" style="max-width:150px;display:<?php echo $image_2 ? 'block' : 'none'; ?>;" />
        <button type="button" class="button juru-upload-image" data-target="juru_fauna_image_2">Select Image</button>
        <button type="button" class="button juru-remove-image" data-target="juru_fauna_image_2" style="display:<?php echo $image_2 ? 'inline-block' : 'none'; ?>;">Remove Image</button>
    </label></p>
    <p><label>Additional Image 2:
        <input type="hidden" name="juru_fauna_image_3" id="juru_fauna_image_3" value="<?php echo esc_attr($image_3); ?>" />
        <img id="juru_fauna_image_3_preview" src="<?php echo $image_3 ? wp_get_attachment_url($image_3) : ''; ?>" style="max-width:150px;display:<?php echo $image_3 ? 'block' : 'none'; ?>;" />
        <button type="button" class="button juru-upload-image" data-target="juru_fauna_image_3">Select Image</button>
        <button type="button" class="button juru-remove-image" data-target="juru_fauna_image_3" style="display:<?php echo $image_3 ? 'inline-block' : 'none'; ?>;">Remove Image</button>
    </label></p>
    <?php
}

// Save Meta Data
function juru_save_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (get_post_type($post_id) === 'juru_system' && isset($_POST['juru_system_nonce']) && wp_verify_nonce($_POST['juru_system_nonce'], 'juru_system_meta')) {
        update_post_meta($post_id, '_juru_economy_type', sanitize_text_field($_POST['juru_economy_type']));
        update_post_meta($post_id, '_juru_economy_strength', sanitize_text_field($_POST['juru_economy_strength']));
        update_post_meta($post_id, '_juru_orbital_bodies', intval($_POST['juru_orbital_bodies']));
    }

    if (get_post_type($post_id) === 'juru_planet' && isset($_POST['juru_planet_nonce']) && wp_verify_nonce($_POST['juru_planet_nonce'], 'juru_planet_meta')) {
        update_post_meta($post_id, '_juru_system', intval($_POST['juru_system']));
        update_post_meta($post_id, '_juru_portal_address', sanitize_text_field($_POST['juru_portal_address']));
    }

    if (get_post_type($post_id) === 'juru_poi' && isset($_POST['juru_poi_nonce']) && wp_verify_nonce($_POST['juru_poi_nonce'], 'juru_poi_meta')) {
        update_post_meta($post_id, '_juru_planet', intval($_POST['juru_planet']));
        update_post_meta($post_id, '_juru_poi_type', sanitize_text_field($_POST['juru_poi_type']));
        update_post_meta($post_id, '_juru_coord_x', floatval($_POST['juru_coord_x']));
        update_post_meta($post_id, '_juru_coord_y', floatval($_POST['juru_coord_y']));
    }

    if (get_post_type($post_id) === 'juru_fauna' && isset($_POST['juru_fauna_nonce']) && wp_verify_nonce($_POST['juru_fauna_nonce'], 'juru_fauna_meta')) {
        update_post_meta($post_id, '_juru_fauna_description', sanitize_textarea_field($_POST['juru_fauna_description']));
        update_post_meta($post_id, '_juru_planet', intval($_POST['juru_planet']));
        update_post_meta($post_id, '_juru_fauna_diet', sanitize_text_field($_POST['juru_fauna_diet']));
        update_post_meta($post_id, '_juru_fauna_produces', sanitize_text_field($_POST['juru_fauna_produces']));
        update_post_meta($post_id, '_juru_fauna_image_2', intval($_POST['juru_fauna_image_2']));
        update_post_meta($post_id, '_juru_fauna_image_3', intval($_POST['juru_fauna_image_3']));
    }
}
add_action('save_post', 'juru_save_meta');

// Frontend Template
function juru_frontend_template($template) {
    if (is_singular(['juru_system', 'juru_planet', 'juru_poi', 'juru_fauna'])) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-juru.php';
        if (file_exists($new_template)) {
            return $new_template;
        } else {
            error_log('Juru Empire Database: single-juru.php template not found at ' . $new_template);
            return $template;
        }
    } elseif (is_post_type_archive(['juru_player', 'juru_fauna'])) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/archive-juru.php';
        if (file_exists($new_template)) {
            return $new_template;
        } else {
            error_log('Juru Empire Database: archive-juru.php template not found at ' . $new_template);
            return $template;
        }
    }
    return $template;
}
add_filter('template_include', 'juru_frontend_template');

// Activation Hook
function juru_activate_plugin() {
    try {
        error_log('Juru Empire Database: Starting plugin activation for version 2.4.0');
        if (!juru_init_filesystem()) {
            error_log('Juru Empire Database: Filesystem initialization failed during activation.');
        }
        juru_register_post_types();
        juru_user_permissions();
        flush_rewrite_rules();
        update_option('juru_plugin_version', '2.4.0');
        error_log('Juru Empire Database: Activation completed successfully.');
    } catch (Exception $e) {
        error_log('Juru Empire Database: Error during activation: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        update_option('juru_plugin_version', '2.4.0');
        juru_register_post_types();
        juru_user_permissions();
        flush_rewrite_rules();
    }
}
register_activation_hook(__FILE__, 'juru_activate_plugin');

// Display admin notice if files are missing
function juru_admin_notices() {
    global $wp_filesystem;
    if (!juru_init_filesystem()) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('Juru Empire Database: Failed to initialize filesystem. Please ensure the plugin directory is writable and all required files are present.', 'juru-empire-database'); ?></p>
        </div>
        <?php
        return;
    }

    $required_files = [
        'templates/single-juru.php' => 'Template for displaying Systems, Planets, POIs, and Fauna',
        'templates/archive-juru.php' => 'Template for displaying Players and Fauna archives',
        'css/juru-styles.css' => 'Stylesheet for frontend styling',
        'js/juru-frontend.js' => 'JavaScript for frontend functionality',
    ];
    $missing_files = [];

    foreach ($required_files as $file => $description) {
        $file_path = plugin_dir_path(__FILE__) . $file;
        if (!$wp_filesystem->exists($file_path)) {
            $missing_files[] = [$file, $description];
        }
    }

    if (!empty($missing_files)) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('Juru Empire Database: The following required files are missing:', 'juru-empire-database'); ?></p>
            <ul>
                <?php foreach ($missing_files as $file) : ?>
                    <li><?php echo esc_html($file[0]) . ' (' . esc_html($file[1]) . ')'; ?></li>
                <?php endforeach; ?>
            </ul>
            <p><?php esc_html_e('Please create these files in the plugin directory (' . plugin_dir_path(__FILE__) . ') with the provided contents and ensure the directory is writable (e.g., chmod 755).', 'juru-empire-database'); ?></p>
        </div>
        <?php
    }

    if (isset($_GET['juru_import_success'])) {
        $count = intval($_GET['juru_import_success']);
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf(esc_html__('Juru Empire Database: Successfully imported %d records.', 'juru-empire-database'), $count); ?></p>
        </div>
        <?php
    } elseif (isset($_GET['juru_import_error'])) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('Juru Empire Database: Import failed: ' . esc_html($_GET['juru_import_error']), 'juru-empire-database'); ?></p>
        </div>
        <?php
    } elseif (isset($_GET['juru_api_success'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Juru Empire Database: Connection Established, transmit complete..', 'juru-empire-database'); ?></p>
        </div>
        <?php
    } elseif (isset($_GET['juru_api_error'])) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('Juru Empire Database: Failed Transmission: ' . esc_html($_GET['juru_api_error']), 'juru-empire-database'); ?></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'juru_admin_notices');

// Enqueue Styles and Scripts
function juru_enqueue_styles_scripts() {
    $css_file = plugin_dir_path(__FILE__) . 'css/juru-styles.css';
    if (file_exists($css_file)) {
        wp_enqueue_style('juru-styles', plugin_dir_url(__FILE__) . 'css/juru-styles.css', [], '2.4.0');
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.3', true);
    } else {
        error_log('Juru Empire Database: CSS file not found at ' . $css_file . ', skipping enqueue.');
    }
    
    $js_file = plugin_dir_path(__FILE__) . 'js/juru-frontend.js';
    if (file_exists($js_file)) {
        wp_enqueue_script('juru-frontend', plugin_dir_url(__FILE__) . 'js/juru-frontend.js', ['jquery', 'wp-api'], '2.4.0', true);
        wp_localize_script('juru-frontend', 'juruAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('juru_frontend_nonce'),
            'max_coord_x' => get_option('juru_max_coord_x', 1000),
            'max_coord_y' => get_option('juru_max_coord_y', 1000),
            'api_url' => get_option('juru_api_url', ''),
        ]);
    } else {
        error_log('Juru Empire Database: JS file not found at ' . $js_file . ', skipping enqueue.');
    }

    // Enqueue WordPress media scripts for image uploads
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'juru_enqueue_styles_scripts');
add_action('admin_enqueue_scripts', 'juru_enqueue_styles_scripts');

function juru_enqueue_scripts() {
    wp_enqueue_style('juru-styles', plugin_dir_url(__FILE__) . 'css/juru-styles.css', [], '2.4.0');
    wp_enqueue_script('juru-frontend', plugin_dir_url(__FILE__) . 'js/juru-frontend.js', ['jquery', 'wp-api'], '2.4.0', true);
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.3', true);
    wp_localize_script('juru-frontend', 'juruAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('juru_frontend_nonce'),
        'max_coord_x' => get_option('juru_max_coord_x', 1000),
        'max_coord_y' => get_option('juru_max_coord_y', 1000),
        'api_url' => get_option('juru_api_url', ''),
    ]);
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'juru_enqueue_scripts');

// Allow Contributors to Add POIs and Fauna, Editors/Admins for Systems and Planets
function juru_user_permissions() {
    $contributor = get_role('contributor');
    $contributor->add_cap('edit_juru_poi');
    $contributor->add_cap('publish_juru_poi');
    $contributor->add_cap('edit_juru_fauna');
    $contributor->add_cap('publish_juru_fauna');

    $editor = get_role('editor');
    $editor->add_cap('edit_juru_system');
    $editor->add_cap('publish_juru_system');
    $editor->add_cap('edit_juru_planet');
    $editor->add_cap('publish_juru_planet');

    $admin = get_role('administrator');
    $admin->add_cap('edit_juru_system');
    $admin->add_cap('publish_juru_system');
    $admin->add_cap('edit_juru_planet');
    $admin->add_cap('publish_juru_planet');
    $admin->add_cap('edit_juru_fauna');
    $admin->add_cap('publish_juru_fauna');
}
add_action('init', 'juru_user_permissions');

// Shortcode for Frontend Navigation and Add Buttons
function juru_navigation_shortcode() {
    ob_start();
    ?>
    <nav class="juru-nav navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url(get_post_type_archive_link('juru_system')); ?>">Systems</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url(get_post_type_archive_link('juru_planet')); ?>">Planets</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="poiDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Points of Interest
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="poiDropdown">
                        <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg('poi_type', 'ships', get_post_type_archive_link('juru_poi'))); ?>">Ships</a></li>
                        <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg('poi_type', 'trading_outpost', get_post_type_archive_link('juru_poi'))); ?>">Trading Outpost</a></li>
                        <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg('poi_type', 'archives_outpost', get_post_type_archive_link('juru_poi'))); ?>">Archives Outpost</a></li>
                        <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg('poi_type', 'minor_settlement', get_post_type_archive_link('juru_poi'))); ?>">Minor Settlement</a></li>
                        <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg('poi_type', 'crashed_freighter', get_post_type_archive_link('juru_poi'))); ?>">Crashed Freighter</a></li>
                        <li><a class="dropdown-item" href="<?php echo esc_url(add_query_arg('poi_type', 'settlements', get_post_type_archive_link('juru_poi'))); ?>">Settlements</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url(get_post_type_archive_link('juru_fauna')); ?>">Fauna</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url(get_post_type_archive_link('juru_player')); ?>">Players</a>
                </li>
                <?php if (is_user_logged_in()) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="addNewDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Add New
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="addNewDropdown">
                            <?php if (current_user_can('publish_juru_system')) : ?>
                                <li><button class="dropdown-item juru-add-button" data-type="system">Add System</button></li>
                            <?php endif; ?>
                            <?php if (current_user_can('publish_juru_planet')) : ?>
                                <li><button class="dropdown-item juru-add-button" data-type="planet">Add Planet</button></li>
                            <?php endif; ?>
                            <?php if (current_user_can('publish_juru_poi')) : ?>
                                <li><button class="dropdown-item juru-add-button" data-type="poi">Add Point of Interest</button></li>
                            <?php endif; ?>
                            <?php if (current_user_can('publish_juru_fauna')) : ?>
                                <li><button class="dropdown-item juru-add-button" data-type="fauna">Add Fauna</button></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php if (is_user_logged_in()) : ?>
        <div id="juru-add-drawer" class="offcanvas offcanvas-end" tabindex="-1" aria-labelledby="juruDrawerLabel">
            <div class="juru-drawer-content offcanvas-body">
                <button id="juru-drawer-close" class="btn btn-outline-danger" data-bs-dismiss="offcanvas" aria-label="Close">Close</button>
                <div id="juru-drawer-form"></div>
            </div>
        </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('juru_navigation', 'juru_navigation_shortcode');

// AJAX Handlers for Frontend Submission
function juru_get_systems() {
    check_ajax_referer('juru_frontend_nonce', 'nonce');
    $systems = get_posts(['post_type' => 'juru_system', 'numberposts' => -1, 'post_status' => 'publish']);
    $data = [];
    foreach ($systems as $system) {
        $data[] = ['id' => $system->ID, 'title' => $system->post_title];
    }
    wp_send_json_success($data);
}
add_action('wp_ajax_juru_get_systems', 'juru_get_systems');

function juru_get_planets() {
    check_ajax_referer('juru_frontend_nonce', 'nonce');
    $planets = get_posts(['post_type' => 'juru_planet', 'numberposts' => -1, 'post_status' => 'publish']);
    $data = [];
    foreach ($planets as $planet) {
        $data[] = ['id' => $planet->ID, 'title' => $planet->post_title];
    }
    wp_send_json_success($data);
}
add_action('wp_ajax_juru_get_planets', 'juru_get_planets');

function juru_add_system() {
    check_ajax_referer('juru_frontend_nonce', 'nonce');
    if (!current_user_can('publish_juru_system')) {
        wp_send_json_error(['message' => 'Insufficient permissions to add a system']);
    }

    $title = sanitize_text_field($_POST['title']);
    if (empty($title)) {
        wp_send_json_error(['message' => 'Title is required']);
    }

    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_type' => 'juru_system',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    if (is_wp_error($post_id)) {
        error_log('Juru Empire Database: Failed to add system: ' . $post_id->get_error_message());
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    update_post_meta($post_id, '_juru_economy_type', sanitize_text_field($_POST['economy_type']));
    update_post_meta($post_id, '_juru_economy_strength', sanitize_text_field($_POST['economy_strength']));
    update_post_meta($post_id, '_juru_orbital_bodies', intval($_POST['orbital_bodies']));
    wp_send_json_success(['permalink' => get_permalink($post_id)]);
}
add_action('wp_ajax_juru_add_system', 'juru_add_system');

function juru_add_planet() {
    check_ajax_referer('juru_frontend_nonce', 'nonce');
    if (!current_user_can('publish_juru_planet')) {
        wp_send_json_error(['message' => 'Insufficient permissions to add a planet']);
    }

    $title = sanitize_text_field($_POST['title']);
    if (empty($title)) {
        wp_send_json_error(['message' => 'Title is required']);
    }

    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_type' => 'juru_planet',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    if (is_wp_error($post_id)) {
        error_log('Juru Empire Database: Failed to add planet: ' . $post_id->get_error_message());
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    update_post_meta($post_id, '_juru_system', intval($_POST['system']));
    update_post_meta($post_id, '_juru_portal_address', sanitize_text_field($_POST['portal_address']));
    wp_send_json_success(['permalink' => get_permalink($post_id)]);
}
add_action('wp_ajax_juru_add_planet', 'juru_add_planet');

function juru_add_poi() {
    check_ajax_referer('juru_frontend_nonce', 'nonce');
    if (!current_user_can('publish_juru_poi')) {
        wp_send_json_error(['message' => 'Insufficient permissions to add a POI']);
    }

    $title = sanitize_text_field($_POST['title']);
    if (empty($title)) {
        wp_send_json_error(['message' => 'Title is required']);
    }

    $planet = intval($_POST['planet']);
    if (empty($planet)) {
        wp_send_json_error(['message' => 'Planet is required']);
    }

    $poi_type = sanitize_text_field($_POST['poi_type']);
    if (empty($poi_type)) {
        wp_send_json_error(['message' => 'POI type is required']);
    }

    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_type' => 'juru_poi',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    if (is_wp_error($post_id)) {
        error_log('Juru Empire Database: Failed to add POI: ' . $post_id->get_error_message());
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    update_post_meta($post_id, '_juru_planet', $planet);
    update_post_meta($post_id, '_juru_poi_type', $poi_type);
    update_post_meta($post_id, '_juru_coord_x', floatval($_POST['juru_coord_x']));
    update_post_meta($post_id, '_juru_coord_y', floatval($_POST['juru_coord_y']));
    wp_send_json_success(['permalink' => get_permalink($post_id)]);
}
add_action('wp_ajax_juru_add_poi', 'juru_add_poi');

function juru_add_fauna() {
    check_ajax_referer('juru_frontend_nonce', 'nonce');
    if (!current_user_can('publish_juru_fauna')) {
        wp_send_json_error(['message' => 'Insufficient permissions to add a fauna']);
    }

    $title = sanitize_text_field($_POST['title']);
    if (empty($title)) {
        wp_send_json_error(['message' => 'Title is required']);
    }

    $planet = intval($_POST['planet']);
    if (empty($planet)) {
        wp_send_json_error(['message' => 'Planet is required']);
    }

    // Check fauna count for the planet
    $existing_fauna = get_posts([
        'post_type' => 'juru_fauna',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_juru_planet',
                'value' => $planet,
                'compare' => '=',
            ],
        ],
    ]);

    if (count($existing_fauna) >= 30) {
        wp_send_json_error(['message' => 'Maximum of 30 fauna per planet reached']);
    }

    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_type' => 'juru_fauna',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    if (is_wp_error($post_id)) {
        error_log('Juru Empire Database: Failed to add fauna: ' . $post_id->get_error_message());
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    update_post_meta($post_id, '_juru_fauna_description', sanitize_textarea_field($_POST['description']));
    update_post_meta($post_id, '_juru_planet', $planet);
    update_post_meta($post_id, '_juru_fauna_diet', sanitize_text_field($_POST['diet']));
    update_post_meta($post_id, '_juru_fauna_produces', sanitize_text_field($_POST['produces']));
    update_post_meta($post_id, '_juru_fauna_image_2', intval($_POST['image_2']));
    update_post_meta($post_id, '_juru_fauna_image_3', intval($_POST['image_3']));
    wp_send_json_success(['permalink' => get_permalink($post_id)]);
}
add_action('wp_ajax_juru_add_fauna', 'juru_add_fauna');

// Export to CSV
function juru_export_to_csv() {
    if (!isset($_POST['juru_export_submit']) || !isset($_POST['juru_export_nonce']) || !wp_verify_nonce($_POST['juru_export_nonce'], 'juru_export_action')) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }

    $export_type = sanitize_text_field($_POST['juru_export_type']);
    if (!in_array($export_type, ['juru_system', 'juru_planet', 'juru_poi', 'juru_fauna'])) {
        wp_die('Invalid export type.');
    }

    $posts = get_posts([
        'post_type' => $export_type,
        'numberposts' => -1,
        'post_status' => 'any',
    ]);

    if (empty($posts)) {
        wp_die('No data to export for ' . esc_html($export_type) . '.');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $export_type . '-' . date('Y-m-d-H-i-s') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if ($export_type === 'juru_system') {
        fputcsv($output, ['ID', 'Title', 'Economy Type', 'Economy Strength', 'Orbital Bodies', 'Status']);
        foreach ($posts as $post) {
            fputcsv($output, [
                $post->ID,
                $post->post_title,
                get_post_meta($post->ID, '_juru_economy_type', true),
                get_post_meta($post->ID, '_juru_economy_strength', true),
                get_post_meta($post->ID, '_juru_orbital_bodies', true),
                $post->post_status,
            ]);
        }
    } elseif ($export_type === 'juru_planet') {
        fputcsv($output, ['ID', 'Title', 'System ID', 'System Title', 'Portal Address', 'Status']);
        foreach ($posts as $post) {
            $system_id = get_post_meta($post->ID, '_juru_system', true);
            $system_title = $system_id ? get_the_title($system_id) : '';
            fputcsv($output, [
                $post->ID,
                $post->post_title,
                $system_id,
                $system_title,
                get_post_meta($post->ID, '_juru_portal_address', true),
                $post->post_status,
            ]);
        }
    } elseif ($export_type === 'juru_poi') {
        fputcsv($output, ['ID', 'Title', 'Planet ID', 'Planet Title', 'POI Type', 'X Coordinate', 'Y Coordinate', 'Status']);
        foreach ($posts as $post) {
            $planet_id = get_post_meta($post->ID, '_juru_planet', true);
            $planet_title = $planet_id ? get_the_title($planet_id) : '';
            fputcsv($output, [
                $post->ID,
                $post->post_title,
                $planet_id,
                $planet_title,
                get_post_meta($post->ID, '_juru_poi_type', true),
                get_post_meta($post->ID, '_juru_coord_x', true),
                get_post_meta($post->ID, '_juru_coord_y', true),
                $post->post_status,
            ]);
        }
    } elseif ($export_type === 'juru_fauna') {
        fputcsv($output, ['ID', 'Title', 'Planet ID', 'Planet Title', 'Description', 'Diet', 'Produces', 'Image 2 ID', 'Image 3 ID', 'Status']);
        foreach ($posts as $post) {
            $planet_id = get_post_meta($post->ID, '_juru_planet', true);
            $planet_title = $planet_id ? get_the_title($planet_id) : '';
            fputcsv($output, [
                $post->ID,
                $post->post_title,
                $planet_id,
                $planet_title,
                get_post_meta($post->ID, '_juru_fauna_description', true),
                get_post_meta($post->ID, '_juru_fauna_diet', true),
                get_post_meta($post->ID, '_juru_fauna_produces', true),
                get_post_meta($post->ID, '_juru_fauna_image_2', true),
                get_post_meta($post->ID, '_juru_fauna_image_3', true),
                $post->post_status,
            ]);
        }
    }

    fclose($output);
    exit;
}
add_action('admin_init', 'juru_export_to_csv');

// Import from CSV
function juru_import_from_csv() {
    if (!isset($_POST['juru_import_submit']) || !isset($_POST['juru_import_nonce']) || !wp_verify_nonce($_POST['juru_import_nonce'], 'juru_import_action')) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_safe_redirect(add_query_arg('juru_import_error', 'Insufficient permissions.', admin_url('admin.php?page=juru_settings')));
        exit;
    }

    if (!isset($_FILES['juru_import_file']) || $_FILES['juru_import_file']['error'] !== UPLOAD_ERR_OK) {
        wp_safe_redirect(add_query_arg('juru_import_error', 'No file uploaded or upload error.', admin_url('admin.php?page=juru_settings')));
        exit;
    }

    $import_type = sanitize_text_field($_POST['juru_import_type']);
    if (!in_array($import_type, ['juru_system', 'juru_planet', 'juru_poi', 'juru_fauna'])) {
        wp_safe_redirect(add_query_arg('juru_import_error', 'Invalid import type.', admin_url('admin.php?page=juru_settings')));
        exit;
    }

    $file = $_FILES['juru_import_file']['tmp_name'];
    if (($handle = fopen($file, 'r')) === false) {
        wp_safe_redirect(add_query_arg('juru_import_error', 'Could not open CSV file.', admin_url('admin.php?page=juru_settings')));
        exit;
    }

    $header = fgetcsv($handle);
    $count = 0;
    $current_user_id = get_current_user_id();

    while (($data = fgetcsv($handle)) !== false) {
        if ($import_type === 'juru_system') {
            if (count($data) < 6) {
                error_log('Juru Empire Database: Invalid CSV row for system import: ' . json_encode($data));
                continue;
            }
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data[1]),
                'post_type' => 'juru_system',
                'post_status' => sanitize_text_field($data[5]),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: Failed to import system: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_economy_type', sanitize_text_field($data[2]));
            update_post_meta($post_id, '_juru_economy_strength', sanitize_text_field($data[3]));
            update_post_meta($post_id, '_juru_orbital_bodies', intval($data[4]));
            $count++;
        } elseif ($import_type === 'juru_planet') {
            if (count($data) < 6) {
                error_log('Juru Empire Database: Invalid CSV row for planet import: ' . json_encode($data));
                continue;
            }
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data[1]),
                'post_type' => 'juru_planet',
                'post_status' => sanitize_text_field($data[5]),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: Failed to import planet: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_system', intval($data[2]));
            update_post_meta($post_id, '_juru_portal_address', sanitize_text_field($data[4]));
            $count++;
        } elseif ($import_type === 'juru_poi') {
            if (count($data) < 8) {
                error_log('Juru Empire Database: Invalid CSV row for POI import: ' . json_encode($data));
                continue;
            }
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data[1]),
                'post_type' => 'juru_poi',
                'post_status' => sanitize_text_field($data[7]),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: Failed to import POI: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_planet', intval($data[2]));
            update_post_meta($post_id, '_juru_poi_type', sanitize_text_field($data[4]));
            update_post_meta($post_id, '_juru_coord_x', floatval($data[5]));
            update_post_meta($post_id, '_juru_coord_y', floatval($data[6]));
            $count++;
        } elseif ($import_type === 'juru_fauna') {
            if (count($data) < 10) {
                error_log('Juru Empire Database: Invalid CSV row for fauna import: ' . json_encode($data));
                continue;
            }
            $planet_id = intval($data[2]);
            $existing_fauna = get_posts([
                'post_type' => 'juru_fauna',
                'numberposts' => -1,
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => '_juru_planet',
                        'value' => $planet_id,
                        'compare' => '=',
                    ],
                ],
            ]);

            if (count($existing_fauna) >= 30) {
                error_log('Juru Empire Database: Skipped fauna import for planet ID ' . $planet_id . ': Maximum of 30 fauna reached');
                continue;
            }

            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($data[1]),
                'post_type' => 'juru_fauna',
                'post_status' => sanitize_text_field($data[9]),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: Failed to import fauna: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_planet', $planet_id);
            update_post_meta($post_id, '_juru_fauna_description', sanitize_textarea_field($data[4]));
            update_post_meta($post_id, '_juru_fauna_diet', sanitize_text_field($data[5]));
            update_post_meta($post_id, '_juru_fauna_produces', sanitize_text_field($data[6]));
            update_post_meta($post_id, '_juru_fauna_image_2', intval($data[7]));
            update_post_meta($post_id, '_juru_fauna_image_3', intval($data[8]));
            $count++;
        }
    }

    fclose($handle);
    wp_safe_redirect(add_query_arg('juru_import_success', $count, admin_url('admin.php?page=juru_settings')));
    exit;
}
add_action('admin_init', 'juru_import_from_csv');

// Register REST API Endpoints
function juru_register_api_endpoints() {
    register_rest_route('juru/v1', '/sync', [
        'methods' => 'POST',
        'callback' => 'juru_api_sync_data',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);
}
add_action('rest_api_init', 'juru_register_api_endpoints');

// API Sync Handler
function juru_api_sync_data($request) {
    $api_key = $request->get_header('X-Juru-API-Key');
    $local_api_key = get_option('juru_api_key', '');
    if (empty($api_key) || $api_key !== $local_api_key) {
        return new WP_Error('invalid_api_key', 'Invalid API key', ['status' => 401]);
    }

    $data = $request->get_json_params();
    $post_type = sanitize_text_field($data['post_type'] ?? '');
    $overwrite = rest_sanitize_boolean($data['overwrite'] ?? false);

    if (!in_array($post_type, ['juru_system', 'juru_planet', 'juru_poi', 'juru_fauna'])) {
        return new WP_Error('invalid_post_type', 'Invalid post type', ['status' => 400]);
    }

    $items = $data['items'] ?? [];
    if (empty($items)) {
        return new WP_Error('no_data', 'No data provided', ['status' => 400]);
    }

    $count = 0;
    $current_user_id = get_current_user_id();

    if ($overwrite) {
        $existing_posts = get_posts([
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any',
        ]);
        foreach ($existing_posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }

    foreach ($items as $item) {
        if ($post_type === 'juru_system') {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($item['title']),
                'post_type' => 'juru_system',
                'post_status' => sanitize_text_field($item['status']),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: API failed to import system: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_economy_type', sanitize_text_field($item['economy_type']));
            update_post_meta($post_id, '_juru_economy_strength', sanitize_text_field($item['economy_strength']));
            update_post_meta($post_id, '_juru_orbital_bodies', intval($item['orbital_bodies']));
            $count++;
        } elseif ($post_type === 'juru_planet') {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($item['title']),
                'post_type' => 'juru_planet',
                'post_status' => sanitize_text_field($item['status']),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: API failed to import planet: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_system', intval($item['system_id']));
            update_post_meta($post_id, '_juru_portal_address', sanitize_text_field($item['portal_address']));
            $count++;
        } elseif ($post_type === 'juru_poi') {
            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($item['title']),
                'post_type' => 'juru_poi',
                'post_status' => sanitize_text_field($item['status']),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: API failed to import POI: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_planet', intval($item['planet_id']));
            update_post_meta($post_id, '_juru_poi_type', sanitize_text_field($item['poi_type']));
            update_post_meta($post_id, '_juru_coord_x', floatval($item['coord_x']));
            update_post_meta($post_id, '_juru_coord_y', floatval($item['coord_y']));
            $count++;
        } elseif ($post_type === 'juru_fauna') {
            $planet_id = intval($item['planet_id']);
            $existing_fauna = get_posts([
                'post_type' => 'juru_fauna',
                'numberposts' => -1,
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => '_juru_planet',
                        'value' => $planet_id,
                        'compare' => '=',
                    ],
                ],
            ]);

            if (count($existing_fauna) >= 30) {
                error_log('Juru Empire Database: API skipped fauna import for planet ID ' . $planet_id . ': Maximum of 30 fauna reached');
                continue;
            }

            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($item['title']),
                'post_type' => 'juru_fauna',
                'post_status' => sanitize_text_field($item['status']),
                'post_author' => $current_user_id,
            ], true);

            if (is_wp_error($post_id)) {
                error_log('Juru Empire Database: API failed to import fauna: ' . $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_juru_planet', $planet_id);
            update_post_meta($post_id, '_juru_fauna_description', sanitize_textarea_field($item['description']));
            update_post_meta($post_id, '_juru_fauna_diet', sanitize_text_field($item['diet']));
            update_post_meta($post_id, '_juru_fauna_produces', sanitize_text_field($item['produces']));
            update_post_meta($post_id, '_juru_fauna_image_2', intval($item['image_2']));
            update_post_meta($post_id, '_juru_fauna_image_3', intval($item['image_3']));
            $count++;
        }
    }

    return rest_ensure_response(['success' => true, 'imported' => $count]);
}

// Handle API Sync from Settings Page
function juru_handle_api_sync() {
    if (!isset($_POST['juru_api_sync']) || !isset($_POST['juru_api_nonce']) || !wp_verify_nonce($_POST['juru_api_nonce'], 'juru_api_action')) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_safe_redirect(add_query_arg('juru_api_error', 'Insufficient permissions.', admin_url('admin.php?page=juru_settings')));
        exit;
    }

    $api_url = sanitize_text_field($_POST['juru_api_url']);
    $api_key = sanitize_text_field($_POST['juru_api_key']);
    $overwrite = isset($_POST['juru_api_overwrite']) && $_POST['juru_api_overwrite'] == '1';

    update_option('juru_api_url', $api_url);
    update_option('juru_api_key', $api_key);
    update_option('juru_api_overwrite', $overwrite);

    $post_types = ['juru_system', 'juru_planet', 'juru_poi', 'juru_fauna'];
    foreach ($post_types as $post_type) {
        $posts = get_posts([
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        $data = [];
        foreach ($posts as $post) {
            $item = [
                'title' => $post->post_title,
                'status' => $post->post_status,
            ];
            if ($post_type === 'juru_system') {
                $item['economy_type'] = get_post_meta($post->ID, '_juru_economy_type', true);
                $item['economy_strength'] = get_post_meta($post->ID, '_juru_economy_strength', true);
                $item['orbital_bodies'] = get_post_meta($post->ID, '_juru_orbital_bodies', true);
            } elseif ($post_type === 'juru_planet') {
                $item['system_id'] = get_post_meta($post->ID, '_juru_system', true);
                $item['portal_address'] = get_post_meta($post->ID, '_juru_portal_address', true);
            } elseif ($post_type === 'juru_poi') {
                $item['planet_id'] = get_post_meta($post->ID, '_juru_planet', true);
                $item['poi_type'] = get_post_meta($post->ID, '_juru_poi_type', true);
                $item['coord_x'] = get_post_meta($post->ID, '_juru_coord_x', true);
                $item['coord_y'] = get_post_meta($post->ID, '_juru_coord_y', true);
            } elseif ($post_type === 'juru_fauna') {
                $item['planet_id'] = get_post_meta($post->ID, '_juru_planet', true);
                $item['description'] = get_post_meta($post->ID, '_juru_fauna_description', true);
                $item['diet'] = get_post_meta($post->ID, '_juru_fauna_diet', true);
                $item['produces'] = get_post_meta($post->ID, '_juru_fauna_produces', true);
                $item['image_2'] = get_post_meta($post->ID, '_juru_fauna_image_2', true);
                $item['image_3'] = get_post_meta($post->ID, '_juru_fauna_image_3', true);
            }
            $data[] = $item;
        }

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Juru-API-Key' => $api_key,
            ],
            'body' => json_encode([
                'post_type' => $post_type,
                'overwrite' => $overwrite,
                'items' => $data,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            wp_safe_redirect(add_query_arg('juru_api_error', $response->get_error_message(), admin_url('admin.php?page=juru_settings')));
            exit;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if (isset($result['success']) && $result['success']) {
            continue;
        } else {
            wp_safe_redirect(add_query_arg('juru_api_error', $result['data']['message'] ?? 'Unknown API error', admin_url('admin.php?page=juru_settings')));
            exit;
        }
    }

    wp_safe_redirect(add_query_arg('juru_api_success', '1', admin_url('admin.php?page=juru_settings')));
    exit;
}
add_action('admin_init', 'juru_handle_api_sync');

add_filter('pre_set_site_transient_update_plugins', 'juru_check_for_updates');

function juru_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = 'juru-empire-database/juru-empire-database.php';
    $remote_url = 'https://yourserver.com/updates/juru-empire-database.json';

    $response = wp_remote_get($remote_url, array('timeout' => 10));
    if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
        $remote = json_decode(wp_remote_retrieve_body($response));
        if (version_compare($transient->checked[$plugin_slug], $remote->version, '<')) {
            $obj = new stdClass();
            $obj->slug = $remote->slug;
            $obj->plugin = $plugin_slug;
            $obj->new_version = $remote->version;
            $obj->url = 'https://yourserver.com';
            $obj->package = $remote->download_url;
            $transient->response[$plugin_slug] = $obj;
        }
    }

    return $transient;
}
?>