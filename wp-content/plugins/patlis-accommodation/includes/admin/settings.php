<?php
if (!defined('ABSPATH')) exit;

class Patlis_Accommodation_Admin_Settings
{
    public static function init(): void
    {
        add_action('admin_post_patlis_accommodation_save_settings', [__CLASS__, 'save_settings']);
    }

    public static function save_settings(): void
    {
        if (!current_user_can('patlis_manage')) {
            wp_die('Forbidden');
        }

        if (
            empty($_POST['patlis_accommodation_nonce']) ||
            !wp_verify_nonce($_POST['patlis_accommodation_nonce'], 'patlis_accommodation_save_settings')
        ) {
            wp_die('Invalid nonce');
        }

        $booking_mode = isset($_POST['booking_mode']) ? (int) $_POST['booking_mode'] : 0;
        if (!in_array($booking_mode, [0, 1, 2, 3], true)) {
            $booking_mode = 0;
        }

        $data = [
            'booking_mode'         => $booking_mode,
            'booking_email'        => isset($_POST['booking_email']) ? sanitize_email((string)$_POST['booking_email']) : '',
            'booking_days_before'  => isset($_POST['booking_days_before']) ? max(0, (int)$_POST['booking_days_before']) : 0,
            'booking_redirect_url' => isset($_POST['booking_redirect_url']) ? esc_url_raw((string)$_POST['booking_redirect_url']) : '',
            'booking_3party_code'  => isset($_POST['booking_3party_code']) ? wp_kses_post((string)$_POST['booking_3party_code']) : '',
            'rooms_per_page'       => isset($_POST['rooms_per_page']) ? max(0, (int)$_POST['rooms_per_page']) : 0,
            'show_prices'          => !empty($_POST['show_prices']) ? 1 : 0,
            'prices_text'          => isset($_POST['prices_text']) ? sanitize_text_field((string)$_POST['prices_text']) : '',
        ];

        // cleanup based on mode (ίδια λογική με το JS)
        if ($booking_mode !== 1) {
            $data['booking_email'] = '';
            $data['booking_days_before'] = 0;
        }
        if ($booking_mode !== 2) {
            $data['booking_3party_code'] = '';
        }
        if ($booking_mode !== 3) {
            $data['booking_redirect_url'] = '';
        }

        update_option(PATLIS_ACCOMMODATION_SETTINGS_KEY, $data);

        wp_safe_redirect(add_query_arg(
            ['page' => 'patlis-accommodation-settings', 'updated' => '1'],
            admin_url('admin.php')
        ));
        exit;
    }
}
