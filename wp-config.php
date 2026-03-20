<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'sql_wp_patlis_co' );

/** Database username */
define( 'DB_USER', 'sql_wp_patlis_co' );

/** Database password */
define( 'DB_PASSWORD', '90027ef72dfa88' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'QUo|U:y=rjx#/5qOgB>g2o?!3.z@_~>@3X>,m90ugsG9QMw.OFZ0Br!b@BX@NC.K' );
define( 'SECURE_AUTH_KEY',  ':LbqZ:&3n.`05krUkM)j,%F#gDW6rI$% S4#E8^7] k>xaG5HLzrAdkk7-|n]]~0' );
define( 'LOGGED_IN_KEY',    '9LY+}D.n$Hzv}=1i!>~}K7a6YEwG>gC(x!`UGk-NjwdY5M_%2vdLitg%gpW6*nZ#' );
define( 'NONCE_KEY',        '2oC[E&Z! ?<_?<EC-V]u&5:R|sIfu]x;pjPk/S(-)$H}Y3xsk%o5M&uz *ob}f/o' );
define( 'AUTH_SALT',        'xAl>*TW=h*s!+}v&4W6i$BoLA4gw&uJZ0[c@a>7x/U7MRv5-v=Ifl81Y? dZMKgR' );
define( 'SECURE_AUTH_SALT', 'vpMP{yZCVVwCCf[I/AWyW~Fn(TOPv15x:9Ww[Mt]D?>saGsnoY?Af.Pwv@GHw[g1' );
define( 'LOGGED_IN_SALT',   'Nb4=3co/L=0<cw;&&idjO6*6?M,#*qbC-Whcp:!h+Iz;(88<n(GC-4lz8Y Wof}g' );
define( 'NONCE_SALT',       '$LE*bpTgVZA66c1c]Y|N!fL7zK;M52y&Y>[n*<TuVbwSjA.MO>SH#v-Q^rcZA$Qa' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_f4bb67_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', false);

/* Add any custom values between this line and the "stop editing" line. */


/** Sets the website Version */
/** gastro, hotel, general, shop, amenities, dining, locations   */
define('PATLIS_VERSION', 'gastro, hotel');

ini_set('display_errors', 'off');
define('WP_DEBUG_DISPLAY', false);
ini_set('log_errors', 'off');
define('WP_DEBUG_LOG', false);
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';