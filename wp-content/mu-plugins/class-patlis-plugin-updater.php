<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Patlis_Plugin_Updater')) {
    class Patlis_Plugin_Updater
    {
        private string $plugin_file;
        private string $plugin_basename;
        private string $slug;
        private string $version;
        private string $update_url;

        public function __construct(string $plugin_file, string $slug, string $version, string $update_url)
        {
            $this->plugin_file     = $plugin_file;
            $this->plugin_basename = plugin_basename($plugin_file);
            $this->slug            = $slug;
            $this->version         = $version;
            $this->update_url      = (string) apply_filters('patlis_updater_update_url', $update_url, $slug);

            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
            add_action('upgrader_process_complete', [$this, 'clear_cache'], 10, 2);
        }

        private function get_cache_key(): string
        {
            return 'patlis_updater_' . md5($this->update_url);
        }

        public function clear_cache($upgrader, array $hook_extra): void
        {
            if (($hook_extra['type'] ?? '') !== 'plugin') {
                return;
            }

            $updated_plugins = $hook_extra['plugins'] ?? [];

            if (is_array($updated_plugins) && in_array($this->plugin_basename, $updated_plugins, true)) {
                delete_site_transient($this->get_cache_key());
            }
        }

        private function normalize_remote_data(array $data): ?array
        {
            $slug    = isset($data['slug']) ? trim((string) $data['slug']) : '';
            $version = isset($data['version']) ? trim((string) $data['version']) : '';
            $package = isset($data['download_url']) ? trim((string) $data['download_url']) : '';

            if ($slug === '' || $slug !== $this->slug) {
                return null;
            }

            if ($version === '' || $package === '' || !wp_http_validate_url($package)) {
                return null;
            }

            return [
                'name'         => isset($data['name']) ? (string) $data['name'] : $this->slug,
                'slug'         => $slug,
                'version'      => $version,
                'download_url' => $package,
                'homepage'     => isset($data['homepage']) ? (string) $data['homepage'] : '',
                'author'       => isset($data['author']) ? (string) $data['author'] : 'Patlis Ioannis',
                'tested'       => isset($data['tested']) ? (string) $data['tested'] : '',
                'requires'     => isset($data['requires']) ? (string) $data['requires'] : '',
                'requires_php' => isset($data['requires_php']) ? (string) $data['requires_php'] : '',
                'last_updated' => isset($data['last_updated']) ? (string) $data['last_updated'] : '',
                'sections'     => (isset($data['sections']) && is_array($data['sections']))
                    ? $data['sections']
                    : [
                        'description' => '',
                        'changelog'   => '',
                    ],
                'icons'        => (isset($data['icons']) && is_array($data['icons'])) ? $data['icons'] : [],
                'banners'      => (isset($data['banners']) && is_array($data['banners'])) ? $data['banners'] : [],
            ];
        }

        private function get_remote_data(bool $force = false): ?array
        {
            $cache_key = $this->get_cache_key();

            if (!$force) {
                $cached = get_site_transient($cache_key);

                if (is_array($cached)) {
                    return $cached;
                }
            }

            $headers = [
                'Accept' => 'application/json',
            ];

            $headers = (array) apply_filters('patlis_updater_request_headers', $headers, $this->slug, $this->update_url);

            $request_args = [
                'timeout'    => 15,
                'headers'    => $headers,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
            ];

            $request_args = (array) apply_filters('patlis_updater_request_args', $request_args, $this->slug, $this->update_url);

            $response = wp_remote_get($this->update_url, $request_args);

            if (is_wp_error($response)) {
                return null;
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                return null;
            }

            $body = wp_remote_retrieve_body($response);

            if (!is_string($body) || $body === '') {
                return null;
            }

            $decoded = json_decode($body, true);

            if (!is_array($decoded)) {
                return null;
            }

            $data = $this->normalize_remote_data($decoded);

            if (!$data) {
                return null;
            }

            set_site_transient($cache_key, $data, HOUR_IN_SECONDS);

            return $data;
        }

        public function check_for_update($transient)
        {
            if (!is_object($transient)) {
                return $transient;
            }

            $force_check = is_admin() && isset($_GET['force-check']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $remote      = $this->get_remote_data($force_check);

            if (!$remote) {
                return $transient;
            }

            if (version_compare($remote['version'], $this->version, '>')) {
                $update               = new stdClass();
                $update->slug         = $this->slug;
                $update->plugin       = $this->plugin_basename;
                $update->new_version  = $remote['version'];
                $update->package      = $remote['download_url'];
                $update->url          = $remote['homepage'];
                $update->tested       = $remote['tested'];
                $update->requires     = $remote['requires'];
                $update->requires_php = $remote['requires_php'];
                $update->icons        = $remote['icons'];
                $update->banners      = $remote['banners'];

                $transient->response[$this->plugin_basename] = $update;
                unset($transient->no_update[$this->plugin_basename]);

                return $transient;
            }

            $item               = new stdClass();
            $item->slug         = $this->slug;
            $item->plugin       = $this->plugin_basename;
            $item->new_version  = $this->version;
            $item->url          = $remote['homepage'];
            $item->package      = '';
            $item->icons        = $remote['icons'];
            $item->banners      = $remote['banners'];
            $item->requires     = $remote['requires'];
            $item->tested       = $remote['tested'];
            $item->requires_php = $remote['requires_php'];

            $transient->no_update[$this->plugin_basename] = $item;
            unset($transient->response[$this->plugin_basename]);

            return $transient;
        }

        public function plugin_info($result, $action, $args)
        {
            if ($action !== 'plugin_information') {
                return $result;
            }

            if (empty($args->slug) || $args->slug !== $this->slug) {
                return $result;
            }

            $remote = $this->get_remote_data(false);

            if (!$remote) {
                return $result;
            }

            $info                = new stdClass();
            $info->name          = $remote['name'];
            $info->slug          = $this->slug;
            $info->version       = $remote['version'];
            $info->author        = $remote['author'];
            $info->homepage      = $remote['homepage'];
            $info->requires      = $remote['requires'];
            $info->tested        = $remote['tested'];
            $info->requires_php  = $remote['requires_php'];
            $info->download_link = $remote['download_url'];
            $info->last_updated  = $remote['last_updated'];
            $info->sections      = $remote['sections'];
            $info->icons         = $remote['icons'];
            $info->banners       = $remote['banners'];

            return $info;
        }
    }
}

if (!function_exists('patlis_register_plugin_updater')) {
    function patlis_register_plugin_updater(string $plugin_file, string $slug, string $version): void
    {
        if (!class_exists('Patlis_Plugin_Updater')) {
            return;
        }

        $update_url = 'https://updates.patlis.com/api/' . $slug . '.json';

        $update_url = (string) apply_filters(
            'patlis_plugin_update_url',
            $update_url,
            $plugin_file,
            $slug,
            $version
        );

        new Patlis_Plugin_Updater($plugin_file, $slug, $version, $update_url);
    }
}

add_filter('plugin_row_meta', function ($links, $file, $plugin_data) {
    $allowed = [
        'patlis-cookies/patlis-cookies.php',
        'patlis-core/patlis-core.php',
        'patlis-menu/patlis-menu.php',
        'patlis-reservations/patlis-reservations.php',
        'patlis-accommodation/patlis-accommodation.php',
    ];

    if (!in_array($file, $allowed, true)) {
        return $links;
    }

    $slug = dirname($file);

    $links[] = sprintf(
        '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
        esc_url(
            self_admin_url(
                'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode($slug) . '&TB_iframe=true&width=600&height=550'
            )
        ),
        esc_attr(sprintf(__('More information about %s', 'patlis-core'), $plugin_data['Name'] ?? $slug)),
        esc_html__('View details', 'patlis-core')
    );

    return $links;
}, 10, 3);