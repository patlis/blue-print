<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form action="options.php" method="post">
        <?php
        settings_fields('patlis_kiosk_settings');
        do_settings_sections('patlis_kiosk_settings');
        submit_button('Save Settings');
        ?>
    </form>

    <p>
        <strong>Note:</strong> The kiosk cookie is automatically set whenever someone visits the <code>/kiosk/</code> landing page. Lifetime is 10 years.<br>
        To forcefully disable it on a device, clear your browser cookies.<br>
        <br>
        Inactivity redirect URL: <code><?php echo esc_html(home_url('/kiosk/')); ?></code><br>
        Plugin image trigger / buttons target: <code><?php echo esc_html(patlis_kiosk_get_target_url()); ?></code><br>
        Language links helper (for custom query loop): <code>patlis_kiosk_get_target_links_by_language()</code>
    </p>
</div>
