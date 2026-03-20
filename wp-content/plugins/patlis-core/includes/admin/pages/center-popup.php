<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Center_Popup {

  public static function boot(): void {
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
  }

  public static function enqueue_admin_assets($hook): void {
    if (empty($_GET['page']) || $_GET['page'] !== 'patlis-center-popup') return;

    wp_enqueue_media();
    wp_enqueue_script('jquery');
  }

  public static function sanitize($input): array {
    $in = is_array($input) ? $input : [];
    $out = [];

    $out['enabled']       = !empty($in['enabled']) ? 1 : 0;

    $show_from = isset($in['show_from']) ? sanitize_text_field($in['show_from']) : 'html';
    if (!in_array($show_from, ['html', 'image', 'video', 'code'], true)) $show_from = 'html';

    $out['show_from'] = $show_from;

    $out['title']         = isset($in['title']) ? sanitize_text_field($in['title']) : '';

    $delay = isset($in['delay_seconds']) ? (int)$in['delay_seconds'] : 0;
    if ($delay < 0) $delay = 0;
    $out['delay_seconds'] = $delay;

    $out['start_date']    = isset($in['start_date']) ? sanitize_text_field($in['start_date']) : '';
    $out['end_date']      = isset($in['end_date']) ? sanitize_text_field($in['end_date']) : '';

    $out['link_url']      = isset($in['link_url']) ? esc_url_raw($in['link_url']) : '';

    $out['video']         = isset($in['video']) ? esc_url_raw($in['video']) : '';

    $out['image_id']      = isset($in['image_id']) ? (int)$in['image_id'] : 0;

    $out['code']          = isset($in['code']) ? (string)$in['code'] : '';
    $out['html']          = isset($in['html']) ? wp_kses_post((string)$in['html']) : '';

    return $out;
  }

  public static function render(): void {
    if (!current_user_can('patlis_manage')) return;

    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];

    $image_id = isset($opt['image_id']) ? (int)$opt['image_id'] : 0;
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

    ?>
    <style>
  .patlis-center-popup-page .form-table th,
  .patlis-center-popup-page .form-table td {
    padding-top: 5px;
    padding-bottom: 0px;
  }

  .patlis-center-popup-page .form-table tr {
    line-height: 1.2;
  }
  </style>
  
  
    <div class="wrap patlis-center-popup-page">
        <h1><?php esc_html_e('Center Pop up', 'patlis-core'); ?></h1>
        
        <?php if (!empty($_GET['patlis_saved'])): ?>
          <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
        <?php endif; ?>


        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="patlis_save_center_popup">
          <?php wp_nonce_field('patlis_save_center_popup'); ?>

        <table class="form-table" role="presentation">

          <tr>
            <th scope="row"><?php esc_html_e('Enable', 'patlis-core'); ?></th>
            <td>
              <label>
                <input type="checkbox"
                  name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[enabled]"
                  value="1" <?php checked(!empty($opt['enabled'])); ?>>
              </label>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_cp_show_from"><?php esc_html_e('Show from', 'patlis-core'); ?></label></th>
            <td>
              <?php $show_from = $opt['show_from'] ?? 'html'; ?>
              <select id="patlis_cp_show_from"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[show_from]">
                <option value="html" <?php selected($show_from, 'html'); ?>>Html</option>
                <option value="code" <?php selected($show_from, 'code'); ?>>Html Code</option>                
                <option value="image" <?php selected($show_from, 'image'); ?>>Image</option>
                <option value="video" <?php selected($show_from, 'video'); ?>>Video</option>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_cp_title"><?php esc_html_e('Title', 'patlis-core'); ?></label></th>
            <td>
              <input id="patlis_cp_title" type="text" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[title]"
                value="<?php echo esc_attr($opt['title'] ?? ''); ?>">
            </td>
          </tr>
          
          <tr>
            <th scope="row"><label for="patlis_cp_link"><?php esc_html_e('Link url', 'patlis-core'); ?></label></th>
            <td>
              <input id="patlis_cp_link" type="text" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[link_url]"
                value="<?php echo esc_attr($opt['link_url'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_cp_delay"><?php esc_html_e('Delay (Seconds)', 'patlis-core'); ?></label></th>
            <td>
              <input id="patlis_cp_delay" type="number" min="0" class="small-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[delay_seconds]"
                value="<?php echo esc_attr((string)($opt['delay_seconds'] ?? 0)); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_cp_start"><?php esc_html_e('Start date', 'patlis-core'); ?></label></th>
            <td>
              <input id="patlis_cp_start" type="date"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[start_date]"
                value="<?php echo esc_attr($opt['start_date'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_cp_end"><?php esc_html_e('Date end', 'patlis-core'); ?></label></th>
            <td>
              <input id="patlis_cp_end" type="date"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[end_date]"
                value="<?php echo esc_attr($opt['end_date'] ?? ''); ?>">
            </td>
          </tr>



            <tr>
              <th scope="row"><?php esc_html_e('Video (self-hosted)', 'patlis-core'); ?></th>
              <td>
                <?php $video_url = isset($opt['video']) ? (string)$opt['video'] : ''; ?>
            
                <input type="hidden"
                  id="patlis_cp_video_url"
                  name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[video]"
                  value="<?php echo esc_attr($video_url); ?>">
            
                <div id="patlis_cp_video_preview" style="margin: 0 0 10px 0;">
                  <?php if ($video_url): ?>
                    <video src="<?php echo esc_url($video_url); ?>" controls style="max-height: 150px; max-width: 360px; height: auto; "></video>
                    <div style="margin-top:6px; font-size:12px; opacity:.8;">
                      <?php echo esc_html($video_url); ?>
                    </div>
                  <?php endif; ?>
                </div>
    
                <button type="button" class="button" id="patlis_cp_pick_video"><?php esc_html_e('Select video', 'patlis-core'); ?></button>
                <button type="button" class="button" id="patlis_cp_remove_video"><?php esc_html_e('Remove', 'patlis-core'); ?></button>
    
                <script>
                jQuery(function($){
                  var videoFrame;
            
                  $('#patlis_cp_pick_video').on('click', function(e){
                    e.preventDefault();
            
                    if (videoFrame) { videoFrame.open(); return; }
            
                    videoFrame = wp.media({
                      title: 'Select video',
                      button: { text: 'Use video' },
                      multiple: false,
                      library: { type: 'video' }
                    });
            
                    videoFrame.on('select', function(){
                      var attachment = videoFrame.state().get('selection').first().toJSON();
                      var url = attachment.url || '';
            
                      $('#patlis_cp_video_url').val(url);
            
                      if (url) {
                        $('#patlis_cp_video_preview').html(
                          '<video src="'+ url +'" controls style="max-width: 360px; height: auto;"></video>' +
                          '<div style="margin-top:6px; font-size:12px; ">'+ url +'</div>'
                        );
                      } else {
                        $('#patlis_cp_video_preview').html('');
                      }
                    });
            
                    videoFrame.open();
                  });
            
                  $('#patlis_cp_remove_video').on('click', function(e){
                    e.preventDefault();
                    $('#patlis_cp_video_url').val('');
                    $('#patlis_cp_video_preview').html('');
                  });
                });
                </script>
              </td>
            </tr>

          <tr>
            <th scope="row"><?php esc_html_e('Image', 'patlis-core'); ?></th>
            <td>
              <input type="hidden"
                id="patlis_cp_image_id"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[image_id]"
                value="<?php echo esc_attr((string)$image_id); ?>">

              <div id="patlis_cp_image_preview" style="margin: 0 0 10px 0;">
                <?php if ($image_url): ?>
                  <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width: 360px; height: auto;">
                <?php endif; ?>
              </div>

              <button type="button" class="button" id="patlis_cp_pick_image"><?php esc_html_e('Select image', 'patlis-core'); ?></button>
              <button type="button" class="button" id="patlis_cp_remove_image"><?php esc_html_e('Remove', 'patlis-core'); ?></button>

              <script>
              jQuery(function($){
                var frame;

                $('#patlis_cp_pick_image').on('click', function(e){
                  e.preventDefault();

                  if (frame) { frame.open(); return; }

                  frame = wp.media({
                    title: 'Select image',
                    button: { text: 'Use image' },
                    multiple: false
                  });

                  frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#patlis_cp_image_id').val(attachment.id);
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    $('#patlis_cp_image_preview').html('<img src="'+ url +'" alt="" style="max-width: 240px; height: auto;">');
                  });

                  frame.open();
                });

                $('#patlis_cp_remove_image').on('click', function(e){
                  e.preventDefault();
                  $('#patlis_cp_image_id').val('');
                  $('#patlis_cp_image_preview').html('');
                });
              });
              </script>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_cp_code"><?php esc_html_e('Html Code', 'patlis-core'); ?></label></th>
            <td>
              <textarea id="patlis_cp_code" rows="5" class="large-text code"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[code]"><?php echo esc_textarea(wp_unslash($opt['code'] ?? '')); ?></textarea>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php esc_html_e('Html', 'patlis-core'); ?></th>
            <td>
              <?php $content = wp_unslash($opt['html'] ?? '');
              wp_editor($content, 'patlis_cp_html_editor', [
                'textarea_name' => Patlis_Core::OPTION_CENTER_POPUP . '[html]',
                'textarea_rows' => 5,
                'media_buttons' => true,
                'teeny'         => false,
              ]);
              ?>
            </td>
          </tr>

        </table>

        <?php submit_button('Save'); ?>
      </form>
      
      <p style="color:red">This popup is shown only after the cookie banner has been completed (consent flag stored in browser storage). If you change/replace your cookie banner, please let us know so we can adjust the popup condition accordingly.</p>
    </div>
    <?php
  }
}

Patlis_Admin_Page_Center_Popup::boot();

