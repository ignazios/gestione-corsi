<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              eduva.org
 * @since             1.0.0
 * @package           Gestione_Corsi
 *
 * @wordpress-plugin
 * Plugin Name:       Gestione Corsi
 * Plugin URI:        corsi.eduva.org
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Ignazio Scimone
 * Author URI:        eduva.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gestione-corsi
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gestione-corsi-activator.php
 */
function activate_gestione_corsi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gestione-corsi-activator.php';
	Gestione_Corsi_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gestione-corsi-deactivator.php
 */
function deactivate_gestione_corsi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gestione-corsi-deactivator.php';
	Gestione_Corsi_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gestione_corsi' );
register_deactivation_hook( __FILE__, 'deactivate_gestione_corsi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gestione-corsi.php';

/**
 * Classe che implementa le funzioni di base per il plugin
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/class-function.php';

/**
 * Classe che implementa le funzionalità per la gestione degli utenti
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/admin/class-utenti.php';

/**
 * Classe che implementa le funzionalità per la gestione dei dati delle scuole
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/admin/class-scuole.php';

/**
 * Classe che implementa le funzionalità per la gestione dei logs
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/admin/class-log.php';

/**
 * Classe che implementa le funzionalità per la gestione dei corsisti
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/admin/class-corsisti.php';

/**
 * Classe che implementa le funzionalità per la gestione dei Formatori Tutor
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/admin/class-formatoritutor.php';

/**
 * Classe che implementa le funzionalità per la gestione delle NewsLetter attraverso il plugin Alo Easy Mail
 * side of the site.
 */
require_once plugin_dir_path(  __FILE__ ) . '/admin/class-newsletter.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

function run_gestione_corsi() {
	global $wpdb,$table_prefix,$funzioni,$log;
	$wpdb->table_corsisti = $table_prefix . "corsi_corsisti";
	$wpdb->table_presenze = $table_prefix . "corsi_presenze";
	$wpdb->table_lezioni  = $table_prefix . "corsi_lezioni";
	$plugin = new Gestione_Corsi();
	$funzioni= new Funzioni();
	$log=new Log();
	$plugin->run();

}
define("Home_Path_Gestione_Corsi",plugins_url( "/", __FILE__ ));

run_gestione_corsi();
