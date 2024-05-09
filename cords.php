<?php

/**
 * Plugin Name: CORDS
 * Description: Plugin for implementing CORDS in your WordPress site. Includes indexing and widget support.
 * Version: 0.0.1
 * Author: Billy
 * Author URI: https://billyhawkes.com
 */

// REGISTER VALUES //
add_action('init', 'cords_register_values');
function cords_register_values()
{
	// Post meta
	register_meta('post', 'cords_enabled', array(
		'show_in_rest' => true,
		'type' => 'boolean',
		'default' => true,
		'single' => true,
		'auth_callback' => function () {
			return current_user_can('edit_posts');
		}
	));
	register_meta('post', 'cords_widget', array(
		'show_in_rest' => true,
		'type' => 'boolean',
		'default' => true,
		'single' => true,
		'auth_callback' => function () {
			return current_user_can('edit_posts');
		}
	));
	// Options
	add_option("cords_api_key", "");
}

// ADMIN MENU //
add_action('admin_menu', 'cords_init_menu');
function cords_init_menu()
{
	add_menu_page('CORDS', 'CORDS', 'manage_options', 'cords', 'cords_admin_page', 'dashicons-admin-post', '2.1');
}
function cords_admin_page()
{
	echo '<div id="cords"></div>';
}
add_action('admin_enqueue_scripts', 'cords_admin_enqueue_scripts');
function cords_admin_enqueue_scripts($hook)
{
	if ($hook === 'toplevel_page_cords') {
		// Include the index.asset.php file
		$script_asset = require plugin_dir_path(__FILE__) . "build/index.asset.php";

		// Enqueue the script
		wp_enqueue_script(
			'cords-script',
			plugin_dir_url(__FILE__) . 'build/index.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_localize_script('cords-script', 'wpApiSettings', array(
			'root' => esc_url_raw(rest_url()),
			'nonce' => wp_create_nonce('wp_rest')
		));
	}
}

// WIDGET //
add_action('wp_footer', 'enqueue_cords_widget_script');
function enqueue_cords_widget_script()
{
	$post_id = get_the_ID();
	$show_widget = get_post_meta($post_id, 'cords_widget', true);

	if ($show_widget) {
		$post_content = strip_tags(get_the_content());
		$encoded_post_content = urlencode($post_content);
		$api_key = get_option('cords_api_key');
		$origin = wp_get_environment_type() === "local" ? "http://localhost:3000" : "https://cords-widget.vercel.app";
		$url = $origin . "?q=" . $encoded_post_content . "&api_key=" . $api_key;
?>
		<script>
			// Resize widget to fit content
			window.addEventListener("message", function(event) {
				if (event.data.type !== "cords-resize") return;
				const widget = document.getElementById("cords-widget");
				widget.style.height = `${event.data.height}px`;
				widget.style.width = `${event.data.width}px`;
			});
			// Create widget
			document.addEventListener('DOMContentLoaded', function() {
				let iframe = document.createElement('iframe');
				iframe.src = '<?php echo $url; ?>';
				iframe.style.cssText = 'pointer-events: all; background: none; border: 0px; float: none; position: absolute; inset: 0px; width: 100%; height: 100%; margin: 0px; padding: 0px; min-height: 0px;';

				let widgetContainer = document.createElement('div');
				widgetContainer.id = 'cords-widget';
				widgetContainer.style.cssText = 'border: 0px; background-color: transparent; pointer-events: none; z-index: 2147483639; position: fixed; bottom: 0px; width: 60px; height: 60px; overflow: hidden; opacity: 1; max-width: 100%; right: 0px; max-height: 100%;';

				widgetContainer.appendChild(iframe);
				document.body.appendChild(widgetContainer);
			});
		</script>
<?php
	}
}

// REST API //
add_action('rest_api_init', 'rest_api_register_route');
function rest_api_register_route()
{
	register_rest_route('cords/v1', '/options', array(
		'methods' => 'GET',
		'callback' => 'get_options_route',
		'permission_callback' => 'permission_callback'
	));
	register_rest_route('cords/v1', '/options', array(
		'methods' => 'POST',
		'callback' => 'update_options_route',
		'permission_callback' => 'permission_callback'
	));
}
function permission_callback()
{
	return current_user_can('manage_options');
}

function get_options_route()
{
	$api_key = get_option('cords_api_key');
	return array(
		'api_key' => $api_key
	);
}

function update_options_route($request)
{
	$api_key = sanitize_text_field($request->get_param('api_key'));
	update_option('cords_api_key', $api_key);
	wp_cache_flush();
	return array(
		'api_key' => $api_key
	);
}
