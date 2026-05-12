<?php
if (!defined('ABSPATH')) exit;

const PATLIS_AMENITY_META_HIGHLIGHT = 'patlis_is_highlight';
const PATLIS_AMENITY_META_ORDER     = 'patlis_order';
const PATLIS_AMENITY_META_ICON      = 'patlis_icon';

function patlis_cascade_delete_amenity_translations_by_term_id(int $term_id): void
{
  if ($term_id <= 0) {
    return;
  }

  if (!function_exists('pll_default_language') || !function_exists('pll_get_term_language') || !function_exists('pll_get_term_translations')) {
    return;
  }

  $default_lang = pll_default_language();
  $term_lang    = pll_get_term_language($term_id);

  if (!is_string($default_lang) || !is_string($term_lang) || $term_lang !== $default_lang) {
    return;
  }

  $translations = pll_get_term_translations($term_id);
  if (!is_array($translations) || empty($translations)) {
    return;
  }

  foreach ($translations as $translated_id) {
    $translated_id = (int) $translated_id;
    if ($translated_id <= 0 || $translated_id === $term_id) {
      continue;
    }

    wp_delete_term($translated_id, 'room_amenity');
  }
}

/**
 * JS reload: force page refresh after any taxonomy AJAX action (add / delete).
 */
add_action('admin_footer-edit-tags.php', function () {
  if (!function_exists('get_current_screen')) {
    return;
  }
  $screen = get_current_screen();
  if (!$screen || ($screen->taxonomy ?? '') !== 'room_amenity') {
    return;
  }
  ?>
  <script>
  jQuery(document).ajaxComplete(function(event, xhr, settings) {
    if (!settings || !settings.data) return;
    
    // Check if the ajax request was for adding or deleting a term
    if (typeof settings.data === 'string' && (settings.data.includes('action=add-tag') || settings.data.includes('action=delete-tag'))) {
      if (settings.data.includes('taxonomy=room_amenity') || settings.data.includes('screen=edit-room_amenity')) {
        setTimeout(function() {
          window.location.reload();
        }, 500);
      }
    }
  });
  </script>
  <?php
});

/**
 * Add form fields (new term)
 */
add_action('room_amenity_add_form_fields', function () {
  ?>
  <div class="form-field term-highlight-wrap">
    <label for="patlis_is_highlight">Highlight</label>
    <input type="checkbox" id="patlis_is_highlight" name="patlis_is_highlight" value="1">
    <p class="description">Αν είναι ενεργό, το amenity θα εμφανίζεται στα Highlights.</p>
  </div>

  <div class="form-field term-order-wrap">
    <label for="patlis_order">Order</label>
    <input type="number" id="patlis_order" name="patlis_order" value="0" min="0" step="1">
    <p class="description">Σειρά εμφάνισης (μικρότερο = πιο πάνω). Ισχύει για κατηγορίες και amenities.</p>
  </div>

  <div class="form-field term-icon-wrap">
    <label for="patlis_icon">Icon (Font Awesome)</label>
    <input type="text" id="patlis_icon" name="patlis_icon" value="" placeholder="fa-solid fa-wifi">
    <p>Go to <a href="https://fontawesome.com/v5/icons" target="_blank">fontawesome </a>and copy the icon you like</p>
    <p class="description">Μόνο για κατηγορίες (Parent Category: None). Βάλε class του Font Awesome.</p>
  </div>
  <?php
});

/**
 * Edit form fields (existing term)
 */
add_action('room_amenity_edit_form_fields', function ($term) {
  $highlight = (int) get_term_meta($term->term_id, PATLIS_AMENITY_META_HIGHLIGHT, true);
  $order     = (int) get_term_meta($term->term_id, PATLIS_AMENITY_META_ORDER, true);
  $icon      = (string) get_term_meta($term->term_id, PATLIS_AMENITY_META_ICON, true);

  $is_parent = ((int) $term->parent === 0);
  ?>
  <tr class="form-field term-highlight-wrap">
    <th scope="row"><label for="patlis_is_highlight">Highlight</label></th>
    <td>
      <label>
        <input type="checkbox" id="patlis_is_highlight" name="patlis_is_highlight" value="1" <?php checked($highlight === 1); ?>>
        Εμφάνιση στα Highlights
      </label>
      <p class="description">Αν είναι ενεργό, το amenity θα εμφανίζεται στα Highlights.</p>
    </td>
  </tr>

  <tr class="form-field term-order-wrap">
    <th scope="row"><label for="patlis_order">Order</label></th>
    <td>
      <input type="number" id="patlis_order" name="patlis_order" value="<?php echo esc_attr($order); ?>" min="0" step="1">
      <p class="description">Σειρά εμφάνισης (μικρότερο = πιο πάνω).</p>
    </td>
  </tr>

  <tr class="form-field term-icon-wrap">
    <th scope="row"><label for="patlis_icon">Icon (Font Awesome)</label></th>
    <td>
      <input
        type="text"
        id="patlis_icon"
        name="patlis_icon"
        value="<?php echo esc_attr($icon); ?>"
        placeholder="fa-solid fa-wifi"
        <?php echo $is_parent ? '' : 'disabled'; ?>
      >
      <p>Go to <a href="https://fontawesome.com/v5/icons" target="_blank">fontawesome </a>and copy the icon you like</p>
      <p class="description">Μόνο για κατηγορίες (Parent Category: None).</p>
    </td>
  </tr>
  <?php
}, 10, 1);

/**
 * Save term meta on create/edit
 */
/**
 * Save term meta on create/edit (robust)
 */
$patlis_save_amenity_meta = function ($term_id) {

  // Highlight
  $highlight = !empty($_POST['patlis_is_highlight']) ? 1 : 0;
  update_term_meta($term_id, PATLIS_AMENITY_META_HIGHLIGHT, $highlight);

  // Order
  $order = isset($_POST['patlis_order']) ? (int) $_POST['patlis_order'] : 0;
  update_term_meta($term_id, PATLIS_AMENITY_META_ORDER, $order);

  // Decide by REAL parent (from DB)
  $term = get_term($term_id, 'room_amenity');
  if (!$term || is_wp_error($term)) return;

  $is_parent_term = ((int) $term->parent === 0);

  // If it is NOT a parent category -> never keep icon
  if (!$is_parent_term) {
    delete_term_meta($term_id, PATLIS_AMENITY_META_ICON);
    return;
  }

  // Parent category -> save icon (even if empty => delete)
  $icon = isset($_POST['patlis_icon']) ? sanitize_text_field($_POST['patlis_icon']) : '';
  $icon = trim($icon);

  if ($icon === '') {
    delete_term_meta($term_id, PATLIS_AMENITY_META_ICON);
  } else {
    update_term_meta($term_id, PATLIS_AMENITY_META_ICON, $icon);
  }
};

add_action('created_room_amenity', $patlis_save_amenity_meta);
add_action('edited_room_amenity',  $patlis_save_amenity_meta);

/**
 * Auto-create missing translations for newly created amenities.
 * Source term remains the single place editors create terms; translations are generated automatically.
 */
add_action('created_room_amenity', function ($term_id) {
  static $is_creating = false;

  if ($is_creating) {
    return;
  }

  if (!function_exists('pll_languages_list') || !function_exists('pll_get_term_language') || !function_exists('pll_set_term_language') || !function_exists('pll_save_term_translations')) {
    return;
  }

  $source = get_term((int) $term_id, 'room_amenity');
  if (!$source || is_wp_error($source)) {
    return;
  }

  $default_lang = function_exists('pll_default_language') ? pll_default_language() : null;
  $source_lang  = pll_get_term_language((int) $term_id);

  if (!is_string($source_lang) || $source_lang === '') {
    $source_lang = is_string($default_lang) && $default_lang !== '' ? $default_lang : '';
    if ($source_lang !== '') {
      pll_set_term_language((int) $term_id, $source_lang);
    }
  }

  if ($source_lang === '') {
    return;
  }

  $langs = pll_languages_list(['fields' => 'slug']);
  if (!is_array($langs) || empty($langs)) {
    return;
  }

  $map = function_exists('pll_get_term_translations') ? pll_get_term_translations((int) $term_id) : [];
  if (!is_array($map)) {
    $map = [];
  }
  $map[$source_lang] = (int) $term_id;

  $is_creating = true;

  foreach ($langs as $lang) {
    $lang = is_string($lang) ? trim($lang) : '';
    if ($lang === '' || isset($map[$lang])) {
      continue;
    }

    $target_parent = 0;
    if ((int) $source->parent > 0 && function_exists('pll_get_term')) {
      $translated_parent = (int) pll_get_term((int) $source->parent, $lang);
      if ($translated_parent > 0) {
        $target_parent = $translated_parent;
      }
    }

    $insert = wp_insert_term(
      (string) $source->name,
      'room_amenity',
      [
        'slug'        => sanitize_title($source->slug . '-' . $lang),
        'description' => (string) $source->description,
        'parent'      => $target_parent,
      ]
    );

    if (is_wp_error($insert)) {
      if ($insert->get_error_code() === 'term_exists') {
        $existing_id = (int) $insert->get_error_data('term_exists');
        if ($existing_id > 0) {
          pll_set_term_language($existing_id, $lang);
          $map[$lang] = $existing_id;
        }
      }
      continue;
    }

    $new_id = (int) ($insert['term_id'] ?? 0);
    if ($new_id <= 0) {
      continue;
    }

    pll_set_term_language($new_id, $lang);

    // Copy synced meta defaults to all generated translations.
    update_term_meta($new_id, PATLIS_AMENITY_META_HIGHLIGHT, (int) get_term_meta((int) $term_id, PATLIS_AMENITY_META_HIGHLIGHT, true));
    update_term_meta($new_id, PATLIS_AMENITY_META_ORDER, (int) get_term_meta((int) $term_id, PATLIS_AMENITY_META_ORDER, true));

    $icon = (string) get_term_meta((int) $term_id, PATLIS_AMENITY_META_ICON, true);
    if ($icon === '') {
      delete_term_meta($new_id, PATLIS_AMENITY_META_ICON);
    } else {
      update_term_meta($new_id, PATLIS_AMENITY_META_ICON, $icon);
    }

    $map[$lang] = $new_id;
  }

  if (count($map) > 1) {
    pll_save_term_translations($map);
  }

  $is_creating = false;
}, 50, 1);

/**
 * Cascade delete translations whenever a room_amenity term is being deleted.
 * This path is taxonomy-native and works for both list and edit delete flows.
 */
add_action('pre_delete_term', function ($term_id, $taxonomy) {
  if ($taxonomy !== 'room_amenity') {
    return;
  }

  patlis_cascade_delete_amenity_translations_by_term_id((int) $term_id);
}, 10, 2);


/**
 * Columns in amenities list
 */
add_filter('manage_room_amenity_custom_column', function ($content, $column, $term_id) {

  if ($column === 'patlis_order') {
    return (string) (int) get_term_meta($term_id, PATLIS_AMENITY_META_ORDER, true);
  }

  if ($column === 'patlis_icon') {
    $term = get_term($term_id, 'room_amenity');
    if (!$term || is_wp_error($term) || (int) $term->parent !== 0) {
      return '—';
    }
    $icon = (string) get_term_meta($term_id, PATLIS_AMENITY_META_ICON, true);
    return $icon !== '' ? esc_html($icon) : '—';
  }

  if ($column === 'patlis_highlight') {
    $val = (int) get_term_meta($term_id, PATLIS_AMENITY_META_HIGHLIGHT, true);
    return $val === 1 ? '✓' : '—';
  }

  return $content;

}, 10, 3);


add_filter('manage_edit-room_amenity_columns', function ($cols) {
  return [
    'cb'              => $cols['cb'],        // checkbox
    'name'            => __('Name'),
    'patlis_order'    => 'Order',
    'patlis_icon'     => 'Icon',
    'patlis_highlight'=> 'Highlight',
  ];
});

 

