<?php
if (!defined('ABSPATH')) {
    exit;
}

function patlis_gallery_post_type(): string
{
    return 'patlis_gallery';
}

function patlis_gallery_meta_key(): string
{
    return 'patlis_gallery_image_ids';
}

function patlis_gallery_system_key_meta(): string
{
    return 'patlis_gallery_system_key';
}

function patlis_gallery_home_key(): string
{
    return 'home_gallery';
}

function patlis_gallery_home_title(): string
{
    return 'Home gallery';
}

function patlis_gallery_force_labels_runtime(): void
{
    global $wp_post_types;

    $post_type = patlis_gallery_post_type();
    if (!isset($wp_post_types[$post_type]) || !is_object($wp_post_types[$post_type])) {
        return;
    }

    $obj = $wp_post_types[$post_type];

    if (!isset($obj->labels) || !is_object($obj->labels)) {
        $obj->labels = (object) [];
    }

    $force = [
        'name' => __('Galleries', 'patlis-core'),
        'singular_name' => __('Gallery', 'patlis-core'),
        'menu_name' => __('Gallery', 'patlis-core'),
        'name_admin_bar' => __('Gallery', 'patlis-core'),
        'all_items' => __('All galleries', 'patlis-core'),
        'add_new' => __('Add new gallery', 'patlis-core'),
        'add_new_item' => __('Add new gallery', 'patlis-core'),
        'edit_item' => __('Edit gallery', 'patlis-core'),
        'new_item' => __('New gallery', 'patlis-core'),
        'view_item' => __('View gallery', 'patlis-core'),
        'search_items' => __('Search galleries', 'patlis-core'),
        'not_found' => __('No galleries found.', 'patlis-core'),
        'not_found_in_trash' => __('No galleries found in Trash.', 'patlis-core'),
    ];

    $obj->label = $force['singular_name'];

    foreach ($force as $key => $value) {
        $obj->labels->{$key} = $value;
    }

    $wp_post_types[$post_type] = $obj;
}

add_filter('register_post_type_args', function (array $args, string $post_type): array {
    if ($post_type !== patlis_gallery_post_type()) {
        return $args;
    }

    if (empty($args['label']) || !is_string($args['label'])) {
        $args['label'] = __('Gallery', 'patlis-core');
    }

    if (!isset($args['labels']) || !is_array($args['labels'])) {
        $args['labels'] = [];
    }

    if (empty($args['labels']['name']) || !is_string($args['labels']['name'])) {
        $args['labels']['name'] = __('Galleries', 'patlis-core');
    }

    if (empty($args['labels']['singular_name']) || !is_string($args['labels']['singular_name'])) {
        $args['labels']['singular_name'] = __('Gallery', 'patlis-core');
    }

    if (empty($args['labels']['menu_name']) || !is_string($args['labels']['menu_name'])) {
        $args['labels']['menu_name'] = __('Gallery', 'patlis-core');
    }

    return $args;
}, 20, 2);

function patlis_gallery_parse_ids($raw): array
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

add_action('init', function (): void {
    $post_type = patlis_gallery_post_type();

    if (post_type_exists($post_type)) {
        return;
    }

    register_post_type($post_type, [
        'label' => __('Gallery', 'patlis-core'),
        'labels' => [
            'name' => __('Galleries', 'patlis-core'),
            'singular_name' => __('Gallery', 'patlis-core'),
            'menu_name' => __('Gallery', 'patlis-core'),
            'all_items' => __('All galleries', 'patlis-core'),
            'add_new' => __('Add new gallery', 'patlis-core'),
            'add_new_item' => __('Add new gallery', 'patlis-core'),
            'edit_item' => __('Edit gallery', 'patlis-core'),
            'new_item' => __('New gallery', 'patlis-core'),
            'view_item' => __('View gallery', 'patlis-core'),
            'search_items' => __('Search galleries', 'patlis-core'),
            'not_found' => __('No galleries found.', 'patlis-core'),
            'not_found_in_trash' => __('No galleries found in Trash.', 'patlis-core'),
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 30,
        'menu_icon' => 'dashicons-format-gallery',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => false,
        'show_in_rest' => true,
        'supports' => ['title', 'page-attributes'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'gallery', 'with_front' => false],
        'publicly_queryable' => true,
        'exclude_from_search' => true,
        'query_var' => true,
    ]);
}, 20);

add_action('init', function (): void {
    $post_type = patlis_gallery_post_type();
    $obj = get_post_type_object($post_type);

    if (!$obj) {
        return;
    }

    if (!isset($obj->label) || !is_string($obj->label) || trim($obj->label) === '') {
        $obj->label = __('Gallery', 'patlis-core');
    }

    if (!isset($obj->labels) || !is_object($obj->labels)) {
        $obj->labels = (object) [];
    }

    if (!isset($obj->labels->name) || !is_string($obj->labels->name) || trim($obj->labels->name) === '') {
        $obj->labels->name = __('Galleries', 'patlis-core');
    }

    if (!isset($obj->labels->singular_name) || !is_string($obj->labels->singular_name) || trim($obj->labels->singular_name) === '') {
        $obj->labels->singular_name = __('Gallery', 'patlis-core');
    }

    if (!isset($obj->labels->menu_name) || !is_string($obj->labels->menu_name) || trim($obj->labels->menu_name) === '') {
        $obj->labels->menu_name = __('Gallery', 'patlis-core');
    }
}, 999);

add_action('admin_init', function (): void {
    patlis_gallery_force_labels_runtime();
}, 1);

add_action('admin_footer', function (): void {
    if (!is_admin()) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';

    $is_rankmath_target_page = in_array($page, ['rank-math-options-sitemap', 'rank-math-options-titles'], true);
    if (!$is_rankmath_target_page) {
        return;
    }

    ?>
    <script>
    (function(){
        function titleCaseFromSlug(slug) {
            if (!slug) {
                return '';
            }

            slug = String(slug)
                .replace(/^tab-panel-\d+-/i, '')
                .replace(/-view$/i, '')
                .replace(/^_+/, '');

            if (slug === 'patlis_gallery') {
                return 'Gallery';
            }

            if (slug === 'taxonomies') {
                return 'Taxonomies:';
            }

            return slug
                .replace(/^(post-type|tax)-/, '')
                .replace(/[_-]+/g, ' ')
                .replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });
        }

        function extractViewValue(node) {
            if (!node) {
                return '';
            }

            var candidates = [
                node.getAttribute('href') || '',
                node.getAttribute('id') || '',
                node.getAttribute('aria-controls') || ''
            ];

            for (var i = 0; i < candidates.length; i++) {
                var value = candidates[i];
                if (!value) {
                    continue;
                }

                var match = value.match(/view=(post-type|tax)-([a-z0-9_-]+)/i);
                if (match && match[2]) {
                    return match[2].toLowerCase();
                }

                match = value.match(/(post-type|tax)-([a-z0-9_-]+)/i);
                if (match && match[2]) {
                    return match[2].toLowerCase();
                }

                match = value.match(/^tab-panel-\d+-(.+?)(?:-view)?$/i);
                if (match && match[1]) {
                    return match[1].toLowerCase();
                }

                match = value.match(/[_-]([a-z0-9_-]+)(?:-view)?$/i);
                if (match && match[1]) {
                    return match[1].toLowerCase();
                }
            }

            return '';
        }

        function isPlaceholderText(text) {
            var normalized = String(text || '').replace(/\s+/g, '');
            if (!normalized) {
                return true;
            }

            // Some Rank Math separator tabs render only punctuation (for example ':').
            return /^[^a-z0-9\u00c0-\u024f\u0370-\u03ff]+$/i.test(normalized);
        }

        function ensureRankMathLabels() {
            var selectors = [
                '[id*="post-type-"]',
                '[id*="tax-"]',
                '[id*="_taxonomies"]',
                '[aria-controls*="post-type-"]',
                '[aria-controls*="tax-"]',
                '[aria-controls*="_taxonomies"]',
                'a[href*="view=post-type-"]',
                'a[href*="view=tax-"]',
                'a[href*="_taxonomies"]',
                'button[id*="post-type-"]',
                'button[id*="tax-"]',
                'button[id*="_taxonomies"]'
            ];

            var nodes = document.querySelectorAll(selectors.join(','));
            nodes.forEach(function(node) {
                var txt = (node.textContent || '').trim();
                if (!isPlaceholderText(txt)) {
                    return;
                }

                var slug = extractViewValue(node);
                var label = titleCaseFromSlug(slug);
                if (!label) {
                    return;
                }

                node.textContent = label;
            });
        }

        ensureRankMathLabels();

        var observer = new MutationObserver(function() {
            ensureRankMathLabels();
        });

        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
    </script>
    <?php
});

function patlis_gallery_get_home_id(): int
{
    $ids = get_posts([
        'post_type' => patlis_gallery_post_type(),
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'fields' => 'ids',
        'numberposts' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'meta_key' => patlis_gallery_system_key_meta(),
        'meta_value' => patlis_gallery_home_key(),
    ]);

    if (empty($ids) || !is_array($ids)) {
        return 0;
    }

    $home_id = (int) $ids[0];

    if (count($ids) > 1) {
        foreach ($ids as $index => $id) {
            if ($index === 0) {
                continue;
            }
            delete_post_meta((int) $id, patlis_gallery_system_key_meta());
        }
    }

    return $home_id;
}

function patlis_gallery_ensure_home_gallery(): int
{
    $post_type = patlis_gallery_post_type();
    if (!post_type_exists($post_type)) {
        return 0;
    }

    $home_id = patlis_gallery_get_home_id();
    if ($home_id > 0) {
        return $home_id;
    }

    $inserted = wp_insert_post([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'post_title' => patlis_gallery_home_title(),
        'post_name' => 'home-gallery',
    ], true);

    if (is_wp_error($inserted) || (int) $inserted <= 0) {
        return 0;
    }

    $home_id = (int) $inserted;
    update_post_meta($home_id, patlis_gallery_system_key_meta(), patlis_gallery_home_key());

    return $home_id;
}

add_action('init', function (): void {
    patlis_gallery_ensure_home_gallery();
}, 30);

add_filter('pre_trash_post', function ($trash, WP_Post $post) {
    if ($post->post_type !== patlis_gallery_post_type()) {
        return $trash;
    }

    $key = (string) get_post_meta((int) $post->ID, patlis_gallery_system_key_meta(), true);
    if ($key !== patlis_gallery_home_key()) {
        return $trash;
    }

    return false;
}, 10, 2);

add_filter('pre_delete_post', function ($delete, WP_Post $post) {
    if ($post->post_type !== patlis_gallery_post_type()) {
        return $delete;
    }

    $key = (string) get_post_meta((int) $post->ID, patlis_gallery_system_key_meta(), true);
    if ($key !== patlis_gallery_home_key()) {
        return $delete;
    }

    return false;
}, 10, 2);

add_action('admin_enqueue_scripts', function (string $hook): void {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== patlis_gallery_post_type()) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
});

function patlis_gallery_render_metabox($post): void
{
    wp_nonce_field('patlis_gallery_save', 'patlis_gallery_nonce');

    $gallery_ids = patlis_gallery_parse_ids(get_post_meta($post->ID, patlis_gallery_meta_key(), true));
    $gallery_ids_csv = esc_attr(implode(',', $gallery_ids));

    echo '<table class="form-table" role="presentation">';
    echo '<tr><th scope="row"><label for="' . esc_attr(patlis_gallery_meta_key()) . '">Gallery images</label></th><td>';
    echo '<input type="hidden" id="' . esc_attr(patlis_gallery_meta_key()) . '" name="' . esc_attr(patlis_gallery_meta_key()) . '" value="' . $gallery_ids_csv . '">';
    echo '<div id="patlis-gallery-images" class="patlis-gallery-images">';

    foreach ($gallery_ids as $attachment_id) {
        $thumb = wp_get_attachment_image((int) $attachment_id, 'thumbnail');
        if (empty($thumb)) {
            continue;
        }

        echo '<div class="patlis-gallery-images__item" data-id="' . (int) $attachment_id . '">';
        echo '<button type="button" class="button-link-delete patlis-gallery-images__remove" aria-label="Remove image">&times;</button>';
        echo $thumb;
        echo '</div>';
    }

    echo '</div>';
    echo '<p>';
    echo '<button type="button" class="button" id="patlis-gallery-images-add">Add Images</button> ';
    echo '<button type="button" class="button" id="patlis-gallery-images-clear">Clear</button>';
    echo '</p>';
    echo '<p class="description">Select multiple images, drag to reorder, then save.</p>';
    echo '</td></tr>';
    echo '</table>';

    ?>
    <style>
    .patlis-gallery-images {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .patlis-gallery-images__item {
        position: relative;
        width: 96px;
        height: 96px;
        border: 1px solid #ccd0d4;
        background: #fff;
        cursor: move;
        overflow: hidden;
        border-radius: 4px;
    }
    .patlis-gallery-images__item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .patlis-gallery-images__remove {
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
        var $list = $('#patlis-gallery-images');
        var $input = $('#<?php echo esc_js(patlis_gallery_meta_key()); ?>');
        var frame = null;

        function updateIds() {
            var ids = [];
            $list.find('.patlis-gallery-images__item').each(function(){
                var id = parseInt($(this).attr('data-id'), 10);
                if (id > 0) {
                    ids.push(id);
                }
            });
            $input.val(ids.join(','));
        }

        function hasImage(id) {
            return $list.find('.patlis-gallery-images__item[data-id="' + id + '"]').length > 0;
        }

        function addItem(id, url) {
            if (!id || hasImage(id)) {
                return;
            }

            var html = '' +
                '<div class="patlis-gallery-images__item" data-id="' + id + '">' +
                    '<button type="button" class="button-link-delete patlis-gallery-images__remove" aria-label="Remove image">&times;</button>' +
                    '<img src="' + url + '" alt="">' +
                '</div>';

            $list.append(html);
        }

        $list.sortable({
            items: '.patlis-gallery-images__item',
            update: updateIds
        });

        $('#patlis-gallery-images-add').on('click', function(e){
            e.preventDefault();

            if (!frame) {
                frame = wp.media({
                    title: 'Select Gallery Images',
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

        $list.on('click', '.patlis-gallery-images__remove', function(e){
            e.preventDefault();
            $(this).closest('.patlis-gallery-images__item').remove();
            updateIds();
        });

        $('#patlis-gallery-images-clear').on('click', function(e){
            e.preventDefault();
            $list.empty();
            updateIds();
        });

        updateIds();
    })(jQuery);
    </script>
    <?php
}

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'patlis_gallery_images_metabox',
        __('Gallery images', 'patlis-core'),
        'patlis_gallery_render_metabox',
        patlis_gallery_post_type(),
        'normal',
        'high'
    );
});

add_action('save_post_' . patlis_gallery_post_type(), function (int $post_id): void {
    if (!isset($_POST['patlis_gallery_nonce']) || !wp_verify_nonce((string) $_POST['patlis_gallery_nonce'], 'patlis_gallery_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $gallery_ids = patlis_gallery_parse_ids($_POST[patlis_gallery_meta_key()] ?? '');

    if (empty($gallery_ids)) {
        delete_post_meta($post_id, patlis_gallery_meta_key());
        return;
    }

    update_post_meta($post_id, patlis_gallery_meta_key(), $gallery_ids);
});

function patlis_gallery_get_items(int $post_id): array
{
    if ($post_id <= 0) {
        return [];
    }

    $ids = patlis_gallery_parse_ids(get_post_meta($post_id, patlis_gallery_meta_key(), true));
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

function patlis_gallery_get_all_images_items(string $scope = 'gallery'): array
{
    $scope = sanitize_key($scope);
    $home_id = patlis_gallery_get_home_id();

    if ($scope === 'home') {
        return patlis_gallery_get_home_items();
    }

    $post_ids = get_posts([
        'post_type' => patlis_gallery_post_type(),
        'post_status' => 'publish',
        'fields' => 'ids',
        'numberposts' => -1,
        'orderby' => 'menu_order date',
        'order' => 'ASC',
    ]);

    if (empty($post_ids) || !is_array($post_ids)) {
        return [];
    }

    $all = [];

    foreach ($post_ids as $post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            continue;
        }

        // "gallery" scope = all galleries except the dedicated Home gallery.
        if ($scope === 'gallery' && $home_id > 0 && $post_id === $home_id) {
            continue;
        }

        $items = patlis_gallery_get_items($post_id);
        if (empty($items)) {
            continue;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item['gallery_id'] = $post_id;
            $item['gallery_title'] = (string) get_the_title($post_id);
            $all[] = $item;
        }
    }

    return $all;
}

function patlis_gallery_get_home_items(): array
{
    $home_id = patlis_gallery_get_home_id();
    if ($home_id <= 0) {
        $home_id = patlis_gallery_ensure_home_gallery();
    }

    if ($home_id <= 0) {
        return [];
    }

    return patlis_gallery_get_items($home_id);
}

add_shortcode('patlis_gallery_json', function ($atts): string {
    $atts = shortcode_atts([
        'id' => 0,
    ], (array) $atts, 'patlis_gallery_json');

    $post_id = (int) $atts['id'];
    if ($post_id <= 0) {
        $current = get_post();
        $post_id = $current instanceof WP_Post ? (int) $current->ID : 0;
    }

    return wp_json_encode(patlis_gallery_get_items($post_id));
});

add_shortcode('patlis_gallery_all_images_json', function ($atts): string {
    $atts = shortcode_atts([
        'scope' => 'gallery',
    ], (array) $atts, 'patlis_gallery_all_images_json');

    return wp_json_encode(patlis_gallery_get_all_images_items((string) $atts['scope']));
});

add_shortcode('patlis_home_gallery_json', function (): string {
    return wp_json_encode(patlis_gallery_get_home_items());
});
