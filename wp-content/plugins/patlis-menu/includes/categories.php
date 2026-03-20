<?php
if (!defined('ABSPATH')) exit;

/**
 * Term meta fields for taxonomy: menu_section (Menu Categories)
 *
 * Meta keys (Option B – categories = pmc_*)
 *  - pmc_show (0/1)
 *  - pmc_sort (int)
 *  - pmc_image_id (attachment id)
 *  - pmc_day0a..pmc_day6b (HH:MM)
 *
 * Day mapping:
 *  day0 = Sunday ... day6 = Saturday
 * Rule:
 *  if from/to empty => visible all day
 */

/* ------------------------------------------------------------
 * Admin assets (Media uploader) only on menu_section screens
 * ------------------------------------------------------------ */
add_action('admin_enqueue_scripts', 'patlis_menu_section_admin_assets');
function patlis_menu_section_admin_assets(string $hook): void
{
    if ($hook !== 'edit-tags.php' && $hook !== 'term.php') return;
    if (!function_exists('get_current_screen')) return;

    $screen = get_current_screen();
    if (!$screen || ($screen->taxonomy ?? '') !== 'menu_section') return;

    wp_enqueue_media();

    // Use a tiny inline script; jQuery is available in WP admin.
    $js = <<<JS
jQuery(function($){
  var frame = null;

  function clearImage(){
    $('#pmc_image_id').val('');
    $('#pmc_image_preview').html('');
    $('#pmc_image_remove').hide();
  }

  function setImage(att){
    var id  = att.id || '';
    var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
    $('#pmc_image_id').val(id);
    $('#pmc_image_preview').html('<img src="'+url+'" style="max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;" />');
    $('#pmc_image_remove').show();
  }

  $(document).on('click', '#pmc_image_select', function(e){
    e.preventDefault();

    if (frame) { frame.open(); return; }

    frame = wp.media({
      title: 'Select category image',
      button: { text: 'Use this image' },
      multiple: false
    });

    frame.on('select', function(){
      var att = frame.state().get('selection').first().toJSON();
      setImage(att);
    });

    frame.open();
  });

  $(document).on('click', '#pmc_image_remove', function(e){
    e.preventDefault();
    clearImage();
  });

  if ($('#pmc_image_id').val()) $('#pmc_image_remove').show();
  else $('#pmc_image_remove').hide();
});
JS;

    wp_add_inline_script('jquery', $js);
}

/* ------------------------------------------------------------
 * Add form fields
 * ------------------------------------------------------------ */
add_action('menu_section_add_form_fields', 'patlis_menu_section_add_fields');
function patlis_menu_section_add_fields(): void
{
    // Our own nonce for term meta saves
    wp_nonce_field('patlis_menu_section_meta', 'patlis_menu_section_nonce');

    ?>
    <div class="form-field">
        <label for="pmc_show">Show</label>
        <input type="checkbox" name="pmc_show" id="pmc_show" value="1" checked>
        <p class="description">If unchecked, this category will be hidden.</p>
    </div>

    <div class="form-field">
        <label for="pmc_sort">Display order</label>
        <input type="number" name="pmc_sort" id="pmc_sort" value="0" step="1">
        <p class="description">Lower number shows first.</p>
    </div>

    <div class="form-field">
        <label>Image</label>
        <div id="pmc_image_preview"></div>
        <input type="hidden" name="pmc_image_id" id="pmc_image_id" value="">
        <p>
            <button type="button" class="button" id="pmc_image_select">Select image</button>
            <button type="button" class="button" id="pmc_image_remove" style="display:none;">Remove</button>
        </p>
    </div>

    <div class="form-field">
        <label>Limit appearance for hours/days (empty = all day)</label>
        <table class="widefat striped" style="max-width:720px">
            <thead>
                <tr>
                    <th style="width:140px;">Day</th>
                    <th style="width:200px;">From</th>
                    <th style="width:200px;">To</th>
                </tr>
            </thead>
            <tbody>
                <?php echo patlis_menu_section_schedule_rows(); ?>
            </tbody>
        </table>
        <p class="description">If both empty for a day, category is visible all day.</p>
    </div>
    <?php
}

/* ------------------------------------------------------------
 * Edit form fields
 * ------------------------------------------------------------ */
add_action('menu_section_edit_form_fields', 'patlis_menu_section_edit_fields');
function patlis_menu_section_edit_fields($term): void
{
    if (!($term instanceof WP_Term)) return;

    // Our own nonce for term meta saves
    wp_nonce_field('patlis_menu_section_meta', 'patlis_menu_section_nonce');

    $show = get_term_meta($term->term_id, 'pmc_show', true);
    $show = ($show === '' ? '1' : (string)$show);

    $sort = get_term_meta($term->term_id, 'pmc_sort', true);
    $sort = ($sort === '' ? '0' : (string)$sort);

    $image_id = (int) get_term_meta($term->term_id, 'pmc_image_id', true);
    $preview  = $image_id ? wp_get_attachment_image($image_id, 'thumbnail', false, [
        'style' => 'max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;'
    ]) : '';

    ?>
    <tr class="form-field">
        <th scope="row"><label for="pmc_show">Show</label></th>
        <td>
            <label>
                <input type="checkbox" name="pmc_show" id="pmc_show" value="1" <?php checked($show, '1'); ?>>
                Visible
            </label>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row"><label for="pmc_sort">Display order</label></th>
        <td>
            <input type="number" name="pmc_sort" id="pmc_sort" value="<?php echo esc_attr($sort); ?>" step="1">
            <p class="description">Lower number shows first.</p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row"><label>Image</label></th>
        <td>
            <div id="pmc_image_preview"><?php echo $preview; ?></div>
            <input type="hidden" name="pmc_image_id" id="pmc_image_id" value="<?php echo esc_attr($image_id); ?>">
            <p>
                <button type="button" class="button" id="pmc_image_select">Select image</button>
                <button type="button" class="button" id="pmc_image_remove" style="<?php echo $image_id ? '' : 'display:none;'; ?>">Remove</button>
            </p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row"><label>Limit appearance for hours/days (empty = all day)</label></th>
        <td>
            <table class="widefat striped" style="max-width:720px">
                <thead>
                    <tr>
                        <th style="width:140px;">Day</th>
                        <th style="width:200px;">From</th>
                        <th style="width:200px;">To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo patlis_menu_section_schedule_rows((int)$term->term_id); ?>
                </tbody>
            </table>
            <p class="description">If both empty for a day, category is visible all day.</p>
        </td>
    </tr>
    <?php
}

function patlis_menu_section_schedule_rows(int $term_id = 0): string
{
    $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $html = '';

    for ($i = 0; $i <= 6; $i++) {
        $a = $term_id ? (string)get_term_meta($term_id, "pmc_day{$i}a", true) : '';
        $b = $term_id ? (string)get_term_meta($term_id, "pmc_day{$i}b", true) : '';

        $html .= '<tr>';
        $html .= '<td>' . esc_html($days[$i]) . '</td>';
        $html .= '<td><input type="time" name="pmc_day' . $i . 'a" value="' . esc_attr($a) . '" step="60" style="width:160px"></td>';
        $html .= '<td><input type="time" name="pmc_day' . $i . 'b" value="' . esc_attr($b) . '" step="60" style="width:160px"></td>';
        $html .= '</tr>';
    }

    return $html;
}

/* ------------------------------------------------------------
 * Save term meta
 * ------------------------------------------------------------ */
add_action('created_menu_section', 'patlis_menu_section_save_fields');
add_action('edited_menu_section', 'patlis_menu_section_save_fields');
function patlis_menu_section_save_fields(int $term_id): void
{
    // Nonce (our fields)
    if (!isset($_POST['patlis_menu_section_nonce']) || !wp_verify_nonce((string)$_POST['patlis_menu_section_nonce'], 'patlis_menu_section_meta')) {
        return;
    }

    // Capability: user must be allowed to edit this term
    if (!current_user_can('edit_term', $term_id)) {
        return;
    }

    $show = isset($_POST['pmc_show']) ? '1' : '0';
    update_term_meta($term_id, 'pmc_show', $show);

    $sort = isset($_POST['pmc_sort']) ? (string)intval($_POST['pmc_sort']) : '0';
    update_term_meta($term_id, 'pmc_sort', $sort);

    $image_id = isset($_POST['pmc_image_id']) ? (int)$_POST['pmc_image_id'] : 0;
    if ($image_id > 0) {
        update_term_meta($term_id, 'pmc_image_id', (string)$image_id);
    } else {
        delete_term_meta($term_id, 'pmc_image_id');
    }

    for ($i = 0; $i <= 6; $i++) {
        $a = isset($_POST["pmc_day{$i}a"]) ? trim((string)$_POST["pmc_day{$i}a"]) : '';
        $b = isset($_POST["pmc_day{$i}b"]) ? trim((string)$_POST["pmc_day{$i}b"]) : '';

        $a = patlis_menu_sanitize_time($a);
        $b = patlis_menu_sanitize_time($b);

        if ($a === '') delete_term_meta($term_id, "pmc_day{$i}a"); else update_term_meta($term_id, "pmc_day{$i}a", $a);
        if ($b === '') delete_term_meta($term_id, "pmc_day{$i}b"); else update_term_meta($term_id, "pmc_day{$i}b", $b);
    }
}

function patlis_menu_sanitize_time(string $t): string
{
    if ($t === '') return '';
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $t) ? $t : '';
}

/**
 * Computed: is category open now?
 * - pmc_show must be 1
 * - if both empty => open all day
 * - if one empty => open all day (simple rule)
 * - supports overnight ranges (e.g. 18:00 -> 02:00)
 */
function patlis_menu_section_is_open_now(int $term_id): bool
{
    $show = (string)get_term_meta($term_id, 'pmc_show', true);
    if ($show === '0') return false;

    $dt = current_datetime();
    $dayIndex = (int)$dt->format('w'); // 0=Sun..6=Sat
    $nowMin = ((int)$dt->format('H')) * 60 + (int)$dt->format('i');

    $a = (string)get_term_meta($term_id, "pmc_day{$dayIndex}a", true);
    $b = (string)get_term_meta($term_id, "pmc_day{$dayIndex}b", true);

    if ($a === '' && $b === '') return true;
    if ($a === '' || $b === '') return true;

    $aMin = ((int)substr($a, 0, 2)) * 60 + (int)substr($a, 3, 2);
    $bMin = ((int)substr($b, 0, 2)) * 60 + (int)substr($b, 3, 2);

    if ($aMin === $bMin) return true;

    if ($aMin < $bMin) {
        return ($nowMin >= $aMin && $nowMin < $bMin);
    }

    return ($nowMin >= $aMin || $nowMin < $bMin);
}
