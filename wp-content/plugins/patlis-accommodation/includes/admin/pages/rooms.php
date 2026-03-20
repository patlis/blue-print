<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'patlis_acc_rooms_register_metabox');
function patlis_acc_rooms_register_metabox() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    add_meta_box(
        'patlis_acc_room_fields',
        'Room Fields (Patlis)',
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

    echo '</table>';
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
}
