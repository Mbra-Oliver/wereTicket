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
define( 'DB_NAME', 'rs2429210_wp1' );

/** Database username */
define( 'DB_USER', 'rs2429210_wp1' );

/** Database password */
define( 'DB_PASSWORD', 'A!8739p@IS' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',         'mtdzdqtytwaa7xypihv4qwduqbiipwlqxbpzldddilliuwr6dafqfaimv4t52ua5' );
define( 'SECURE_AUTH_KEY',  'zazl5xd89au5hgj5tnlgr4vk2jghezdsanlx9qcqflsbpvw0jzwgcnzwz083q2iu' );
define( 'LOGGED_IN_KEY',    'kjkgyhgs1tzgnicupfbv2n4ewxqli2afplvkrlf3hgnzrfxkzprn8woxonszc9qg' );
define( 'NONCE_KEY',        'gaqmgjlkwj223xaocgqzexhrajntmqioawkduye2siurgqhpuh3ssi6zrj5nyo3b' );
define( 'AUTH_SALT',        'tqjq8uycwonu3p0twcyybstu6hxloxz3t1wygts2fw0rc1vynjtpemgu8nk3wddz' );
define( 'SECURE_AUTH_SALT', 'oz5jv8ve2nkozdawj7sifybtsmdld0yzvryy3hbk9dvqyk5xhpu0kakckobvcczj' );
define( 'LOGGED_IN_SALT',   '6vcrpxpivpu1wlitiecx1uixvsp4rabs2xja99aoes3dcdulsrsj7bud1wwdwex1' );
define( 'NONCE_SALT',       'rjbjvtuag5dlay4ombm2qxxvwhpmvwyuflsl3camv0v9gsf5qwhbgotlumkz9hvt' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpcj_';

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
