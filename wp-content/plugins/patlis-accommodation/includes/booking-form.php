<?php
if (!defined('ABSPATH')) exit;

/**
 * Patlis Accommodation - Booking helpers
 * - REST endpoint: /wp-json/patlis-acc/v1/rooms
 * - Inline script ONLY on /booking/ or /{lang}/booking/
 * - Populates select[name="room_id"] with <option value="ID">Title</option>
 * - Preselect via URL: /booking/?room_id=123
 */

function patlis_acc_is_booking_page(): bool
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $path = rtrim($path, '/') . '/';

    // /booking/ OR /{lang}/booking/
    return (bool) preg_match('~^/(?:[a-zA-Z-]{2,10}/)?booking/~', $path);
}

/* ============================================================
 * Cache clear for rooms list (transient)
 * ============================================================ */
function patlis_acc_rooms_list_cache_clear(): void
{
    delete_transient('patlis_acc_rooms_list_v1');
}

add_action('save_post_patlis_room', function ($post_id) {
    // ignore autosave & revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    patlis_acc_rooms_list_cache_clear();
});

add_action('trashed_post', function ($post_id) {
    if (get_post_type($post_id) === 'patlis_room') {
        patlis_acc_rooms_list_cache_clear();
    }
});

add_action('deleted_post', function ($post_id) {
    if (get_post_type($post_id) === 'patlis_room') {
        patlis_acc_rooms_list_cache_clear();
    }
});

/* ============================================================
 * 1) REST endpoint: rooms list
 * ============================================================ */
add_action('rest_api_init', function () {
    register_rest_route('patlis-acc/v1', '/rooms', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {

            $cache_key = 'patlis_acc_rooms_list_v1';
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                return rest_ensure_response($cached);
            }

            $q = new WP_Query([
                'post_type'      => 'patlis_room',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);

            $out = [];
            if (!empty($q->posts)) {
                foreach ($q->posts as $pid) {
                    $out[] = [
                        'id'    => (int) $pid,
                        'title' => (string) get_the_title($pid),
                        'slug'  => (string) get_post_field('post_name', $pid),
                    ];
                }
            }

            set_transient($cache_key, $out, 10 * MINUTE_IN_SECONDS);

            return rest_ensure_response($out);
        },
    ]);
});

/* ============================================================
 * 2) Inline script (ONLY on booking page)
 * ============================================================ */
add_action('wp_footer', function () {
    if (is_admin()) return;
    if (!patlis_acc_is_booking_page()) return;

    $rest_url = esc_url_raw(rest_url('patlis-acc/v1/rooms'));
    ?>
    <script>
    (function () {
        function getParam(name) {
            try {
                return (new URLSearchParams(window.location.search)).get(name) || '';
            } catch (e) {
                return '';
            }
        }

        function buildOption(value, text) {
            var opt = document.createElement('option');
            opt.value = String(value);
            opt.textContent = String(text);
            return opt;
        }

        function init() {
            var select = document.querySelector('select[name="room_id"]');
            if (!select) return;

            var selectedId = getParam('room_id');

            // κρατάμε το πρώτο option ως placeholder αν υπάρχει (π.χ. "Select a Room")
            var placeholderText = '';
            if (select.options.length > 0) {
                placeholderText = select.options[0].textContent || '';
            }
            select.innerHTML = '';

            if (placeholderText) {
                var ph = buildOption('', placeholderText);
                select.appendChild(ph);
            }

            fetch(<?php echo wp_json_encode($rest_url); ?>, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    if (!Array.isArray(list)) return;

                    list.forEach(function (room) {
                        if (!room || !room.id) return;
                        var title = room.title || ('Room ' + room.id);
                        select.appendChild(buildOption(room.id, title));
                    });

                    if (selectedId) {
                        select.value = String(parseInt(selectedId, 10));
                    }
                })
                .catch(function () {
                    // silent fail
                });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
    <?php
}, 20);
