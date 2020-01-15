<?php

/**
 * Fired during plugin activation
 *
 * @link       eduva.org
 * @since      1.0.0
 *
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/includes
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Gestione_Corsi_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		Gestione_Corsi_Activator::CreaTabella($wpdb->table_corsisti);
		Gestione_Corsi_Activator::CreaTabella($wpdb->table_presenze);
		Gestione_Corsi_Activator::CreaTabella($wpdb->table_lezioni);
		Gestione_Corsi_Activator::CreaCapacitaRuoli();
//		die();
//		add_option('Corsisti',"Sono Passato");
	}

	protected function CreaCapacitaRuoli(){
		$role = get_role( 'administrator' );

        /* Aggiunta dei ruoli all'Amministratore */
        if ( !empty( $role ) ) {
            $role->add_cap( 'corsi_admin' );       //amministratore corsi **Può fare tutto sui Corsi|Dati Scuole|Creazione/Gestione Utenti
            $role->add_cap( 'corsi_gest_corsi' );  //gestore corsi  **Può fare tutto sui Corsi 
			$role->add_cap( 'corsi_gest_ass' );	   //Formatore Tutor ** Può gestire le assenze, i contenuti del corso e creare espedire newsletter
			$role->add_cap( 'corsi_corsista' );	   //Corsista può vedere i corsi a cui è iscritto e stamapre l'attestato
			$role->add_cap( 'corsi_organizzatore' );	   //Scuola che organizza il corso, ha le stesse capacità del gestore ma solo per i propri corsi
        }
		
		$role = get_role( 'editor' );

        /* Aggiunta dei ruoli agli Editori */
        if ( !empty( $role ) ) {
            $role->add_cap( 'corsi_gest_corsi' );
			$role->add_cap( 'corsi_gest_ass' );
			$role->add_cap( 'corsi_corsista' );
        }		
		foreach (get_editable_roles() as $role_name => $role_info){
			$role = get_role( $role_name );
			$role->add_cap( 'corsi_corsista' );
		}

        /* Creazione ruolo di Amministratore Corsi*/
		if( get_role('amministratore_corsi') ){		
			remove_role( 'amministratore_corsi' );
		}
        add_role(
            'amministratore_corsi',
            'Amministratore Corsi',
            array(
				'read'							=> true, 
                'corsi_admin'					=> true,
                'corsi_gest_corsi'				=> true,
				'corsi_gest_ass'				=> true,
				'corsi_corsista'				=> true,
				'delete_event_categories'		=>true,
				'delete_events'					=>true,
				'delete_locations'				=>true,
				'delete_others_events'			=>true,
				'delete_others_locations'		=>true,
				'delete_others_recurring_events'=>true,
				'delete_recurring_events'		=>true,
				'edit_event_categories'			=>true,
				'edit_events'					=>true,
				'edit_locations'				=>true,
				'edit_others_events'			=>true,
				'edit_others_locations'			=>true,
				'edit_others_recurring_events'	=>true,
				'edit_recurring_events'			=>true,
				'manage_others_bookings'		=>true,
				'publish_events'				=>true,
				'publish_locations'				=>true,
				'read_others_locations'			=>true,
				'read_private_events'			=>true,
				'read_private_locations'		=>true,
				'read_private_newsletters'		=>true,
				'upload_event_images'			=>true,
				'delete_newsletters'			=>true,
				'delete_others_newsletters'		=>true,
				'edit_newsletters'				=>true,
				'edit_others_newsletters'		=>true,
				'publish_newsletters'			=>true,
				'publish_recurring_events'		=>true,
				'manage_newsletter_subscribers'	=>true)
        );
		/* Creazione ruolo di Organizzatore Corsi*/
		if( get_role('organizzatore_corsi') ){		
			remove_role( 'organizzatore_corsi' );
		}
        add_role(
            'organizzatore_corsi',
            'Organizzatore Corsi',
            array(
				'read'							=> true, 
				'corsi_gest_ass'				=> true,
				'corsi_organizzatore'			=> true,
				'manage_bookings'				=> true,
				'delete_events'					=>true,
				'delete_locations'				=>true,
				'delete_recurring_events'		=>true,
				'edit_event_categories'			=>true,
				'edit_events'					=>true,
				'edit_locations'				=>true,
				'edit_recurring_events'			=>true,
				'publish_events'				=>true,
				'publish_locations'				=>true,
				'upload_event_images'			=>true,
				'delete_newsletters'			=>true,
				'edit_newsletters'				=>true,
				'publish_newsletters'			=>true,
				'publish_recurring_events'		=>true,
				'manage_newsletter_subscribers'	=>true)
        );
		/* Creazione ruolo di Gestore Corsi*/
		if( get_role('gestore_corsi') ){		
			remove_role( 'gestore_corsi' );
		}
        add_role(
            'gestore_corsi',
            'Gestore Corsi',
            array(
				'read'						=> true, 
                'corsi_gest_corsi'			=> true,
				'corsi_gest_ass'			=> true,
				'corsi_corsista'			=> true,
				'delete_event_categories'	=>true,
				'delete_events'				=>true,
				'delete_locations'			=>true,
				'delete_recurring_events'	=>true,
				'edit_event_categories'		=>true,
				'edit_events'				=>true,
				'edit_locations'			=>true,
				'edit_recurring_events'		=>true,
				'manage_others_bookings'	=>true,
				'publish_events'			=>true,
				'publish_locations'			=>true,
				'publish_recurring_events'	=>true,
				'read_others_locations'		=>true,
				'read_private_events'		=>true,
				'read_private_locations'	=>true,
				'upload_event_images'		=>true,
				'edit_newsletters'			=>true,
				'publish_newsletters'		=>true,
				'delete_newsletters'			=>true,
				'delete_others_newsletters'		=>true,
				'edit_others_newsletters'		=>true,
				'publish_recurring_events'		=>true,
				'manage_newsletter_subscribers'	=>true)
        );
	        /* Creazione ruolo di Docente Corsi*/
		if( get_role('docente_corsi') ){		
			remove_role( 'docente_corsi' );
		}
        add_role(
            'docente_corsi',
            'Docente Corsi',
            array(
				'read'						=> true, 
 				'corsi_gest_ass'			=> true,
				'corsi_corsista'			=> true,
				'manage_others_bookings'	=> true,
				'read_others_locations'		=>true,
				'read_private_events'		=>true,
				'read_private_locations'	=>true,	
				'delete_newsletters'			=>true,
				'edit_newsletters'				=>true,
				'publish_newsletters'			=>true)
        );		
	        /* Creazione ruolo di Corsista*/
		if( get_role('corsista_corsi') ){		
			remove_role( 'corsista_corsi' );
		}
        add_role(
            'corsista_corsi',
            'Corsista',
            array(
				'read' => true, 
 				'corsi_corsista' => true)
        );		
	}
	protected function CreaTabella($Tabella){
		global $wpdb;
		switch ($Tabella){
			case $wpdb->table_corsisti:
				$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->table_corsisti." (
					IDCorsista bigint(20) NOT NULL auto_increment,
					IDCorso bigint(20) UNSIGNED,
					IDUser bigint(20) UNSIGNED,
				    IDBooking bigint(20) UNSIGNED,
					DataCreazione timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 				    PRIMARY KEY  (IDCorsista),
					UNIQUE KEY IscrizioneUnivoca (IDCorso,IDUser));";
				break;
			case $wpdb->table_presenze:
				$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->table_presenze." (
					IDCorsista bigint(20) NOT NULL,
					DataLezione DATE NOT NULL,
					Presenza TINYINT(1) DEFAULT 1,
					AssenzaMin smallint(6) NOT NULL DEFAULT '0',
					Note VARCHAR(255),
					PRIMARY KEY (IDCorsista,DataLezione));";
				break;
			case $wpdb->table_lezioni:
				$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->table_lezioni." (
					IDCorso bigint(20) UNSIGNED,
					DataLezione DATE NOT NULL,
					Argomenti TEXT,
					Consolidata TINYINT NOT NULL DEFAULT '0',
					PRIMARY KEY (IDCorso,DataLezione));";
				break;
		}
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//		echo $sql;
		dbDelta($sql);
	}	
}
