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
        <strong>Note:</strong> The kiosk cookie is automatically set whenever someone visits the <code>/kiosk/</code> <br>
        To forcefully disable it on a device, clear your browser cookies.<br>
        <br>
        The browser refresh every 15 minutes to get the latest settings and slides. 
    </p>
</div>
