<?php

if (!defined('ABSPATH')) exit;

function patlis_sliders_option_name(): string {
	return 'patlis_sliders';
}

function patlis_sliders_get_languages(): array {
	$slugs = [];

	if (function_exists('patlis_get_effective_language_slugs_for_current_user')) {
		$slugs = patlis_get_effective_language_slugs_for_current_user();
	} elseif (function_exists('pll_languages_list')) {
		$slugs = pll_languages_list(['fields' => 'slug']);
	}

	$languages = [];

	if (is_array($slugs)) {
		foreach ($slugs as $slug) {
			$slug = sanitize_key((string) $slug);

			if ($slug !== '') {
				$languages[$slug] = strtoupper($slug);
			}
		}
	}

	return $languages ?: ['default' => __('Default', 'patlis-core')];
}

function patlis_sliders_get_default_language(): string {
	if (function_exists('patlis_get_default_language')) {
		$language = sanitize_key(patlis_get_default_language());

		if ($language !== '') {
			return $language;
		}
	}

	if (function_exists('pll_default_language')) {
		$language = pll_default_language('slug');

		if (is_string($language) && $language !== '') {
			return sanitize_key($language);
		}
	}

	return (string) array_key_first(patlis_sliders_get_languages());
}

function patlis_sliders_normalize_multilingual_value($value): array {
	if (!is_array($value)) {
		return $value === '' || $value === null
			? []
			: [patlis_sliders_get_default_language() => (string) $value];
	}

	$normalized = [];

	foreach ($value as $language => $text) {
		$language = sanitize_key((string) $language);

		if ($language !== '' && is_scalar($text)) {
			$normalized[$language] = (string) $text;
		}
	}

	return $normalized;
}

function patlis_sliders_sanitize_multilingual_value($value, $existing_value, string $type): array {
	$output = patlis_sliders_normalize_multilingual_value($existing_value);
	$value = is_array($value)
		? $value
		: [patlis_sliders_get_default_language() => $value];
	$editable_languages = array_keys(patlis_sliders_get_languages());

	foreach ($value as $language => $text) {
		$language = sanitize_key((string) $language);

		if ($language === '' || !in_array($language, $editable_languages, true)) {
			continue;
		}

		if ($type === 'html') {
			$output[$language] = wp_kses_post((string) $text);
		} elseif ($type === 'url') {
			$url = trim((string) $text);

			if ($url !== '' && !in_array($url[0], ['/', '#', '?'], true) && !wp_parse_url($url, PHP_URL_SCHEME)) {
				$url = 'https://' . $url;
			}

			$output[$language] = esc_url_raw($url);
		} else {
			$output[$language] = sanitize_text_field((string) $text);
		}
	}

	return $output;
}

function patlis_sliders_get_translated_value($value, string $language = ''): string {
	$values = patlis_sliders_normalize_multilingual_value($value);

	if ($values === []) {
		return '';
	}

	if ($language === '') {
		$language = function_exists('patlis_get_current_language')
			? sanitize_key(patlis_get_current_language())
			: '';
	}

	if ($language !== '' && !empty($values[$language])) {
		return $values[$language];
	}

	$default_language = patlis_sliders_get_default_language();

	if (!empty($values[$default_language])) {
		return $values[$default_language];
	}

	foreach ($values as $text) {
		if ($text !== '') {
			return $text;
		}
	}

	return '';
}

function patlis_sliders_get_group(string $group, string $language = ''): array {
	$group = sanitize_key($group);
	$saved = get_option(patlis_sliders_option_name(), []);

	if (!is_array($saved) || !isset($saved[$group]) || !is_array($saved[$group])) {
		return [
			'settings' => [
				'autoplay' => 1,
				'duration' => 5000,
			],
			'slides' => [],
		];
	}

	$saved_group = $saved[$group];
	$settings = isset($saved_group['settings']) && is_array($saved_group['settings'])
		? $saved_group['settings']
		: [];
	$slides = isset($saved_group['slides']) && is_array($saved_group['slides'])
		? $saved_group['slides']
		: [];
	$translated_fields = ['small_title', 'header', 'content', 'cta1_text', 'cta1_url', 'cta2_text', 'cta2_url'];
	$resolved_slides = [];

	foreach ($slides as $slide) {
		if (!is_array($slide) || empty($slide['enabled'])) {
			continue;
		}

		foreach ($translated_fields as $field) {
			$slide[$field] = patlis_sliders_get_translated_value($slide[$field] ?? '', $language);
		}

		$resolved_slides[] = $slide;
	}

	usort($resolved_slides, static function (array $first, array $second): int {
		return absint($first['sort'] ?? 0) <=> absint($second['sort'] ?? 0);
	});

	return [
		'settings' => [
			'autoplay' => array_key_exists('autoplay', $settings) ? (int) !empty($settings['autoplay']) : 1,
			'duration' => max(1000, absint($settings['duration'] ?? 5000)),
		],
		'slides' => $resolved_slides,
	];
}

function patlis_sliders_get_registry(): array {
	return require __DIR__ . '/sliders-registry.php';
}

function patlis_sliders_get_layouts(): array {
	$registry = patlis_sliders_get_registry();
	$layouts = [];

	foreach ($registry['layouts'] as $layout_key => $layout) {
		$layouts[$layout_key] = $layout['label'];
	}

	return $layouts;
}

function patlis_sliders_sanitize_layout_options(string $layout, $value, $existing_value = []): array {
	$registry = patlis_sliders_get_registry();
	$fields = $registry['layouts'][$layout]['fields'] ?? [];
	$value = is_array($value) ? $value : [];
	$existing_value = is_array($existing_value) ? $existing_value : [];
	$output = [];

	foreach ($fields as $field) {
		if (!in_array($field['type'], ['dropdown', 'range'], true)) {
			continue;
		}

		$option_key = $field['option_key'];

		foreach ([$value[$option_key] ?? null, $existing_value[$option_key] ?? null, $field['default']] as $candidate) {
			if (!is_scalar($candidate)) {
				continue;
			}

			$candidate = sanitize_text_field((string) $candidate);

			if ($field['type'] === 'range') {
				if (!is_numeric($candidate) || !is_finite((float) $candidate)) {
					continue;
				}

				$minimum = (float) $field['min'];
				$maximum = (float) $field['max'];
				$step = (float) $field['step'];
				$number = max($minimum, min($maximum, (float) $candidate));

				if ($step > 0) {
					$number = $minimum + round(($number - $minimum) / $step) * $step;
				}

				$output[$option_key] = round(max($minimum, min($maximum, $number)), 10);
				break;
			}

			if (array_key_exists($candidate, $field['options'])) {
				$output[$option_key] = $candidate;
				break;
			}
		}
	}

	return $output;
}

function patlis_sliders_query(string $group): array {
	$slides = patlis_sliders_get_group($group)['slides'];
	$registry = patlis_sliders_get_registry();
	$template_ids = [];

	foreach ($slides as &$slide) {
		$layout = isset($slide['layout']) && is_string($slide['layout'])
			? sanitize_key($slide['layout'])
			: $registry['default_layout'];

		if (!isset($registry['layouts'][$layout])) {
			$layout = $registry['default_layout'];
		}

		if (!array_key_exists($layout, $template_ids)) {
			$template = get_page_by_path($layout, OBJECT, 'bricks_template');
			$template_ids[$layout] = $template instanceof WP_Post && $template->post_status === 'publish'
				? (int) $template->ID
				: 0;
		}

		$slide['layout'] = $layout;
		$slide['template_id'] = $template_ids[$layout];
		$options = isset($slide['options']) && is_array($slide['options']) ? $slide['options'] : [];

		foreach (patlis_sliders_sanitize_layout_options($layout, $options[$layout] ?? []) as $option_key => $option_value) {
			$slide[$option_key] = $option_value;
		}
	}
	unset($slide);

	return $slides;
}

function patlis_sliders_count(string $group): int {
	return count(patlis_sliders_query($group));
}

function patlis_sliders_duration(string $group): int {
	return patlis_sliders_get_group($group)['settings']['duration'];
}

function patlis_slide_template_shortcode(): string {
	if (!class_exists('\Bricks\Query') || !shortcode_exists('bricks_template')) {
		return '';
	}

	$slide = \Bricks\Query::get_loop_object();

	if (!is_array($slide)) {
		return '';
	}

	$template_id = absint($slide['template_id'] ?? 0);

	if (!$template_id) {
		return '';
	}

	$had_previous_slide = array_key_exists('patlis_current_slide', $GLOBALS);
	$previous_slide = $GLOBALS['patlis_current_slide'] ?? null;
	$GLOBALS['patlis_current_slide'] = $slide;

	try {
		return do_shortcode(sprintf('[bricks_template id="%d"]', $template_id));
	} finally {
		if ($had_previous_slide) {
			$GLOBALS['patlis_current_slide'] = $previous_slide;
		} else {
			unset($GLOBALS['patlis_current_slide']);
		}
	}
}

add_shortcode('patlis_slide_template', 'patlis_slide_template_shortcode');
