<?php
  $itemnr         = trim(bricks_render_dynamic_data('{patlis_menu_item_itemnr}'));
  $title          = trim(bricks_render_dynamic_data('{patlis_menu_item_title}'));
  $allergies      = trim(bricks_render_dynamic_data('{patlis_menu_item_allergies}'));
  $size1          = trim(bricks_render_dynamic_data('{patlis_menu_item_size1}'));
  $size2          = trim(bricks_render_dynamic_data('{patlis_menu_item_size2}'));
  $size3          = trim(bricks_render_dynamic_data('{patlis_menu_item_size3}'));
  $price1_raw     = trim(bricks_render_dynamic_data('{patlis_menu_item_price}'));
  $price2_raw     = trim(bricks_render_dynamic_data('{patlis_menu_item_price2}'));
  $price3_raw     = trim(bricks_render_dynamic_data('{patlis_menu_item_price3}'));
  $price1         = trim(bricks_render_dynamic_data('{patlis_menu_item_price_currency}'));
  $price2         = trim(bricks_render_dynamic_data('{patlis_menu_item_price2_currency}'));
  $price3         = trim(bricks_render_dynamic_data('{patlis_menu_item_price3_currency}'));
  $desc           = trim(html_entity_decode(wp_strip_all_tags(bricks_render_dynamic_data('{patlis_menu_item_description}')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  $vegetarian     = trim(bricks_render_dynamic_data('{patlis_menu_item_vegetarian}'));
  $vegan          = trim(bricks_render_dynamic_data('{patlis_menu_item_vegan}'));
  $image_url      = trim(bricks_render_dynamic_data('{patlis_menu_item_image_url}'));
  $image_id       = (int) trim(bricks_render_dynamic_data('{patlis_menu_item_image_id}'));

  $show_prices    = trim(bricks_render_dynamic_data('{patlis_menu_show_prices}'));
  $show_allergies = trim(bricks_render_dynamic_data('{patlis_menu_show_allergies}'));

  // flags
  $has_price1     = ($show_prices === '1' && $price1_raw !== '');
  $has_price2     = ($show_prices === '1' && $price2_raw !== '');
  $has_price3     = ($show_prices === '1' && $price3_raw !== '');

  $has_allergies  = ($show_allergies === '1' && $allergies !== '');
  $has_desc       = ($desc !== '' && mb_strlen($desc) > 1);
  $has_veg        = ($vegetarian === '1');
  $has_vegan      = ($vegan === '1');
  $has_image      = ($image_url !== '');

  $show_line1 = ($itemnr !== '' || $title !== '' || $has_allergies || $has_price1);

  $show_line2 = ($has_price2 || $has_price3 || $has_desc || $has_veg || $has_vegan || ($has_image && $image_id > 0));
?>

<div class="menu-item">
  <?php if ($show_line1): ?>
    <div class="menu-normal-line1">
      <div class="menu-item-groups">
        <?php if ($itemnr !== ''): ?>
          <span><?php echo esc_html($itemnr); ?></span>
        <?php endif; ?>

        <?php if ($title !== ''): ?>
          <span class="h5 item-title"><?php echo esc_html($title); ?></span>
        <?php endif; ?>

        <?php if ($has_allergies): ?>
          <sup class="allergies-link pseudolink js-allergy-trigger"><?php echo esc_html($allergies); ?></sup>
        <?php endif; ?>
      </div>

      <?php if ($has_price1): ?>
        <div class="menu-item-groups">
          <?php if ($size1 !== ''): ?>
            <span><?php echo esc_html($size1); ?></span>
          <?php endif; ?>

          <span class="h5"><?php echo esc_html($price1); ?></span>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($show_line2): ?>
    <div class="menu-normal-line2">
      <?php if ($has_image && $image_id > 0): ?>
        <?php
          $full_data  = wp_get_attachment_image_src($image_id, 'full');
          $thumb_data = wp_get_attachment_image_src($image_id, 'thumbnail');

          $full_url   = $full_data ? $full_data[0] : $image_url;
          $full_w     = $full_data ? (int) $full_data[1] : 0;
          $full_h     = $full_data ? (int) $full_data[2] : 0;

          $thumb_url  = $thumb_data ? $thumb_data[0] : $image_url;
          $thumb_w    = $thumb_data ? (int) $thumb_data[1] : 150;
          $thumb_h    = $thumb_data ? (int) $thumb_data[2] : 150;
        ?>
        <a
          class="brxe-pbapqx brxe-image tag bricks-lightbox"
          href="<?php echo esc_url($full_url); ?>"
          data-pswp-src="<?php echo esc_url($full_url); ?>"
          data-pswp-width="<?php echo esc_attr($full_w); ?>"
          data-pswp-height="<?php echo esc_attr($full_h); ?>"
          data-pswp-id="menu-images"
        >
          <img
            src="<?php echo esc_url($thumb_url); ?>"
            width="<?php echo esc_attr($thumb_w); ?>"
            height="<?php echo esc_attr($thumb_h); ?>"
            class="menu-img"
            alt="<?php echo esc_attr($title); ?>"
            loading="lazy"
            decoding="async"
          >
        </a>
      <?php endif; ?>

      <div class="menu-normal-line2-content">
        <?php if ($has_price2): ?>
          <div class="menu-extra-prices">
            <?php if ($size2 !== ''): ?>
              <span><?php echo esc_html($size2); ?></span>
            <?php endif; ?>

            <span class="h5"><?php echo esc_html($price2); ?></span>
          </div>
        <?php endif; ?>

        <?php if ($has_price3): ?>
          <div class="menu-extra-prices">
            <?php if ($size3 !== ''): ?>
              <span><?php echo esc_html($size3); ?></span>
            <?php endif; ?>

            <span class="h5"><?php echo esc_html($price3); ?></span>
          </div>
        <?php endif; ?>

        <?php if ($has_desc || $has_veg || $has_vegan): ?>
          <div class="menu-description-line">

            <?php if ($has_veg): ?>
              <span class="menu-vv-icons">🌿</span>
            <?php endif; ?>

            <?php if ($has_vegan): ?>
              <span class="menu-vv-icons">🌱</span>
            <?php endif; ?>

            <?php if ($has_desc): ?>
              <span><?php echo esc_html($desc); ?></span>
            <?php endif; ?>

          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>