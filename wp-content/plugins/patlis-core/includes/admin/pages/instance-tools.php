<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Patlis_Admin_Page_Instance_Tools
{
    private static function count_post_types(array $post_types): int
    {
        global $wpdb;

        $post_types = array_values(array_filter(array_map('sanitize_key', $post_types)));
        if ($post_types === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $sql = "
            SELECT COUNT(1)
            FROM {$wpdb->posts}
            WHERE post_type IN ({$placeholders})
              AND post_status <> 'auto-draft'
        ";

        $count = $wpdb->get_var($wpdb->prepare($sql, ...$post_types));

        return max(0, (int) $count);
    }

    private static function count_taxonomy_terms(string $taxonomy): int
    {
        global $wpdb;

        $taxonomy = sanitize_key($taxonomy);
        if ($taxonomy === '') {
            return 0;
        }

        $sql = "SELECT COUNT(1) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s";
        $count = $wpdb->get_var($wpdb->prepare($sql, $taxonomy));

        return max(0, (int) $count);
    }

    private static function table_exists(string $table_name): bool
    {
        global $wpdb;

        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));

        return is_string($found) && $found === $table_name;
    }

    private static function count_table_rows(string $table_suffix): int
    {
        global $wpdb;

        $table_suffix = preg_replace('/[^a-z0-9_]/', '', strtolower($table_suffix));
        if (!is_string($table_suffix) || $table_suffix === '') {
            return 0;
        }

        $table_name = $wpdb->prefix . $table_suffix;
        if (!self::table_exists($table_name)) {
            return 0;
        }

        $sql = "SELECT COUNT(1) FROM {$table_name}";
        $count = $wpdb->get_var($sql);

        return max(0, (int) $count);
    }

    private static function definition(): array
    {
        return [
            'Menu' => [
                [
                    'label' => 'Delete menu categories',
                    'count' => self::count_taxonomy_terms('menu_section'),
                ],
                [
                    'label' => 'Delete menu items',
                    'count' => self::count_post_types(['menu_item']),
                ],
                [
                    'label'  => 'Delete menu Pdfs',
                    'count'  => self::count_post_types(['menu_pdf']),
                    'action' => 'delete_menu_pdfs',
                ],
            ],
            'Leads' => [
                [
                    'label' => 'Delete Reservations (legacy placeholder)',
                    'count' => self::count_post_types(['reservation', 'reservations', 'patlis_reservation']),
                ],
                [
                    'label' => 'Delete Bookings',
                    'count' => self::count_table_rows('patlis_bookings'),
                ],
                [
                    'label' => 'Delete Contact form leads (legacy placeholder)',
                    'count' => self::count_post_types(['flamingo_inbound']),
                ],
            ],
            'Content' => [
                [
                    'label' => 'Delete Services',
                    'count' => self::count_post_types(['services', 'service']),
                ],
                [
                    'label' => 'Delete Events',
                    'count' => self::count_post_types(['events', 'event']),
                ],
                [
                    'label' => 'Delete Galley',
                    'count' => self::count_post_types(['patlis_gallery', 'gallery']),
                ],
                [
                    'label' => 'Delete Reviews',
                    'count' => self::count_post_types(['reviews', 'review']),
                ],
                [
                    'label' => 'Delete Timelines (for about us)',
                    'count' => self::count_post_types(['timeline_item', 'timelines', 'timeline']),
                ],
            ],
            'Kiosk Mode' => [
                [
                    'label' => 'Delete Kiosk slides',
                    'count' => self::count_post_types(['kiosk_slide']),
                ],
            ],
            'Accomidation' => [
                [
                    'label' => 'Delete rooms',
                    'count' => self::count_post_types(['patlis_room', 'room']),
                ],
                [
                    'label' => 'Delete offers & Packages',
                    'count' => self::count_post_types(['rates']),
                ],
                [
                    'label' => 'Delete Property Facilities',
                    'count' => self::count_taxonomy_terms('property_facility'),
                ],
                [
                    'label' => 'Delete Property Services',
                    'count' => self::count_taxonomy_terms('property_service'),
                ],
                [
                    'label' => 'Delete Room Amenities',
                    'count' => self::count_taxonomy_terms('room_amenity'),
                ],
                [
                    'label' => 'Delete Meal Plans',
                    'count' => self::count_taxonomy_terms('room_meal_plan'),
                ],
                [
                    'label' => 'Delete Experiences',
                    'count' => self::count_post_types(['experience']),
                ],
                [
                    'label' => 'Delete Rate Periods',
                    'count' => self::count_post_types(['hotel_rate_periods']),
                ],
                [
                    'label' => 'Delete Room Rates',
                    'count' => self::count_post_types(['patlis_room_rate']),
                ],
            ],
            'Reset values' => [
                ['label' => 'Basic settings'],
                ['label' => 'Contact form'],
                ['label' => 'Currency'],
                ['label' => 'Social Media'],
                ['label' => 'Opening times'],
                ['label' => 'Center Pop up'],
                ['label' => 'Notification Bar'],
                ['label' => 'Home page settings'],
                ['label' => 'Cookie Settings'],
                ['label' => 'Menu options'],
                ['label' => 'Reservation Settings'],
                ['label' => 'Accommodation - Settings'],
                ['label' => 'Kiosk Mode Settings'],
            ],
        ];
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        $sections = self::definition();
        $nonce    = wp_create_nonce('patlis_instance_tool');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Instance Tools', 'patlis-core'); ?></h1>

            <p class="description">
                <?php esc_html_e('Destructive actions. Each active button will ask for confirmation before executing.', 'patlis-core'); ?>
            </p>

            <style>
                .patlis-instance-tools-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
                    gap: 16px;
                    margin-top: 16px;
                }

                .patlis-instance-tools-card {
                    background: #fff;
                    border: 1px solid #dcdcde;
                    border-radius: 8px;
                    padding: 14px;
                }

                .patlis-instance-tools-card h2 {
                    margin: 0 0 10px;
                    font-size: 16px;
                }

                .patlis-instance-tools-list {
                    display: grid;
                    gap: 8px;
                }

                .patlis-instance-tool-button {
                    width: 100%;
                    text-align: left;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .patlis-instance-tool-badge {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 26px;
                    height: 22px;
                    padding: 0 8px;
                    border-radius: 999px;
                    background: #f0f0f1;
                    color: #1d2327;
                    font-weight: 600;
                    font-size: 12px;
                }

                .patlis-instance-tool-live {
                    border-color: #cc1818 !important;
                    color: #cc1818 !important;
                }

                .patlis-instance-tool-live:hover {
                    background: #cc1818 !important;
                    color: #fff !important;
                    border-color: #cc1818 !important;
                }

                .patlis-instance-tool-running {
                    opacity: 0.6;
                    cursor: wait !important;
                    pointer-events: none;
                }
            </style>

            <div class="patlis-instance-tools-grid">
                <?php foreach ($sections as $section_title => $actions) : ?>
                    <section class="patlis-instance-tools-card">
                        <h2><?php echo esc_html($section_title); ?></h2>

                        <div class="patlis-instance-tools-list">
                            <?php foreach ($actions as $action) :
                                $is_live = !empty($action['action']);
                            ?>
                                <button
                                    type="button"
                                    class="button patlis-instance-tool-button<?php echo $is_live ? ' patlis-instance-tool-live' : ''; ?>"
                                    <?php echo $is_live ? '' : 'disabled'; ?>
                                    <?php if ($is_live) : ?>
                                        data-action="<?php echo esc_attr($action['action']); ?>"
                                        data-label="<?php echo esc_attr($action['label'] ?? ''); ?>"
                                        data-count="<?php echo (int) ($action['count'] ?? 0); ?>"
                                    <?php endif; ?>
                                >
                                    <span><?php echo esc_html((string) ($action['label'] ?? '')); ?></span>
                                    <?php if ($section_title !== 'Reset values') : ?>
                                        <span class="patlis-instance-tool-badge"><?php echo esc_html((string) ((int) ($action['count'] ?? 0))); ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <script>
            (function () {
                var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                var nonce   = <?php echo wp_json_encode($nonce); ?>;

                document.querySelectorAll('.patlis-instance-tool-live').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var label  = btn.dataset.label  || btn.innerText.trim();
                        var count  = btn.dataset.count  || '?';
                        var action = btn.dataset.action;

                        var msg = 'Are you sure you want to run:\n"' + label + '"?\n\n' +
                                  'This will permanently delete ' + count + ' item(s).\n' +
                                  'This cannot be undone.';

                        if (!window.confirm(msg)) {
                            return;
                        }

                        btn.disabled = true;
                        btn.classList.add('patlis-instance-tool-running');

                        var formData = new FormData();
                        formData.append('action',      'patlis_instance_tool');
                        formData.append('nonce',       nonce);
                        formData.append('tool_action', action);

                        fetch(ajaxUrl, {
                            method:      'POST',
                            credentials: 'same-origin',
                            body:        formData,
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            btn.classList.remove('patlis-instance-tool-running');
                            if (data.success) {
                                alert(data.data.message);
                                window.location.reload();
                            } else {
                                var errMsg = (data.data && data.data.message) ? data.data.message : 'Unknown error.';
                                alert('Error: ' + errMsg);
                                btn.disabled = false;
                            }
                        })
                        .catch(function () {
                            btn.classList.remove('patlis-instance-tool-running');
                            alert('Request failed. Please try again.');
                            btn.disabled = false;
                        });
                    });
                });
            }());
            </script>
        </div>
        <?php
    }

    public static function handle_ajax(): void
    {
        check_ajax_referer('patlis_instance_tool', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Not allowed.'], 403);
        }

        $tool_action = isset($_POST['tool_action']) ? sanitize_key((string) $_POST['tool_action']) : '';

        switch ($tool_action) {
            case 'delete_menu_pdfs':
                $message = self::do_delete_post_type('menu_pdf');
                break;
            default:
                wp_send_json_error(['message' => 'Unknown action.'], 400);
                return;
        }

        wp_send_json_success(['message' => $message]);
    }

    private static function do_delete_post_type(string $post_type): string
    {
        $post_type = sanitize_key($post_type);

        $ids = get_posts([
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'lang'           => '',   // Polylang: bypass language filter, return all languages
        ]);

        $deleted = 0;
        foreach ($ids as $id) {
            if (wp_delete_post((int) $id, true)) {
                $deleted++;
            }
        }

        return sprintf('Deleted %d %s item(s).', $deleted, $post_type);
    }
}

add_action('wp_ajax_patlis_instance_tool', [Patlis_Admin_Page_Instance_Tools::class, 'handle_ajax']);
