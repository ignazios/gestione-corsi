<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       eduva.org
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Gestione_Corsi {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Gestione_Corsi_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * Libreria di funzioni general pourpose del plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      object.
	 */
	protected $Funzioni;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	
	public function __construct() {
		
		$this->plugin_name = 'gestione-corsi';
		$this->version = '1.0.0';
		
		$this->load_dependencies();
		$this->documentazione();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function documentazione(){
		$Operazione=filter_input( INPUT_GET, "op" );
		$TipoOperazione=filter_input( INPUT_GET, "mod" );
		$IDCorso= filter_input(INPUT_GET, "event_id");
		$IDUtente= filter_input(INPUT_GET, "user_id");
		switch ($Operazione){
			case "registro": 
				switch ($TipoOperazione){
					case "stafogliofirma":
						$Pdf=new Gestione_Documenti();
						$Corso=new Gestione_Corso($IDCorso,TRUE);
						$Pdf->Crea_Elenco_Firma_Corso($Corso);
						break;
					case "staregistro":
						$Pdf=new Gestione_Documenti();
						$Corso=new Gestione_Corso($IDCorso,TRUE);
						$Pdf->Crea_Registro_Corso($Corso);
						break;
					case "stattestato":	
						require_once( ABSPATH . 'wp-includes/pluggable.php' );
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'AttestatoFrequenza' ) ) {
							die( 'Errore di sicurezza' ); 
						} 
						$Pdf=new Gestione_Documenti();
						$Corso=new Gestione_Corso($IDCorso,TRUE);
						$Pdf->CreaAttestato($Corso,$IDUtente);
						break;		
					case "stattestati":
						require_once( ABSPATH . 'wp-includes/pluggable.php' );
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'AttestatoFrequenza' ) ) {
							die( 'Errore di sicurezza' ); 
						} 
						$Pdf=new Gestione_Documenti();
						$Corso=new Gestione_Corso($IDCorso,TRUE);
						$Pdf->CreaAttestati($Corso);
						break;
				}
				break;
			case "moveData":
				require_once( ABSPATH . 'wp-includes/pluggable.php' );
				$nonce = $_REQUEST['secur'];
				if ( !wp_verify_nonce( $nonce, 'moveDataLezione' ) ) {
					die( 'Errore di sicurezza' ); 
				}
				$Corso=new Gestione_Corso($IDCorso);
				$Date=$_GET["datalezione"];
				$OldData=array_keys($Date);
				//echo FUNZIONI::FormatDataItaliano($OldData[0])."  ".$Date[$OldData[0]];die();
				$location = "?page=corsi&op=registro&event_id=".$_GET['event_id']."&secur=".wp_create_nonce( 'Ortsiger' );
				if (!$Corso->is_DataLezione($OldData[0])){
					//echo $OldData[0]." ".FUNZIONI::FormatDataDB($Date[$OldData[0]]);
					//$Corso->replace_DataLezione($OldData[0],FUNZIONI::FormatDataDB($Date[$OldData[0]]));
					$MessaggioRitorno=$Corso->replace_DataLezione($OldData[0],FUNZIONI::FormatDataDB($Date[$OldData[0]]));	
					$Corso->change_DataLezione(FUNZIONI::FormatDataItaliano($OldData[0]),$Date[$OldData[0]]);
				}else{
					$MessaggioRitorno="Data%sLezione%sgià%spresente";
				}
				$location = add_query_arg( 'message',$MessaggioRitorno, $location );			
				wp_redirect( $location );
				break;
			case "stampe":
				switch ($TipoOperazione){
					case "CorsiAperti":	
						require_once( ABSPATH . 'wp-includes/pluggable.php' );
						$Pdf=new Gestione_Documenti();
						$Pdf->CreaStampaCorsiAperti("Pdf");	
						$location = "?page=stampe";
						wp_redirect( $location );
					break;
				}
			}
	}
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Gestione_Corsi_Loader. Orchestrates the hooks of the plugin.
	 * - Gestione_Corsi_i18n. Defines internationalization functionality.
	 * - Gestione_Corsi_Admin. Defines all hooks for the admin area.
	 * - Gestione_Corsi_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	
	private function load_dependencies() {
		
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gestione-corsi-loader.php';

		/**
		 * The class per al gestione del Social BuddyPress
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-social.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-gestione-corsi-admin.php';

		/**
		 * Inclusione della classe che permette la gestione dei singoli corsi.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-gestione-corso.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-gestione-corsi-public.php';

		/**
		 * Classe per la gestione delle chiamate Ajax
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-gestione-corsi-ajax.php';

		/**
		 * Classe per la gestione dei documenti fogli firme etc... in PDF
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-documenti.php';

		/**
		 * Classe per la gestione degli shortcode
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shortcode.php';

		$this->loader = new Gestione_Corsi_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_utenti = new Utenti();
		$plugin_admin = new Gestione_Corsi_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_ajax = new Gestione_Corsi_Ajax( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_head',								$plugin_admin,   'set_head_BE' );
		$this->loader->add_action( 'admin_enqueue_scripts',						$plugin_admin,   'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts',						$plugin_admin,   'enqueue_scripts' );
		$this->loader->add_action( 'wp_ajax_VerificaNuoviUtenti',				$plugin_ajax,    'VerificaNuoviUtenti' );
		$this->loader->add_action( 'wp_ajax_CreaNuoviUtenti',					$plugin_ajax,    'CreaNuoviUtenti' );
		$this->loader->add_action( 'wp_ajax_VerificaNuoviUtentiExcel',			$plugin_ajax,    'VerificaNuoviUtentiExcel' );
		$this->loader->add_action( 'wp_ajax_VerificaCFUtentiExcel',				$plugin_ajax,    'VerificaCFUtentiExcel' );
		$this->loader->add_action( 'wp_ajax_CreaCFUtentiExcel',					$plugin_ajax,    'CreaCFUtenti' );
		$this->loader->add_action( 'wp_ajax_CreaNuoviUtentiExcel',				$plugin_ajax,    'CreaNuoviUtenti' );
		$this->loader->add_action( 'wp_ajax_CorsoSetPresenza',					$plugin_ajax,    'CorsoSetPresenza' );
		$this->loader->add_action( 'wp_ajax_CorsoSetAssenza',					$plugin_ajax,    'CorsoSetAssenza' );
		$this->loader->add_action( 'wp_ajax_CorsoSetNote',						$plugin_ajax,    'CorsoSetNote' );
		$this->loader->add_action( 'wp_ajax_CorsoSetAssenzaMinuti',				$plugin_ajax,    'CorsoSetAssenzaMinuti' );
		$this->loader->add_action( 'wp_ajax_CorsoSetOreOnLine',					$plugin_ajax,    'CorsoSetOreOnLine' );
		$this->loader->add_action( 'wp_ajax_CorsoArgomentiLezione',				$plugin_ajax,    'CorsoArgomentiLezione' );
		$this->loader->add_action( 'wp_ajax_FiltroScuola',						$plugin_ajax,    'FiltroScuola' );
		$this->loader->add_action( 'wp_ajax_ArgomentiLezione',					$plugin_ajax,    'GetArgomentiLezione',10,0 );
		$this->loader->add_action( 'wp_ajax_StatisticheTitoloCorso',			$plugin_ajax,    'StatisticheTitoloCorso',10,0 );
		$this->loader->add_action( 'wp_ajax_StatisticheTabellaCorso',			$plugin_ajax,    'StatisticheTabellaCorso',10,0 );
		$this->loader->add_action( 'wp_ajax_FiltroCorsista',					$plugin_ajax,    'FiltroCorsista',10,0 );
		$this->loader->add_action( 'wp_ajax_AddUserByEmail',					$plugin_ajax,    'AddUserByEmail',10,0 );
		$this->loader->add_action( 'wp_ajax_CaricaDatiDaFondere',				$plugin_ajax,    'CaricaDatiDaFondere',10,0 );
		$this->loader->add_action( 'wp_ajax_FondiAccount',						$plugin_ajax,    'FondiAccount',10,0 );
		$this->loader->add_action( 'wp_ajax_CreaCartellaZip',					$plugin_ajax,    'CreaCartellaZip',10,0 );
		$this->loader->add_action( 'wp_ajax_CreaAttestatoSingolo',				$plugin_ajax,    'CreaAttestatoSingolo',10,0 );
		$this->loader->add_action( 'wp_ajax_CreaZIPAttestati',					$plugin_ajax,    'CreaZIPAttestati',10,0 );
		$this->loader->add_action( 'admin_head-gestione-corsi_page_statistiche',$plugin_admin,   'HeadStatistiche');
		$this->loader->add_action( 'admin_menu',								$plugin_admin,   'Menu_Corsi');
		$this->loader->add_action( 'show_user_profile',							$plugin_utenti,  'utenti_crea_extra_profile_fields');
		$this->loader->add_action( 'edit_user_profile',							$plugin_utenti,  'utenti_crea_extra_profile_fields');
		$this->loader->add_action( 'personal_options_update',					$plugin_utenti,  'utenti_save_extra_profile_fields');
		$this->loader->add_action( 'edit_user_profile_update',					$plugin_utenti,  'utenti_save_extra_profile_fields');
		$this->loader->add_action( 'em_booking_form_custom',					$plugin_admin,   'CustomFormCorsi');
		$this->loader->add_filter( 'em_booking_add_registration_result',		$plugin_admin,   'MemoCampiAggiuntiviFormRegistrazione',10,3);
		$this->loader->add_filter( 'em_register_new_user',						$plugin_admin,   'NuovoUtenteRegistrato',10,1);
		$this->loader->add_filter( 'em_my_bookings_booking_actions',			$plugin_admin,   'NuoveFunzionalitaMieiCorsi',10,2);
		$this->loader->add_action( 'wp_ajax_ScriviLogCorso',					$plugin_ajax,    'ScriviLogStatoCorso' );
		$this->loader->add_action( 'wp_ajax_CreaAttestato',						$plugin_ajax,    'CreaAttestato',10 );
	//	$this->loader->add_filter( 'wp_mail_from',								$plugin_admin,   'wpb_sender_email' );
	//	$this->loader->add_filter( 'wp_mail_from_name',							$plugin_admin,   'wpb_sender_name' );
		$this->loader->add_filter( 'em_calendar_get',							$plugin_admin,   'mod_CalendarWidget',10,2);
		$this->loader->add_filter( 'retrieve_password_message',					$plugin_admin,   'mod_urllogin',2,4);
		$this->loader->add_action( 'add_meta_boxes',							$plugin_admin,   'Alo_NL_crea_box_Dest',10,1);
		$this->loader->add_action( 'add_meta_boxes',							$plugin_admin,   'Eventi_crea_box_Date',10,1);
		$this->loader->add_action( 'save_post',									$plugin_admin,   'Eventi_save_box_Date',10,1);
		$this->loader->add_action( 'manage_event_posts_columns',				$plugin_admin,   'Eventi_NuoveColonne',10,1);
		$this->loader->add_action( 'manage_event_posts_custom_column',			$plugin_admin,   'Eventi_NuoveColonneContenuto',10,2);
		$this->loader->add_action( 'init',										$plugin_admin,   'my_docs_init',12,1);
		$this->loader->add_filter( 'em_event_output',							$plugin_admin,	 'My_lista_eventi_personalizzati',10,4);
		$this->loader->add_filter( 'em_event_output_show_condition',			$plugin_admin,	 'My_condizioni_eventi_personalizzati',10,4);
		$this->loader->add_filter( 'em_event_output_condition',					$plugin_admin,	 'My_output_eventi_personalizzati',10,4);
				
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('email-log/email-log.php')){
			$this->loader->add_action( 'user_row_actions',						$plugin_utenti,   'Log_user_email',10,2);
			$this->loader->add_action( 'load-users.php',						$plugin_utenti,   'Export_Log_user_email');
		}
//		$this->loader->add_filter( 'wp_mail',									$plugin_admin,	  'corsi_personalize_mail',10,1);
//		$this->loader->add_filter( 'wp_mail_content_type',						$plugin_admin,	  'corsi_set_content_type',10,1);
		$this->loader->add_action( 'phpmailer_init',						    $plugin_admin,    'corsi_disable_WordWrap',10,1);
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters',		$plugin_admin,	  'corsi_personal_data_exporter',10);
		}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public    = new Gestione_Corsi_Public( $this->get_plugin_name(), $this->get_version() );
		$plugin_ajax      = new Gestione_Corsi_Ajax( $this->get_plugin_name(), $this->get_version() );
				
		$this->loader->add_action( 'wp_head',				$plugin_public, 'set_head_FE' );
		$this->loader->add_action( 'wp_enqueue_scripts',		$plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts',		$plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_ajax_ScriviLogCorsoPublic',	$plugin_ajax,   'ScriviLogStatoCorsoPublic' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Gestione_Corsi_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
