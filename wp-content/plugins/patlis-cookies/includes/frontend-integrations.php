<?php
if (!defined('ABSPATH')) exit;

/**
 * Frontend integrations loader
 * - Loads GTM / GA4 / FB Pixel based ONLY on options (enabled + id not empty)
 * - Consent (send / not send) is handled by your cookies.js via gtag consent mode
 */

add_action('wp_footer', 'patlis_cookies_output_integrations', 5);
function patlis_cookies_output_integrations() {

    // If you want: respect "Enable banner" as master switch for *everything*.
    // If banner OFF => don't load integrations either.
    $opt = get_option('patlis_cookies_integrations', []);
    if (empty($opt['enable_banner'])) {
      // Hide the cookie settings link if banner is not enabled
      echo "<script>document.addEventListener('DOMContentLoaded',function(){
      var l=document.getElementById('cookie-settings-link');if(l)l.style.display='none';});</script>";
      return;
    }

    $gtm_enabled   = !empty($opt['gtm_enabled']);
    $gtm_id        = trim($opt['gtm_id'] ?? '');

    $ga4_enabled   = !empty($opt['ga4_enabled']);
    $ga4_id        = trim($opt['ga4_id'] ?? '');

    $pixel_enabled = !empty($opt['pixel_enabled']);
    $pixel_id      = trim($opt['pixel_id'] ?? '');

    // Nothing to load
    if ((!$gtm_enabled || $gtm_id === '') && (!$ga4_enabled || $ga4_id === '') && (!$pixel_enabled || $pixel_id === '')) {
        return;
    }

    // Ensure dataLayer exists (your cookies.js also does this, but harmless)
    echo "<script>window.dataLayer = window.dataLayer || [];</script>\n";

    /* -------------------------
     * GA4 (Google tag / gtag.js)
     * ------------------------- */
    if ($ga4_enabled && $ga4_id !== '') : ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4_id); ?>"></script>
        <script>
          // cookies.js already defines gtag() + consent default/update.
          // Here we only ensure GA4 is configured. Consent will decide if it sends.
          if (typeof gtag === 'function') {
            gtag('js', new Date());
            gtag('config', <?php echo json_encode($ga4_id); ?>);
          }
        </script>
    <?php endif; ?>

    <?php
    /* -------------------------
     * GTM
     * ------------------------- */
    if ($gtm_enabled && $gtm_id !== '') : ?>
        <script>
          (function(w,d,s,l,i){
            w[l]=w[l]||[];
            w[l].push({'gtm.start': new Date().getTime(), event:'gtm.js'});
            var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),
                dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;
            j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
            f.parentNode.insertBefore(j,f);
          })(window,document,'script','dataLayer',<?php echo json_encode($gtm_id); ?>);
        </script>
    <?php endif; ?>

    <?php
    /* -------------------------
     * Facebook Pixel
     * -------------------------
     * NOTE (GDPR): If you truly want "load always", this does it.
     * But strict compliance usually loads it only after marketing consent.
     */
    if ($pixel_enabled && $pixel_id !== '') : ?>
        <script>
          !function(f,b,e,v,n,t,s){
            if(f.fbq)return; n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n; n.push=n; n.loaded=!0; n.version='2.0';
            n.queue=[]; t=b.createElement(e); t.async=!0;
            t.src=v; s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)
          }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');

          fbq('init', <?php echo json_encode($pixel_id); ?>);
          fbq('track', 'PageView');
        </script>
    <?php endif;
}
