<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Menu_Admin_Page_Options
{
    public const OPTION_NAME = 'patlis_menu_options';

    public static function get_display_modes(): array
    {
        $modes = [
            'normal'   => __('Normal', 'patlis-menu'),
            'centered' => __('Centered', 'patlis-menu'),
        ];

        $modes = apply_filters('patlis_menu_display_modes', $modes);

        $clean = [];
        foreach ($modes as $key => $label) {
            $k = is_string($key) ? trim($key) : '';
            $l = is_string($label) ? $label : '';
            if ($k !== '' && $l !== '') {
                $clean[$k] = $l;
            }
        }

        if (!isset($clean['normal'])) {
            $clean = ['normal' => __('Normal', 'patlis-menu')] + $clean;
        }

        return $clean;
    }

    public static function sanitize($input): array
    {
        $in = is_array($input) ? $input : [];
        $out = [];

        $out['show_catalog_prices'] = !empty($in['show_catalog_prices']) ? 1 : 0;

        $modes = self::get_display_modes();
        $mode  = isset($in['display_mode']) ? (string) $in['display_mode'] : 'normal';
        $out['display_mode'] = array_key_exists($mode, $modes) ? $mode : 'normal';

        $out['show_veg_filters'] = !empty($in['show_veg_filters']) ? 1 : 0;
        $out['show_allergies'] = !empty($in['show_allergies']) ? 1 : 0;

        $out['allergies_description_html'] = self::sanitize_allergies_description_input($in);

        return $out;
    }

    protected static function sanitize_allergies_description_input(array $in): array
    {
        $raw = $in['allergies_description_html'] ?? [];
    
        // Ξεκινάμε από τα ήδη αποθηκευμένα, ώστε οι hidden γλώσσες να μείνουν άθικτες
        $existing = self::allergies_description_html_all();
        $out = is_array($existing) ? $existing : [];
    
        if (is_string($raw)) {
            $default_lang = self::get_default_language();
            $out[$default_lang] = wp_kses_post($raw);
            return $out;
        }
    
        if (!is_array($raw)) {
            return $out;
        }
    
        // Μόνο τις γλώσσες που βλέπει ο τρέχων χρήστης επιτρέπεται να αλλάξει
        $editable_langs = array_keys(self::get_languages());
    
        foreach ($raw as $lang => $value) {
            $lang = is_string($lang) ? sanitize_key($lang) : '';
            if ($lang === '') {
                continue;
            }
    
            if (!empty($editable_langs) && !in_array($lang, $editable_langs, true)) {
                continue;
            }
    
            $out[$lang] = is_string($value) ? wp_kses_post($value) : '';
        }
    
        return $out;
    }

    public static function get_options(): array
    {
        $opt = get_option(self::OPTION_NAME, []);
        return is_array($opt) ? $opt : [];
    }

    public static function show_catalog_prices(): bool
    {
        $opt = self::get_options();
        return !isset($opt['show_catalog_prices']) || !empty($opt['show_catalog_prices']);
    }

    public static function show_veg_filters(): bool
    {
        $opt = self::get_options();
        return !empty($opt['show_veg_filters']);
    }

    public static function show_allergies(): bool
    {
        $opt = self::get_options();
        return !isset($opt['show_allergies']) || !empty($opt['show_allergies']);
    }

    public static function allergies_description_html(): string
    {
        $all = self::allergies_description_html_all();

        if (!$all) {
            return '';
        }

        $current_lang = self::get_current_language();
        if (isset($all[$current_lang]) && is_string($all[$current_lang]) && $all[$current_lang] !== '') {
            return $all[$current_lang];
        }

        $default_lang = self::get_default_language();
        if (isset($all[$default_lang]) && is_string($all[$default_lang]) && $all[$default_lang] !== '') {
            return $all[$default_lang];
        }

        foreach ($all as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    public static function allergies_description_html_all(): array
    {
        $opt = self::get_options();
        $raw = $opt['allergies_description_html'] ?? [];

        if (is_string($raw)) {
            $default_lang = self::get_default_language();
            return [$default_lang => $raw];
        }

        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $lang => $value) {
            $lang = is_string($lang) ? sanitize_key($lang) : '';
            if ($lang === '') {
                continue;
            }

            $out[$lang] = is_string($value) ? $value : '';
        }

        return $out;
    }

    public static function display_mode(): string
    {
        $opt   = self::get_options();
        $modes = self::get_display_modes();

        $mode = isset($opt['display_mode']) ? (string) $opt['display_mode'] : 'normal';
        return array_key_exists($mode, $modes) ? $mode : 'normal';
    }

    public static function is_centered_mode(): bool
    {
        return self::display_mode() === 'centered';
    }

    protected static function get_languages(): array
    {
        $slugs = [];
    
        if (function_exists('patlis_get_effective_language_slugs_for_current_user')) {
            $slugs = patlis_get_effective_language_slugs_for_current_user();
        } elseif (function_exists('pll_languages_list')) {
            $slugs = pll_languages_list(['fields' => 'slug']);
        }
    
        if (is_array($slugs) && !empty($slugs)) {
            $languages = [];
    
            foreach ($slugs as $slug) {
                if (!is_string($slug) || $slug === '') {
                    continue;
                }
    
                $languages[$slug] = strtoupper($slug);
            }
    
            if (!empty($languages)) {
                return $languages;
            }
        }
    
        return ['default' => 'Default'];
    }

    protected static function get_current_language(): string
    {
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');
            if (is_string($lang) && $lang !== '') {
                return $lang;
            }
        }

        return self::get_default_language();
    }

    protected static function get_default_language(): string
    {
        if (function_exists('pll_default_language')) {
            $lang = pll_default_language('slug');
            if (is_string($lang) && $lang !== '') {
                return $lang;
            }
        }

        $languages = self::get_languages();
        $first = array_key_first($languages);

        return is_string($first) && $first !== '' ? $first : 'default';
    }

    public static function render(): void
    {
        if (!current_user_can('patlis_manage')) return;

        $opt       = self::get_options();
        $modes     = self::get_display_modes();
        $languages = self::get_languages();

        $mode = isset($opt['display_mode']) ? (string) $opt['display_mode'] : 'normal';
        if (!array_key_exists($mode, $modes)) {
            $mode = 'normal';
        }

        $allergies_all = self::allergies_description_html_all();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('My Menu – Options', 'patlis-menu'); ?></h1>

            <?php if (!empty($_GET['patlis_saved'])): ?>
                <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="patlis_menu_save_options">
                <?php wp_nonce_field('patlis_menu_save_options'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Show prices in the product catalog', 'patlis-menu'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_catalog_prices]"
                                       value="1" <?php checked(!empty($opt['show_catalog_prices']) || !isset($opt['show_catalog_prices'])); ?>>
                                <?php esc_html_e('Yes', 'patlis-menu'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If disabled, price tags will output empty values.', 'patlis-menu'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Display mode', 'patlis-menu'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[display_mode]">
                                <?php foreach ($modes as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($mode, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <p class="description">
                                <?php esc_html_e('Choose how the catalog layout is displayed.', 'patlis-menu'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Show Vegetarian & Vegan filter', 'patlis-menu'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_veg_filters]"
                                       value="1" <?php checked(!empty($opt['show_veg_filters'])); ?>>
                                <?php esc_html_e('Yes', 'patlis-menu'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If enabled, vegetarian/vegan filter buttons can be shown in the catalog.', 'patlis-menu'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Show allergies', 'patlis-menu'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_allergies]"
                                       value="1" <?php checked(!empty($opt['show_allergies']) || !isset($opt['show_allergies'])); ?>>
                                <?php esc_html_e('Yes', 'patlis-menu'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('If disabled, allergies output will be empty.', 'patlis-menu'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Allergies description (HTML)', 'patlis-menu'); ?></th>
                        <td>
                            <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                <?php
                                $content = isset($allergies_all[$lang_slug]) && is_string($allergies_all[$lang_slug])
                                    ? $allergies_all[$lang_slug]
                                    : '';

                                $editor_id = 'patlis_menu_allergies_description_html_' . sanitize_key($lang_slug);
                                ?>
                                <div style="margin-bottom: 24px;">
                                    <div><strong><?php echo esc_html($lang_label); ?></strong></div>
                                    <?php
                                    wp_editor(
                                        $content,
                                        $editor_id,
                                        [
                                            'textarea_name' => self::OPTION_NAME . '[allergies_description_html][' . $lang_slug . ']',
                                            'textarea_rows' => 6,
                                            'media_buttons' => false,
                                            'teeny'         => true,
                                            'quicktags'     => true,
                                        ]
                                    );
                                    ?>
                                </div>
                            <?php endforeach; ?>

                            <p class="description">
                                <?php esc_html_e('Shown inside the allergies popup. Add one version per language. Allowed HTML is sanitized.', 'patlis-menu'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save', 'patlis-menu')); ?>
            </form>
        </div>
        <?php
    }
}