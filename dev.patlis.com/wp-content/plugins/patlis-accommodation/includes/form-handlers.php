<?php
if (!defined('ABSPATH')) exit;

add_action('bricks/form/custom_action', function ($form) {
    $fields = $_POST;

    if (($fields['lead_type'] ?? '') === 'Booking Form') {
        patlis_acc_handle_booking_form($fields);
    }
});

function patlis_acc_get_default_post_title(int $post_id): string
{
    $default_post_id = $post_id;

    if (function_exists('pll_default_language') && function_exists('pll_get_post')) {
        $default_lang = pll_default_language('slug');
        if (is_string($default_lang) && $default_lang !== '') {
            $translated_id = (int) pll_get_post($post_id, $default_lang);
            if ($translated_id > 0) {
                $default_post_id = $translated_id;
            }
        }
    }

    return (string) get_the_title($default_post_id);
}

function patlis_acc_parse_check_in_date(string $value): ?DateTimeImmutable
{
    $timezone = wp_timezone();

    foreach (['d.m.Y', 'Y-m-d'] as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value, $timezone);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date;
        }
    }

    return null;
}


/* insert the lead into the database and send emails */
function patlis_acc_handle_booking_form(array $fields): void
{
    global $wpdb;

    $room_id = (int) ($fields['room_id'] ?? $fields['room'] ?? 0);
    $meal_plan_id = (int) ($fields['diet_type_id'] ?? 0);
    $offer_id = (int) ($fields['offers_package'] ?? 0);
    $nights = max(1, (int) ($fields['nights'] ?? 0));
    $adults = max(1, (int) ($fields['adults'] ?? 1));
    $children = max(0, (int) ($fields['children'] ?? 0));
    $infants = max(0, (int) ($fields['infants'] ?? 0));
    $name = sanitize_text_field($fields['customer_name'] ?? '');
    $email = sanitize_email($fields['customer_email'] ?? '');
    $phone = sanitize_text_field($fields['customer_phone'] ?? '');
    $notes = sanitize_textarea_field($fields['customer_notes'] ?? '');
    $check_in = patlis_acc_parse_check_in_date(sanitize_text_field($fields['check_in'] ?? ''));

    if (
        $room_id <= 0 ||
        get_post_type($room_id) !== 'patlis_room' ||
        get_post_status($room_id) !== 'publish' ||
        !$check_in ||
        !is_email($email)
    ) {
        return;
    }

    $minimum_check_in = (new DateTimeImmutable('today', wp_timezone()))
        ->modify('+' . max(0, (int) patlis_acc_get_setting_value('booking_days_before')) . ' days');
    if ($check_in < $minimum_check_in) {
        return;
    }

    if ($meal_plan_id > 0 && (get_post_type($meal_plan_id) !== 'patlis_meal_plan' || get_post_status($meal_plan_id) !== 'publish')) {
        return;
    }

    $offer_title = '';
    if ($offer_id > 0) {
        if (get_post_type($offer_id) !== 'rates' || get_post_status($offer_id) !== 'publish') {
            return;
        }

        $linked_room_ids = function_exists('patlis_parse_linked_rooms_ids')
            ? patlis_parse_linked_rooms_ids(get_post_meta($offer_id, 'package_linked_rooms', true))
            : [];
        $room_translation_ids = function_exists('patlis_get_post_translation_ids')
            ? patlis_get_post_translation_ids($room_id)
            : [$room_id];

        if (!empty($linked_room_ids) && empty(array_intersect($linked_room_ids, $room_translation_ids))) {
            return;
        }

        $offer_title = patlis_acc_get_default_post_title($offer_id);
    }

    $source_url = sanitize_text_field($fields['source_url'] ?? '');
    $language = sanitize_key($fields['langCode'] ?? $fields['language'] ?? $fields['lang'] ?? '');
    $device_type = sanitize_text_field($fields['device_type'] ?? '');
    $traffic = [];
    $traffic_raw = wp_unslash($fields['traffic_info'] ?? '');
    if ($traffic_raw !== '') {
        $decoded = json_decode($traffic_raw, true);
        if (is_array($decoded)) {
            $traffic = $decoded;
        }
    }

    $consent_json = '';
    $cookie_preferences = 0;
    $cookie_statistics = 0;
    $cookie_marketing = 0;
    $cookie = $_COOKIE['patlis-cookie'] ?? '';
    if ($cookie !== '') {
        $consent = json_decode(stripslashes($cookie), true);
        if (is_array($consent)) {
            $consent_json = wp_json_encode($consent);
            $cookie_preferences = !empty($consent['preferences']) ? 1 : 0;
            $cookie_statistics = !empty($consent['statistics']) ? 1 : 0;
            $cookie_marketing = !empty($consent['marketing']) ? 1 : 0;
        }
    }

    $uuid = wp_generate_uuid4();
    $wpdb->insert(
        $wpdb->prefix . 'patlis_leads',
        [
            'uuid' => $uuid,
            'lead_type' => 'Booking Form',
            'visitor_id' => sanitize_text_field($traffic['visitor_id'] ?? ''),
            'language' => $language,
            'device_type' => $device_type,
            'utm_source' => sanitize_text_field($traffic['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($traffic['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($traffic['utm_campaign'] ?? ''),
            'utm_content' => sanitize_text_field($traffic['utm_content'] ?? ''),
            'utm_term' => sanitize_text_field($traffic['utm_term'] ?? ''),
            'referrer' => sanitize_text_field($traffic['referrer'] ?? ''),
            'landing_page' => sanitize_text_field($traffic['landing_page'] ?? ''),
            'source_url' => $source_url,
            'gclid' => sanitize_text_field($traffic['gclid'] ?? ''),
            'gbraid' => sanitize_text_field($traffic['gbraid'] ?? ''),
            'wbraid' => sanitize_text_field($traffic['wbraid'] ?? ''),
            'msclkid' => sanitize_text_field($traffic['msclkid'] ?? ''),
            'fbclid' => sanitize_text_field($traffic['fbclid'] ?? ''),
            'consent_json' => $consent_json,
            'cookie_preferences' => $cookie_preferences,
            'cookie_statistics' => $cookie_statistics,
            'cookie_marketing' => $cookie_marketing,
        ]
    );

    $booking_inserted = $wpdb->insert(
        $wpdb->prefix . 'patlis_bookings',
        [
            'room_id' => $room_id,
            'check_in' => $check_in->format('Y-m-d'),
            'check_out' => $check_in->modify('+' . $nights . ' days')->format('Y-m-d'),
            'nights' => $nights,
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'meal_plan_id' => $meal_plan_id ?: null,
            'offer_package_id' => $offer_id ?: null,
            'offer_package_title' => $offer_title ?: null,
            'status' => 0,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'customer_notes' => $notes ?: null,
            'lead_uuid' => $uuid,
        ]
    );

    patlis_acc_send_booking_notification(
        $room_id,
        $check_in,
        $nights,
        $adults,
        $children,
        $infants,
        $meal_plan_id,
        $offer_title,
        $name,
        $email,
        $phone,
        $notes,
        $language
    );
    patlis_acc_send_booking_confirmation($name, $email, $language);
}
/* Send notification email to the admin */

function patlis_acc_send_booking_notification(
    int $room_id,
    DateTimeImmutable $check_in,
    int $nights,
    int $adults,
    int $children,
    int $infants,
    int $meal_plan_id,
    string $offer_title,
    string $name,
    string $email,
    string $phone,
    string $notes,
    string $language
): void {
    $settings = function_exists('patlis_accommodation_get_settings')
        ? patlis_accommodation_get_settings()
        : [];
    $to = sanitize_email((string) ($settings['booking_email'] ?? ''));
    $subject = sanitize_text_field((string) ($settings['booking_email_subject'] ?? ''));
    if ($to === '' || $subject === '') return;

    $translations = function_exists('patlis_get_translations') ? patlis_get_translations() : [];
    $default_lang = function_exists('patlis_get_default_language') ? patlis_get_default_language() : '';
    $t = static function (string $key) use ($translations, $default_lang): string {
        $value = $translations[$key][$default_lang] ?? '';
        return $value !== '' ? $value : $key;
    };

    $check_out = $check_in->modify('+' . $nights . ' days');
    $room_title = patlis_acc_get_default_post_title($room_id);
    $meal_plan_title = $meal_plan_id > 0 ? patlis_acc_get_default_post_title($meal_plan_id) : '';
    $rows = [
        'patlis_acc_booking_room' => (string) $room_title,              /*ok*/
        'patlis_acc_checkin_date' => $check_in->format('d.m.Y'),        /*ok*/
        'patlis_acc_booking_check_out' => $check_out->format('d.m.Y'),  /*ok*/
        'patlis_acc_nights' => (string) $nights,                        /*ok*/
        'patlis_room_adults' => (string) $adults,                       /*ok*/
        'patlis_acc_children' => (string) $children,                    /*ok*/
        'patlis_acc_infants' => (string) $infants,                      /*ok*/
        'patlis_acc_meal_plan_optional' => $meal_plan_title,            /*ok*/
        'patlis_acc_offer_label' => $offer_title,                       /*ok*/
        'patlis_acc_name' => $name,                                     /*ok*/
        'patlis_acc_email' => $email,                                 /*ok*/    
        'patlis_acc_phone' => $phone,                                 /*ok*/   
        'patlis_form_language' => $language !== '' ? strtoupper($language) : '',
    ];

    $phone_href = preg_replace('/[^0-9+]/', '', $phone);
    $body = '';
    foreach ($rows as $label => $value) {
        $display_value = nl2br(esc_html($value !== '' ? $value : '-'));
        if ($label === 'patlis_acc_phone' && $phone_href !== '') {
            $display_value = '<a href="tel:' . esc_attr($phone_href) . '">' . esc_html($phone) . '</a>';
        }
        $body .= esc_html($t($label)) . ': <strong>' . $display_value . '</strong><br>';
    }

    if ($notes !== '') {
        $body .= '<br>' . nl2br(esc_html($notes));
    }

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($name) . ' <' . $to . '>',
        'Reply-To: ' . esc_html($name) . ' <' . $email . '>',
    ];

    wp_mail($to, $subject, $body, $headers);
}

/*  Send confirmation email to the customer*/
function patlis_acc_send_booking_confirmation(string $name, string $email, string $language): void
{
    $settings = function_exists('patlis_accommodation_get_settings')
        ? patlis_accommodation_get_settings()
        : [];
    $from_email = sanitize_email((string) ($settings['booking_email'] ?? ''));
    $company_name = class_exists('Patlis_Core')
        ? trim((string) Patlis_Core::get_basic('company_name', ''))
        : '';
    $subject = patlis_acc_get_confirmation_text('confirm_subject', $language);
    $body = patlis_acc_get_confirmation_text('confirm_body', $language);

    if ($from_email === '' || $subject === '' || $body === '') {
        return;
    }

    $sender_name = $company_name !== '' ? $company_name : get_bloginfo('name');
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($sender_name) . ' <' . $from_email . '>',
        'Reply-To: ' . esc_html($sender_name) . ' <' . $from_email . '>',
    ];

    wp_mail($email, $subject, nl2br(wp_kses_post($body)), $headers);
}