<?php
if (!defined('ABSPATH')) exit;

const PATLIS_FACILITY_META_HIGHLIGHT = 'patlis_is_highlight';
const PATLIS_FACILITY_META_ORDER     = 'patlis_order';
const PATLIS_FACILITY_META_ICON      = 'patlis_icon';

/**
 * Add form fields (new term)
 */
add_action('property_facility_add_form_fields', function () {
  ?>
  <div class="form-field term-highlight-wrap">
    <label for="patlis_is_highlight">Highlight</label>
    <input type="checkbox" id="patlis_is_highlight" name="patlis_is_highlight" value="1">
    <p class="description">Αν είναι ενεργό, το item θα εμφανίζεται στα Highlights.</p>
  </div>

  <div class="form-field term-order-wrap">
    <label for="patlis_order">Order</label>
    <input type="number" id="patlis_order" name="patlis_order" value="0" min="0" step="1">
    <p class="description">Σειρά εμφάνισης (μικρότερο = πιο πάνω). Ισχύει για κατηγορίες και items.</p>
  </div>

  <div class="form-field term-icon-wrap">
    <label for="patlis_icon">Icon (Font Awesome)</label>
    <input type="text" id="patlis_icon" name="patlis_icon" value="" placeholder="fa-solid fa-wifi">
    <p>Go to <a href="https://fontawesome.com/v5/icons" target="_blank">fontawesome</a> and copy the icon you like</p>
    <p class="description">Μόνο για κατηγορίες (Parent Category: None).</p>
  </div>
  <?php
});

/**
 * Edit form fields (existing term)
 */
add_action('property_facility_edit_form_fields', function ($term) {
  $highlight = (int) get_term_meta($term->term_id, PATLIS_FACILITY_META_HIGHLIGHT, true);
  $order     = (int) get_term_meta($term->term_id, PATLIS_FACILITY_META_ORDER, true);
  $icon      = (string) get_term_meta($term->term_id, PATLIS_FACILITY_META_ICON, true);

  $is_parent = ((int) $term->parent === 0);
  ?>
  <tr class="form-field term-highlight-wrap">
    <th scope="row"><label for="patlis_is_highlight">Highlight</label></th>
    <td>
      <label>
        <input type="checkbox" id="patlis_is_highlight" name="patlis_is_highlight" value="1" <?php checked($highlight === 1); ?>>
        Εμφάνιση στα Highlights
      </label>
      <p class="description">Αν είναι ενεργό, το item θα εμφανίζεται στα Highlights.</p>
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
      <p>Go to <a href="https://fontawesome.com/v5/icons" target="_blank">fontawesome</a> and copy the icon you like</p>
      <p class="description">Μόνο για κατηγορίες (Parent Category: None).</p>
    </td>
  </tr>
  <?php
}, 10, 1);

/**
 * Save term meta (robust)
 */
$patlis_save_facility_meta = function ($term_id) {

  $highlight = !empty($_POST['patlis_is_highlight']) ? 1 : 0;
  update_term_meta($term_id, PATLIS_FACILITY_META_HIGHLIGHT, $highlight);

  $order = isset($_POST['patlis_order']) ? (int) $_POST['patlis_order'] : 0;
  update_term_meta($term_id, PATLIS_FACILITY_META_ORDER, $order);

  $term = get_term($term_id, 'property_facility');
  if (!$term || is_wp_error($term)) return;

  $is_parent_term = ((int) $term->parent === 0);

  if (!$is_parent_term) {
    delete_term_meta($term_id, PATLIS_FACILITY_META_ICON);
    return;
  }

  $icon = isset($_POST['patlis_icon']) ? sanitize_text_field($_POST['patlis_icon']) : '';
  $icon = trim($icon);

  if ($icon === '') {
    delete_term_meta($term_id, PATLIS_FACILITY_META_ICON);
  } else {
    update_term_meta($term_id, PATLIS_FACILITY_META_ICON, $icon);
  }
};

add_action('created_property_facility', $patlis_save_facility_meta);
add_action('edited_property_facility',  $patlis_save_facility_meta);

/**
 * Columns: κρατάμε μόνο Name + 3 custom (όπως το έκανες)
 */
add_filter('manage_edit-property_facility_columns', function ($cols) {
  return [
    'cb'               => $cols['cb'],
    'name'             => __('Name'),
    'patlis_order'     => 'Order',
    'patlis_icon'      => 'Icon',
    'patlis_highlight' => 'Highlight',
  ];
});

add_filter('manage_property_facility_custom_column', function ($content, $column, $term_id) {

  if ($column === 'patlis_order') {
    return (string) (int) get_term_meta($term_id, PATLIS_FACILITY_META_ORDER, true);
  }

  if ($column === 'patlis_icon') {
    $term = get_term($term_id, 'property_facility');
    if (!$term || is_wp_error($term) || (int) $term->parent !== 0) return '—';
    $icon = (string) get_term_meta($term_id, PATLIS_FACILITY_META_ICON, true);
    return $icon !== '' ? esc_html($icon) : '—';
  }

  if ($column === 'patlis_highlight') {
    $val = (int) get_term_meta($term_id, PATLIS_FACILITY_META_HIGHLIGHT, true);
    return $val === 1 ? '✓' : '—';
  }

  return $content;

}, 10, 3);
