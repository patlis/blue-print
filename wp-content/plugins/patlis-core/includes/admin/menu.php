<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Menu {

  public static function register(): void {
      $capability  = 'patlis_manage';

    add_menu_page(
      __('Patlis.com', 'patlis-core'),
      __('Patlis.com', 'patlis-core'),
      $capability,
      'patlis-basic',
      ['Patlis_Admin_Page_Basic', 'render'],
      'dashicons-admin-generic',
     26
    );

    add_submenu_page(
      'patlis-basic',
      __('Basic settings', 'patlis-core'),
      __('Basic settings', 'patlis-core'),
      $capability,
      'patlis-basic',
      ['Patlis_Admin_Page_Basic', 'render']
    );
    
    add_submenu_page(
      'patlis-basic',
      __('Social Media', 'patlis-core'),
      __('Social Media', 'patlis-core'),
      $capability,
      'patlis-social',
      ['Patlis_Admin_Page_Social', 'render']
    );

    add_submenu_page(
      'patlis-basic',
      __('Opening times', 'patlis-core'),
      __('Opening times', 'patlis-core'),
      $capability,
      'patlis-opening',
      ['Patlis_Admin_Page_Opening', 'render']
    );
    
    add_submenu_page(
      'patlis-basic',
      __('Center Pop up', 'patlis-core'),
      __('Center Pop up', 'patlis-core'),
      $capability,
      'patlis-center-popup',
      ['Patlis_Admin_Page_Center_Popup', 'render']
    );
    
    add_submenu_page(
      'patlis-basic',
      __('Notification Bar', 'patlis-core'),
      __('Notification Bar', 'patlis-core'),
      $capability,
      'patlis-notification-bar',
      ['Patlis_Admin_Page_Notification_Bar', 'render']
    );

    
  }
}
