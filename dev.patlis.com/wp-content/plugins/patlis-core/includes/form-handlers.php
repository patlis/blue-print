<?php
if (!defined('ABSPATH')) exit;

/**
 * Bricks custom form action handler.
 * Saves form submissions to patlis_leads + patlis_contacts tables.
 */
add_action('bricks/form/custom_action', function ($form) {
    $fields = $_POST;

    // ── Identify form by lead_type hidden field ───────────────────────────────
    if (($fields['lead_type'] ?? '') === 'Contact Form') {
        patlis_core_handle_contact_form($fields);
    }

    if (($fields['lead_type'] ?? '') === 'Reservation Form') {
        patlis_core_handle_reservation_form($fields);
    }
});

/**
 * Contact form → patlis_leads + patlis_contacts
 */
function patlis_core_handle_contact_form(array $fields): void {
    global $wpdb;

    // Form fields
    $name    = sanitize_text_field($fields['client_name']   ?? '');
    $phone   = sanitize_text_field($fields['client_phone']  ?? '');
    $email   = sanitize_email($fields['client_email']       ?? '');
    $message = sanitize_textarea_field($fields['client_mesage'] ?? '');

    if (!is_email($email)) return;

    // Tracking fields
    $source_url  = sanitize_text_field($fields['source_url']  ?? '');
    $language    = sanitize_text_field($fields['language']    ?? '');
    $device_type = sanitize_text_field($fields['device_type'] ?? '');

    // Parse traffic_info JSON
    $traffic = [];
    $traffic_raw = wp_unslash($fields['traffic_info'] ?? '');
    if ($traffic_raw !== '') {
        $decoded = json_decode($traffic_raw, true);
        if (is_array($decoded)) {
            $traffic = $decoded;
        }
    }

    // Parse consent cookie
    $consent_json       = '';
    $cookie_references  = 0;
    $cookie_statistics  = 0;
    $cookie_marketing   = 0;

    $patlis_cookie = $_COOKIE['patlis-cookie'] ?? '';
    if ($patlis_cookie !== '') {
        $consent = json_decode(stripslashes($patlis_cookie), true);
        if (is_array($consent)) {
            $consent_json      = wp_json_encode($consent);
            $cookie_references = !empty($consent['preferences']) ? 1 : 0;
            $cookie_statistics = !empty($consent['statistics'])  ? 1 : 0;
            $cookie_marketing  = !empty($consent['marketing'])   ? 1 : 0;
        }
    }

    // Generate linking UUID
    $uuid = wp_generate_uuid4();

    // ── INSERT patlis_leads ───────────────────────────────────────────────────
    $wpdb->insert(
        $wpdb->prefix . 'patlis_leads',
        [
            'uuid'              => $uuid,
            'lead_type'         => sanitize_text_field($fields['lead_type'] ?? ''),
            'visitor_id'        => sanitize_text_field($traffic['visitor_id']   ?? ''),
            'language'          => $language,
            'device_type'       => $device_type,
            'utm_source'        => sanitize_text_field($traffic['utm_source']   ?? ''),
            'utm_medium'        => sanitize_text_field($traffic['utm_medium']   ?? ''),
            'utm_campaign'      => sanitize_text_field($traffic['utm_campaign'] ?? ''),
            'utm_content'       => sanitize_text_field($traffic['utm_content']  ?? ''),
            'utm_term'          => sanitize_text_field($traffic['utm_term']     ?? ''),
            'referrer'          => sanitize_text_field($traffic['referrer']     ?? ''),
            'landing_page'      => sanitize_text_field($traffic['landing_page'] ?? ''),
            'source_url'        => $source_url,
            'gclid'             => sanitize_text_field($traffic['gclid']        ?? ''),
            'gbraid'            => sanitize_text_field($traffic['gbraid']       ?? ''),
            'wbraid'            => sanitize_text_field($traffic['wbraid']       ?? ''),
            'msclkid'           => sanitize_text_field($traffic['msclkid']      ?? ''),
            'fbclid'            => sanitize_text_field($traffic['fbclid']       ?? ''),
            'consent_json'      => $consent_json,
            'cookie_references' => $cookie_references,
            'cookie_statistics' => $cookie_statistics,
            'cookie_marketing'  => $cookie_marketing,
        ],
        ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%d']
    );

    if ($wpdb->last_error) return;

    // ── INSERT patlis_contacts ────────────────────────────────────────────────
    $wpdb->insert(
        $wpdb->prefix . 'patlis_contacts',
        [
            'lead_uuid' => $uuid,
            'name'      => $name,
            'email'     => $email,
            'phone'     => $phone,
            'message'   => $message,
            'status'    => 0,
        ],
        ['%s','%s','%s','%s','%s','%d']
    );

    if ($wpdb->last_error) return;

    // ── Send notification email ───────────────────────────────────────────────
    patlis_core_send_contact_notification($name, $phone, $email, $message);
    patlis_core_send_contact_confirmation($name, $email);
}

/**
 * Send contact form notification email to owner.
 */
function patlis_core_send_contact_notification(string $name, string $phone, string $email, string $message): void {
    $to      = trim((string) Patlis_Core::get_basic('contact_form_recipient_email', ''));
    $subject = trim((string) Patlis_Core::get_basic('contact_form_email_subject', ''));

    // Labels in default language
    $translations = function_exists('patlis_get_translations')     ? patlis_get_translations()     : [];
    $default_lang = function_exists('patlis_get_default_language') ? patlis_get_default_language() : '';

    $t = static function (string $key) use ($translations, $default_lang): string {
        $val = $translations[$key][$default_lang] ?? '';
        return $val !== '' ? $val : $key;
    };

    $body  = '<strong>' . esc_html($t('patlis_form_your_name'))  . ':</strong> ' . esc_html($name)  . '<br>';
    $body .= '<strong>' . esc_html($t('patlis_form_your_phone')) . ':</strong> ' . esc_html($phone) . '<br>';
    $body .= '<strong>' . esc_html($t('patlis_form_email'))      . ':</strong> ' . esc_html($email) . '<br><br>';
    $body .= nl2br(esc_html($message));

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($name) . ' <' . $to . '>',
        'Reply-To: ' . esc_html($name) . ' <' . $email . '>',
    ];

    wp_mail($to, $subject, $body, $headers);
}

/**
 * Send confirmation email to the client (in their language).
 */
function patlis_core_send_contact_confirmation(string $name, string $email): void {
    $from_email   = trim((string) Patlis_Core::get_basic('contact_form_recipient_email', ''));
    $company_name = trim((string) Patlis_Core::get_basic('company_name', ''));

    $subject = function_exists('patlis_get_contact_confirm_subject') ? patlis_get_contact_confirm_subject() : '';
    $body    = function_exists('patlis_get_contact_confirm_body')    ? nl2br(patlis_get_contact_confirm_body()) : '';

    if (empty($subject) && empty($body)) return;

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($company_name) . ' <' . $from_email . '>',
        'Reply-To: ' . esc_html($company_name) . ' <' . $from_email . '>',
    ];

    wp_mail($email, $subject, $body, $headers);
}



/**
 * Reservation form → patlis_leads + patlis_reservations
 */
function patlis_core_handle_reservation_form(array $fields): void {
    global $wpdb;

    // Form fields
    $name    = sanitize_text_field($fields['client_name']        ?? '');
    $phone   = sanitize_text_field($fields['client_phone']       ?? '');
    $email   = sanitize_email($fields['client_email']            ?? '');
    $message = sanitize_textarea_field($fields['client_mesage']  ?? '');
    $date    = sanitize_text_field($fields['reservation_date']   ?? '');
    $guests  = max(1, (int) ($fields['persons']                  ?? 1));

    if (!is_email($email)) return;

    // Tracking fields
    $source_url  = sanitize_text_field($fields['source_url']  ?? '');
    $language    = sanitize_text_field($fields['language']    ?? '');
    $device_type = sanitize_text_field($fields['device_type'] ?? '');

    // Parse traffic_info JSON
    $traffic = [];
    $traffic_raw = wp_unslash($fields['traffic_info'] ?? '');
    if ($traffic_raw !== '') {
        $decoded = json_decode($traffic_raw, true);
        if (is_array($decoded)) {
            $traffic = $decoded;
        }
    }

    // Parse consent cookie
    $consent_json       = '';
    $cookie_references  = 0;
    $cookie_statistics  = 0;
    $cookie_marketing   = 0;

    $patlis_cookie = $_COOKIE['patlis-cookie'] ?? '';
    if ($patlis_cookie !== '') {
        $consent = json_decode(stripslashes($patlis_cookie), true);
        if (is_array($consent)) {
            $consent_json      = wp_json_encode($consent);
            $cookie_references = !empty($consent['preferences']) ? 1 : 0;
            $cookie_statistics = !empty($consent['statistics'])  ? 1 : 0;
            $cookie_marketing  = !empty($consent['marketing'])   ? 1 : 0;
        }
    }

    // Generate linking UUID
    $uuid = wp_generate_uuid4();

    // ── INSERT patlis_leads ───────────────────────────────────────────────────
    $wpdb->insert(
        $wpdb->prefix . 'patlis_leads',
        [
            'uuid'              => $uuid,
            'lead_type'         => sanitize_text_field($fields['lead_type'] ?? ''),
            'visitor_id'        => sanitize_text_field($traffic['visitor_id']   ?? ''),
            'language'          => $language,
            'device_type'       => $device_type,
            'utm_source'        => sanitize_text_field($traffic['utm_source']   ?? ''),
            'utm_medium'        => sanitize_text_field($traffic['utm_medium']   ?? ''),
            'utm_campaign'      => sanitize_text_field($traffic['utm_campaign'] ?? ''),
            'utm_content'       => sanitize_text_field($traffic['utm_content']  ?? ''),
            'utm_term'          => sanitize_text_field($traffic['utm_term']     ?? ''),
            'referrer'          => sanitize_text_field($traffic['referrer']     ?? ''),
            'landing_page'      => sanitize_text_field($traffic['landing_page'] ?? ''),
            'source_url'        => $source_url,
            'gclid'             => sanitize_text_field($traffic['gclid']        ?? ''),
            'gbraid'            => sanitize_text_field($traffic['gbraid']       ?? ''),
            'wbraid'            => sanitize_text_field($traffic['wbraid']       ?? ''),
            'msclkid'           => sanitize_text_field($traffic['msclkid']      ?? ''),
            'fbclid'            => sanitize_text_field($traffic['fbclid']       ?? ''),
            'consent_json'      => $consent_json,
            'cookie_references' => $cookie_references,
            'cookie_statistics' => $cookie_statistics,
            'cookie_marketing'  => $cookie_marketing,
        ],
        ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%d']
    );

    if ($wpdb->last_error) return;

    // ── INSERT patlis_reservations ────────────────────────────────────────────
    $wpdb->insert(
        $wpdb->prefix . 'patlis_reservations',
        [
            'lead_uuid'        => $uuid,
            'name'             => $name,
            'email'            => $email,
            'phone'            => $phone,
            'message'          => $message,
            'reservation_date' => $date,
            'guests'           => $guests,
            'status'           => 0,
        ],
        ['%s','%s','%s','%s','%s','%s','%d','%d']
    );

    if ($wpdb->last_error) return;

    // ── Send emails ───────────────────────────────────────────────────────────
    patlis_core_send_reservation_notification($name, $phone, $email, $message, $date, $guests);
    patlis_core_send_reservation_confirmation($name, $email);
}

/**
 * Send reservation notification email to owner.
 */
function patlis_core_send_reservation_notification(string $name, string $phone, string $email, string $message, string $date, int $guests): void {
    $res_settings = function_exists('patlis_reservations_get_settings') ? patlis_reservations_get_settings() : [];
    $to      = trim((string) ($res_settings['notify_email']  ?? ''));
    $subject = trim((string) ($res_settings['email_subject'] ?? ''));

    // Labels in default language
    $translations = function_exists('patlis_get_translations')     ? patlis_get_translations()     : [];
    $default_lang = function_exists('patlis_get_default_language') ? patlis_get_default_language() : '';

    $t = static function (string $key) use ($translations, $default_lang): string {
        $val = $translations[$key][$default_lang] ?? '';
        return $val !== '' ? $val : $key;
    };

    $body  = esc_html($t('patlis_reservation_name'))    . ': <strong>' . esc_html($name) . '</strong><br>';
    $body .= esc_html($t('patlis_reservation_phone'))   . ': <strong>' . esc_html($phone) . '</strong><br>';
    $body .= esc_html($t('patlis_reservation_email'))   . ': <strong>' . esc_html($email) . '</strong><br>';
    $body .= esc_html($t('patlis_reservation_date'))    . ': <strong>' . esc_html($date) . '</strong><br>';
    $body .= esc_html($t('patlis_reservation_persons')) . ': <strong>' . esc_html((string) $guests) . '</strong><br><br>';
    $body .= nl2br(esc_html($message));

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($name) . ' <' . $to . '>',
        'Reply-To: ' . esc_html($name) . ' <' . $email . '>',
    ];

    wp_mail($to, $subject, $body, $headers);
}

/**
 * Send reservation confirmation email to the client.
 */
function patlis_core_send_reservation_confirmation(string $name, string $email): void {
    $res_settings = function_exists('patlis_reservations_get_settings') ? patlis_reservations_get_settings() : [];
    $from_email   = trim((string) ($res_settings['notify_email'] ?? ''));
    $company_name = trim((string) Patlis_Core::get_basic('company_name', ''));

    $subject  = function_exists('patlis_reservations_get_confirm_subject') ? patlis_reservations_get_confirm_subject() : '';
    $template = function_exists('patlis_reservations_get_confirm_body') ? patlis_reservations_get_confirm_body() : '';

    $body = nl2br($template);


    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($company_name) . ' <' . $from_email . '>',
        'Reply-To: ' . esc_html($company_name) . ' <' . $from_email . '>',
    ];

    wp_mail($email, $subject, $body, $headers);
}
