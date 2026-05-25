<?php
if (!defined('ABSPATH')) {
    exit;
}

function patlis_core_parse_gallery_ids($raw): array
{
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

function patlis_core_resolve_gallery_ids_for_post(int $post_id, string $meta_key): array
{
    if ($post_id <= 0 || $meta_key === '') {
        return [];
    }

    $resolve_ids = function (int $id) use ($meta_key): array {
        $raw_ids = get_post_meta($id, $meta_key, true);
        return patlis_core_parse_gallery_ids($raw_ids);
    };

    $ids = $resolve_ids($post_id);

    if (
        empty($ids)
        && function_exists('pll_get_post_language')
        && function_exists('pll_default_language')
        && function_exists('pll_get_post')
    ) {
        $current_lang = pll_get_post_language($post_id, 'slug');
        $default_lang = pll_default_language('slug');

        if (
            is_string($current_lang)
            && is_string($default_lang)
            && $current_lang !== ''
            && $default_lang !== ''
            && $current_lang !== $default_lang
        ) {
            $default_post_id = (int) pll_get_post($post_id, $default_lang);
            if ($default_post_id > 0 && $default_post_id !== $post_id) {
                $ids = $resolve_ids($default_post_id);
            }
        }
    }

    return $ids;
}

function patlis_core_get_gallery_items_by_meta(int $post_id, string $meta_key): array
{
    $ids = patlis_core_resolve_gallery_ids_for_post($post_id, $meta_key);

    if (empty($ids)) {
        return [];
    }

    $items = [];

    foreach ($ids as $id) {
        $full = wp_get_attachment_image_src($id, 'full');
        if (!is_array($full) || empty($full[0])) {
            continue;
        }

        $caption = wp_get_attachment_caption($id);
        $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
        $thumb_url = wp_get_attachment_image_url($id, 'thumbnail') ?: '';
        $medium_url = wp_get_attachment_image_url($id, 'medium') ?: '';
        $large_url = wp_get_attachment_image_url($id, 'large') ?: '';
        $full_url = (string) $full[0];

        $items[] = [
            'id' => (int) $id,
            'title' => (string) get_the_title($id),
            'alt' => is_string($alt) ? $alt : '',
            'caption' => is_string($caption) ? $caption : '',
            'url' => $full_url,
            'width' => (int) ($full[1] ?? 0),
            'height' => (int) ($full[2] ?? 0),
            'thumbnail' => $thumb_url,
            'medium' => $medium_url,
            'large' => $large_url,
            'full' => $full_url,
            'sizes' => [
                'thumbnail' => $thumb_url,
                'medium' => $medium_url,
                'large' => $large_url,
                'full' => $full_url,
            ],
        ];
    }

    return $items;
}

function patlis_core_events_gallery_metabox_assets(string $hook): void
{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->post_type, ['events', 'services'], true)) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
}
add_action('admin_enqueue_scripts', 'patlis_core_events_gallery_metabox_assets');

function patlis_core_render_events_gallery_metabox($post): void
{
    wp_nonce_field('patlis_core_events_gallery_save', 'patlis_core_events_gallery_nonce');

    $gallery_ids = patlis_core_parse_gallery_ids(get_post_meta($post->ID, 'events_gallery_ids', true));
    $gallery_ids_csv = esc_attr(implode(',', $gallery_ids));

    echo '<table class="form-table" role="presentation">';
    echo '<tr><th scope="row"><label for="events_gallery_ids">Event Gallery</label></th><td>';
    echo '<input type="hidden" id="events_gallery_ids" name="events_gallery_ids" value="' . $gallery_ids_csv . '">';
    echo '<div id="patlis-events-gallery" class="patlis-events-gallery">';

    foreach ($gallery_ids as $attachment_id) {
        $thumb = wp_get_attachment_image((int) $attachment_id, 'thumbnail');
        if (empty($thumb)) {
            continue;
        }

        echo '<div class="patlis-events-gallery__item" data-id="' . (int) $attachment_id . '">';
        echo '<button type="button" class="button-link-delete patlis-events-gallery__remove" aria-label="Remove image">&times;</button>';
        echo $thumb;
        echo '</div>';
    }

    echo '</div>';
    echo '<p>';
    echo '<button type="button" class="button" id="patlis-events-gallery-add">Add Images</button> ';
    echo '<button type="button" class="button" id="patlis-events-gallery-clear">Clear</button>';
    echo '</p>';
    echo '<p class="description">Select multiple images, drag to reorder, remove what you do not need.</p>';
    echo '</td></tr>';
    echo '</table>';

    ?>
    <style>
    .patlis-events-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .patlis-events-gallery__item {
        position: relative;
        width: 96px;
        height: 96px;
        border: 1px solid #ccd0d4;
        background: #fff;
        cursor: move;
        overflow: hidden;
        border-radius: 4px;
    }
    .patlis-events-gallery__item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .patlis-events-gallery__remove {
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
        var $list = $('#patlis-events-gallery');
        var $input = $('#events_gallery_ids');
        var frame = null;

        function updateIds() {
            var ids = [];
            $list.find('.patlis-events-gallery__item').each(function(){
                var id = parseInt($(this).attr('data-id'), 10);
                if (id > 0) {
                    ids.push(id);
                }
            });
            $input.val(ids.join(','));
        }

        function hasImage(id) {
            return $list.find('.patlis-events-gallery__item[data-id="' + id + '"]').length > 0;
        }

        function addItem(id, url) {
            if (!id || hasImage(id)) {
                return;
            }
            var html = '' +
                '<div class="patlis-events-gallery__item" data-id="' + id + '">' +
                    '<button type="button" class="button-link-delete patlis-events-gallery__remove" aria-label="Remove image">&times;</button>' +
                    '<img src="' + url + '" alt="">' +
                '</div>';
            $list.append(html);
        }

        $list.sortable({
            items: '.patlis-events-gallery__item',
            update: updateIds
        });

        $('#patlis-events-gallery-add').on('click', function(e){
            e.preventDefault();

            if (!frame) {
                frame = wp.media({
                    title: 'Select Event Gallery Images',
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

        $list.on('click', '.patlis-events-gallery__remove', function(e){
            e.preventDefault();
            $(this).closest('.patlis-events-gallery__item').remove();
            updateIds();
        });

        $('#patlis-events-gallery-clear').on('click', function(e){
            e.preventDefault();
            $list.empty();
            updateIds();
        });

        updateIds();
    })(jQuery);
    </script>
    <?php
}

function patlis_core_register_events_gallery_metabox(): void
{
    add_meta_box(
        'patlis_core_events_gallery',
        'Event Gallery',
        'patlis_core_render_events_gallery_metabox',
        'events',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'patlis_core_register_events_gallery_metabox');

function patlis_core_save_events_gallery_metabox(int $post_id): void
{
    if (!isset($_POST['patlis_core_events_gallery_nonce']) || !wp_verify_nonce((string) $_POST['patlis_core_events_gallery_nonce'], 'patlis_core_events_gallery_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $gallery_ids = patlis_core_parse_gallery_ids($_POST['events_gallery_ids'] ?? '');

    if (empty($gallery_ids)) {
        delete_post_meta($post_id, 'events_gallery_ids');
        return;
    }

    update_post_meta($post_id, 'events_gallery_ids', $gallery_ids);
}
add_action('save_post_events', 'patlis_core_save_events_gallery_metabox');

function patlis_core_get_events_gallery_items(int $post_id): array
{
    return patlis_core_get_gallery_items_by_meta($post_id, 'events_gallery_ids');
}

function patlis_core_render_services_gallery_metabox($post): void
{
    wp_nonce_field('patlis_core_services_gallery_save', 'patlis_core_services_gallery_nonce');

    $gallery_ids = patlis_core_parse_gallery_ids(get_post_meta($post->ID, 'services_gallery_ids', true));
    $gallery_ids_csv = esc_attr(implode(',', $gallery_ids));

    echo '<table class="form-table" role="presentation">';
    echo '<tr><th scope="row"><label for="services_gallery_ids">Service Gallery</label></th><td>';
    echo '<input type="hidden" id="services_gallery_ids" name="services_gallery_ids" value="' . $gallery_ids_csv . '">';
    echo '<div id="patlis-services-gallery" class="patlis-services-gallery">';

    foreach ($gallery_ids as $attachment_id) {
        $thumb = wp_get_attachment_image((int) $attachment_id, 'thumbnail');
        if (empty($thumb)) {
            continue;
        }

        echo '<div class="patlis-services-gallery__item" data-id="' . (int) $attachment_id . '">';
        echo '<button type="button" class="button-link-delete patlis-services-gallery__remove" aria-label="Remove image">&times;</button>';
        echo $thumb;
        echo '</div>';
    }

    echo '</div>';
    echo '<p>';
    echo '<button type="button" class="button" id="patlis-services-gallery-add">Add Images</button> ';
    echo '<button type="button" class="button" id="patlis-services-gallery-clear">Clear</button>';
    echo '</p>';
    echo '<p class="description">Select multiple images, drag to reorder, remove what you do not need.</p>';
    echo '</td></tr>';
    echo '</table>';

    ?>
    <style>
    .patlis-services-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .patlis-services-gallery__item {
        position: relative;
        width: 96px;
        height: 96px;
        border: 1px solid #ccd0d4;
        background: #fff;
        cursor: move;
        overflow: hidden;
        border-radius: 4px;
    }
    .patlis-services-gallery__item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .patlis-services-gallery__remove {
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
        var $list = $('#patlis-services-gallery');
        var $input = $('#services_gallery_ids');
        var frame = null;

        function updateIds() {
            var ids = [];
            $list.find('.patlis-services-gallery__item').each(function(){
                var id = parseInt($(this).attr('data-id'), 10);
                if (id > 0) {
                    ids.push(id);
                }
            });
            $input.val(ids.join(','));
        }

        function hasImage(id) {
            return $list.find('.patlis-services-gallery__item[data-id="' + id + '"]').length > 0;
        }

        function addItem(id, url) {
            if (!id || hasImage(id)) {
                return;
            }
            var html = '' +
                '<div class="patlis-services-gallery__item" data-id="' + id + '">' +
                    '<button type="button" class="button-link-delete patlis-services-gallery__remove" aria-label="Remove image">&times;</button>' +
                    '<img src="' + url + '" alt="">' +
                '</div>';
            $list.append(html);
        }

        $list.sortable({
            items: '.patlis-services-gallery__item',
            update: updateIds
        });

        $('#patlis-services-gallery-add').on('click', function(e){
            e.preventDefault();

            if (!frame) {
                frame = wp.media({
                    title: 'Select Service Gallery Images',
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

        $list.on('click', '.patlis-services-gallery__remove', function(e){
            e.preventDefault();
            $(this).closest('.patlis-services-gallery__item').remove();
            updateIds();
        });

        $('#patlis-services-gallery-clear').on('click', function(e){
            e.preventDefault();
            $list.empty();
            updateIds();
        });

        updateIds();
    })(jQuery);
    </script>
    <?php
}

function patlis_core_register_services_gallery_metabox(): void
{
    add_meta_box(
        'patlis_core_services_gallery',
        'Service Gallery',
        'patlis_core_render_services_gallery_metabox',
        'services',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'patlis_core_register_services_gallery_metabox');

function patlis_core_save_services_gallery_metabox(int $post_id): void
{
    if (!isset($_POST['patlis_core_services_gallery_nonce']) || !wp_verify_nonce((string) $_POST['patlis_core_services_gallery_nonce'], 'patlis_core_services_gallery_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $gallery_ids = patlis_core_parse_gallery_ids($_POST['services_gallery_ids'] ?? '');

    if (empty($gallery_ids)) {
        delete_post_meta($post_id, 'services_gallery_ids');
        return;
    }

    update_post_meta($post_id, 'services_gallery_ids', $gallery_ids);
}
add_action('save_post_services', 'patlis_core_save_services_gallery_metabox');

function patlis_core_get_services_gallery_items(int $post_id): array
{
    return patlis_core_get_gallery_items_by_meta($post_id, 'services_gallery_ids');
}