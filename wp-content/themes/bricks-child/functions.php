<?php 
/**
 * Register/enqueue custom scripts and styles
 */
add_action( 'wp_enqueue_scripts', function() {
	// Enqueue your files on the canvas & frontend, not the builder panel. Otherwise custom CSS might affect builder)
	if ( ! bricks_is_builder_main() ) {
		wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime( get_stylesheet_directory() . '/style.css' ) );
	}
} );

/**
 * Register custom elements
 */
add_action( 'init', function() {
  $element_files = [
    __DIR__ . '/elements/title.php',
  ];

  foreach ( $element_files as $file ) {
    \Bricks\Elements::register_element( $file );
  }
}, 11 );

/**
 * Add text strings to builder
 */
add_filter( 'bricks/builder/i18n', function( $i18n ) {
  // For element category 'custom'
  $i18n['custom'] = esc_html__( 'Custom', 'bricks' );

  return $i18n;
} );

// disable adding comments using POST requests
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], 'wp-comments-post.php') !== false) {
        wp_die('Comments are disabled.', '', ['response' => 403]);
    }
});

// Bricks local Font Awesome 6
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'bricks-fa6-local',
        get_template_directory_uri() . '/assets/css/libs/font-awesome-6.min.css',
        [],
        null
    );

}, 100);

// date time picker 24 H 
add_filter( 'bricks/element/form/datepicker_options', function( $options, $element ) {
    $options['time_24hr'] = true;
    $options['dateFormat'] = 'd.m.Y H:i'; 
	$options['minuteIncrement'] = 15;
    return $options;
}, 10, 2 );

/**
 * Load Roboto Fonts
*/
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style(
    'patlis-fonts-roboto',
    get_stylesheet_directory_uri() . '/assets/fonts/fonts.css',
    [],
    '1.0.1'
  );
}, 5);

/**
 * Προσθετει τα functions για polylang
*/
require_once get_stylesheet_directory() . '/inc/multilingual.php';

/**
 * Προσθετει location στο menu
*/
add_action('after_setup_theme', function () {
    register_nav_menus([
        'header_menu' => __('Header Menu', 'bricks-child')
    ]);
});


/**
 * Κανει redirect αναλογα το mode
*/
add_action('template_redirect', function () {

    if (is_admin()) return;
    if (is_user_logged_in()) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    $settings = get_option('bricks_global_settings');
    $mode = $settings['maintenanceMode'] ?? '';

    if (!$mode) return;

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    if ($mode === 'comingSoon') {
        $target_path = '/en/coming-soon/';
    } elseif ($mode === 'maintenance') {
        $target_path = '/en/under-construction/';
        status_header(503);
    } else {
        return;
    }

    // αποφυγή redirect loop
    if ($request_uri === $target_path || rtrim($request_uri, '/') === rtrim($target_path, '/')) {
        return;
    }

    wp_redirect(home_url($target_path), 302);
    exit;

}, 1);