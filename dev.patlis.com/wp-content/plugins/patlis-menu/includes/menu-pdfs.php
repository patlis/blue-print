<?php
if (!defined('ABSPATH')) exit;

/**
 * Menu PDF post meta
 * - pmpdf_file_id
 * - pmpdf_file_url
 */

add_action('admin_enqueue_scripts', 'patlis_menu_pdfs_admin_assets');
function patlis_menu_pdfs_admin_assets(string $hook): void
{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    if (!function_exists('get_current_screen')) return;

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'menu_pdf') return;

    wp_enqueue_media();

    $js = <<<JS
jQuery(function($){
  var frame = null;

  function updateRemove(){
    if ($('#pmpdf_file_url').val()) $('#pmpdf_file_remove').show();
    else $('#pmpdf_file_remove').hide();
  }

  $(document).on('click', '#pmpdf_file_select', function(e){
    e.preventDefault();

    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      title: 'Select PDF',
      button: { text: 'Use this PDF' },
      library: { type: 'application/pdf' },
      multiple: false
    });

    frame.on('select', function(){
      var file = frame.state().get('selection').first().toJSON();
      $('#pmpdf_file_id').val(file.id || '');
      $('#pmpdf_file_url').val(file.url || '');
      updateRemove();
    });

    frame.open();
  });

  $(document).on('click', '#pmpdf_file_remove', function(e){
    e.preventDefault();
    $('#pmpdf_file_id').val('');
    $('#pmpdf_file_url').val('');
    updateRemove();
  });

  $(document).on('input', '#pmpdf_file_url', updateRemove);
  updateRemove();
});
JS;

    wp_add_inline_script('jquery', $js);
}

add_action('add_meta_boxes', 'patlis_menu_pdfs_add_metaboxes');
function patlis_menu_pdfs_add_metaboxes(): void
{
    add_meta_box(
        'patlis_menu_pdf_details',
        'PDF Details',
        'patlis_menu_pdfs_metabox_render',
        'menu_pdf',
        'normal',
        'high'
    );
}

function patlis_menu_pdfs_metabox_render(WP_Post $post): void
{
    wp_nonce_field('patlis_menu_pdf_save', 'patlis_menu_pdf_nonce');

    $file_id  = (int) get_post_meta($post->ID, 'pmpdf_file_id', true);
    $file_url = (string) get_post_meta($post->ID, 'pmpdf_file_url', true);
    ?>
    <p>
        <label for="pmpdf_file_url"><strong>PDF file</strong></label>
    </p>

    <input type="hidden" id="pmpdf_file_id" name="pmpdf_file_id" value="<?php echo esc_attr($file_id); ?>">

    <input type="url"
           id="pmpdf_file_url"
           name="pmpdf_file_url"
           class="regular-text"
           value="<?php echo esc_attr($file_url); ?>"
           placeholder="https://example.com/file.pdf">

    <p style="margin-top:8px;">
        <button type="button" class="button" id="pmpdf_file_select">Select PDF</button>
        <button type="button" class="button" id="pmpdf_file_remove" style="<?php echo $file_url !== '' ? '' : 'display:none;'; ?>">Remove</button>
    </p>

    <p class="description">Use the post title as the PDF name shown on the frontend.</p>
    <?php
}

add_action('save_post_menu_pdf', 'patlis_menu_pdfs_save', 10, 2);
function patlis_menu_pdfs_save(int $post_id, WP_Post $post): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (!isset($_POST['patlis_menu_pdf_nonce']) || !wp_verify_nonce((string) $_POST['patlis_menu_pdf_nonce'], 'patlis_menu_pdf_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $file_id = isset($_POST['pmpdf_file_id']) ? max(0, (int) $_POST['pmpdf_file_id']) : 0;
    $file_url = isset($_POST['pmpdf_file_url']) ? esc_url_raw((string) $_POST['pmpdf_file_url']) : '';

    if ($file_id > 0) {
        update_post_meta($post_id, 'pmpdf_file_id', (string) $file_id);
    } else {
        delete_post_meta($post_id, 'pmpdf_file_id');
    }

    if ($file_url !== '') {
        update_post_meta($post_id, 'pmpdf_file_url', $file_url);
    } else {
        delete_post_meta($post_id, 'pmpdf_file_url');
    }
}

add_action('admin_head', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) return;

    if (!in_array($screen->base, ['post', 'post-new'], true)) return;
    if ($screen->post_type !== 'menu_pdf') return;

    $back_url = admin_url('edit.php?post_type=menu_pdf');
    $label = '← Back to PDFs';
    ?>
    <script>
    (function () {
        function addBackBtn() {
            var h1 = document.querySelector('.wrap h1.wp-heading-inline');
            if (!h1) return;
            if (document.getElementById('patlis-back-to-menu-pdfs')) return;

            var a = document.createElement('a');
            a.id = 'patlis-back-to-menu-pdfs';
            a.className = 'page-title-action';
            a.href = <?php echo json_encode($back_url); ?>;
            a.textContent = <?php echo json_encode($label); ?>;
            h1.insertAdjacentElement('afterend', a);
        }

        document.addEventListener('DOMContentLoaded', addBackBtn);
        addBackBtn();
    })();
    </script>
    <?php
});