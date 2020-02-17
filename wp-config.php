<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'afpa' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'A~ADWoHjHP9:Y&l2.<M5pYkFCPIXT0Rp_gX;8[p-!I(/r[!KEV9HBuuDFa#6&KGe' );
define( 'SECURE_AUTH_KEY',  'Vj ~cx;7d86<Z#M,4YT7/(bd&~r .AwHe~!)isxF;k^>^{l$~uKEYr}@Xi-_S-,|' );
define( 'LOGGED_IN_KEY',    '[j~}b k@~*Ua6%.FOEz;<xWk[+zEwy&}Tz*VO<DvxO5K;wj(g74-9V=x{|yBmUqz' );
define( 'NONCE_KEY',        '8kLWnihMlMm%<}VhErHqe4q<8d=Utl l|Ow8,OO5YrqWl7C1ECnWl7_N)iB{ivW}' );
define( 'AUTH_SALT',        '9pZOy<UVm>}SUz67NvdouMbPC^}A]UoDx&!sC4TFO4yW0/C>}l#DWE;xih>4=9[E' );
define( 'SECURE_AUTH_SALT', ',DI^K`BE$Bv9ymfU#Bx=VUCe$XFjAS0-7xG:iq71]}17sR2wRxsR@-w&YlUFAnYR' );
define( 'LOGGED_IN_SALT',   '!_qgh-d!#<u`rvk*!la>),gg@:T]]D|mWxhgZJi:TLPZX9^ydNp,[<T7X2Gr2MDL' );
define( 'NONCE_SALT',       'w#/l;}3_MD[g*-wDR2)fwB%y/Dl$o0MYd3W50:{?BlXf@hu;BDy2SbpB6c-jXh!:' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
