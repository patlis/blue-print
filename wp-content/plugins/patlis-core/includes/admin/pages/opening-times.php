<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Opening {

  public static function render(): void {
    if (!current_user_can('patlis_manage')) return;

    ?>
    <div class="wrap">
      <h1>Opening times</h1>
      <p>Coming soon.</p>
    </div>
    <?php
  }
}
