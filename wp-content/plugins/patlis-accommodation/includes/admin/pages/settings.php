<?php
if (!defined('ABSPATH')) exit;

function patlis_accommodation_render_settings_page() {
    if (!current_user_can('patlis_manage')) return;

    $s = function_exists('patlis_accommodation_get_settings')
        ? patlis_accommodation_get_settings()
        : [];

    ?>
    <div class="wrap">
        <h1>Accommodation – Settings</h1>

        <?php if (!empty($_GET['updated'])): ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('patlis_accommodation_save_settings', 'patlis_accommodation_nonce'); ?>
          <input type="hidden" name="action" value="patlis_accommodation_save_settings">


            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Booking Mode</th>
                    <td>
                        <label>
                            <select name="booking_mode" id="patlis-acc-booking-mode">
                              <option value="0" <?php selected((int)($s['booking_mode'] ?? 0), 0); ?>>Switched off (0)</option>
                              <option value="1" <?php selected((int)($s['booking_mode'] ?? 0), 1); ?>>With our simple system (1)</option>
                              <option value="2" <?php selected((int)($s['booking_mode'] ?? 0), 2); ?>>Code from the other company (2)</option>
                              <option value="3" <?php selected((int)($s['booking_mode'] ?? 0), 3); ?>>Redirection (3)</option>
                            </select>
                        </label>
                         <p><code>[patlis_acc_booking_mode]</code> 0/1/2/3</p>
                    </td>
                </tr>

                <tr id="patlis-acc-row-email">
                    <th scope="row">Booking Email </th>
                    <td>
                        <input id="patlis-acc-booking-email" type="email" class="regular-text" name="booking_email" value="<?php echo esc_attr($s['booking_email'] ?? ''); ?>">
                        <p><code>[patlis_acc_booking_email]</code></p>
                    </td>
                </tr>

                <tr id="patlis-acc-row-days-before">
                    <th scope="row">Days Before</th>
                    <td>
                        <input id="patlis-acc-booking-days-before" type="number" min="0" class="small-text" name="booking_days_before" value="<?php echo (int)($s['booking_days_before'] ?? 0); ?>">
                        <p><code>[patlis_acc_booking_days_before]</code></p>
                    </td>
                </tr>

                <tr id="patlis-acc-row-redirect-url">
                    <th scope="row">Redirect URL</th>
                    <td>
                        <input id="patlis-acc-booking-redirect-url" type="text" class="regular-text" name="booking_redirect_url" value="<?php echo esc_attr($s['booking_redirect_url'] ?? ''); ?>">
                        <p><code>[patlis_acc_booking_redirect_url]</code></p>
                    </td>
                </tr>

                <tr id="patlis-acc-row-3party-code">
                    <th scope="row">3rd-party Code</th>
                    <td>
                        <textarea id="patlis-acc-booking-3party-code" name="booking_3party_code" rows="6" class="large-text code"><?php echo esc_textarea($s['booking_3party_code'] ?? ''); ?></textarea>
                        <p><code>[patlis_acc_booking_3party_code]</code></p>
                    </td>
                </tr>
                
                <tr>
                  <th scope="row">Rooms per page</th>
                  <td>
                    <input type="number" min="0" class="small-text" name="rooms_per_page"
                           value="<?php echo (int)($s['rooms_per_page'] ?? 0); ?>">
                    <p class="description">0 = show all rooms.</p>
                    <p><code>[patlis_acc_rooms_per_page]</code></p>
                  </td>
                </tr>
                
                <tr>
                  <th scope="row">Show prices</th>
                  <td>
                    <label>
                      <input type="checkbox" name="show_prices" value="1" <?php checked(!empty($s['show_prices'] ?? 0)); ?>>
                      Enable prices
                    </label>
                    <p><code>[patlis_acc_show_prices]</code> 1/0</p>
                  </td>
                </tr>
                
                <tr>
                  <th scope="row">Prices text (when prices are hidden)</th>
                  <td>
                    <input type="text" class="regular-text" name="prices_text" value="<?php echo esc_attr($s['prices_text'] ?? ''); ?>">
                    <p class="description">Example: Upon request</p>
                    <p><code>[patlis_acc_prices_text]</code></p>
                  </td>
                </tr>
   
                
                
            </table>

            <p class="submit">
                <button type="submit" name="patlis_accommodation_settings_submit" class="button button-primary">Save Settings</button>
            </p>
        </form>
        
        <script>
            (function () {
              const modeEl = document.getElementById('patlis-acc-booking-mode');
              if (!modeEl) return;
            
              const rowEmail    = document.getElementById('patlis-acc-row-email');
              const rowDays     = document.getElementById('patlis-acc-row-days-before');
              const rowRedirect = document.getElementById('patlis-acc-row-redirect-url');
              const row3party   = document.getElementById('patlis-acc-row-3party-code');
            
              const emailEl     = document.getElementById('patlis-acc-booking-email');
              const daysEl      = document.getElementById('patlis-acc-booking-days-before');
              const redirectEl  = document.getElementById('patlis-acc-booking-redirect-url');
              const codeEl      = document.getElementById('patlis-acc-booking-3party-code');
            
              function setRowVisible(row, visible) {
                if (!row) return;
                row.style.display = visible ? '' : 'none';
              }
            
              function setRequired(el, required) {
                if (!el) return;
                if (required) el.setAttribute('required', 'required');
                else el.removeAttribute('required');
              }
            
              function refresh() {
                const mode = parseInt(modeEl.value || '0', 10);
            
                // 0 = off
                // 1 = our system (needs email + days)
                // 2 = 3rd-party code (needs code)
                // 3 = redirect (needs redirect url)
            
                setRowVisible(rowEmail,    mode === 1);
                setRowVisible(rowDays,     mode === 1);
                setRowVisible(row3party,   mode === 2);
                setRowVisible(rowRedirect, mode === 3);
            
                setRequired(emailEl,    mode === 1);
                setRequired(daysEl,     mode === 1);
                setRequired(redirectEl, mode === 3);
                setRequired(codeEl, mode === 2);
            
              }
            
              modeEl.onchange = refresh;
              refresh();
            })();
        </script>


    </div>
    <?php
}
