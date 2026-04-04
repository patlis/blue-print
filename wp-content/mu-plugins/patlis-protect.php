<?php
/**
 * Plugin Name: Patlis Protect
 * Description: Hard security rules (MU plugin).
 */

if (!defined('ABSPATH')) {  exit; }

/**
 * Site Owner: μπορεί να διαχειρίζεται χρήστες,
 * αλλά μπορεί να αναθέτει ρόλους μόνο από whitelist.
 */
add_filter('editable_roles', function (array $roles): array {

    if (current_user_can('administrator')) {
        return $roles;
    }

    // apply to site owners (ή γενικά σε όσους έχουν patlis_manage)
    if (!current_user_can('patlis_manage')) {
        return $roles;
    }

    $allowed = [
        'subscriber',
        'contributor',
        'author',
        'editor',
        'site_owner',
    ];

    foreach ($roles as $role_key => $_) {
        if (!in_array($role_key, $allowed, true)) {
            unset($roles[$role_key]);
        }
    }

    return $roles;
}, 20);

/**
 * Hard safety net: αν κάποιος πάει να θέσει ρόλο εκτός whitelist,
 * κάνε revert σε ασφαλή ρόλο.
 */
add_action('set_user_role', function ($user_id, $role, $old_roles) {

    if (current_user_can('administrator')) return;
    if (!current_user_can('patlis_manage')) return;

    $allowed = ['subscriber','contributor','author','editor','site_owner'];

    if (!in_array($role, $allowed, true)) {
        $u = new WP_User($user_id);
        $u->set_role('subscriber');
    }
}, 20, 3);

/**
 * Site Owner: κόψε πρόσβαση σε Theme/Plugin file editors + plugin/theme management.
 */
add_action('init', function () {

    $role = get_role('site_owner');
    if ($role) {
        foreach ([
            'edit_themes',
            'edit_plugins',
            'activate_plugins',
            'install_plugins',
            'update_plugins',
            'delete_plugins',
            'switch_themes',
            'install_themes',
            'update_themes',
            'delete_themes',
            'update_core',
        ] as $cap) {
            $role->remove_cap($cap);
        }
    }

    // Extra safety: κανείς non-admin να μην έχει file editor, ακόμα κι αν πάρει cap κατά λάθος
    if (is_admin() && !current_user_can('administrator')) {
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }
}, 20);
