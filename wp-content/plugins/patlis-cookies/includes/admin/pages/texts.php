<?php
if (!defined('ABSPATH')) exit;

/**
 * Patlis Cookies – Admin Page: Texts
 *
 * Option name: patlis_cookies_texts
 * Settings group: patlis_cookies_texts_group
 */

/**
 * Default texts (standalone όπως στο screenshot).
 */
function patlis_cookies_text_defaults(): array {
    return [
        // Banner
        'title'       => 'This website uses cookies',
        'description' => 'We use cookies to personalise content and ads, to provide social media features and to analyse traffic on our website. We also share information about your use of our website with our social media, advertising and analytics partners. Our partners may combine this information with other data you have provided to them or that they have collected as part of your use of the services.',

        // Buttons
        'btn_allow_all'      => 'Allow All Cookies',
        'btn_save_close'     => 'Apply and Close',
        'btn_customize'      => 'Cookie Settings',

        // Category names (NEW)
        'catname_necessary'    => 'Necessary cookies',
        'catname_statistics'   => 'Statistics cookies',
        'catname_marketing'    => 'Marketing cookies',
        'catname_preferences'  => 'Preference cookies',
        'catname_unclassified' => 'Unclassified cookies',

        // Category descriptions
        'cat_necessary'    => 'Necessary cookies help make a website usable by enabling basic functions such as page navigation and access to secure areas of the website. The website cannot function properly without these cookies.',
        'cat_statistics'   => 'Statistics cookies help website owners understand how visitors interact with websites by collecting and reporting information anonymously.',
        'cat_marketing'    => 'Marketing cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user and therefore more valuable for publishers and third-party advertisers.',
        'cat_preferences'  => 'Preference cookies enable a website to remember information that changes the way the website behaves or looks, such as your preferred language or the region you are in.',
        'cat_unclassified' => 'Unclassified cookies are cookies that we are currently in the process of classifying, together with the providers of individual cookies.',
    ];
}

function patlis_cookies_sanitize_texts($input): array {

    $defaults = patlis_cookies_text_defaults();
    $out      = $defaults;
    // Helper: sanitize multilingual value
    $sanitize_multilang = function($val, $type = 'text') {
        if (is_array($val)) {
            $out = [];
            foreach ($val as $lang => $v) {
                $lang = is_string($lang) ? sanitize_key($lang) : '';
                if ($lang === '') continue;
                $out[$lang] = $type === 'textarea' ? sanitize_textarea_field($v) : sanitize_text_field($v);
            }
            return $out;
        } else {
            return $type === 'textarea' ? sanitize_textarea_field($val) : sanitize_text_field($val);
        }
    };

    // Category names (NEW)
    foreach ([
        'catname_necessary',
        'catname_statistics',
        'catname_marketing',
        'catname_preferences',
        'catname_unclassified',
    ] as $catname_key) {
        if (array_key_exists($catname_key, $input)) {
            $out[$catname_key] = $sanitize_multilang($input[$catname_key], 'text');
        }
    }

    if (!is_array($input)) {
        return $out;
    }

    // Helper: sanitize multilingual value
    $sanitize_multilang = function($val, $type = 'text') {
        if (is_array($val)) {
            $out = [];
            foreach ($val as $lang => $v) {
                $lang = is_string($lang) ? sanitize_key($lang) : '';
                if ($lang === '') continue;
                $out[$lang] = $type === 'textarea' ? sanitize_textarea_field($v) : sanitize_text_field($v);
            }
            return $out;
        } else {
            return $type === 'textarea' ? sanitize_textarea_field($val) : sanitize_text_field($val);
        }
    };

    // Banner
    if (array_key_exists('title', $input)) {
        $out['title'] = $sanitize_multilang($input['title'], 'text');
    }
    if (array_key_exists('description', $input)) {
        $out['description'] = $sanitize_multilang($input['description'], 'textarea');
    }

    // Buttons
    if (array_key_exists('btn_allow_all', $input)) {
        $out['btn_allow_all'] = $sanitize_multilang($input['btn_allow_all'], 'text');
    }
    if (array_key_exists('btn_save_close', $input)) {
        $out['btn_save_close'] = $sanitize_multilang($input['btn_save_close'], 'text');
    }
    if (array_key_exists('btn_customize', $input)) {
        $out['btn_customize'] = $sanitize_multilang($input['btn_customize'], 'text');
    }

    // Category descriptions
    foreach ([
        'cat_necessary',
        'cat_statistics',
        'cat_marketing',
        'cat_preferences',
        'cat_unclassified',
    ] as $cat_key) {
        if (array_key_exists($cat_key, $input)) {
            $out[$cat_key] = $sanitize_multilang($input[$cat_key], 'textarea');
        }
    }

    return $out;
}


/**
 * Render page callback (called from your menu.php).
 */
function patlis_cookies_render_texts_page() {
    if (!current_user_can('patlis_manage')) return;

        $defaults = patlis_cookies_text_defaults();
        $t        = get_option('patlis_cookies_texts', []);

        // Helper: get language slugs (active for user)
        $slugs = [];
        if (function_exists('patlis_get_effective_language_slugs_for_current_user')) {
                $slugs = patlis_get_effective_language_slugs_for_current_user();
        } elseif (function_exists('pll_languages_list')) {
                $slugs = pll_languages_list(['fields' => 'slug']);
        }
        if (!is_array($slugs) || empty($slugs)) {
                $slugs = ['default'];
        }

        // Build language labels
        $languages = [];
        foreach ($slugs as $slug) {
                if (!is_string($slug) || $slug === '') continue;
                $languages[$slug] = strtoupper($slug);
        }
        if (empty($languages)) {
                $languages = ['default' => 'Default'];
        }

        // Helper: get value for key/lang
        $val = function (string $key, string $lang) use ($t, $defaults) {
                if (isset($t[$key]) && is_array($t[$key]) && isset($t[$key][$lang])) {
                        return $t[$key][$lang];
                }
                if (isset($defaults[$key])) {
                        return $defaults[$key];
                }
                return '';
        };

        ?>
        <style>
            .form-table th,
            .form-table td {
                padding-top: 5px;
                padding-bottom: 0px;
            }
            .patlis-lang-label { font-weight: 600; margin-bottom: 4px; }
            .patlis-lang-block { margin-bottom: 16px; }
        </style>
        <div class="wrap">
            <h1>Cookies – Texts</h1>
            <?php if (!empty($_GET['patlis_saved'])): ?>
                <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="patlis_cookies_save_texts">
                <?php wp_nonce_field('patlis_cookies_save_texts'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Banner title</th>
                        <td>
                            <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                <div class="patlis-lang-block">
                                    <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                    <input type="text" class="regular-text" name="patlis_cookies_texts[title][<?php echo esc_attr($lang_slug); ?>]" value="<?php echo esc_attr($val('title', $lang_slug)); ?>">
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Banner description</th>
                        <td>
                            <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                <div class="patlis-lang-block">
                                    <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                    <textarea class="large-text" rows="3" name="patlis_cookies_texts[description][<?php echo esc_attr($lang_slug); ?>]"><?php echo esc_textarea($val('description', $lang_slug)); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Btn: Allow all:</th>
                        <td>
                            <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                <div class="patlis-lang-block">
                                    <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                    <input type="text" class="regular-text" name="patlis_cookies_texts[btn_allow_all][<?php echo esc_attr($lang_slug); ?>]" value="<?php echo esc_attr($val('btn_allow_all', $lang_slug)); ?>">
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Btn: Save & close:</th>
                        <td>
                            <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                <div class="patlis-lang-block">
                                    <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                    <input type="text" class="regular-text" name="patlis_cookies_texts[btn_save_close][<?php echo esc_attr($lang_slug); ?>]" value="<?php echo esc_attr($val('btn_save_close', $lang_slug)); ?>">
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Btn: Customize:</th>
                        <td>
                            <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                <div class="patlis-lang-block">
                                    <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                    <input type="text" class="regular-text" name="patlis_cookies_texts[btn_customize][<?php echo esc_attr($lang_slug); ?>]" value="<?php echo esc_attr($val('btn_customize', $lang_slug)); ?>">
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Category names</th>
                        <td>
                            <?php foreach ([
                                'catname_necessary' => 'Necessary',
                                'catname_statistics' => 'Statistics',
                                'catname_marketing' => 'Marketing',
                                'catname_preferences' => 'Preferences',
                                'catname_unclassified' => 'Unclassified',
                            ] as $catname_key => $cat_label): ?>
                                <p><strong style="font-size:1.1rem"><?php echo esc_html($cat_label); ?></strong><br>
                                    <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                        <div class="patlis-lang-block">
                                            <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                            <input type="text" class="regular-text" name="patlis_cookies_texts[<?php echo esc_attr($catname_key); ?>][<?php echo esc_attr($lang_slug); ?>]" value="<?php echo esc_attr($val($catname_key, $lang_slug)); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </p>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Category descriptions</th>
                        <td>
                            <?php foreach ([
                                'cat_necessary' => 'Necessary',
                                'cat_statistics' => 'Statistics',
                                'cat_marketing' => 'Marketing',
                                'cat_preferences' => 'Preferences',
                                'cat_unclassified' => 'Unclassified',
                            ] as $cat_key => $cat_label): ?>
                                <p><strong><?php echo esc_html($cat_label); ?></strong><br>
                                    <?php foreach ($languages as $lang_slug => $lang_label): ?>
                                        <div class="patlis-lang-block">
                                            <div class="patlis-lang-label"><?php echo esc_html($lang_label); ?></div>
                                            <textarea class="large-text" rows="2" name="patlis_cookies_texts[<?php echo esc_attr($cat_key); ?>][<?php echo esc_attr($lang_slug); ?>]"><?php echo esc_textarea($val($cat_key, $lang_slug)); ?></textarea>
                                        </div>
                                    <?php endforeach; ?>
                                </p>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save changes'); ?>
            </form>
        </div>
        <?php
}