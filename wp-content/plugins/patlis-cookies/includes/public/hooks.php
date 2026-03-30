<?php
if (!defined('ABSPATH')) exit;

// Helper: get multilingual text for current language (with fallback)
function patlis_cookies_get_text($arr) {
    if (!is_array($arr)) return (string)$arr;
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
    if ($lang && isset($arr[$lang]) && $arr[$lang] !== '') return $arr[$lang];
    foreach ($arr as $v) if ($v !== '') return $v;
    return '';
}

function patlis_cookies_is_banner_enabled(): bool {

    // Αν για κάποιο λόγο δεν έχει φορτώσει το integrations.php με τα defaults
    if (!function_exists('patlis_cookies_integrations_defaults')) {
        return true; // fallback = ON
    }

    $opt = wp_parse_args(
        get_option('patlis_cookies_integrations', []),
        patlis_cookies_integrations_defaults()
    );

    return !empty($opt['enable_banner']);
}

add_action('init', function () {
    if (is_admin()) return;
    if (!patlis_cookies_is_banner_enabled()) return;

    add_action('wp_enqueue_scripts', 'patlis_cookies_enqueue_assets');
    add_action('wp_footer', 'add_cookie_banner');

    add_filter('the_content', 'rename_youtube_iframe_in_content', 20);
    add_filter('the_content', 'rename_external_script_src_in_content', 20);
});


function patlis_cookies_enqueue_assets() {

    wp_enqueue_script(
        'patlis-cookies-js',
        PATLIS_COOKIES_URL . 'assets/cookies.js',
        [],
        filemtime(PATLIS_COOKIES_PATH . 'assets/cookies.js'),
        false // load in head
    );

    wp_enqueue_style(
        'patlis-cookies-css',
        PATLIS_COOKIES_URL . 'assets/cookies.css',
        [],
        filemtime(PATLIS_COOKIES_PATH . 'assets/cookies.css')
    );
}

function add_cookie_banner() {

    if (!function_exists('patlis_cookies_text_defaults')) {
        return;
    }

    $t = wp_parse_args(
        get_option('patlis_cookies_texts', []),
        patlis_cookies_text_defaults()
    );

    ?>
    <!-- cookies modal -->
    <div class="ex-modal" id="cookie-banner" aria-modal="true" role="dialog" aria-labelledby="cookie-banner">
        <div class="ex-modal-dialog ">
            <div class="ex-modal-content">
                <div class="ex-modal-body">
                    <div id="cookie-title">
                        <span aria-hidden="true"><?php echo esc_html(patlis_cookies_get_text($t['title'])); ?></span>
                    </div>

                    <p id="cookie-description" aria-hidden="true">
                        <?php echo esc_html(patlis_cookies_get_text($t['description'])); ?>
                    </p>
                </div>

                <div class="ex-modal-footer">
                    <div class="ex-row ex-w-100">
                        <div class="ex-btn-columns ex-p-1">
                            <button type="button"class="ex-btn ex-btn-warning ex-btn-block"
                                    onclick="acceptAll()"><?php echo esc_html(patlis_cookies_get_text($t['btn_allow_all'])); ?></button>
                        </div>
                        <div class="ex-btn-columns ex-p-1">
                            <button type="button" class="ex-btn ex-btn-outline-primary ex-btn-block"
                                    onclick="getCookiehtml()"><?php echo esc_html(patlis_cookies_get_text($t['btn_customize'])); ?> »</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /cookies modal -->

    <script>
    function getCookiehtml(){
        document.getElementById("cookie-banner").style.display = "none";
        try { document.getElementById("cookie-settings").remove(); } catch (e) {}

        fetch("<?php echo esc_js(PATLIS_COOKIES_URL . 'assets/cookies.html'); ?>")
            .then(response => response.text())
            .then(html => {
                document.body.insertAdjacentHTML("beforeend", html);

                $settingsExist = true;

                document.getElementById("preferences-cookies").checked = preferencesCookies;
                document.getElementById("statistics-cookies").checked = statisticsCookies;
                document.getElementById("marketing-cookies").checked = marketingCookies;

                document.getElementById("cookie-settings").style.display = "flex";

                // Pass texts to cookies.html (based on YOUR current ids)
                try {
                    const map = {
                        // Footer buttons in cookie-settings modal
                        "allow-all-btn": <?php echo wp_json_encode(patlis_cookies_get_text($t['btn_allow_all'])); ?>,
                        "apply-btn": <?php echo wp_json_encode(patlis_cookies_get_text($t['btn_save_close'])); ?>,

                        // Category names (NEW)
                        "catname-necessary": <?php echo wp_json_encode(patlis_cookies_get_text($t['catname_necessary'])); ?>,
                        "catname-statistics": <?php echo wp_json_encode(patlis_cookies_get_text($t['catname_statistics'])); ?>,
                        "catname-marketing": <?php echo wp_json_encode(patlis_cookies_get_text($t['catname_marketing'])); ?>,
                        "catname-preferences": <?php echo wp_json_encode(patlis_cookies_get_text($t['catname_preferences'])); ?>,
                        "catname-unclassified": <?php echo wp_json_encode(patlis_cookies_get_text($t['catname_unclassified'])); ?>,

                        // Category descriptions
                        "necessary-descr": <?php echo wp_json_encode(patlis_cookies_get_text($t['cat_necessary'])); ?>,
                        "statistic-descr": <?php echo wp_json_encode(patlis_cookies_get_text($t['cat_statistics'])); ?>,
                        "marketing-descr": <?php echo wp_json_encode(patlis_cookies_get_text($t['cat_marketing'])); ?>,
                        "preference-descr": <?php echo wp_json_encode(patlis_cookies_get_text($t['cat_preferences'])); ?>,
                        "unclassified-descr": <?php echo wp_json_encode(patlis_cookies_get_text($t['cat_unclassified'])); ?>
                    };

                    Object.keys(map).forEach(id => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.textContent = map[id] || "";
                    });
                } catch(e){}
            })
            .catch(err => showMessage(err));
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $loadBasicModal !== "undefined" && $loadBasicModal && typeof showBasicModal === "function") {
            showBasicModal();
        }

        document.querySelectorAll('a[href="#cookies"]').forEach(function(el) {
            el.addEventListener("click", function(e) {
                userClick = true;
                e.preventDefault();
                getCookiehtml();
            });
        });
    });
    </script>
    <?php
}

add_filter('the_content', 'rename_youtube_iframe_in_content');
function rename_youtube_iframe_in_content($content) {
    $pattern = '/<iframe([^>]*)\s+(src|data-src)=["\']((https?:)?\/\/(www\.)?(youtube(?:-nocookie)?\.com|youtu\.be|player.vimeo\.com)[^"\']*)["\']([^>]*)><\/iframe>/i';
    $replacement = '<iframe$1 blocked-$2="$3"$7></iframe>';
    $replacement .= '<noscript><iframe $2="$3"$1$7></iframe></noscript>';
    return preg_replace($pattern, $replacement, $content);
}

add_filter('the_content', 'rename_external_script_src_in_content');
function rename_external_script_src_in_content($content) {
    $domain = preg_quote(get_main_domain($_SERVER['HTTP_HOST']), '/');
    $pattern = '/<script([^>]*)\s+(src|data-src)=["\']((https?:)?\/\/(?!' . $domain . ')[^"\']+)["\']([^>]*)><\/script>/i';
    $replacement = '<script$1 blocked-$2="$3" type="text/plain" $4></script>';
    return preg_replace($pattern, $replacement, $content);
}

function get_main_domain($host) {
    $parts = explode('.', $host);
    $count = count($parts);
    if ($count > 2) { return $parts[$count - 2] . '.' . $parts[$count - 1]; }
    return $host;
}
