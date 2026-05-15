<?php
if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', 'patlis_acc_rooms_enqueue_gallery_assets');
function patlis_acc_rooms_enqueue_gallery_assets($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'patlis_room') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
}

function patlis_acc_parse_gallery_ids($raw): array {
    if (is_array($raw)) {
        $ids = $raw;
    } elseif (is_string($raw)) {
        $parts = preg_split('/[\s,]+/', trim($raw));
        $ids = is_array($parts) ? $parts : [];
    } else {
        $ids = [];
    }

    $ids = array_map('absint', $ids);
    $ids = array_values(array_filter($ids, function ($id) {
        return $id > 0;
    }));

    return array_values(array_unique($ids));
}

add_action('add_meta_boxes', 'patlis_acc_rooms_register_metabox');
function patlis_acc_rooms_register_metabox() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    add_meta_box(
        'patlis_acc_room_fields',
        'Room Fields',
        'patlis_acc_rooms_render_metabox',
        'patlis_room',
        'normal',
        'high'
    );
}

function patlis_acc_rooms_render_metabox($post) {
    wp_nonce_field('patlis_acc_room_fields_save', 'patlis_acc_room_fields_nonce');

    $v = function($key) use ($post) {
        return get_post_meta($post->ID, $key, true);
    };

    $item_nr   = esc_attr($v('room_item_nr'));
    $beds      = esc_attr($v('room_beds'));
    $persons   = esc_attr($v('room_persons'));
    $count     = esc_attr($v('room_count'));
    $video_url = esc_attr($v('room_video_url'));
    $img360    = esc_attr($v('room_img_360_url'));
    $book_url  = esc_attr($v('room_book_url'));
    $sticky    = (int) $v('room_sticky') === 1;

    // New fields
    $short_desc = esc_textarea($v('room_short_desc'));
    $size_m2    = esc_attr($v('room_size_m2'));
    $bed_type   = esc_attr($v('room_bed_type'));
    $view       = esc_attr($v('room_view'));

    $gallery_ids_raw = $v('room_gallery_ids');
    $gallery_ids = patlis_acc_parse_gallery_ids($gallery_ids_raw);
    $gallery_ids_csv = esc_attr(implode(',', $gallery_ids));

    echo '<table class="form-table" role="presentation">';

    echo '<tr><th scope="row"><label for="room_item_nr">Room Item Nr</label></th>
              <td><input type="text" class="regular-text" id="room_item_nr" name="room_item_nr" value="'.$item_nr.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_beds">Beds</label></th>
              <td><input type="number" min="0" step="1" class="small-text" id="room_beds" name="room_beds" value="'.$beds.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_persons">Persons</label></th>
              <td><input type="number" min="0" step="1" class="small-text" id="room_persons" name="room_persons" value="'.$persons.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_count">Count (how many identical rooms)</label></th>
              <td><input type="number" min="0" step="1" class="small-text" id="room_count" name="room_count" value="'.$count.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_video_url">Room Video URL</label></th>
              <td><input type="url" class="regular-text" id="room_video_url" name="room_video_url" value="'.$video_url.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_img_360_url">Room 360° Image URL</label></th>
              <td><input type="url" class="regular-text" id="room_img_360_url" name="room_img_360_url" value="'.$img360.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_book_url">Room Booking URL</label></th>
              <td><input type="url" class="regular-text" id="room_book_url" name="room_book_url" value="'.$book_url.'"></td></tr>';

    echo '<tr><th scope="row">Sticky</th>
              <td><label><input type="checkbox" name="room_sticky" value="1" '.checked($sticky, true, false).'> Sticky room</label></td></tr>';

    echo '<tr><th scope="row"><label for="room_short_desc">Short description</label></th>
              <td><textarea class="large-text" rows="3" id="room_short_desc" name="room_short_desc">'.$short_desc.'</textarea></td></tr>';

    echo '<tr><th scope="row"><label for="room_size_m2">Room size (m²)</label></th>
              <td><input type="text" class="small-text" id="room_size_m2" name="room_size_m2" value="'.$size_m2.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_bed_type">Bed type</label></th>
              <td><input type="text" class="regular-text" id="room_bed_type" name="room_bed_type" value="'.$bed_type.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_view">View</label></th>
              <td><input type="text" class="regular-text" id="room_view" name="room_view" value="'.$view.'"></td></tr>';

    echo '<tr><th scope="row"><label for="room_gallery_ids">Room Gallery</label></th><td>';
    echo '<input type="hidden" id="room_gallery_ids" name="room_gallery_ids" value="' . $gallery_ids_csv . '">';
    echo '<div id="patlis-room-gallery" class="patlis-room-gallery">';
    foreach ($gallery_ids as $attachment_id) {
        $thumb = wp_get_attachment_image((int) $attachment_id, 'thumbnail');
        if (empty($thumb)) {
            continue;
        }
        echo '<div class="patlis-room-gallery__item" data-id="' . (int) $attachment_id . '">';
        echo '<button type="button" class="button-link-delete patlis-room-gallery__remove" aria-label="Remove image">&times;</button>';
        echo $thumb;
        echo '</div>';
    }
    echo '</div>';
    echo '<p>';
    echo '<button type="button" class="button" id="patlis-room-gallery-add">Add Images</button> ';
    echo '<button type="button" class="button" id="patlis-room-gallery-clear">Clear</button>';
    echo '</p>';
    echo '<p class="description">Select multiple images, drag to reorder, remove what you do not need.</p>';
    echo '</td></tr>';

    echo '</table>';

    ?>
    <style>
    .patlis-room-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .patlis-room-gallery__item {
        position: relative;
        width: 96px;
        height: 96px;
        border: 1px solid #ccd0d4;
        background: #fff;
        cursor: move;
        overflow: hidden;
        border-radius: 4px;
    }
    .patlis-room-gallery__item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .patlis-room-gallery__remove {
        position: absolute;
        top: 2px;
        right: 4px;
        z-index: 2;
        color: #b32d2e;
        text-decoration: none;
        font-size: 18px;
        line-height: 1;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 2px;
        padding: 0 2px;
    }
    </style>
    <script>
    (function($){
        var $list = $('#patlis-room-gallery');
        var $input = $('#room_gallery_ids');
        var frame = null;

        function updateIds() {
            var ids = [];
            $list.find('.patlis-room-gallery__item').each(function(){
                var id = parseInt($(this).attr('data-id'), 10);
                if (id > 0) {
                    ids.push(id);
                }
            });
            $input.val(ids.join(','));
        }

        function hasImage(id) {
            return $list.find('.patlis-room-gallery__item[data-id="' + id + '"]').length > 0;
        }

        function addItem(id, url) {
            if (!id || hasImage(id)) {
                return;
            }
            var html = '' +
                '<div class="patlis-room-gallery__item" data-id="' + id + '">' +
                    '<button type="button" class="button-link-delete patlis-room-gallery__remove" aria-label="Remove image">&times;</button>' +
                    '<img src="' + url + '" alt="">' +
                '</div>';
            $list.append(html);
        }

        $list.sortable({
            items: '.patlis-room-gallery__item',
            update: updateIds
        });

        $('#patlis-room-gallery-add').on('click', function(e){
            e.preventDefault();

            if (!frame) {
                frame = wp.media({
                    title: 'Select Room Gallery Images',
                    library: { type: 'image' },
                    button: { text: 'Use selected images' },
                    multiple: true
                });

                frame.on('select', function(){
                    var selection = frame.state().get('selection');
                    selection.each(function(attachment){
                        var data = attachment.toJSON();
                        var thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
                        addItem(data.id, thumb);
                    });
                    updateIds();
                });
            }

            frame.open();
        });

        $list.on('click', '.patlis-room-gallery__remove', function(e){
            e.preventDefault();
            $(this).closest('.patlis-room-gallery__item').remove();
            updateIds();
        });

        $('#patlis-room-gallery-clear').on('click', function(e){
            e.preventDefault();
            $list.empty();
            updateIds();
        });

        updateIds();
    })(jQuery);
    </script>
    <?php
}

add_action('save_post_patlis_room', 'patlis_acc_rooms_save_metabox');
function patlis_acc_rooms_save_metabox($post_id) {

    if (!isset($_POST['patlis_acc_room_fields_nonce']) || !wp_verify_nonce($_POST['patlis_acc_room_fields_nonce'], 'patlis_acc_room_fields_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $set = function($key, $val) use ($post_id) {
        update_post_meta($post_id, $key, $val);
    };

    $set('room_item_nr', sanitize_text_field($_POST['room_item_nr'] ?? ''));

    $set('room_beds',    isset($_POST['room_beds']) ? max(0, (int)$_POST['room_beds']) : 0);
    $set('room_persons', isset($_POST['room_persons']) ? max(0, (int)$_POST['room_persons']) : 0);
    $set('room_count',   isset($_POST['room_count']) ? max(0, (int)$_POST['room_count']) : 0);

    $set('room_video_url',   isset($_POST['room_video_url']) ? esc_url_raw((string)$_POST['room_video_url']) : '');
    $set('room_img_360_url', isset($_POST['room_img_360_url']) ? esc_url_raw((string)$_POST['room_img_360_url']) : '');
    $set('room_book_url',    isset($_POST['room_book_url']) ? esc_url_raw((string)$_POST['room_book_url']) : '');

    $set('room_sticky', !empty($_POST['room_sticky']) ? 1 : 0);

    // New fields
    $set('room_short_desc', isset($_POST['room_short_desc']) ? sanitize_textarea_field((string)$_POST['room_short_desc']) : '');

    // allow numbers like 35 or 35.5, but keep as string
    $size_raw = isset($_POST['room_size_m2']) ? (string)$_POST['room_size_m2'] : '';
    $size_raw = str_replace(',', '.', $size_raw);
    $size_raw = preg_replace('~[^0-9\.]~', '', $size_raw);
    $set('room_size_m2', $size_raw);

    $set('room_bed_type', isset($_POST['room_bed_type']) ? sanitize_text_field((string)$_POST['room_bed_type']) : '');
    $set('room_view',     isset($_POST['room_view']) ? sanitize_text_field((string)$_POST['room_view']) : '');

    $gallery_ids = patlis_acc_parse_gallery_ids($_POST['room_gallery_ids'] ?? '');
    if (empty($gallery_ids)) {
        delete_post_meta($post_id, 'room_gallery_ids');
    } else {
        $set('room_gallery_ids', $gallery_ids);
    }
}
