<?php

/**
 * Plugin Name: Awanui Collection Centre Block
 * Description: Displays Awanui Labs collection centre information.
 * Version: 1.0.0
 * Author: Camilo Pinz&oacute;n
 */

defined('ABSPATH') || exit;

add_action('init', 'awanui_debug_environment', 1);

function awanui_debug_environment()
{
    error_log('[Awanui] === DEBUGGING START ===');
    error_log('[Awanui] Plugin file: ' . __FILE__);
    error_log('[Awanui] Plugin dir: ' . __DIR__);
    error_log('[Awanui] Build path: ' . plugin_dir_path(__FILE__) . 'build/');
    error_log('[Awanui] WordPress version: ' . get_bloginfo('version'));
    error_log('[Awanui] Current theme: ' . get_template());
    error_log('[Awanui] Is admin: ' . (is_admin() ? 'YES' : 'NO'));

    $build_dir = plugin_dir_path(__FILE__) . 'build/';
    error_log('[Awanui] Build directory exists: ' . (is_dir($build_dir) ? 'YES' : 'NO'));

    if (is_dir($build_dir)) {
        $files = scandir($build_dir);
        error_log('[Awanui] Files in build directory: ' . implode(', ', $files));
    }
}

add_action('rest_api_init', 'awanui_register_rest_routes');

function awanui_register_rest_routes()
{
    error_log('[Awanui] Registering REST routes');

    register_rest_route('awanui/v1', '/centres', array(
        'methods' => 'GET',
        'callback' => 'awanui_get_centres',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('awanui/v1', '/centre/(?P<slug>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'awanui_get_centre',
        'permission_callback' => '__return_true',
        'args' => array(
            'slug' => array(
                'validate_callback' => function ($param) {
                    return is_string($param);
                }
            ),
        ),
    ));

    error_log('[Awanui] REST routes registered successfully');
}

function awanui_get_centres($request)
{
    $centres = array(
        array('name' => 'Auckland Central', 'slug' => 'auckland-central'),
        array('name' => 'Wellington Hub', 'slug' => 'wellington-hub'),
        array('name' => 'Christchurch Centre', 'slug' => 'christchurch-centre')
    );
    return new WP_REST_Response($centres, 200);
}

function awanui_get_centre($request)
{
    $slug = $request->get_param('slug');

    $centres_data = array(
        'auckland-central' => array(
            'name' => 'Auckland Central Collection Centre',
            'address' => '123 Queen Street',
            'city' => 'Auckland',
            'phone' => '09-123-4567',
            'hours' => array(
                array('day' => 'Monday', 'hours' => '8:00 AM - 5:00 PM'),
                array('day' => 'Tuesday', 'hours' => '8:00 AM - 5:00 PM'),
                array('day' => 'Wednesday', 'hours' => '8:00 AM - 5:00 PM'),
                array('day' => 'Thursday', 'hours' => '8:00 AM - 5:00 PM'),
                array('day' => 'Friday', 'hours' => '8:00 AM - 5:00 PM'),
                array('day' => 'Saturday', 'hours' => 'Closed'),
                array('day' => 'Sunday', 'hours' => 'Closed')
            ),
            'map_link' => 'https://maps.google.com'
        ),
    );

    if (isset($centres_data[$slug])) {
        return new WP_REST_Response($centres_data[$slug], 200);
    } else {
        return new WP_Error('centre_not_found', 'Centre not found', array('status' => 404));
    }
}

add_action('init', 'awanui_register_block', 10);

function awanui_register_block()
{
    error_log('[Awanui] === BLOCK REGISTRATION START ===');

    try {
        $plugin_dir = plugin_dir_path(__FILE__);
        $build_path = $plugin_dir . 'build/';

        error_log('[Awanui] Plugin directory: ' . $plugin_dir);
        error_log('[Awanui] Build path: ' . $build_path);

        // Check if build directory exists
        if (!is_dir($build_path)) {
            error_log('[Awanui] ERROR: Build directory does not exist: ' . $build_path);
            return;
        }

        $required_files = array('index.js', 'block.json');
        $missing_files = array();

        foreach ($required_files as $file) {
            if (!file_exists($build_path . $file)) {
                $missing_files[] = $file;
                error_log('[Awanui] Missing file: ' . $build_path . $file);
            } else {
                error_log('[Awanui] Found file: ' . $build_path . $file);
            }
        }

        if (empty($missing_files)) {
            error_log('[Awanui] Attempting block.json registration');

            $result = register_block_type($build_path, array(
                'render_callback' => 'awanui_render_frontend'
            ));

            if ($result !== false) {
                error_log('[Awanui] SUCCESS: Block registered via block.json');
                error_log('[Awanui] Block name: ' . $result->name);
                return;
            } else {
                error_log('[Awanui] FAILED: block.json registration failed');
            }
        }

        error_log('[Awanui] Attempting manual registration');

        if (!file_exists($build_path . 'index.js')) {
            error_log('[Awanui] ERROR: index.js file missing, cannot register block');
            return;
        }

        $script_handle = 'awanui-block-editor';
        $script_url = plugins_url('build/index.js', __FILE__);
        $script_path = $build_path . 'index.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : '1.0.0';

        error_log('[Awanui] Script URL: ' . $script_url);
        error_log('[Awanui] Script version: ' . $script_version);

        $script_registered = wp_register_script(
            $script_handle,
            $script_url,
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'),
            $script_version,
            true
        );

        if (!$script_registered) {
            error_log('[Awanui] ERROR: Failed to register script');
            return;
        }

        error_log('[Awanui] Script registered successfully');

        $style_handle = 'awanui-block-style';
        if (file_exists($build_path . 'index.css')) {
            wp_register_style(
                $style_handle,
                plugins_url('build/index.css', __FILE__),
                array(),
                filemtime($build_path . 'index.css')
            );
            error_log('[Awanui] Style registered: index.css');
        }

        $block_registered = register_block_type('awanui/collection-centre', array(
            'editor_script' => $script_handle,
            'editor_style' => file_exists($build_path . 'index.css') ? $style_handle : null,
            'render_callback' => 'awanui_render_frontend',
            'attributes' => array(
                'centreId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'centreData' => array(
                    'type' => 'object',
                    'default' => null
                )
            )
        ));

        if ($block_registered !== false) {
            error_log('[Awanui] SUCCESS: Block registered manually');
            error_log('[Awanui] Block name: ' . $block_registered->name);
        } else {
            error_log('[Awanui] ERROR: Manual block registration failed');
        }
    } catch (Exception $e) {
        error_log('[Awanui] EXCEPTION during block registration: ' . $e->getMessage());
        error_log('[Awanui] Stack trace: ' . $e->getTraceAsString());
    }

    error_log('[Awanui] === BLOCK REGISTRATION END ===');
}

function awanui_render_frontend($attributes, $content)
{
    error_log('[Awanui] Frontend render called with attributes: ' . print_r($attributes, true));

    $centre_data = isset($attributes['centreData']) ? $attributes['centreData'] : null;

    if (!$centre_data) {
        return '<div class="awanui-collection-centre-placeholder">No collection centre selected.</div>';
    }

    ob_start();
?>
    <div class="awanui-collection-centre">
        <h3><?php echo esc_html($centre_data['name']); ?></h3>
        <div class="address">
            <p><?php echo esc_html($centre_data['address']); ?></p>
            <p><?php echo esc_html($centre_data['city']); ?></p>
        </div>
        <?php if (!empty($centre_data['phone'])): ?>
            <p class="phone">
                <a href="tel:<?php echo esc_attr($centre_data['phone']); ?>"><?php echo esc_html($centre_data['phone']); ?></a>
            </p>
        <?php endif; ?>
    </div>
<?php

    return ob_get_clean();
}

// STEP 5: Comprehensive debug notices
add_action('admin_notices', 'awanui_debug_notices');

function awanui_debug_notices()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $registry = WP_Block_Type_Registry::get_instance();
    $is_registered = $registry->is_registered('awanui/collection-centre');

    error_log('[Awanui] Block registration check: ' . ($is_registered ? 'REGISTERED' : 'NOT REGISTERED'));

    if ($is_registered) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Awanui block registered successfully!</strong></p>';
        echo '</div>';

        $registered_blocks = $registry->get_all_registered();
        foreach ($registered_blocks as $name => $block) {
            if (strpos($name, 'awanui') !== false) {
                error_log('[Awanui] Found registered block: ' . $name);
            }
        }
    } else {
        echo '<div class="notice notice-error">';
        echo '<p><strong>Awanui block failed to register.</strong></p>';
        echo '<p>Check the WordPress debug log for detailed error messages.</p>';
        echo '<p><strong>Quick checks:</strong></p>';
        echo '<ul>';
        echo '<li>Ensure the <code>build/</code> directory exists in your plugin folder</li>';
        echo '<li>Ensure <code>build/index.js</code> and <code>build/block.json</code> exist</li>';
        echo '<li>Check file permissions on the build directory</li>';
        echo '<li>Look for JavaScript errors in the browser console</li>';
        echo '</ul>';

        $build_path = plugin_dir_path(__FILE__) . 'build/';
        echo '<p><strong>File Status:</strong></p>';
        echo '<ul>';
        echo '<li>Build directory: ' . (is_dir($build_path) ? 'Exists' : 'Missing') . '</li>';
        echo '<li>index.js: ' . (file_exists($build_path . 'index.js') ? 'Exists' : 'Missing') . '</li>';
        echo '<li>block.json: ' . (file_exists($build_path . 'block.json') ? 'Exists' : 'Missing') . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        echo '<div class="notice notice-info">';
        echo '<p><strong>Debug logging is enabled.</strong> Check <code>/wp-content/debug.log</code> for detailed messages.</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>Debug logging is disabled.</strong> To enable it, add these lines to wp-config.php:</p>';
        echo '<pre>define(\'WP_DEBUG\', true);\ndefine(\'WP_DEBUG_LOG\', true);</pre>';
        echo '</div>';
    }
}

add_action('admin_menu', 'awanui_add_diagnostic_menu');

function awanui_add_diagnostic_menu()
{
    add_options_page(
        'Awanui Diagnostics',
        'Awanui Diagnostics',
        'manage_options',
        'awanui-diagnostics',
        'awanui_diagnostic_page'
    );
}

function awanui_diagnostic_page()
{
?>
    <div class="wrap">
        <h1>Awanui Block Diagnostics</h1>

        <?php
        $build_path = plugin_dir_path(__FILE__) . 'build/';
        $registry = WP_Block_Type_Registry::get_instance();
        $is_registered = $registry->is_registered('awanui/collection-centre');
        ?>

        <h2>Registration Status</h2>
        <p><strong>Block Registered:</strong> <?php echo $is_registered ? 'YES' : 'NO'; ?></p>

        <h2>File System Check</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>File/Directory</th>
                    <th>Status</th>
                    <th>Path</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Build Directory</td>
                    <td><?php echo is_dir($build_path) ? 'Exists' : 'Missing'; ?></td>
                    <td><?php echo esc_html($build_path); ?></td>
                </tr>
                <tr>
                    <td>index.js</td>
                    <td><?php echo file_exists($build_path . 'index.js') ? 'Exists' : 'Missing'; ?></td>
                    <td><?php echo esc_html($build_path . 'index.js'); ?></td>
                </tr>
                <tr>
                    <td>block.json</td>
                    <td><?php echo file_exists($build_path . 'block.json') ? 'Exists' : 'Missing'; ?></td>
                    <td><?php echo esc_html($build_path . 'block.json'); ?></td>
                </tr>
            </tbody>
        </table>

        <h2>WordPress Environment</h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>WordPress Version</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>Theme</strong></td>
                    <td><?php echo get_template(); ?></td>
                </tr>
                <tr>
                    <td><strong>Debug Mode</strong></td>
                    <td><?php echo (defined('WP_DEBUG') && WP_DEBUG) ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
                <tr>
                    <td><strong>Debug Log</strong></td>
                    <td><?php echo (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
            </tbody>
        </table>

        <h2>API Endpoints Test</h2>
        <p><a href="<?php echo home_url('/wp-json/awanui/v1/centres'); ?>" target="_blank">Test Centres API</a></p>
        <p><a href="<?php echo home_url('/wp-json/awanui/v1/centre/auckland-central'); ?>" target="_blank">Test Centre Details API</a></p>
    </div>
<?php
}
