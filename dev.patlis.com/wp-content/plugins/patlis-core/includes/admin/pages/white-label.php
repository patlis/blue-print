<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_White_Label
{
    private const DEFAULT_RESELLER_DOMAIN = 'https://patlis.com';
    private const DEFAULT_RESELLER_COMPANY_NAME = 'PATLIS.COM';

    public static function sanitize($input): array
    {
        $in = is_array($input) ? $input : [];

        $domain = isset($in['reseller_domain']) ? sanitize_text_field((string) $in['reseller_domain']) : '';
        $domain = trim($domain);
        if ($domain !== '' && !preg_match('~^https?://~i', $domain)) {
            $domain = 'https://' . ltrim($domain, '/');
        }
        $domain = esc_url_raw($domain);
        if ($domain === '') {
            $domain = self::DEFAULT_RESELLER_DOMAIN;
        }

        $company_name = isset($in['reseller_company_name']) ? sanitize_text_field((string) $in['reseller_company_name']) : '';
        if ($company_name === '') {
            $company_name = self::DEFAULT_RESELLER_COMPANY_NAME;
        }

        return [
            'reseller_domain' => $domain,
            'reseller_company_name' => $company_name,
        ];
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        $opt = get_option(Patlis_Core::OPTION_WHITE_LABEL, []);
        if (!is_array($opt)) {
            $opt = [];
        }

        $reseller_domain = (string) ($opt['reseller_domain'] ?? self::DEFAULT_RESELLER_DOMAIN);
        if ($reseller_domain === '') {
            $reseller_domain = self::DEFAULT_RESELLER_DOMAIN;
        }

        $reseller_company_name = (string) ($opt['reseller_company_name'] ?? self::DEFAULT_RESELLER_COMPANY_NAME);
        if ($reseller_company_name === '') {
            $reseller_company_name = self::DEFAULT_RESELLER_COMPANY_NAME;
        }
        ?>
        <div class="wrap">
            <h1>White label settings</h1>

            <?php if (!empty($_GET['patlis_saved'])): ?>
                <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="patlis_save_white_label">
                <?php wp_nonce_field('patlis_save_white_label'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="patlis_reseller_domain">Reseller domain</label></th>
                        <td>
                            <input
                                id="patlis_reseller_domain"
                                type="text"
                                class="regular-text"
                                placeholder="https://patlis.com"
                                name="<?php echo esc_attr(Patlis_Core::OPTION_WHITE_LABEL); ?>[reseller_domain]"
                                value="<?php echo esc_attr($reseller_domain); ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="patlis_reseller_company_name">Reseller company name</label></th>
                        <td>
                            <input
                                id="patlis_reseller_company_name"
                                type="text"
                                class="regular-text"
                                name="<?php echo esc_attr(Patlis_Core::OPTION_WHITE_LABEL); ?>[reseller_company_name]"
                                value="<?php echo esc_attr($reseller_company_name); ?>"
                            >
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save settings'); ?>
            </form>
        </div>
        <?php
    }
}
