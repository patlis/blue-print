<?php
if (!defined('ABSPATH')) exit;

/**
 * Gutenberg template + lock + allow only Gallery block
 * for selected post types.
 */
add_filter('register_post_type_args', function ($args, $post_type) {

	$config = [
		//'events'   => ['columns' => 3, 'linkTo' => 'file'],
		//'services' => ['columns' => 3, 'linkTo' => 'file'],
		'stores'   => ['columns' => 3, 'linkTo' => 'file'],
		'rooms'    => ['columns' => 3, 'linkTo' => 'file'],
	];

	if (!isset($config[$post_type])) {
		return $args;
	}

	$columns = (int) ($config[$post_type]['columns'] ?? 3);
	$linkTo  = (string) ($config[$post_type]['linkTo'] ?? 'file');

	$args['template'] = [
		[
			'core/gallery',
			[
				'linkTo'  => $linkTo,
				'columns' => $columns,
			],
		],
	];

	$args['template_lock'] = 'all';

	return $args;
}, 20, 2);

add_filter('allowed_block_types_all', function ($allowed, $editor_context) {

	$allowed_post_types = [
	    //'events', 
	    //'services', 
	    'stores', 
	    'rooms'
	    ];

	if (!empty($editor_context->post) && in_array($editor_context->post->post_type, $allowed_post_types, true)) {
		return ['core/gallery'];
	}

	return $allowed;
}, 10, 2);