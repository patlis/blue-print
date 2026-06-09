<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Homepage {

    const DEFAULT_ORDER = ['welcome','dishes','rooms','offers','experience','services','events','gallery','reviews','cta'];

    private static function labels(): array {
        return [
            'welcome'    => __('Welcome Section',        'patlis-core'),
            'dishes'     => __('Top Dishes',             'patlis-core'),
            'rooms'      => __('Top Rooms',              'patlis-core'),
            'offers'     => __('Offers and Packages',    'patlis-core'),
            'experience' => __('Experience',             'patlis-core'),
            'services'   => __('Top Services',           'patlis-core'),
            'events'     => __('Upcoming Events',        'patlis-core'),
            'gallery'    => __('Home Gallery',           'patlis-core'),
            'reviews'    => __('Last Reviews',           'patlis-core'),
            'cta'        => __('Action banner (CTA)',    'patlis-core'),
        ];
    }

    public static function render(): void {
        if (!current_user_can('patlis_manage')) return;

        $opt   = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
        $saved = isset($opt['sections_order']) && is_array($opt['sections_order']) ? $opt['sections_order'] : [];

        $cta_bg_image_id = isset($opt['cta_bg_image_id']) ? (int)$opt['cta_bg_image_id'] : 0;
        $cta_bg_preview  = $cta_bg_image_id > 0
            ? wp_get_attachment_image($cta_bg_image_id, 'thumbnail', false, ['style' => 'max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;'])
            : '';

        wp_enqueue_media();

        $labels = self::labels();
        $order  = array_values(array_filter($saved, fn($s) => isset($labels[$s])));

        foreach (self::DEFAULT_ORDER as $s) {
            if (!in_array($s, $order, true)) {
                $order[] = $s;
            }
        }

        wp_enqueue_script('jquery-ui-sortable');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Home page settings', 'patlis-core'); ?></h1>

            <?php if (!empty($_GET['patlis_saved'])): ?>
                <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="patlis_save_homepage">
                <?php wp_nonce_field('patlis_save_homepage'); ?>

                <h2><?php esc_html_e('CTA Background image', 'patlis-core'); ?></h2>
                <table class="form-table" role="presentation">
                  <tr>
                    <th scope="row"><label><?php esc_html_e('CTA Background', 'patlis-core'); ?></label></th>
                    <td>
                      <div id="patlis_cta_bg_preview"><?php echo $cta_bg_preview; ?></div>
                      <input type="hidden" id="patlis_cta_bg_image_id" name="patlis_cta_bg_image_id" value="<?php echo esc_attr($cta_bg_image_id); ?>">
                      <p>
                        <button type="button" class="button" id="patlis_cta_bg_select"><?php esc_html_e('Select image', 'patlis-core'); ?></button>
                        <button type="button" class="button" id="patlis_cta_bg_remove" style="<?php echo $cta_bg_image_id > 0 ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove', 'patlis-core'); ?></button>
                      </p>
                    </td>
                  </tr>
                </table>

                <h2><?php esc_html_e('Sections order', 'patlis-core'); ?></h2>
                <p class="description"><?php esc_html_e('Drag to reorder.', 'patlis-core'); ?></p>

                <style>
                    #patlis-sections-sortable {
                        list-style: none;
                        margin: 16px 0;
                        padding: 0;
                        max-width: 380px;
                    }
                    #patlis-sections-sortable li {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 10px 14px;
                        margin-bottom: 6px;
                        background: #fff;
                        border: 1px solid #ccd0d4;
                        border-radius: 4px;
                        cursor: grab;
                        font-size: 14px;
                        font-weight: 500;
                        user-select: none;
                    }
                    #patlis-sections-sortable li:active { cursor: grabbing; }
                    #patlis-sections-sortable li .patlis-drag-handle {
                        color: #aaa;
                        font-size: 18px;
                        line-height: 1;
                    }
                    #patlis-sections-sortable li .patlis-section-nr {
                        min-width: 22px;
                        color: #999;
                        font-size: 12px;
                    }
                    #patlis-sections-sortable li.ui-sortable-helper {
                        box-shadow: 0 4px 12px rgba(0,0,0,.15);
                    }
                    #patlis-sections-sortable li.ui-sortable-placeholder {
                        visibility: visible !important;
                        background: #f0f6fc;
                        border: 2px dashed #72aee6;
                    }
                </style>

                <ul id="patlis-sections-sortable">
                    <?php foreach ($order as $i => $slug): ?>
                        <li data-slug="<?php echo esc_attr($slug); ?>">
                            <span class="patlis-drag-handle" aria-hidden="true">&#9776;</span>
                            <span class="patlis-section-nr"><?php echo ($i + 1); ?></span>
                            <?php echo esc_html($labels[$slug] ?? $slug); ?>
                            <input type="hidden" name="patlis_sections_order[]" value="<?php echo esc_attr($slug); ?>">
                        </li>
                    <?php endforeach; ?>
                </ul>

                <script>
                jQuery(function ($) {
                    var $list = $('#patlis-sections-sortable');

                    $list.sortable({
                        items: 'li',
                        axis: 'y',
                        handle: '.patlis-drag-handle',
                        placeholder: 'ui-sortable-placeholder',
                        update: function () {
                            $list.find('li').each(function (idx) {
                                $(this).find('.patlis-section-nr').text(idx + 1);
                                $(this).find('input[type="hidden"]').val($(this).data('slug'));
                            });
                        }
                    });
                });
                </script>

                <?php submit_button('Save'); ?>

                <script>
                (function(){
                    var ctaBgFrame   = null;
                    var ctaBgInput   = document.getElementById('patlis_cta_bg_image_id');
                    var ctaBgPreview = document.getElementById('patlis_cta_bg_preview');
                    var ctaBgSelect  = document.getElementById('patlis_cta_bg_select');
                    var ctaBgRemove  = document.getElementById('patlis_cta_bg_remove');

                    function setCtaBgImage(attachment) {
                        if (!ctaBgInput || !ctaBgPreview || !ctaBgRemove) return;
                        var imageId  = attachment && attachment.id ? attachment.id : 0;
                        var imageUrl = '';
                        if (attachment && attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) {
                            imageUrl = attachment.sizes.thumbnail.url;
                        } else if (attachment && attachment.url) {
                            imageUrl = attachment.url;
                        }
                        ctaBgInput.value       = imageId;
                        ctaBgPreview.innerHTML = imageUrl ? '<img src="' + imageUrl + '" style="max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;" />' : '';
                        ctaBgRemove.style.display = imageId ? '' : 'none';
                    }

                    if (ctaBgSelect) {
                        ctaBgSelect.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (ctaBgFrame) { ctaBgFrame.open(); return; }
                            ctaBgFrame = wp.media({
                                title: '<?php echo esc_js(__('Select CTA background image', 'patlis-core')); ?>',
                                button: { text: '<?php echo esc_js(__('Use this image', 'patlis-core')); ?>' },
                                multiple: false
                            });
                            ctaBgFrame.on('select', function() {
                                var attachment = ctaBgFrame.state().get('selection').first().toJSON();
                                setCtaBgImage(attachment);
                            });
                            ctaBgFrame.open();
                        });
                    }

                    if (ctaBgRemove) {
                        ctaBgRemove.addEventListener('click', function(e) {
                            e.preventDefault();
                            setCtaBgImage(null);
                        });
                    }
                })();
                </script>
            </form>
        </div>
        <?php
    }
}
