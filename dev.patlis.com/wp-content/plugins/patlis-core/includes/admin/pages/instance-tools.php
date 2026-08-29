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

    private static function count_published_pages(): int
    {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(1) FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish'"
        );

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
                    'label'  => 'Delete menu categories',
                    'count'  => self::count_taxonomy_terms('menu_section'),
                    'action' => 'delete_menu_categories',
                ],
                [
                    'label'  => 'Delete menu items',
                    'count'  => self::count_post_types(['menu_item']),
                    'action' => 'delete_menu_items',
                ],
                [
                    'label'  => 'Delete menu Pdfs',
                    'count'  => self::count_post_types(['menu_pdf']),
                    'action' => 'delete_menu_pdfs',
                ],
            ],
            'Leads' => [
                [
                    'label'  => 'Delete Leads',
                    'count'  => self::count_table_rows('patlis_leads'),
                    'action' => 'delete_table_patlis_leads',
                ],
                [
                    'label'  => 'Delete Contacts',
                    'count'  => self::count_table_rows('patlis_contacts'),
                    'action' => 'delete_table_patlis_contacts',
                ],
                [
                    'label'  => 'Delete Reservations',
                    'count'  => self::count_table_rows('patlis_reservations'),
                    'action' => 'delete_table_patlis_reservations',
                ],
                [
                    'label'  => 'Delete Bookings',
                    'count'  => self::count_table_rows('patlis_bookings'),
                    'action' => 'delete_table_patlis_bookings',
                ],
            ],
            'Content' => [
                [
                    'label'  => 'Delete Slides',
                    'count'  => self::count_post_types(['slide']),
                    'action' => 'delete_slides',
                ],
                [
                    'label'  => 'Delete Services',
                    'count'  => self::count_post_types(['services', 'service']),
                    'action' => 'delete_services',
                ],
                [
                    'label'  => 'Delete Events',
                    'count'  => self::count_post_types(['events', 'event']),
                    'action' => 'delete_events',
                ],
                [
                    'label'  => 'Delete Galley',
                    'count'  => self::count_post_types(['patlis_gallery', 'gallery']),
                    'action' => 'delete_gallery',
                ],
                [
                    'label'  => 'Delete Reviews',
                    'count'  => self::count_post_types(['reviews', 'review']),
                    'action' => 'delete_reviews',
                ],
                [
                    'label'  => 'Delete Timelines (for about us)',
                    'count'  => self::count_post_types(['timeline_item', 'timelines', 'timeline']),
                    'action' => 'delete_timelines',
                ],
            ],
            'Kiosk Mode' => [
                [
                    'label'  => 'Delete Kiosk slides',
                    'count'  => self::count_post_types(['kiosk_slide']),
                    'action' => 'delete_kiosk_slides',
                ],
            ],
            'Accommodation' => [
                [
                    'label'  => 'Delete rooms',
                    'count'  => self::count_post_types(['patlis_room', 'room']),
                    'action' => 'delete_rooms',
                ],
                [
                    'label'  => 'Delete offers & Packages',
                    'count'  => self::count_post_types(['rates']),
                    'action' => 'delete_offers_packages',
                ],
                [
                    'label'  => 'Delete Property Facilities',
                    'count'  => self::count_taxonomy_terms('property_facility'),
                    'action' => 'delete_property_facilities',
                ],
                [
                    'label'  => 'Delete Property Services',
                    'count'  => self::count_taxonomy_terms('property_service'),
                    'action' => 'delete_property_services',
                ],
                [
                    'label'  => 'Delete Room Amenities',
                    'count'  => self::count_taxonomy_terms('room_amenity'),
                    'action' => 'delete_room_amenities',
                ],
                [
                    'label'  => 'Delete Meal Plans',
                    'count'  => self::count_post_types(['patlis_meal_plan']),
                    'action' => 'delete_meal_plans',
                ],
                [
                    'label'  => 'Delete Experiences',
                    'count'  => self::count_post_types(['experience']),
                    'action' => 'delete_experiences',
                ],
                [
                    'label'  => 'Delete Rate Periods',
                    'count'  => self::count_post_types(['hotel_rate_periods']),
                    'action' => 'delete_rate_periods',
                ],
                [
                    'label'  => 'Delete Room Rates',
                    'count'  => self::count_post_types(['patlis_room_rate']),
                    'action' => 'delete_room_rates',
                ],
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
        $page_count = self::count_published_pages();
        ?>
        
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

                .patlis-instance-tool-seo {
                    border-color: #2271b1 !important;
                    color: #2271b1 !important;
                }

                .patlis-instance-tool-seo:hover {
                    background: #2271b1 !important;
                    color: #fff !important;
                    border-color: #2271b1 !important;
                }

                .patlis-instance-tool-running {
                    opacity: 0.6;
                    cursor: wait !important;
                    pointer-events: none;
                }
            </style>
        <div class="wrap">
            <h1>Instance Tools</h1>

            <h2>Steps for Installation: </h2>
            <h3>aapanel</h3>
            <ol>
                <li>Wp Toolkit > Select site > Config > Config file > Copy the configuration file from dev (replace the dev.patlis.com with the appropriate domain)</li>
                <li>Files > /www/wwwroot/site-domain > wp-config.php > Set: <b style="color: red;">PATLIS_VERSION</b></li>
            </ol>
            
            <h3>Bricks</h3>
            <ol>
                <li>Settings > Custom code > Regenerate code signatures</li>
                <li>Settings > Performance > Regenerate CSS files"</li>
            </ol>

            <h3>WordPress settings</h3>
            <ol>
                <li>Settings > General Settings > set: Site Title & Tagline</li>                
                <li>Settings > General Settings > set: WordPress Address (URL) & Site Address (URL)</li>
                <li>Settings > General Settings > Set: Time zone</li>
                <li>Plugin Languages > Set: default language</li>
                <li>Settings > Permalinks > Save (to flush rewrite rules)</li>  
                <li>Plugin WP Mail SMTP: SMTP Username & Password</li>
                <li>Appearance > Menus: Create the main menu with the required structure</li>
                <li>Settings > MainWP Child: configure the child settings</li>
                <li>Users > Add new: Create a user with <b style="color: red;">Site Owner</b> role for the client (and share credentials)</li>
            </ol>
            
            <h3>Theme settings</h3>
            <ol>
                <li>Plugin Site settings >  Fill all the required fields</li>
                <li>Plugin Site settings > Languages: Set Languages Visibility (only 2 by default)</li>
                <li>Plugin Cookies: Set Google Tag Manager ID & GA4 if existing</li> 
                <li>Plugin Reservations: Set the reservation settings (Gastro & Dining Version)</li> 
                <li>Plugin Accommodation > Settings: Set the accommodation settings (Hotel version)</li>
                <li>Plugin Kiosk Mode: Set the kiosk mode settings (kiosk version)</li>
            </ol>


            <h2 style="margin-top: 4em;">Database Cleanup Tools</h2>

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
                                    <span class="patlis-instance-tool-badge"><?php echo esc_html((string) ((int) ($action['count'] ?? 0))); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <h2 style="margin-top: 4em;">SEO</h2>
            <div class="patlis-instance-tools-grid">
                <section class="patlis-instance-tools-card">
                    <h2>Rank Math Page Indexing</h2>
                    <div class="patlis-instance-tools-list">
                        <button
                            type="button"
                            class="button patlis-instance-tool-button patlis-instance-tool-seo"
                            data-action="set_page_language_indexing"
                            data-label="Set page indexing by language"
                            data-count="<?php echo (int) $page_count; ?>"
                        >
                            <span>Index active-version default pages / noindex all others</span>
                            <span class="patlis-instance-tool-badge"><?php echo (int) $page_count; ?></span>
                        </button>
                    </div>
                </section>
            </div>

            <script>
            (function () {
                var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                var nonce   = <?php echo wp_json_encode($nonce); ?>;

                                document.querySelectorAll('.patlis-instance-tool-live, .patlis-instance-tool-seo').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var label  = btn.dataset.label  || btn.innerText.trim();
                        var count  = btn.dataset.count  || '?';
                        var action = btn.dataset.action;
                                                var isSeoAction = btn.classList.contains('patlis-instance-tool-seo');
                                                var msg = isSeoAction
                                                        ? 'Are you sure you want to run:\n"' + label + '"?\n\n' +
                                                            'This will update Rank Math robots settings on ' + count + ' published page(s).\n' +
                                                            'Only active-version default-language pages will be index; all other pages will be noindex.'
                                                        : 'Are you sure you want to run:\n"' + label + '"?\n\n' +
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
            case 'delete_menu_categories':      $message = self::do_delete_taxonomy_terms('menu_section'); break;
            case 'delete_menu_items':           $message = self::do_delete_post_types(['menu_item']); break;
            case 'delete_menu_pdfs':            $message = self::do_delete_post_types(['menu_pdf']); break;
            case 'delete_table_patlis_leads':   $message = self::do_truncate_table('patlis_leads'); break;
            case 'delete_table_patlis_contacts':$message = self::do_truncate_table('patlis_contacts'); break;
            case 'delete_table_patlis_reservations': $message = self::do_truncate_table('patlis_reservations'); break;
            case 'delete_table_patlis_bookings':$message = self::do_truncate_table('patlis_bookings'); break;
            case 'delete_services':             $message = self::do_delete_post_types(['services', 'service']); break;
            case 'delete_slides':               $message = self::do_delete_post_types(['slide']); break;
            case 'delete_events':               $message = self::do_delete_post_types(['events', 'event']); break;
            case 'delete_gallery':              $message = self::do_delete_post_types(['patlis_gallery', 'gallery']); break;
            case 'delete_reviews':              $message = self::do_delete_post_types(['reviews', 'review']); break;
            case 'delete_timelines':            $message = self::do_delete_post_types(['timeline_item', 'timelines', 'timeline']); break;
            case 'delete_kiosk_slides':         $message = self::do_delete_post_types(['kiosk_slide']); break;
            case 'delete_rooms':                $message = self::do_delete_post_types(['patlis_room', 'room']); break;
            case 'delete_offers_packages':      $message = self::do_delete_post_types(['rates']); break;
            case 'delete_property_facilities':  $message = self::do_delete_taxonomy_terms('property_facility'); break;
            case 'delete_property_services':    $message = self::do_delete_taxonomy_terms('property_service'); break;
            case 'delete_room_amenities':       $message = self::do_delete_taxonomy_terms('room_amenity'); break;
            case 'delete_meal_plans':           $message = self::do_delete_post_types(['patlis_meal_plan']); break;
            case 'delete_experiences':          $message = self::do_delete_post_types(['experience']); break;
            case 'delete_rate_periods':         $message = self::do_delete_post_types(['hotel_rate_periods']); break;
            case 'delete_room_rates':           $message = self::do_delete_post_types(['patlis_room_rate']); break;
            case 'set_page_language_indexing':  $message = self::do_set_page_language_indexing(); break;
            default:
                wp_send_json_error(['message' => 'Unknown action.'], 400);
                return;
        }

        wp_send_json_success(['message' => $message]);
    }

    private static function do_set_page_language_indexing(): string
    {
        if (!defined('RANK_MATH_VERSION')) {
            return 'Rank Math must be active before updating page indexing.';
        }

        if (!function_exists('pll_default_language') || !function_exists('pll_get_post_language')) {
            return 'Polylang must be active before updating page indexing.';
        }

        $default_language = sanitize_key((string) pll_default_language('slug'));
        if ($default_language === '') {
            return 'No Polylang default language is configured.';
        }

        $page_ids = get_posts([
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'fields'           => 'ids',
            'lang'             => '',
            'suppress_filters' => true,
        ]);

        $indexed_count          = 0;
        $always_noindex_count   = 0;
        $inactive_version_count = 0;
        $other_language_count   = 0;
        $skipped_count          = 0;

        foreach ($page_ids as $page_id) {
            $language = sanitize_key((string) pll_get_post_language((int) $page_id, 'slug'));
            if ($language === '') {
                $skipped_count++;
                continue;
            }

            if ($language !== $default_language) {
                self::set_rank_math_indexing((int) $page_id, false);
                $other_language_count++;
                continue;
            }

            if (self::is_always_noindex_page((int) $page_id)) {
                self::set_rank_math_indexing((int) $page_id, false);
                $always_noindex_count++;
                continue;
            }

            $is_active_version_page = self::is_active_version_page((int) $page_id);
            self::set_rank_math_indexing((int) $page_id, $is_active_version_page);

            if ($is_active_version_page) {
                $indexed_count++;
            } else {
                $inactive_version_count++;
            }
        }

        if (class_exists('RankMath\\Sitemap\\Cache')) {
            \RankMath\Sitemap\Cache::invalidate_storage('page');
        }

        return sprintf(
            'Updated Rank Math robots settings and refreshed the Page sitemap cache: %1$d active-version default-language page(s) set to index, %2$d always-noindex default-language page(s) set to noindex, %3$d inactive-version default-language page(s) set to noindex, and %4$d other-language page(s) set to noindex. Skipped %5$d page(s) without a Polylang language.',
            $indexed_count,
            $always_noindex_count,
            $inactive_version_count,
            $other_language_count,
            $skipped_count
        );
    }

    private static function is_always_noindex_page(int $page_id): bool
    {
        $page_path = trim((string) get_page_uri($page_id), '/');
        if (in_array($page_path, ['under-construction', 'coming-soon'], true)) {
            return true;
        }

        $taxonomy = function_exists('patlis_version_get_page_template_taxonomy')
            ? patlis_version_get_page_template_taxonomy()
            : 'template';

        if (!taxonomy_exists($taxonomy)) {
            return false;
        }

        $page_template_terms = wp_get_object_terms($page_id, $taxonomy, ['fields' => 'slugs']);
        if (is_wp_error($page_template_terms) || $page_template_terms === []) {
            return false;
        }

        $always_noindex_templates = [
            'about',
            'coming-soon',
            'contact',
            'experience',
            'image-gallery',
            'kiosk',
            'reviews',
            'booking',
            'reservation',
            'text-pages',
        ];

        return array_intersect($page_template_terms, $always_noindex_templates) !== [];
    }

    private static function is_active_version_page(int $page_id): bool
    {
        $taxonomy = function_exists('patlis_version_get_page_template_taxonomy')
            ? patlis_version_get_page_template_taxonomy()
            : 'template';

        if (!taxonomy_exists($taxonomy)) {
            return true;
        }

        $page_template_terms = wp_get_object_terms($page_id, $taxonomy, ['fields' => 'slugs']);
        if (is_wp_error($page_template_terms) || $page_template_terms === []) {
            return true;
        }

        $term_map = function_exists('patlis_version_get_page_template_term_map')
            ? patlis_version_get_page_template_term_map()
            : [];

        if ($term_map === []) {
            return true;
        }

        $versioned_terms = [];
        foreach ($term_map as $version => $terms) {
            if ($version === 'all-versions') {
                continue;
            }

            $versioned_terms = array_merge($versioned_terms, $terms);
        }

        $page_template_terms = array_values(array_filter(array_map('sanitize_key', $page_template_terms)));
        $versioned_terms     = array_values(array_unique(array_filter(array_map('sanitize_key', $versioned_terms))));
        $active_terms        = function_exists('patlis_version_get_allowed_page_template_terms')
            ? patlis_version_get_allowed_page_template_terms()
            : [];

        if (array_intersect($page_template_terms, $active_terms) !== []) {
            return true;
        }

        return array_intersect($page_template_terms, $versioned_terms) === [];
    }

    private static function set_rank_math_indexing(int $post_id, bool $should_index): void
    {
        $current_robots = get_post_meta($post_id, 'rank_math_robots', true);
        $robots = is_array($current_robots) ? $current_robots : [];
        $robots = array_values(array_filter($robots, static function ($directive): bool {
            return is_string($directive) && !in_array(strtolower($directive), ['index', 'noindex'], true);
        }));
        $robots[] = $should_index ? 'index' : 'noindex';

        update_post_meta($post_id, 'rank_math_robots', $robots);
    }

    private static function do_truncate_table(string $table_suffix): string
    {
        global $wpdb;

        $table_suffix = preg_replace('/[^a-z0-9_]/', '', strtolower($table_suffix));
        $table_name   = $wpdb->prefix . $table_suffix;

        if (!self::table_exists($table_name)) {
            return 'Table not found: ' . $table_name;
        }

        $count  = (int) $wpdb->get_var("SELECT COUNT(1) FROM {$table_name}");
        $wpdb->query("DELETE FROM {$table_name}");

        return sprintf('Deleted %d row(s) from %s.', $count, $table_suffix);
    }

    private static function do_delete_post_types(array $post_types): string
    {
        $deleted = 0;
        foreach ($post_types as $post_type) {
            $post_type = sanitize_key($post_type);
            $ids = get_posts([
                'post_type'      => $post_type,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'lang'           => '',
            ]);
            foreach ($ids as $id) {
                if (wp_delete_post((int) $id, true)) $deleted++;
            }
        }
        return sprintf('Deleted %d item(s).', $deleted);
    }

    private static function do_delete_taxonomy_terms(string $taxonomy): string
    {
        global $wpdb;

        $taxonomy = sanitize_key($taxonomy);

        // Direct SQL to bypass Polylang language filtering
        $term_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
            $taxonomy
        ));

        $deleted = 0;
        foreach ($term_ids as $term_id) {
            if (!is_wp_error(wp_delete_term((int) $term_id, $taxonomy))) $deleted++;
        }
        return sprintf('Deleted %d term(s) from %s.', $deleted, $taxonomy);
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
