<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       eduva.org
 * @since      1.0.0
 *
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Gestione_Corsi_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	private $isInstalledEM;
	private $isInstalledML;
	private $isInstalledRW;
	private $isInstalledLM;
	
	protected function CalcColonna($Col){
		$DoppiaLC=="";	
		if($Col>26){
			$DoppiaLC=chr(64+intval($Col/26));
			$Col=intval($Col%27)+1;
		}
//		echo $DoppiaLC.chr(64+$Col)."<br />";
		return $DoppiaLC.chr(64+$Col);
	}
	protected function crea_CartellaExcel($Dir,$Permessi=0777){
		$Risultato=FALSE;
		if (!is_dir($Dir)){
			if(mkdir($Dir,(int)$Permessi,TRUE)){
				$Risultato=TRUE;
			}			
		}else{
			$Risultato=TRUE;
		}
	return $Risultato;
	}
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$operazione=filter_input(INPUT_GET,"op");
		require_once( ABSPATH . 'wp-includes/pluggable.php' );
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$this->isInstalledEM=is_plugin_active('events-manager/events-manager.php');
		$this->isInstalledML=is_plugin_active('alo-easymail/alo-easymail.php');
		$this->isInstalledLM=is_plugin_active('email-log/email-log.php');
		$this->isInstalledRW=is_plugin_active('re-welcome/re-welcome.php');
		switch($operazione){
			case "exportattestati":
				$nonce = filter_input(INPUT_GET,'secur');
				$file = filter_input(INPUT_GET,'file');
				if (! wp_verify_nonce( $nonce, 'AttestatiFrequenza' ) ) {
							FUNZIONI::die_secur(); 
							break;
				} 		
				$this->DownloadFile($file);
	   			break;
			case "exportcorsi":
				$nonce = filter_input(INPUT_GET,'secur');
				if (! wp_verify_nonce( $nonce, 'Corsitocsv' ) ) {
							FUNZIONI::die_secur(); 
							break;
				} 		
				$dir = plugin_dir_path( __FILE__ );
				$pf = fopen($dir."ScadenziarioCorsi.csv", "w");
    			fwrite($pf, $this->export_ScadenziarioCorsi());
    			fclose($pf);
				$this->DownloadFile($dir."ScadenziarioCorsi.csv");
				unlink($dir."ScadenziarioCorsi.csv");
	   			break;
			case "exportcorsisti":
				$nonce = filter_input(INPUT_GET,'secur');
				if (! wp_verify_nonce( $nonce, 'Corsistitocsv' ) ) {
							FUNZIONI::die_secur(); 
							break;
				} 			
				$dir = plugin_dir_path( __FILE__ );
				
				if(isset($_GET['event_id'])){
					$Corso=new Gestione_Corso($_GET['event_id']);
					$NomeCorso="_".$Corso->get_NomeCorso();
					//$pf = fopen($dir."ElencoCorsisti$NomeCorso.csv", "w");
					//fwrite($pf, $this->export_ElencoCorsisti($_GET['event_id']));
					$DatiCorsisti=$this->export_ElencoCorsisti($_GET['event_id']);
				}else{
					$NomeCorso="";
					//$pf = fopen($dir."ElencoCorsisti$NomeCorso.csv", "w");
					$DatiCorsisti=$this->export_ElencoCorsisti();				
				}
    			//fclose($pf);
				require_once( ABSPATH .'wp-content/plugins/gestione-corsi/includes/PHPExcel/PHPExcel.php');		
				require_once( ABSPATH . 'wp-admin/includes/file.php' );		
				$Dir=get_home_path()."wp-content/GestioneCorsi/Excel";
				$Risultato=$this->crea_CartellaExcel($Dir,0711,"a");
				if (!$Risultato){
					die("Errore creazione directory del file Excel");			
				}
				$objPHPExcel = new PHPExcel();
				$objPHPExcel->getProperties()->setCreator("Gestione Corsi")
					->setLastModifiedBy("Gestione Corsi")
					->setTitle("Elenco Corsisti")
					->setSubject("elenco iscritti al corso ")
					->setDescription($NomeCorso)
					->setKeywords("Formazione Corsi")
					->setCategory("");
				$Riga=$Col=1;
				$sheet = $objPHPExcel->getActiveSheet(0);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "ID Utente");
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Nome");
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Cognome');
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Email');
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Scuola');
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Stato Iscrizione');
				$Riga++;
				foreach($DatiCorsisti as $DatiCorsista){
					$Col=1;
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorsista[0]);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorsista[1]);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorsista[2]);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorsista[3]);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorsista[4]);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorsista[5]);
					$Riga++;
				}					
				$sheet->setTitle('Elenco Corsisti');
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
				$objWriter->save($Dir."/ElencoCorsisti".$NomeCorso.".xlsx");
				
				$this->DownloadFile($Dir."/ElencoCorsisti$NomeCorso.xlsx");
				unlink($dir."/ElencoCorsisti$NomeCorso.csv");
	   			break;
			case "exportstatistichedw":
				require_once( ABSPATH . 'wp-admin/includes/file.php' );	
				$this->DownloadFile(get_home_path()."wp-content/GestioneCorsi/Excel/StatisticheCorsi.xlsx");
				break;
			case "exportstampaCorsiAperti":
				require_once( ABSPATH . 'wp-admin/includes/file.php' );	
				$this->DownloadFile(get_home_path()."wp-content/GestioneCorsi/Excel/CorsiAperti.xlsx");
				break;
			}
	}

	public function My_condizioni_eventi_personalizzati($show_condition, $condition, $conditionals, $output){
		$Corso=new Gestione_Corso($output->event_id);
		if($condition=="is_corso" And $Corso->get_CodiceCorso()==="")
			return false;
		else 
			return true;
	}
	public function My_output_eventi_personalizzati($replacement, $condition, $conditionals, $evento){
		$Corso=new Gestione_Corso($output->event_id);
		if($condition=="is_corso" And $Corso->get_CodiceCorso()==="")
			return "";
		else 
			return $replacement;
	}
		
	public function my_docs_init($force_init = false){
		global $EM_Documentation;
		$EM_Documentation['placeholders']['events']['Date and Times']['placeholders']['#_DATECORSO']=array( 'desc' => 'Lista le date delle lezioni del corso.' );
		$EM_Documentation['placeholders']['events']['Date and Times']['placeholders']['#_DATEINIZIOFINECORSO']=array( 'desc' => 'Visualizza la data di inizio e fine corso.' );
//		var_dump($EM_Documentation['placeholders']['events']);wp_die();
	}
	public function My_lista_eventi_personalizzati($event_string, $obj_event, $format, $target){
		$placeholders=array('#_DATECORSO','#_DATEINIZIOFINECORSO',"#_SCADENZAPRENOTAZIONI");
		foreach($placeholders as $segnaposto) {
			switch( $segnaposto ){
				case '#_DATECORSO':
					$SeriaLezioni=get_post_meta($obj_event->post_id, "_lezioniCorso",TRUE);
					$Lezioni=($SeriaLezioni?unserialize( $SeriaLezioni):array());
					$replace = "Lezioni (";
					foreach($Lezioni as $Lezione){
						$replace .= $Lezione[0].", ";
					}
					$replace=strlen($replace)>0?substr($replace,0,-2):"";
					$replace.=")";
					break;
				case '#_DATEINIZIOFINECORSO':
					$SeriaLezioni=get_post_meta($obj_event->post_id, "_lezioniCorso",TRUE);
					$Lezioni=($SeriaLezioni?unserialize( $SeriaLezioni):array());
					$replace = "Inizio ";
					$replace.=count($Lezioni)>0?$Lezioni[0][0]." Fine ".$Lezioni[count($Lezioni)-1][0]:" NON DEFINITA Fine NON DEFINITA";
					break;
				case '#_SCADENZAPRENOTAZIONI':
					$Scadenza=get_post_meta($obj_event->post_id, "_event_rsvp_date",TRUE);
					if($Scadenza==""){
						$replace="Iscrizioni non ancora aperte";
					}elseif($Scadenza>date("Y-m-d")){
						$replace =FUNZIONI::FormatDataItaliano($Scadenza);
						}else{
							$replace ="Iscrizioni Chiuse in data ".FUNZIONI::FormatDataItaliano($Scadenza);
						}
					break;			
			}
			$event_string=str_replace($segnaposto, $replace, $event_string);
		}
		return $event_string;
	}
	public function EsportaDatiUtente($email_address, $page = 1){
		$User= get_user_by("email", $email_address);
/*
 *	Campi aggiunti dal plugin Gestione Corsi 
 *  - Scuola di servizio
 *  - Codice Fiscale
 *  - Telefono (event manager)
 */	
		$Nelementi=0;
		$data = array(
			array(
			  'name' => "Scuola di Servizio",
			  'value' => get_user_meta($User->ID, "Scuola",TRUE)
			),
			array(
			  'name' => "Codice Fiscale",
			  'value' => get_user_meta($User->ID, "CF",TRUE)
			),
			array(
			  'name' => "Telefono",
			  'value' => get_user_meta($User->ID, "dbem_phone",TRUE)
			)
		);
		$item_id = "gestione-corsi-1";
        $group_id = 'gestione-corsi';
		$group_label = "Campi aggiuntivi Gestione Corsi";
		$export_items[] = array(
			'group_id' => $group_id,
			'group_label' => $group_label,
			'item_id' => $item_id,
			'data' => $data,
		);
/*
 *	Campi Extra di Cimy 
 *  Certificazione Linguistica
 */		
		$Nelementi++;
		$item_id = "campi-aggiuntivi-1";
        $group_id = 'campi-aggiuntivi';
		$group_label = "Campi Aggiuntivi dell'Utente";
		if( function_exists( "get_cimyFieldValue")){
			$data = array();
			$values = get_cimyFieldValue(1, false);
			foreach ($values as $value) {
				$data[]=array(
					'name' => $value['LABEL'],
					'value' => cimy_uef_sanitize_content($value['VALUE'])
				);
			}
			$export_items[] = array(
				'group_id' => $group_id,
				'group_label' => $group_label,
				'item_id' => $item_id,
				'data' => $data,
			);			
		}
/*
 *	Campi Newsletter 
 *  
 */	
		$Nelementi++;
		$item_id = "newsletter-1";
        $group_id = 'newsletter';
		$group_label = "Newsletter Corsi";
		if( function_exists( "alo_em_is_subscriber")){
			$data = array();
			$data[]=array(
					'name' => "Ricezione NewsLetter",
					'value' => (alo_em_is_subscriber($email_address)?"Si":"No"));
			global $wpdb;
			$lists = $wpdb->get_var ( $wpdb->prepare( "SELECT lists FROM {$wpdb->prefix}easymail_subscribers WHERE email = %s", $email_address ) );
			if ( $lists	) {
				$user_lists = explode ( "|", trim ($lists, "|" ) );
				if ( is_array($user_lists) && $user_lists[0] != false  ) {
					asort ( $user_lists ); 
				}
				$mailinglists = alo_em_get_mailinglists( 'admin,public' );
				$languages = alo_em_get_all_languages ( true );
				$NewsLetter =array();
				foreach ( $user_lists as $user_list ) {
					 $NewsLetter[]= alo_em_translate_multilangs_array ( alo_em_get_language(),$mailinglists[$user_list]["name"], true ) ;
				}
				$NewsLetter=implode(", ",$NewsLetter);
			}else
				$NewsLetter ="";
			
			$data[]=array(
					'name' => "NewsLetter a cui sei iscritto",
					'value' => $NewsLetter);			
			$export_items[] = array(
				'group_id' => $group_id,
				'group_label' => $group_label,
				'item_id' => $item_id,
				'data' => $data,
			);			
		}
/*
 * Corsi dell'utente
 */
		$Utente=new Utenti();
		$CorsiUtente=$Utente->get_Corsi_per_User($User->ID);
		if($CorsiUtente["FormatoreTutor"]){
			$Nelementi++;
			$group_id = 'corsi-formatore-tutor';
			$group_label = "Corsi in cui sei Formatore/Tutor";
			$NomeCorsi=array();
			$data = array();
			$i=0;
			foreach ( $CorsiUtente["FormatoreTutor"] as $Corsi) {
				$i++;
				$item_id = "formatoretutor-".$i;
				$data[] = array(
					  'name' => "Corsi",
					  'value' => $Corsi->post_title);
			}
			$export_items[] = array(
			  'group_id' => $group_id,
			  'group_label' => $group_label,
			  'item_id' => $item_id,
			  'data' => $data);	
		}
		if($CorsiUtente["Corsista"]){
			$Nelementi++;
			$group_id = 'corsi-corsista';
			$group_label = "Corso a cui hai partecipato";
			$NomeCorsi=array();
			$data = array();
			$i=0;
			foreach ( $CorsiUtente["Corsista"] as $Corsi) {
				$i++;
				$item_id = "corsista-".$i;
				$data[] = array(
					  'name' => "Corsi",
					  'value' => $Corsi->event_name);
			}
			$export_items[] = array(
			  'group_id' => $group_id,
			  'group_label' => $group_label,
			  'item_id' => $item_id,
			  'data' => $data);	
		}
		return array(
			'data' => $export_items,
			'done' => $Nelementi,
		);
	}
	public function corsi_personal_data_exporter( $exporters ) {
		$Utente=new Utenti();
		$CorsiUtente=$Utente->get_Corsi_per_User($User->ID);

		$exporters['gestione-corsi'] = array(
			'exporter_friendly_name' => "Gestione Corsi",
			'callback' => array($this,'EsportaDatiUtente'));
  return $exporters;
}

	public function mod_urllogin($message, $key, $user_login, $user_data ){
		global $aio_wp_security;
		$new_url_login=$aio_wp_security->configs->get_value('aiowps_login_page_slug');
		$message=str_replace("wp-login.php",$new_url_login,$message);
		return $message;
	}
	
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$Pagina=filter_input(INPUT_GET,"page");
		$Pagine=array("gestione_corsi",
				      "dati_scuole",
				      "creazione_utenti",
				      "corsi",
				      "scadenziariocorsi",
				      "corsisti",
				      "formatoritutor",
				      "utility",
				      "statistiche",
					  "stampe");
			$Operazione=filter_input(INPUT_GET,"op");
		if(in_array($Pagina,$Pagine) Or get_post_type()=="event"){
			wp_enqueue_style( $this->plugin_name."_appendGrid", plugin_dir_url( __FILE__ ) . 'css/jquery.appendGrid-1.6.3.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."_ui_theme", plugin_dir_url( __FILE__ ) . 'css/jquery-ui.theme.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."_ui", plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."_ui_structure", plugin_dir_url( __FILE__ ) . 'css/query-ui.structure.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name."_fonts_Awesome", plugin_dir_url( __FILE__ ) . 'font/css/all.css',  array(),  $this->version, 'all' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_style( $this->plugin_name."_gestione-corsi-admin", plugin_dir_url( __FILE__ ) . 'css/gestione-corsi-admin.css', array(), $this->version, 'all' );
			if($Pagina=="corsi" And ($Operazione=="gestiscritti" Or $Operazione=="assformtutor")){
				wp_enqueue_style( $this->plugin_name."_gestione-iscritti", plugin_dir_url( __FILE__ ) . 'css/gestione-iscritti-corso.css', array(), $this->version, 'all' );		
		}	
			}		

	}
	
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	/**
 * Box meta: Recipients
 */
	function gestione_corsi_alo_em_meta_recipients ( $post ) {
		wp_nonce_field( ALO_EM_PLUGIN_DIR, "edit_newsletter" );
		$FormTut= new class_formatoritutor();
		$IMieiCorsi=$FormTut->get_MyCorsi();
		$recipients = alo_em_get_recipients_from_meta ( $post->ID );
		//print_r ( alo_em_get_recipients_from_meta($post->ID) ); print_r ( alo_em_get_all_languages() );
		echo "<p " . ( ( alo_em_count_recipients_from_meta( $post->ID ) == 0 ) ? "class=\"easymail-txtwarning\"" : "" ) ." >";
		echo "<strong>" .__("Selected recipients", "alo-easymail") .": ". alo_em_count_recipients_from_meta( $post->ID ) ."</strong></p>";

		if ( alo_em_get_newsletter_status ( $post->ID ) == "sent" || alo_em_is_newsletter_recipients_archived ( $post->ID ) ) {
			echo "<div class=\"easymail-alert\"><p>". __("This newsletter was already sent", "alo-easymail") .".</p>";
			echo "</div>";
			return; // exit
		}

		if ( alo_em_count_newsletter_recipients ( $post->ID ) > 0 ) {
			echo "<div class=\"easymail-alert\"><p>". __("The creation of the recipients list has already started", "alo-easymail") .".</p>";
			echo "<p><input type=\"checkbox\" name=\"easymail-reset-all-recipients\" id=\"easymail-reset-all-recipients\" value=\"yes\" /> ";
			echo "<strong><label for=\"easymail-reset-all-recipients\">". __("Check this flag to delete the existing list and save new recipients now", "alo-easymail") .".</label></strong></p>";
			echo "</div>";
		}

		?>

		<div class="easymail-edit-recipients easymail-edit-recipients-subscribers">
			<ul class="level-1st">
				<li class="list-title"><?php _e("Newsletter subscribers", "alo-easymail"); ?>:</li>
				<li>
					<?php $checked = ( isset( $recipients['subscribers']) ) ? ' checked="checked" ' : ''; ?>
					<label for="easymail-recipients-all-subscribers" class="easymail-metabox-update-count"><?php echo __("All subscribers", "alo-easymail") /*. " (". count( alo_em_get_recipients_subscribers() ) .")"*/; ?></label>
					<input type="checkbox" name="easymail-recipients-all-subscribers" id="easymail-recipients-all-subscribers" value="checked" <?php echo $checked ?> class="easymail-metabox-update-count" />
				</li>

				<?php // if mailing lists
				$mailinglists = alo_em_get_mailinglists( 'admin,public' );
				foreach($mailinglists as $Key=>$MLs){
					$Trovato=False;
					foreach($MLs['name'] as $ML){
						if(!in_array($ML,$IMieiCorsi)){
							unset($mailinglists[$Key]);
						}
					}
				}
				if ( $mailinglists ) : ?>
					<li><a href="#" class="easymail-filter-subscribers-by-lists"><?php _e("Filter subscribers according to lists", "alo-easymail"); ?>...</a></li>
					<li>
						<ul id="easymail-filter-ul-lists" class="level-2st">
							<?php
							foreach ( $mailinglists as $list => $val) {
								if ( $val['available'] == "deleted" || $val['available'] == "hidden" ) continue;
								$checked = ( isset( $recipients['list'] ) && in_array( $list, $recipients['list'] ) ) ? ' checked="checked" ' : '';
								?>
								<li>
									<label for="list_<?php echo $list ?>" class="easymail-metabox-update-count"><?php echo alo_em_translate_multilangs_array ( alo_em_get_language(), $val['name'], true ) /*. " (".  count ( alo_em_get_recipients_subscribers( $list ) ).")"*/; ?></label>
									<input type="checkbox" name="check_list[]" class="check_list easymail-metabox-update-count" id="list_<?php echo $list ?>" value="<?php echo $list ?>" <?php echo $checked ?>  />
								</li>
							<?php } ?>
						</ul>
					</li>
				<?php endif; // $mailinglists ?>

				<?php // if languages
				$languages = alo_em_get_all_languages( false );
				if ( $languages ) : ?>
					<li><a href="#" class="easymail-filter-subscribers-by-languages"><?php _e("Filter subscribers according to languages", "alo-easymail"); ?>...</a></li>
					<li>
						<ul id="easymail-filter-ul-languages" class="level-2st">
							<?php
							foreach ( $languages as $index => $lang) {
								$checked = ( ( isset( $recipients['lang'] ) && in_array( $lang, $recipients['lang'] )) || !isset( $recipients['lang'] ) ) ? ' checked="checked" ' : '';
								$tot_sub_x_lang = alo_em_count_subscribers_by_lang( $lang, true );
								?>
								<li>
									<label for="check_lang_<?php echo $lang ?>" class="easymail-metabox-update-count" > <?php echo esc_html ( alo_em_get_lang_name ( $lang ) ) /* . " (". $tot_sub_x_lang .")"*/; ?></label>
									<input type="checkbox" name="check_lang[]" class="check_lang easymail-metabox-update-count" id="check_lang_<?php echo $lang ?>" value="<?php echo $lang ?>" <?php echo $checked ?> />
								</li>
							<?php }
							$checked = ( (isset($recipients['lang']) && in_array( "UNKNOWN", $recipients['lang'] )) || !isset($recipients['lang']) ) ? ' checked="checked" ' : ''; ?>
							<li>
								<label for="check_lang_unknown" class="easymail-metabox-update-count"> <?php _e("Not specified / others", "alo-easymail"); ?>
									<?php /*echo ' ('. alo_em_count_subscribers_by_lang(false, true).')';*/ ?></label>
								<input type="checkbox" name="check_lang[]" class="check_lang easymail-metabox-update-count" id="check_lang_unknown" value="UNKNOWN" <?php echo $checked ?> />
							</li>
						</ul>
					</li>
				<?php endif; // $languages ?>


			</ul>

		</div><!-- /easymail-edit-recipients-subscribers -->


		<?php
	}
	

	public function enqueue_scripts() {

		$Pagina=filter_input(INPUT_GET,"page");
		wp_enqueue_script( $this->plugin_name."_gestione-corsi-admin", plugin_dir_url( __FILE__ ) . 'js/gestione-corsi-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-core','', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-tabs','', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'jquery-ui-draggable','', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-button','', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-sortable','', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-datepicker','', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-slider','', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-progressbar','', array( 'jquery' ), $this->version, false );
		if($Pagina=="corsi" Or $Pagina=="statistiche"){
			wp_enqueue_script( 'jquery-ui-tooltip','', array( 'jquery' ), $this->version, false );
		}		
		wp_enqueue_media();
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script( $this->plugin_name."_appendGrid", plugin_dir_url( __FILE__ ) . 'js/jquery.appendGrid-1.6.3.min.js', array( 'jquery' ), $this->version, false );
		$Operazione=filter_input(INPUT_GET,"op");
		if($Pagina=="corsi" And ($Operazione=="gestiscritti" Or $Operazione=="assformtutor")){
			wp_enqueue_script( $this->plugin_name."_gestione-iscritti", plugin_dir_url( __FILE__ ) . 'js/gestione-iscritti-corso.js', array( 'jquery' ), $this->version, false );			
		}
	}
		public function set_head_BE(){
	?>	
		<script type='text/javascript'>
			 <?php echo "ajaxsec = '".wp_create_nonce('adminsecretmemostatusPrenotazione')."';" ?>
		</script>
	<?php		
		}
	/** Hook per la gestione della posta in uscita
	 *  Imposta le mail in uscita come Testo
    */
	public function corsi_set_content_type( $content_type ) {
		return 'text/plain';
	}
	/** Hook per la gestione della posta in uscita
	 *  Imposta il WordWrap del Testo in questo modo viene disattivato, veniva impostato su 50 da Alo easy Mail
    */
	public function corsi_disable_WordWrap($phpmailer){
		$phpmailer->WordWrap = 0;
	}
	/** Hook per la gestione della posta in uscita
	 *  Permette di aggiungere in coda al messaggio il testo gestito nella bacheca e memorizzato nell'Opzione  formazione_testofootermail
	 *  di solito viene utilizzata per inserire il messagio che avvisa di non rispondere alle mail di sitema in quanto la mail di sitema non viene letta e vengono proposte le mail 
	 *  di amministrazione per i contatti
    */
	public function corsi_personalize_mail( $args ) {
		$TestoFooterMail= get_option('formazione_testofootermail'); 
		if(isset($TestoFooterMail) And (strlen($TestoFooterMail)>0 And $TestoFooterMail!="")){
			$new_wp_mail = array(
				'to'          => $args['to'],
				'subject'     => $args['subject'],
				'message'     => $args['message'],
				'headers'     => $args['headers'],
				'attachments' => $args['attachments'],
			);
			$new_wp_mail['message'].=$TestoFooterMail;
		}
		return $new_wp_mail;
	}
	public function Alo_NL_crea_box_Dest($post){
		$user = wp_get_current_user();
		if ( in_array('docente_corsi', (array) $user->roles)  OR in_array('organizzatore_corsi', (array) $user->roles ) ) {
			remove_meta_box( "alo_easymail_newsletter_recipients", "newsletter", "side" );
			add_meta_box( "gestione_corsi_alo_em_meta_recipients", __("Recipients", "alo-easymail"),  array($this,"gestione_corsi_alo_em_meta_recipients"), "newsletter", "side", "high" );
		}
	}
	
	public function NuoveFunzionalitaMieiCorsi($cancel_link, $EM_Booking){
		$Utente=get_current_user_id();
		$Corso=new Gestione_Corso($EM_Booking->event_id);
		if (!$Corso->is_Iscritto($Utente)){
			return "";
		}
		$Registro=$Corso->creaRegistroCorsista($Utente);
//		var_dump($Registro);
		$Social=new Piattaforma_Social();
		$SitoSocial= get_option('formazione_sitosocial');
		$Ritorno='<i class="fa fa-info-circle fa-2x infoCorso" aria-hidden="true" title="Visualizza dati Corso" id="'.$EM_Booking->event_id.'"></i>';
/*
 * Stampa Attestato Corso
 */
		if(FUNZIONI::CorsoConsolidato($Corso)){
			$Ritorno.= ' <a href="'.admin_url().'admin.php?page=gestione_corsi&op=registro&event_id='.$EM_Booking->event_id.'&user_id='.$Utente.'&mod=stattestato&secur='.wp_create_nonce("AttestatoFrequenza").'"><i class="fa fa-graduation-cap fa-2x attestatoCorso" aria-hidden="true" title="Crea Attestato Corso" id="'.$EM_Booking->event_id.';'.get_current_user_id().'"></i></a>';
		}
		if($Social->IsSocial() And $Social->group_exists( $Corso->get_SlugCorso() ))
			if(filter_var($SitoSocial, FILTER_VALIDATE_URL) !== FALSE){
				$Ritorno.= ' <a href="'.$SitoSocial.'/gruppi/'.$Corso->get_SlugCorso().'" target="_blank"><i class="fas fa-globe fa-2x" title="Vai al sito Social"></i></a>';
			}
		$Ritorno.= '<div id="Info'.$EM_Booking->event_id.'" class="InfoRegistro" style="display:none">'
				. '	<h4>Lezioni</h4>'
				. '<ul>';
		foreach($Registro['Lezioni'] as $Lezione){
				if($Lezione['Data']<date("Y-m-d")){
					$Ritorno.= '	<li>'.FUNZIONI::FormatDataItaliano($Lezione['Data'])." ".($Lezione['Presenza']==1?"Presente":"Assente")."<br />".$Lezione['Note']."</li>";			
				}
		}
				$Ritorno.= '</ul>'
						. '</div>';
		return $Ritorno;
	}
	
	 /**  Funzione che codifica il box con i parametri personalizzati dei corsi
	 *  Valori codificati:
	 *						- Codice Corso
	 *						- Date delle lezioni
	 *						- Docente 
	 *						- Tutor
	 *						
	 */
	
	
	public function Crea_Box_Parametri($post){
		$Codice_corso=get_post_meta($post->ID, "_codiceCorso",TRUE);
		$Aula=get_post_meta($post->ID, "_aulaCorso",TRUE);
		$SeriaLezioni=get_post_meta($post->ID, "_lezioniCorso",TRUE);
		$Lezioni=($SeriaLezioni?unserialize( $SeriaLezioni):array());
		$SeriaAttivita=get_post_meta($post->ID, "_attivita",TRUE);
		$Attivita=($SeriaAttivita?unserialize( $SeriaAttivita):array());
		$OreLezioni=get_post_meta($post->ID, "_oreLezioni",TRUE);
//		$OreOnLine=get_post_meta($post->ID, "_oreOnLine",TRUE);
//		$OreOnLineIndividualizzate=get_post_meta($post->ID, "_oreOnLineIndividualizzate",TRUE);
		$Corso=new Gestione_Corso();
		$Docenti=get_post_meta($post->ID, "_docenteCorso",FALSE);
		$Tutor=get_post_meta($post->ID, "_tutorCorso",FALSE);
//		$ListaTutor=$Corso->crea_Lista_FormatoriTutor("Tutti",TRUE,"select","tutor","tutor","",$Tutor);
		$ListaDocTutor=$Corso->crea_Lista_FormatoriTutor("Tutti",TRUE,"Array");
		array_unshift($ListaDocTutor, array("Id"=>0,"Cognome"=>"Non Assegnato","Nome"=>""));
		$ElencoOre="";
		$LOre=array('00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30',
			  '10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30',
			  '20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30');
		$numItems=0;
		$MinutiTotali=0;
		foreach($Lezioni as $Lezione){
/*			$ListaOreI="";
			foreach($LOre as $Value){
				if($Value==$Lezioni[1])
				$ListaOreI.="<option value=\"$Value\">".$Value."</option>\n";
			}
*/			$MinutiTotali+=$Minuti=FUNZIONI::CalcolaTempo($Lezione);
			$Ore=intval($Minuti/60);
			$Ore=($Ore<10?"0".$Ore:$Ore);
			$Minuti=$Minuti%60;
			$Minuti=($Minuti<10?"0".$Minuti:$Minuti);
			$ElencoOre.= "<div id=\"Lezione[".$numItems."]\">
                    <blockquote data=\"".$numItems."\">
					Data: <input class=\"calendario\" type=\"text\" id=\"datalezione[".$numItems."]\" name=\"datalezione[".$numItems."]\" size=\"8\" value=\"$Lezione[0]\">
                    Inizio: ".Funzioni::ListaOre( 'orainizio['.$numItems.']', "orainizio_".$numItems, $Lezione[1],"orario" )." <a href=\"#\" class=\"CopiaOraInizio1 Puls\" title=\"Ricopia Ora Inizio Primo Blocco fino al fine della lista degli incontri\" data=\"".$numItems."\"> <i class=\"fas fa-clock\"></i></a>
                    Fine: ".Funzioni::ListaOre( 'orafine['.$numItems.']', "orafine_".$numItems, $Lezione[2],"orario"  )."  <a href=\"#\" class=\"CopiaOraFine1 Puls\" title=\"Ricopia Ora Fine Primo Blocco fino al fine della lista degli incontri\" data=\"".$numItems."\"> <i class=\"fas fa-clock\"></i></a>
                    Inizio: ".Funzioni::ListaOre( 'orainizio2['.$numItems.']', "orainizio2_".$numItems, $Lezione[3],"orario"  )." <a href=\"#\" class=\"CopiaOraInizio2 Puls\" title=\"Ricopia Ora Inizio Secondo Blocco fino al fine della lista degli incontri\" data=\"".$numItems."\"> <i class=\"fas fa-clock\"></i></a>
                    Fine: ".Funzioni::ListaOre( 'orafine2['.$numItems.']',"orafine2_".$numItems, $Lezione[4],"orario"  )." <a href=\"#\" class=\"CopiaOraFine2 Puls\"title=\"Ricopia Ora Fine Secondo Blocco fino al fine della lista degli incontri\" data=\"".$numItems."\"> <i class=\"fas fa-clock\"></i></a>
					<a href=\"#\" class=\"EliminaRiga PulsDel\"><i class=\"fas fa-calendar-times\"></i></a>
					Ore Lezione: <p id=\"OreLezione_".$numItems."\" style=\"display:inline;font-weight: bold;\" data=\"".$numItems."\">".$Ore.":".$Minuti."</p>
					</blockquote>
                  </div>";
			$numItems++;
		}
		$Ore=0;
		$Minuti=0;
		if($MinutiTotali>0){
			$Ore=intval($MinutiTotali/60);
			$Minuti=$MinutiTotali%60;		
		}
		$Ore=($Ore<10?"0":"").$Ore;
		$Minuti=($Minuti<10?"0":"").$Minuti;
		$StileOrePianificate="color:green;font-weight: bold;";
		if ($OreLezioni!=$Ore.":".$Minuti){
			$StileOrePianificate="color:red;font-weight: bold;";
		}
?>
		<input type="hidden" id="ElementiListaDocTutor" value='<?php echo json_encode($ListaDocTutor);?>' />
		<div id="corso">
			<h3>Dati Corso</h3>
			<label id="codice_corso"><strong>Codice Corso</strong></label>
			<input type="text" name="codice_corso" id="codice_corso" value="<?php echo $Codice_corso;?>" size="15"/>
			<label id="aula"><strong>Aula</strong></label>
			<input type="text" name="aula" id="aula" value="<?php echo $Aula;?>" />
		</div>
		<div id="docenti">
			<h3>Docenti <a href="#" id="AddDocente" title="Aggiungi Docente" class="PulsAdd"> <i class="fa fa-user-plus" aria-hidden="true"></i></a></h3>
			<?php 
				$i=1;
				foreach ($Docenti as $Doc){
					echo "<div id=\"Doce[".$i."]\" >
					<label><strong>Docente</strong></label> ";
					echo $Corso->crea_Lista_FormatoriTutor("Tutti",TRUE,"select","docente[".$i."]","docente[".$i."]","docenti",$Doc)." <a href=\"#\" class=\"EliminaDocente PulsDel\"><i class=\"fas fa-user-times\"></i></a>"
							. "</div>";
					$i++;
				}			
			?>
		</div>
		<div id="tutor">
			<h3>Tutor <a href="#" id="AddTutor" title="Aggiungi Tutor" class="PulsAdd"> <i class="fa fa-user-plus" aria-hidden="true"></i></a></h3> 
			<?php 
				$i=1;
				foreach ($Tutor as $Doc){
					echo "<div id=\"Tuto[".$i."]\" >
					<label><strong>Tutor</strong></label> ";
					echo $Corso->crea_Lista_FormatoriTutor("Tutti",TRUE,"select","tutor[".$i."]","tutor[".$i."]","tutor",$Doc)." <a href=\"#\" class=\"EliminaTutor PulsDel\"><i class=\"fas fa-user-times\"></i></a>"
							. "</div>";
					$i++;
				}			
			?>
		</div>
		<div id="ore">
			<h3>Ore attività corso</h3>
			<label for="ore_lezioni"><strong>Ore di lezione frontale</strong></label>
			<input type="text" name="ore_lezioni" id="ore_lezioni" value="<?php echo $OreLezioni;?>" size="4" maxlength="5"/> <strong>Ore pianificate:</strong> <p id="OreTotaliLezioni" style="display:inline;font-size:1.3em;<?php echo $StileOrePianificate;?>"><?php echo $Ore.":".$Minuti;?></p><br /><em>Inserire le ore nel formato <strong>OO:MM</strong></em>
			<div id="attivita">
				<br />
				<strong>Attività non in presenza</strong> <a href="#" id="AddAttivita" class="PulsAdd"> <i class="fas fa-plus-square"></i></a>
	<?php
		$numItems=0;
		foreach($Attivita as $Attiv){
			$Selezionato=($Attiv[2]=="Si"?"checked":"");
			echo "<div id=\"Attivita[".$numItems."]\" >
                    <blockquote>
					Attività: <input class=\"desatt\" type=\"text\" id=\"descrizione[".$numItems."]\" name=\"descrizione[".$numItems."]\" size=\"50\" value=\"$Attiv[0]\">
                    Ore max riconosciute: <input type=\"number\" id=\"ore[".$numItems."]\" name=\"ore[".$numItems."]\" size=\"3\" value=\"$Attiv[1]\" maxlength=\"3\" style=\"width:4em;\">
                    Gestione individualizzata: <input type=\"checkbox\" value=\"Si\" ".$Selezionato." id=\"individualizzata[".$numItems."]\" name=\"individualizzata[".$numItems."]\" \">
					<a href=\"#\" class=\"EliminaRigaAttivita PulsDel\"><i class=\"fas fa-trash\"></i></a>
					</blockquote>
                  </div>";
			$numItems++;
		}
	?>		</div>
		</div>
		<div id="date">
			<br />
			<h3 style="display:inline;">Date Lezioni</h3>  <a href="#" id="AddData" class="PulsAdd"><i class="fas fa-calendar-plus"></i></a>
			<?php echo $ElencoOre;?>
		</div>
	<br />
	<?php
	}
	/**
	 *	Funzione di callback che permette di creare le colonne personalizzate negli eventi in riferimento ai corsi
	 * @param type $defaults; array cin le colonne degli eventi a cui aggiungere le nuove colonne personalizzate
	 * @return string
	 */
	public function Eventi_NuoveColonne($defaults) {  
		if ($_GET['post_type']=="event"){
			$defaults['Date_Lezioni'] = 'Date Lezioni';  
		}
	   return $defaults;  
	}  
	/**
	 *	Funzione di callback che permette di inserire il contenuto delle colonne personalizzate negli eventi in riferimento ai corsi
	 * @param type $column_name; nome della colonna
	 * @param type $post_ID; ID del post
	 */
	function Eventi_NuoveColonneContenuto($column_name, $post_ID) {  
		if ($_GET['post_type']=="event"){
			if ($column_name == 'Date_Lezioni') { 
				global $wpdb;	 
				$events_table = EM_EVENTS_TABLE;
				$Query="SELECT $events_table.event_id FROM $events_table WHERE $events_table.post_id=$post_ID";
				$results = $wpdb->get_results( $Query, ARRAY_A);
				$Id_Evento=$results[0]['event_id'];
				$StatoLezioni="";
				$Differenza="";
				if( is_numeric( $Id_Evento )){
					$Corso=new Gestione_Corso($Id_Evento);
					$OreLezioni=FUNZIONI::daOreMin_aMin($Corso->get_OreLezioni());
					$OreLezioniPianificate=$Corso->get_OreLezioniPianificate("TotOre");
					if($OreLezioni!=""){
						if($OreLezioni==$OreLezioniPianificate){
							$StatoLezioni="<i class=\"fas fa-calendar-check corsoSemVerde\" title=\"Tutte le ore di lezioni sono pianificate\"></i>";
						}else{
							$StatoLezioni="<i class=\"fas fa-calendar-check corsoSemRosso\" title=\"Non tutte le ore di lezioni sono state pianificate\"></i> ";
							$StatoLezioni.=($OreLezioniPianificate-$OreLezioni>0?"+":"")."<strong>".FUNZIONI::daMin_aOreMin($OreLezioniPianificate-$OreLezioni,"Stringa")."</string>";
						}
					}else{
						$StatoLezioni="<i class=\"fas fa-exclamation-circle corsoSemRosso\" title=\"Non sono state codificate le ore di lezione\"></i>";
					}				
				}
			echo $StatoLezioni;
			}
		}
	}
	/**
	 *  Funzione di callback che mi permette di creare il box con i parametri personalizzati dei corsi
	 *						
	 */
	public function Eventi_crea_box_Date($post){
	    add_meta_box('Eventi_Campicampi', 'Dati Corso', array($this,'Crea_Box_Parametri'), 'event', 'advanced', 'high');
	}
	/**
	 *  Funzione di callback che mi permette di salvare il box con i parametri personalizzati dei corsi
	 *						
	 */	
	public function Eventi_save_box_Date($post_id){
		global $wpdb,$table_prefix;
		if ( $_POST['post_type'] == 'event' ) {	
			$CodiceCorso=trim(filter_input(INPUT_POST,"codice_corso"));
			update_post_meta( $post_id, '_codiceCorso', $CodiceCorso);
			if (isset($_POST["aula"])) update_post_meta( $post_id, '_aulaCorso', $_POST["aula"]);
		delete_post_meta($post_id, '_docenteCorso');
		delete_post_meta($post_id, '_tutorCorso');
		if(isset($_POST["docente"])){
			
			foreach($_POST["docente"] as $Formatore){
				add_post_meta($post_id, "_docenteCorso", $Formatore);
			}
		}
		if(isset($_POST["tutor"])){
			foreach($_POST["tutor"] as $Tutor){
				add_post_meta($post_id, "_tutorCorso", $Tutor);
			}			
		}
			$SortDate=array();
			$SortedLezioni=array();
			if(!is_null($_POST["datalezione"])){
				foreach($_POST["datalezione"] as $key => $value){
					$SortDate[]=FUNZIONI::FormatDataDB($value); 
				}
				sort($SortDate); 
				foreach($SortDate as $i => $data) { 
					$SortDate[$i] = FUNZIONI::FormatDataItaliano( $data); 
				}
				$Lezioni=array();
				if (isset($_POST["datalezione"])){
					foreach($_POST["datalezione"] as $key => $value){
						$Lezioni[]=array($value,$_POST["orainizio"][$key],$_POST["orafine"][$key],$_POST["orainizio2"][$key],$_POST["orafine2"][$key]);
					}
					foreach ($SortDate as $Data){
						foreach($Lezioni as $Lezione){
							if($Lezione[0]==$Data)
								$SortedLezioni[]=$Lezione;
						}
					}
				}
			}
		update_post_meta( $post_id, '_lezioniCorso', serialize($SortedLezioni));
		$Attivita=array();
		if(!is_null($_POST["descrizione"])){
			$Attivita=array();
			foreach($_POST["descrizione"] as $key => $value){
				$Attivita[]=array($value,$_POST["ore"][$key],$_POST["individualizzata"][$key]);
			}
		}
		update_post_meta( $post_id, '_attivita', serialize($Attivita));
		update_post_meta( $post_id, '_oreLezioni', $_POST["ore_lezioni"]);
		}		
	}

	/**
	 * Modifica il form di registrazione Standard
	 */
	public function CustomFormCorsi(){
		$SA="";
		$GestScuole= get_option('gestione_scuole'); 
		if(!empty($_REQUEST['scuola'])){
			$SA=$_REQUEST['scuola'];
		}
		$Scuole=get_option("Corsi_CM_scuole","");
		$Lista=explode("\n",$Scuole);
		$TestoLista="";//<option value=''>Scuola non assegnata</option>";
		foreach($Lista as $Scuola)
			$TestoLista.="<option value='".substr($Scuola,0,10)."' ".(substr($Scuola,0,10)==$SA?"selected":"").">".stripslashes($Scuola)."</option>";
		if( !is_user_logged_in() && apply_filters('em_booking_form_show_register_form',true) ): ?>
	<?php //User can book an event without registering, a username will be created for them based on their email and a random password will be created. ?>
	<input type="hidden" name="register_user" value="1" />
	<p>
		<label for='user_name'>Nome</label>
		<input type="text" name="user_name" id="user_name" class="input" value="<?php if(!empty($_REQUEST['user_name'])) echo esc_attr($_REQUEST['user_name']); ?>" required/>
	</p>
	<p>
		<label for='user_cognome'>Cognome</label>
		<input type="text" name="user_cognome" id="user_cognome" class="input" value="<?php if(!empty($_REQUEST['user_cognome'])) echo esc_attr($_REQUEST['user_cognome']); ?>" required/>
	</p>
	<p>
		<label for='user_email'>Email</label> 
		<input type="text" name="user_email" id="user_email" class="input" value="<?php if(!empty($_REQUEST['user_email'])) echo esc_attr($_REQUEST['user_email']); ?>"  />
	</p>
	<p>
		<label for='booking_comment'>Note</label>
		<textarea name='booking_comment' rows="2" cols="20" class="input"><?php echo !empty($_REQUEST['booking_comment']) ? esc_attr($_REQUEST['booking_comment']):'' ?></textarea>
	</p>
<?php if($GestScuole=="Si"):?>
	<p>
		<label for='scuola'>Scuola di appartenenza</label>
		<select required name="scuola" id="Scuola"  class="input">
			<?php echo $TestoLista;?>
		</select>
	</p>
<?php  endif;
		do_action('em_register_form'); //careful if making an add-on, this will only be used if you're not using custom booking forms ?>					
<?php endif; 
	}
	
	/**
	 * Funzione per la memorizzazione dei campi aggiunti nel form di registrazione
	 */
	public function MemoCampiAggiuntiviFormRegistrazione($registration, $EM_Booking, $EM_Notices){
		if($registration){
			$GestScuole= get_option('gestione_scuole'); 
			$Prenotazione=new EM_Booking();
			$Consenso = (!empty($_REQUEST['data_privacy_consent'])) ? wp_kses_data($_REQUEST['data_privacy_consent']):'';
			$Scuola = (!empty($_REQUEST['scuola'])) ? wp_kses_data($_REQUEST['scuola']):'';
			$Cognome = (!empty($_REQUEST['user_cognome'])) ? wp_kses_data($_REQUEST['user_cognome']):'';
			if ($Consenso){
				add_user_meta($EM_Booking->person_id,"Consenso_Privacy",$Consenso);
			}
			if($Scuola And $GestScuole=="Si"){
				add_user_meta($EM_Booking->person_id,"Scuola",$Scuola);
			}
			if($Cognome){
				update_user_meta($EM_Booking->person_id,"last_name",$Cognome);
			}
			$home=set_url_scheme( get_option( 'home' ), 'http' );
			$siteurl =set_url_scheme( get_option( 'siteurl' ), 'http' );
			if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
				$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
				$pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
				$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
				$home_path = trailingslashit( $home_path );
			} else {
				$home_path = ABSPATH;
			}
			$home_path=str_replace( '\\', '/', $home_path );		
			if($fp=fopen($home_path."wp-content/GestioneCorsi/log/Iscrizioni.log","a")){
				if($Prenotazione->get(array("event_id"=>$EM_Booking->event->event_id,
										"person_id"=>$EM_Booking->person_id))){
					$P=$Prenotazione->booking_id;
				}else{
					$P="Non Definita";
				}
				$Dati=array(
					"Slug"          => $EM_Booking->event->event_slug,
					"Proprietario"  => $EM_Booking->event->event_owner,
					"Nome"			=> $EM_Booking->event->event_name,
					"InizioOra"		=> $EM_Booking->event->event_start_time,
					"FineOra"		=> $EM_Booking->event->event_end_time,
					"TuttoGiorno"	=> $EM_Booking->event->event_all_day,
					"InizioData"	=> $EM_Booking->event->event_start_date,
					"FineData"		=> $EM_Booking->event->event_attributes['CodiceEvento'],
					"Nome"			=> $EM_Booking->event->event_name,
					"Spazi"			=> $EM_Booking->spaces
				);
				$Riga= date("Y-m-d h:m:s")."\t|Richiesta Iscrizione\t|".$EM_Booking->person_id."\t|".$EM_Booking->person->data->display_name."\t|".$EM_Booking->event->event_id."\t|".$P."\t|".$EM_Booking->booking_spaces."\t|In Attesa\t|Sito\t|".serialize($Dati)."¶\r\n";
				fwrite($fp,$Riga);
				fclose($fp);
			}		
		}
		return $registration;
	}
	
	/*
	 * Funzione che setta il campo congnome della form di registrano del FrontEnd
	 */
	public function SettaCampiFormRegistrazione($user_data){
		$nome= strtolower(preg_replace('/[^\w\-]+/u', "", $_REQUEST['user_name']));
		$cognome=strtolower(preg_replace('/[^\w\-]+/u', "", $_REQUEST['user_cognome']));
		$user_data['user_name']=$nome." ".$cognome;
		$user_data['user_login']= $nome.".".$cognome;
		return $user_data;	
	}
	
	/*
	 * Funzione per la memorizzazione del log degli eventi Utenti quando avviene la registrazione dal FrontEnd
	 */
	public function NuovoUtenteRegistrato($user_id){
		global $em_temp_user_data;
		$home=set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl =set_url_scheme( get_option( 'siteurl' ), 'http' );
	    if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
			$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
			$home_path = trailingslashit( $home_path );
		} else {
			$home_path = ABSPATH;
		}
		$home_path=str_replace( '\\', '/', $home_path );		
		if($fp=fopen($home_path."wp-content/GestioneCorsi/log/Utenti.log","a")){
			$Riga= date("Y-m-d h:m:s")."\t|Creazione Utenti\t|".$user_id."\t|".$em_temp_user_data['user_login']."\t|Utente Creato\t|Sito\t|".serialize($em_temp_user_data)."¶\r\n";
			fwrite($fp,$Riga);
			fclose($fp);
		}
		
		return($user_id);
	}
	/**
	 * Aggiunge una pagina di amministrazione 
	 *
	 * @since    1.0.0
	 */
	
	public function Menu_Corsi() {

		global $log;
		$plugin_utenti = new Utenti();
		$plugin_scuole = new Scuole();
		$GestScuole= get_option('gestione_scuole'); 
		
		add_menu_page('Bacheca', 'Gestione Corsi', 'corsi_gest_ass', 'gestione_corsi', array($this,'Bacheca'), 'dashicons-screenoptions', 63);
		if($GestScuole=="Si")
	 		add_submenu_page('gestione_corsi', 'Dati Scuole', 'Dati Scuole', 'corsi_admin', 'dati_scuole', array($plugin_scuole,'DatiScuole') );
	 	add_submenu_page('gestione_corsi', 'Utenti', 'Gestione Utenti', 'corsi_organizzatore', 'creazione_utenti', array($plugin_utenti,'CreaUtenti') );
	 	if ($this->isInstalledEM){
			add_submenu_page('gestione_corsi', 'Corsi', 'Corsi', 'corsi_gest_ass', 'corsi',array($this,'GestioneCorsi'));
			add_submenu_page('gestione_corsi', 'Scadenziario Corsi', 'Scadenziario Corsi', 'corsi_gest_ass', 'scadenziariocorsi',array($this,'ScadenziarioCorsi') );
		}
	 	add_submenu_page('gestione_corsi', 'Corsisti', 'Corsisiti', 'corsi_organizzatore', 'corsisti',array($this,'GestioneCorsisti') );
	 	add_submenu_page('gestione_corsi', 'Fromatori Tutor', 'Formatori Tutor', 'corsi_organizzatore', 'formatoritutor',array($this,'GestioneFormatoriTutor') );
	 	add_submenu_page('gestione_corsi', 'Utility', 'Utility', 'corsi_admin', 'utility',array($this,'Utility') );
	 	add_submenu_page('gestione_corsi', 'Statistiche', 'Statistiche', 'corsi_organizzatore', 'statistiche',array($this,'Statistiche') );
	 	add_submenu_page('gestione_corsi', 'Stampe', 'Stampe', 'corsi_organizzatore', 'stampe',array($this,'Stampe') );
	 	add_submenu_page('gestione_corsi', 'Logs', 'Logs', 'corsi_admin', 'logs',array($log,'VisualizzaLog') );
	}

	protected function Lista_Corsisti(){
		$table = new class_corsisti(); // Il codice della classe a seguire
		$table->prepare_items(); // Metodo per elenco campi

		  // Definizione variabili per contenere i valori
		  // di paginazione e il nome della pagina visualizzata

		  $page  = filter_input(INPUT_GET,'page' ,FILTER_SANITIZE_STRIPPED);
		  $paged = filter_input(INPUT_GET,'paged',FILTER_SANITIZE_NUMBER_INT);

		  echo '<div class="wrap">';
		  echo '<h2>Corsisti</h2>';

		// Form di ricerca da aggiungere prima della tabella
		 // indicare i campi hidden che si vogliono conservare
		  if(isset($_REQUEST['k']) and !empty($_REQUEST['k'])){
			  $Campo=$_REQUEST['k'];			  
		  }
		 echo '<form method="get" style="float:left;margin-right:10px;">';
		 echo '<select name="k" id="k" >'
		 . '<option value="Cognome" '.($Campo=="Cognome"?"selected":"").'>Cognome</option>'
		 . '<option value="Nome"'.($Campo=="Nome"?"selected":"").'>Nome</option>'
		 . '<option value="Scuola"'.($Campo=="Scuola"?"selected":"").'>Scuola</option>'
		 . '</select>';
		 echo '<input type="hidden" name="page" value="'.$page. '"/>';
		   $table->search_box( 'Ricerca','search_id');
		 echo '</form>'
		   . '<a href="'.admin_url().'admin.php?page=corsisti&op=exportcorsisti&secur='. wp_create_nonce("Corsistitocsv").'" title="Scarica elenco corsisti in Csv"><i class="fa fa-download fa-2x" aria-hidden="true"></i></a>';
		// Form per contenere la tabella con elenco records
		  // presenti nel database e campi definiti nella classe

		  echo '<form id="persons-table" method="GET">';
		  echo '<input type="hidden" name="paged" value="'.$paged.'"/>';
			$table->display(); // Metodo per visualizzare elenco records
		  echo '</form>';

		  echo '</div>';
	}
	/**
	 * Pagina Principale per la gestione dei corsi
	 */
	protected function Lista_Corsi($Titolo="",$Id_user=0,$Tipo="_docenteCorso",$Cosa=""){
		global $wpdb;
		$Utenti=new Utenti();
		$scope_names = array (
		'past' => __ ( 'Past events', 'events-manager'),
		'all' => __ ( 'All events', 'events-manager'),
		'future' => __ ( 'Future events', 'events-manager')
	);
	//die();
	$Social=new Piattaforma_Social();
	$SitoSocial= get_option('formazione_sitosocial'); 
	$idCorsoDaGestire= filter_input(INPUT_GET, "idCorso");
	$Corsi= filter_input(INPUT_GET, "_corsi");
	if( !current_user_can('corsi_admin') And  !current_user_can('corsi_gest_corsi') And (isset($idCorsoDaGestire) And !$Utenti->is_CorsoForMe($idCorsoDaGestire))){
		wp_die( '<h2>Non sei autorizzato ad eseguire questa OPERAZIONE</h2>');
	}
	if(current_user_can("corsi_gest_ass")){
		$CorsiPersonali=$Utenti->get_CorsiPersonali(get_current_user_id());
		if(count($CorsiPersonali)>0){
			$CorsiP=implode(",",$CorsiPersonali);
			}else{
				$CorsiP="1";
			}
	}
	if($Id_user>0){
		$Sql="SELECT $wpdb->posts.ID "
			. "FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID=$wpdb->postmeta.post_id "
			. "WHERE $wpdb->postmeta.meta_key=\"%s\" AND  $wpdb->postmeta.meta_value=%d";
		$ResultSet=$wpdb->get_results($wpdb->prepare($Sql,$Tipo,$Id_user),OBJECT );
//		echo $wpdb->prepare($Sql,$Tipo,$Id_user);
		$CorsiP="";
		foreach($ResultSet as $Rec){
			$CorsiP.=$Rec->ID.",";
		}
		$CorsiP=substr($CorsiP,0,-1);
	}
	if($idCorsoDaGestire){
		$CorsiP=$idCorsoDaGestire;
	}
	$action_scope = ( !empty($_REQUEST['em_obj']) && $_REQUEST['em_obj'] == 'corsi_table' );
	$action = ( $action_scope && !empty($_GET ['action']) ) ? $_GET ['action']:'';
	$order = ( $action_scope && !empty($_GET ['order']) ) ? $_GET ['order']:'ASC';
	$limit = ( $action_scope && !empty($_GET['limit']) ) ? $_GET['limit'] : 50;//Default limit
	$page = ( $action_scope && !empty($_GET['pno']) ) ? $_GET['pno']:1;
	$offset = ( $action_scope && $page > 1 ) ? ($page-1)*$limit : 0;
	$scope = ( $action_scope && !empty($_GET ['scope']) && array_key_exists($_GET ['scope'], $scope_names) ) ? $_GET ['scope']:'future';
	if($Cosa!="")
		$scope = ( array_key_exists($Cosa, $scope_names) ) ? $Cosa:'future';
	$owner = !current_user_can('manage_others_bookings') ? get_current_user_id() : false;
	if(current_user_can("corsi_gest_corsi") Or current_user_can("corsi_admin") Or current_user_can("corsi_organizzatore")){
		if($idCorsoDaGestire Or $Id_user>0){
			$events = EM_Events::get( array('scope'=>$scope, 'limit'=>$limit, 'offset' => $offset, 'order'=>$order, 'bookings'=>true, 'owner' => $owner, 'post_id' => $CorsiP) );
			$events_count = EM_Events::count( array('scope'=>$scope, 'limit'=>0, 'order'=>$order, 'bookings'=>true, 'owner' => $owner, 'post_id' => $CorsiP) );	
		}else{
			$events = EM_Events::get( array('scope'=>$scope, 'limit'=>$limit, 'offset' => $offset, 'order'=>$order, 'bookings'=>true, 'owner' => $owner) );
			$events_count = EM_Events::count( array('scope'=>$scope, 'limit'=>0, 'order'=>$order, 'bookings'=>true, 'owner' => $owner) );				
		}
	}else{
			$events = EM_Events::get( array('scope'=>$scope, 'limit'=>$limit, 'offset' => $offset, 'order'=>$order, 'bookings'=>true, 'owner' => $owner, 'post_id' => $CorsiP) );
			$events_count = EM_Events::count( array('scope'=>$scope, 'limit'=>0, 'order'=>$order, 'bookings'=>true, 'owner' => $owner , 'post_id' => $CorsiP) );		
	}
	$use_events_end = get_option ( 'dbem_use_event_end' );
	?>
    <div class="wrap">
	<h2>Corsi <?php echo $Titolo;?></h2>
	<div class="wrap em_bookings_events_table em_obj">
		<form id="posts-filter" action="" method="get">
			<?php if(!empty($_GET['page'])): ?>
			<input type='hidden' name='page' value='corsi' />
			<?php endif; ?>		
			<input type="hidden" name="em_obj" value="corsi_table" />
			<div class="tablenav">			
				<div class="alignleft actions">
					<!--
					<select name="action">
						<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions' ); ?></option>
						<option value="deleteEvents"><?php esc_html_e( 'Delete selected','events-manager'); ?></option>
					</select> 
					<input type="submit" value="<?php esc_html_e( 'Apply' ); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
					 --> 
					<select name="scope">
						<?php
						foreach ( $scope_names as $key => $value ) {
							$selected = "";
							if ($key == $scope)
								$selected = "selected='selected'";
							echo "<option value='$key' $selected>$value</option>  ";
						}
						?>
					</select>
					<input id="post-query-submit" class="button-secondary" type="submit" value="<?php esc_html_e( 'Filter' )?>" />
				</div>
				<!--
				<div class="view-switch">
					<a href="/wp-admin/edit.php?mode=list"><img class="current" id="view-switch-list" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="List View" alt="List View" name="view-switch-list" /></a> <a href="/wp-admin/edit.php?mode=excerpt"><img id="view-switch-excerpt" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="Excerpt View" alt="Excerpt View" name="view-switch-excerpt" /></a>
				</div>
				-->
				<?php 
				if ( $events_count >= $limit ) {
					$events_nav = em_admin_paginate( $events_count, $limit, $page, array('em_ajax'=>0, 'em_obj'=>'corsi_table'));
					echo $events_nav;
				}
				?>
			</div>
				
			<?php
			if (empty ( $events )) {
				// TODO localize
				echo "Non ci sono eventi da gestire";
			} else {
			?>
			<div class='table-wrap'>	
			<table class="widefat">
				<thead>
					<tr>
						<th ><?php esc_html_e( 'Event', 'events-manager'); ?></th>
						<th >Codice</th>
						<th style="min-width:40px;">Date</th>
						<th style="min-width:100px;">Stato</th>
						<th style="min-width:150px;">Operazioni</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rowno = 0;
					foreach ( $events as $event ) {
						/* @var $event EM_Event */
						$Corso=new Gestione_Corso($event->event_id);
						$NumLezioni=$Corso->get_NumLezioni();
						$NumCorsistiDB=$Corso->get_NumCorsistiDB();
						$NumIscritti=$Corso->get_NumIscritti();
						$NumIscrittiNL=$Corso->get_NumIscrittiMailingList();
						$NumPresenze=$Corso->get_NumCorsistiPresenze();
						$NumPrevistiIscritti=$NumIscritti*$NumLezioni;
						$OreLezioni=$Corso->get_OreLezioni();
						$OreLezioniPianificate=FUNZIONI::daMin_aOreMin($Corso->get_OreLezioniPianificate("TotOre"),"Stringa");
						$StatoLezioni="";
						if($OreLezioni!=""){
							if($OreLezioni==$OreLezioniPianificate){
								$StatoLezioni="<i class=\"fas fa-calendar-check corsoSemVerde\" title=\"Tutte le ore di lezioni sono pianificate\"></i>";
							}else{
								$StatoLezioni="<i class=\"fas fa-calendar-check corsoSemRosso\" title=\"Non tutte le ore di lezioni sono state pianificate\"></i>";
							}
						}else{
							$StatoLezioni="<i class=\"fas fa-exclamation-circle corsoSemRosso\" title=\"Non sono state codificate le ore di lezione\"></i>";
						}
						$StatoCorso=array("Corso"=>"corsoSemVerde","MailingList"=>"corsoSemVerde","Social"=>"corsoSemVerde");
						$DesStatoCorso=array("Corso"=>"Il Corso è Creato Correttamente","MailingList"=>"Mailing List Creata ed Allineata","Social"=>"Gruppo Social Creato");
						$StatisticheCorso="Numero Lezioni  :".$NumLezioni."\n"
								  . "Numero Iscritti   :".$NumIscritti."\n"
								  . "Numero Corsisti  :".$NumCorsistiDB;
/*						$Sql="SELECT count($wpdb->table_lezioni.DataLezione) as Num "
									."FROM $wpdb->table_lezioni "
									."WHERE $wpdb->table_lezioni.IDCorso= %d ";
						$SqlFiltrato=$wpdb->prepare($Sql,$event->event_id);
						$NumLezioniDB=$wpdb->get_results($SqlFiltrato, ARRAY_A);
						$NumLezioniDB=$NumLezioniDB[0]['Num'];
*/						$NumLezioniDB=$Corso->get_NumLezioniDB();	
//echo $NumLezioniDB." ".$NumLezioni;
						if (($IdNL=Funzioni::get_MailingListID($Corso->get_CodiceCorso()))!==FALSE){
							$IscrittiNewsLetter=count(alo_em_get_recipients_subscribers($IdNL));
						}else{
							$IscrittiNewsLetter=0;
						}
						//echo $IdNL;wp_die();
						$ver_IscrittiCorsisti=$Corso->ver_IscrittiCorsisti();
//						echo $NumIscrittiNL." - ".$IscrittiNewsLetter." - ".$ver_IscrittiCorsisti."<br />";
												if($NumIscrittiNL!=$IscrittiNewsLetter or !$ver_IscrittiCorsisti){
							$StatoCorso["MailingList"]="corsoSemRosso";
							$DesStatoCorso["MailingList"]="Bisogna Creare o Ricreare la Mailing List";
						}
						if($Corso->get_CodiceCorso()==""){
							$StatoCorso["MailingList"]="corsoSemRosso";
							$DesStatoCorso["MailingList"]="Non si può Creare la Mailing List perchè manca il Codice Corso";							
						}
						$VoceSocial="Social";
						$id_Gruppo=0;
						if ($Social->IsSocial()){
							if(!$Social->group_exists( $Corso->get_SlugCorso() )){
								$StatoCorso["Social"]="corsoSemRosso";
								$VoceSocial="Crea Gruppo Social";
								$DesStatoCorso["Social"]="Bisogna Creare il Gruppo Social";
							}else{
								$id_Gruppo=$Social->get_group_id($Corso->get_SlugCorso());
								$MembriGruppo=$Social->get_Soggetti_Gruppo(intval($id_Gruppo));
								if(count($MembriGruppo)>$NumCorsistiDB){
									$StatoCorso["Social"]="corsoSemRosso";
									$VoceSocial="Allinea Gruppo Social";
									$DesStatoCorso["Social"]="Bisogna Allineare i Corsisti(".$NumCorsistiDB.") con i Membri del Gruppo Social(".count($MembriGruppo).")";
								}
								if(count($MembriGruppo)<$NumCorsistiDB){
									$StatoCorso["Social"]="corsoSemRosso";
									$VoceSocial="Allinea Gruppo Social";
									$DesStatoCorso["Social"]="Bisogna Allineare i Membri del Gruppo(".count($MembriGruppo).") Social con i Corsisti(".$NumCorsistiDB.")";
								}
							}
						}else{
							$StatoCorso["Social"]="corsoSemDisabilitato";
							$DesStatoCorso["Social"]="Sito Social non Impostato";
						}
						$DaAllineareCorsisti=FALSE;
						if($NumLezioniDB<$NumLezioni OR $NumCorsistiDB==0 OR $NumIscritti>$NumCorsistiDB){
		//					echo $event->event_id." ".$NumPrevistiIscritti." ".$NumPresenze." ".$NumIscritti."-".$NumCorsistiDB." NLDB-".$NumLezioniDB." NL-".$NumLezioni."<br />";
							$StatoCorso["Corso"]="corsoSemRosso";
							$StatoVoceGeneraCorso="<span class=\"evidenziaVoceImportante\"> Genera Corso</span>";
							if($NumLezioniDB<$NumLezioni){
								$DesStatoCorso["Corso"]="Bisogna Rigenerare il Corso per Aggiungere Lezioni";
							}
							if($NumCorsistiDB==0){
								$DesStatoCorso["Corso"]="Bisogna Generare il Corso";
							}
							if($NumIscritti>$NumCorsistiDB){
								$DesStatoCorso["Corso"]="Bisogna Rigenerare il Corso";
							}
						}else{
							$StatoVoceGeneraCorso=" Genera Corso";
						}
						if(FUNZIONI::CorsoConsolidato($Corso)){
							$StatoCorso["Cosolidato"]="<i class=\"fas fa-lock corsoSemVerde\" title=\"Corso Consolidato\"></i>";
						}else{
							$StatoCorso["Cosolidato"]="<i class=\"fas fa-lock-open corsoSemRosso\" title=\"Corso in Esecuzione\"></i>";
						}
						if($NumCorsistiDB>$NumIscritti){
							$DaAllineareCorsisti=TRUE;
							$StatoCorso["Corso"]="corsoSemRosso";
							$DesStatoCorso["Corso"]="Bisogna Allineare i Corsisti";
						}
						if(($Motivo=$Corso->are_LezioniAllineate())!==TRUE){
							$StatoCorso["Corso"]="corsoSemRosso";
							if($Motivo==1){
//								echo $event->event_id." ".$NumPrevistiIscritti." ".$NumPresenze."  NLDB-".$NumLezioniDB." NL-".$NumLezioni."<br />";
								$StatoVoceGeneraCorso="<span class=\"evidenziaVoceImportante\"> Genera Corso</span>";
								$DesStatoCorso["Corso"]="Bisogna Rigenerare il Corso per Aggiungere le Lezioni Mancanti";
								$DaAllineareLezione= FALSE;
							}else{
								$DesStatoCorso["Corso"]="Bisogna Cancelalre le Lezioni Mancanti";
								$DaAllineareLezione= TRUE;
							}
						}else{
							$DaAllineareLezione= FALSE;
						}
//						echo $NumPrevistiIscritti." ".$NumPresenze." ";
						$StatoVoci="";
						if($StatoCorso["Corso"]=="corsoSemRosso"){
							$StatoVoci="class=\"disabled\"";
						}
						$rowno++;
						$class = ($rowno % 2) ? ' class="alternate"' : '';
						// FIXME set to american
						$localised_start_date = date_i18n(get_option('date_format'), $event->start);
						$localised_end_date = date_i18n(get_option('date_format'), $event->end);
						$style = "";
						$today = date ( "Y-m-d" );
						if ($event->start_date < $today && $event->end_date < $today){
							$style = "style ='background-color: #FADDB7;'";
						}							
						?>
						<tr <?php echo "$class $style"; ?>>
							<td>
								<strong>
									<?php   if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or current_user_can('corsi_organizzatore')){
												echo $event->output('#_BOOKINGSLINK'); 
											}else{
												echo $event->output('#_EVENTLINK');
											}?>
								</strong>
								&ndash; 
								<?php esc_html_e("Booked Spaces",'events-manager') ?>: <?php echo $event->get_bookings()->get_booked_spaces()."/".$event->get_spaces() ?>
								<?php if( get_option('dbem_bookings_approval') == 1 ) : ?>
									| <?php esc_html_e("Pending",'events-manager') ?>: <?php echo $event->get_bookings()->get_pending_spaces(); ?>
								<?php endif; ?>
							</td>
							<td><?php 
							if($Corso->is_Duplicate_CodiceCorso()){
								echo "<spam style=\"color:red;cursor:help;\" title=\"".$Corso->Titolo_Duplicate_CodiceCorso()."\">".$Corso->get_CodiceCorso()."</spam>";
							}else{
								echo $Corso->get_CodiceCorso();
							}
							if($Corso->get_Lezioni("lista")!=""){
								$ElencoDate="title=\"".$Corso->get_Lezioni("lista")."\"";
								$StileElencoDate="";
							}else{
								$ElencoDate="title=\"Date non definite\"";
								$StileElencoDate="style=\"color:red;\"";
							}
							?></td>
							<td><span style="cursor:help;"><?php echo "<i class=\"fas fa-calendar fa-lg\" $ElencoDate $StileElencoDate aria-hidden=\"true\"></i>";?> <?php echo $StatoLezioni;?></span></td>
							<td>
								<span style="cursor:help;"><?php echo $StatoCorso["Cosolidato"];?>
														   <i class="fas fa-graduation-cap <?php echo $StatoCorso["Corso"];?>" title="<?php echo $DesStatoCorso["Corso"];?>"></i> 
														   <i class="fa fa-envelope <?php echo $StatoCorso["MailingList"];?>" title="<?php echo $DesStatoCorso["MailingList"];?>"></i> 
														   <i class="fas fa-comments <?php echo $StatoCorso["Social"];?>" title="<?php echo $DesStatoCorso["Social"];?>"></i>
								</span>
							</td>
							<td><?php  //echo $Corso->get_NumIscritti()." ".$Corso->get_NumLezioni()." ".$Corso->get_NumCorsistiDB()." ".$Corso->get_NumCorsistiPresenze();

								?>
								<div class="btn-group" id="Corso<?php echo $event->event_id;?>">
									<a class="btn  btn-primary" href="#" style="width: 80px;" title="<?php echo $StatisticheCorso;?>"><i class="fa fa-user fa-fw"></i> Strumenti</a>
									<a class="btn btn-primary dropdown-toggle <?php //echo $StatoSem;?>" data-toggle="dropdown" href="#" style="width: 10px;" >
									  <span class="fa fa-caret-down" title="Toggle dropdown menu" ></span>
									</a>
									<ul class="dropdown-menu">
				<?php
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') OR (current_user_can("corsi_organizzatore") And $Corso->isMy())){
?>										<li>
											<a href="?page=corsi&op=duplicacorso&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("DuplicaCorso");?>" ><i class="fas fa-clone"></i> Duplica Corso</a>
										</li>
										<li>
											<a href="?page=corsi&op=gestiscritti&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("IscrittiGesti");?>" ><i class="fa fa-users fa-fw"></i> Gestisci Iscrizioni</a>
										</li>
										<li>
											<a href="?page=corsi&op=creacorso&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("CorsoCrea");?>"><i class="fa fa-cog fa-fw"></i> <?php echo $StatoVoceGeneraCorso; ?></a>
										</li>
<?php							if($DaAllineareCorsisti And $StatoCorso["Corso"]=="corsoSemRosso"){
										?><li>
											<a href="?page=corsi&op=analisiallineacorsisti&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("CorsistiAllinea");?>"><span class="evidenziaVoceImportante"><i class="fas fa-cogs" aria-hidden="true"></i> Allinea Corsisti>Iscritti</span></a>
										</li>								
							<?php }
							if($DaAllineareLezione /*And $StatoCorso["Corso"]!="corsoSemRosso"*/){//$NumLezioniDB>$NumLezioni And 
										?><li>
											<a href="?page=corsi&op=analisiallinealezioni&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("LezioniAllinea");?>"><span class="evidenziaVoceImportante"><i class="fa fa-cogs" aria-hidden="true"></i> Allinea Lezioni</span></a>
										</li>								
							<?php } ?>
										<li>
											<a href="?page=corsi&op=eliminacorso&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("EliminaCorso");?>"><i class="fa fa-trash-alt fa-fw"></i> Elimina Corso</a>
										</li>
										<li>
											<a class="Tooltip" href="?page=corsi&op=spostadata&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce( 'spostadata' );?>" title="Sposta la data della lezione"  id="L;<?php echo $event->event_id.";";?>"> <i class="fa fa-calendar Tooltip" aria-hidden="true" ></i> Sposta data lezione</a>
										</li>
										<li >
											<a href="?page=corsi&op=assformtutor&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("TutorFormAss");?>"><i class="fa fa-graduation-cap fa-fw"></i> Assegna Formatore/Tutor</a>
										</li>
										<?php 
							if($this->isInstalledML){	
								if ($StatoCorso["Corso"]!="corsoSemRosso" And $StatoCorso["MailingList"]=="corsoSemRosso"){?>	
										<li  <?php echo $StatoVoci;?>>
											<a href="?page=corsi&op=comunicazioni&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("Inoizacinumoc");?>"><span class="evidenziaVoceImportante"><i class="fas fa-cogs" aria-hidden="true"></i> Crea Mailinglist Corso</span></a>
										</li>
						<?php	} 
							}							
							if($Social->IsSocial()){
								if($StatoCorso["Social"]=="corsoSemRosso"){
									if($StatoCorso["Corso"]!="corsoSemRosso"){	
										if($VoceSocial=="Allinea Gruppo Social"){
									?>	<li>
											<a href="?page=corsi&op=analisiallineasocial&event_id=<?php echo $event->event_id;?>&group_id=<?php echo $id_Gruppo;?>&secur=<?php echo wp_create_nonce("SocialSincro");?>"><span class="evidenziaVoceImportante"><i class="fa fa-cogs" aria-hidden="true"></i> <span class="<?php echo $StatoCorso["Social"];?>"> <?php echo $VoceSocial;?></span></a>
										</li>
<?php									}else{
									?>	<li>
											<a href="?page=corsi&op=createsocial&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("SocialCreate");?>"><i class="fas fa-comments" aria-hidden="true"></i><span class="<?php echo ($StatoCorso["Social"]!="corsoSemVerde"?$StatoCorso["Social"]:"");?>"> <?php echo $VoceSocial;?></span></a>
										</li>
<?php									}
									}
								}						
							}
						}
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or current_user_can('corsi_gest_ass') Or current_user_can("corsi_organizzatore")){
									if(!$this->isInstalledML Or $StatoCorso["Corso"]=="corsoSemRosso" Or $StatoCorso["MailingList"]=="corsoSemRosso"){	
										$StatoVociNewLetter="class=\"disabled\"";
									}else{
										$StatoVociNewLetter="";
									}
?>									<li  <?php echo $StatoVociNewLetter;?>>
										<a href="?page=corsi&op=createnewsletter&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("NewsLetterCreate");?>"><i class="fa fa-envelope-open" aria-hidden="true"></i> NewsLetter</a>							
									</li>	
<?php								if($Social->IsSocial() And $StatoCorso["Social"]!="corsoSemRosso"){
										if(filter_var($SitoSocial, FILTER_VALIDATE_URL) !== FALSE){?>
									<li>
										<a href="<?php echo $SitoSocial;?>/gruppi/<?php echo $event->event_slug;?>" target="_blank"><i class="fas fa-globe"></i> Sito Gruppo Social</a>
									</li>	
<?php									}
									}?>					
									<li <?php echo $StatoVoci;?>>
										<a href="?page=corsi&op=corsisti&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("Itsisroc");?>"><i class="fa fa-users" aria-hidden="true"></i> Elenco Corsisti</a>
									</li>
									<li  <?php echo $StatoVoci;?>>
										<?php if ($StatoCorso["Corso"]=="corsoSemRosso"){
										?>	<a href="#"><i class="fa fa-list-alt fa-fw"></i> Registro Corso</a>
										<?php }else{
										?>	<a href="?page=corsi&op=registro&event_id=<?php echo $event->event_id;?>&secur=<?php echo wp_create_nonce("Ortsiger");?>"><i class="fa fa-list-alt fa-fw"></i> Registro Corso</a>
										<?php } ?>											
									</li>
<?php					}?>
									</ul>
								</div>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			</div>
			<?php
			} // end of table
			?>
			<div class='tablenav'>
				<div class="alignleft actions">
				<br class='clear' />
				</div>
				<?php if (!empty($events_nav) &&  $events_count >= $limit ) : ?>
				<div class="tablenav-pages">
					<?php
					echo $events_nav;
					?>
				</div>
				<?php endif; ?>
				<br class='clear' />
			</div>
		</form>
	</div>
    </div>
	<?php		
	}
	

	private function CreaTabellaStatCorsi($IdCorso){
		$Corso=new Gestione_Corso($IdCorso);
		$Dati=$Corso->StatisticaCorso();
		$PercentP=$Dati['Presenze%'];
		$OreLezioni=$Corso->get_OreLezioni();
		if($Dati['Totale_Ore_Lezioni']!=$OreLezioni){
			$StatoOreCorso="<span style=\"color:red;\">".$Dati['Totale_Ore_Lezioni']."/".$OreLezioni."</span>";
		}else{
			$StatoOreCorso=$Dati['Totale_Ore_Lezioni']."/".$OreLezioni;
		}
		if($Dati['Numero_Lezioni']>0)
			$PercLezioni=(($Dati['LezioniFatte']/$Dati['Numero_Lezioni'])*100);
		else
			$PercLezioni=0;
		$StaCorso= "<tr>
				<td><a href=\"admin.php?page=statistiche&op=statcorso&idcorso=".$Dati['IDCorso']."\" target=\"_blank\">".$Dati['Nome_Corso']."</a></td>
				<td><strong>".$Corso->get_CodiceCorso()."</strong></td>
				<td class=\"informazioni\">".$Dati['LezioniFatte']."/".$Dati['Numero_Lezioni']
		. "<div id=\"Semaforo".$Dati['IDCorso']."\" ></div>
   <script type=\"text/javascript\">
      google.charts.load('current', {'packages':['gauge']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['Label', 'Value'],
          ['Lezioni', 0],
         ]);

        var options = {
		  width: 400, height: 120,
          redFrom: 100, redTo: 100,
          yellowFrom:75, yellowTo: 99,
          minorTicks: 5
        };

        var chart = new google.visualization.Gauge(document.getElementById('Semaforo".$Dati['IDCorso']."'));

        data.setValue(0, 1, Math.round(".$PercLezioni."));
        chart.draw(data, options);
  
      }
    </script>					

				</td>
				<td class=\"informazioni orelezione\">".$StatoOreCorso."</td>
				<td>
					<div id=\"Grafico".$Dati['IDCorso']."\" ></div>
    <script type=\"text/javascript\">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

		function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['%', 'Corsisti'],
          ['0%',  ".$PercentP[0]."],
          ['10%', ".$PercentP[1]."],
          ['20%',  ".$PercentP[2]."],
          ['30%',  ".$PercentP[3]."],
          ['40%', ".$PercentP[4]."],
          ['50%',  ".$PercentP[5]."],
          ['60%',  ".$PercentP[6]."],
          ['70%', ".$PercentP[7]."],
          ['80%',  ".$PercentP[8]."],
          ['90%',  ".$PercentP[9]."],
          ['100%',  ".$PercentP[10]."]
        ]);

       var options = {
          title: 'Corsisti totali ".$Dati['Numero_Corsisti']."',
			'width':400,
			'height':200,
			'backgroundColor': 'transparent',
			'pieSliceText': 'value',
			'is3D':true,
		};
        var chart = new google.visualization.PieChart(document.getElementById('Grafico".$Dati['IDCorso']."'));
        chart.draw(data, options);
      }
    </script>	

				</td>
			</tr>";
			return $StaCorso;
	}
	
	public function HeadStatistiche(){
?>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<?php		
	}
	public function stampe(){
		$Documenti=new Gestione_Documenti();
//		$CorsiPeriodo=FUNZIONI::get_CorsiPeriodoFormazione();
		$Date= get_option('PeriodoFormazione');
		$Periodi= unserialize( $Date );
		$Operazione=filter_input(INPUT_GET,'op');
		$Formato=filter_input(INPUT_GET,'fo');
		switch($Operazione){
			case "corsiAperti":
				$nonce=filter_input(INPUT_GET,'secur');
				if (!wp_verify_nonce( $nonce, 'Stampe' )) {
					die( 'Errore di sicurezza' ); 
				}
				if($Formato=="Xls"){
					$Documenti->CreaStampaCorsiAperti("Xls");
					echo '<meta http-equiv="refresh" content="0;url=admin.php?page=stampe&op=exportstampaCorsiAperti"/>';
				}else{
					echo '<meta http-equiv="refresh" content="0;url=admin.php?page=stampe&op=stampe&mod=CorsiAperti"/>';
					die();
				}
				break;

		}
		?>		
			<div class="wrap">
				<h2>Gestione Stampe/Esportazione Dati</h2>
				<div id="Stampe">
					<table id="StampeCorsi" class="wp-list-table widefat striped" style="margin-top:20px;">
					<thead>
						<tr>
							<th>Stampa</th>
							<th class="informazioni">Descrizione</th>
							<th class="informazioni">crea Pdf</th>
							<th class="informazioni">crea Excel</th>
						</tr>
					</thead>
						<tbody>
							<tr>
								<td>Corsi aperti nel periodo </td>
								<td>Stampa dei corsi del periodo <?php echo $Periodi[0]." - ".$Periodi[1];?> che non sono stati ancora consolidati.<br />
									Le informazioni riportate sono:
										<ul>
											<li>Titolo del Corso</li>
											<li>Nome/Email Formatori</li>
											<li>Nome/Email Tutor</li>
											<li>Lezioni Aperte</li>
										</ul>
								</td>
								<td><a href="<?php echo admin_url();?>admin.php?page=stampe&op=corsiAperti&fo=Pdf&secur=<?php echo wp_create_nonce("Stampe")?>" title="Stampa corsi aperti in Pdf"><i class="far fa-file-pdf fa-2x"></i></a></td>
								<td><a href="<?php echo admin_url();?>admin.php?page=stampe&op=corsiAperti&fo=Xls&secur=<?php echo wp_create_nonce("Stampe")?>" title="Stampa corsi aperti in Escel"><i class="far fa-file-excel fa-2x"></i></a></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		<?php
	}
	public function statistiche(){
		$Operazione=filter_input(INPUT_GET,'op');
		$IdCorso=filter_input(INPUT_GET,'idcorso');
		switch($Operazione){
			case "statcorso":
				$this->vis_StatisticheCorso($IdCorso);
				break;
			case "exportstatistiche":
				$nonce=filter_input(INPUT_GET,'secur');
				if (!wp_verify_nonce( $nonce, 'StatisticheDWN' )) {
				    die( 'Errore di sicurezza' ); 
				}
				$Excel=new Gestione_Documenti();
				$Excel->CreaStatistiche();
				echo '<div id="message" class="updated"><p><strong>File Statistiche creato correttamente</strong></p></div>
				<meta http-equiv="refresh" content="2;url=admin.php?page=statistiche&op=exportstatistichedw"/>';
				break;
			default:
				$CorsiPeriodo=FUNZIONI::get_CorsiPeriodoFormazione();
				$Date= get_option('PeriodoFormazione');
				$Periodi= unserialize( $Date );
		?>		
			<div class="wrap">
				<h2>Statistica Corsi</h2>
				<h3>Periodo di Formazione <?php echo $Periodi[0]." - ".$Periodi[1];?><br />Corsi : <?php echo count($CorsiPeriodo);?></h3>
				<div style="margin-top:20px;font-size: 1.5em;font: bold;">
					Scarica Statische Periodo in Excel <a href="<?php echo admin_url();?>admin.php?page=statistiche&op=exportstatistiche&secur=<?php echo wp_create_nonce("StatisticheDWN")?>" title="Scarica statistiche corsi"><i class="fa fa-download fa-2x" aria-hidden="true"></i></a>
				</div>
				<div id="Dati">
					<table id="DatiStatistici" class="wp-list-table widefat striped" style="margin-top:20px;">
					<thead>
						<tr>
							<th>Corso</th>
							<th>Codice</th>
							<th class="informazioni">Lezioni</th>
							<th class="informazioni">Ore Lezione</th>
							<th class="informazioni">Presenze</th>
						</tr>
					</thead>
						<tbody>
<?php
				foreach($CorsiPeriodo as $CorsoP){
					//echo $CorsoP->event_id." - ";
					echo $this->CreaTabellaStatCorsi($CorsoP->event_id);
				}					
?>					
						</tbody>
					</table>
				</div>
			</div>
		<?php
				break;
		}
	}
	
	public function vis_StatisticheCorso($IdCorso){
		$Corso=new Gestione_Corso($IdCorso);
		$Totali=$Corso->get_TempoLezioni();
		$Presenze=$Corso->StatisticheCorsoDettaglio($Totali);
		$OreMin=FUNZIONI::daMin_aOreMin($Totali[0]);
		$DateLezioni=$Totali[2];
		$SeriaAttivita=get_post_meta($Corso->get_CodicePost(), "_attivita",TRUE);
		$Attivita=($SeriaAttivita?unserialize( $SeriaAttivita):array());
		$NLP=0;
?>		
	<div class="wrap" id="FormCreaUtenti">
	    <h2>Statistiche Corso <?php echo $Corso->get_TitoloCorso();?></h2>
		<div id="tabstat">
		<ul>
		  <li><a href="#tabstat-1">Dati Generali</a></li>
		  <li><a href="#tabstat-2">Lezioni</a></li>
		  <li><a href="#tabstat-3">Corsisti</a></li>
		</ul>
		<div id="tabstat-1">
			<table class="widefat striped tabDati">
				<tr>
					<th>Stato Corso</th>
					<td><?php echo (FUNZIONI::CorsoConsolidato($Corso)?"<span style=\"color:green\">Chiuso</span>":"<span style=\"color:red\">Aperto</span>");?></td>
				</tr>
				<tr>
					<th>Numero Lezioni</th>
					<td><?php echo $Corso->get_NumLezioni();?></td>
				</tr>
				<tr>
					<th>Totale Ore di Lezione</th>
					<td><?php echo $OreMin['Ore'].":".$OreMin['Min'];?></td>
				</tr>
				<tr>
					<th>Numero Corsisti</th>
					<td><?php echo $Corso->get_NumIscritti();?></td>
				</tr>
			</table>
		</div>
		<div id="tabstat-2">
						<table class="widefat striped">
							<thead>
								<tr>
<?php
				foreach($DateLezioni as $DataL){
					echo "<th style=\"text-align:center;\">".$DataL[0]."</th>";
					$NLP++;
				}
				foreach($Attivita as $Attiv){
					echo "<th style=\"text-align:center;\">".$Attiv[0]."</th>";
				}
?>								</tr>
							</thead>
							<tbody>
								<tr>
<?php
				foreach($DateLezioni as $DataL){
					echo "<td style=\"text-align:center;\">"."<span style=\"font-weight: bold;padding:5px;color:#".($DataL[2]=="C"?"FFF":"000").";background-color:".($DataL[2]=="C"?"red":"#FFFF00").";\">".$DataL[1]."</span></td>";
				}
				foreach($Attivita as $Attiv){
					echo "<th style=\"text-align:center;\">".$Attiv[1]."</th>";
				}
?>										
								</tr>
							</tbody>
						</table>
			<p><em>Tempo espresso in minuti. In <span style="color:#fff;background-color:red;font-weight: bold;padding:5px;">Rosso</span> è indicato il tempo delle lezioni svolte invece in <span style="color:#000;background-color:#FFFF00;font-weight: bold;padding:5px;">Giallo</span> le lezioni da svolgere</em></p>
		</div>
		<div id="tabstat-3">				
						<table class="widefat striped">
							<thead>
								<tr>
									<th>Corsista</th>
									<th>Frequenza</th>
<?php				foreach($DateLezioni as $DataLezione){
						echo "<th style=\"text-align:center;\">".substr($DataLezione[0],0,6).substr($DataLezione[0],-2) ."</th>";
					}
					foreach($Attivita as $Attiv){
					echo "<th style=\"text-align:center;\">".$Attiv[0]."</th>";
				}

?>								</tr>
							</thead>
							<tbody>
								<tr>
<?php		
//echo "<pre>";print_r($Presenze);echo "</pre>";wp_die();
				foreach($Presenze as $IdCorsista => $Presenza){
					echo "<td>".$Presenza[0]."</td>
								<td>".$Presenza[1]."%</td>";
				foreach($Presenza[2]as $DatiPresenza){
					if(substr($DatiPresenza[0], -1, 4)!="0000")
						echo "<td style=\"text-align:center;\">".$DatiPresenza[2]." (".$DatiPresenza[1].")</td>";
					else
						echo "<td style=\"text-align:center;\">".$DatiPresenza[1]."</td>";
			}
					?>
								</tr>
<?php												
					}
?>					
							</tbody>
						</table>
			
		</div>
	</div>
		<?php
	}

	public function GestioneCorsisti(){
		if (filter_input(INPUT_GET,'op')) {	
			$scelta=filter_input(INPUT_GET,'op');
//			$event_id= filter_input(INPUT_GET, 'event_id');
//			$Corso=new Gestione_Corso($event_id);
			switch ($scelta){
				default:
					$this->Lista_Corsisti();
					break;
			}
		}else{
			$this->Lista_Corsisti();
		}	
	}
	protected function Lista_FormatoriTutor(){
		$table = new class_formatoritutor(); // Il codice della classe a seguire
		$table->prepare_items(); // Metodo per elenco campi

		  // Definizione variabili per contenere i valori
		  // di paginazione e il nome della pagina visualizzata

		  $page  = filter_input(INPUT_GET,'page' ,FILTER_SANITIZE_STRIPPED);
		  $paged = filter_input(INPUT_GET,'paged',FILTER_SANITIZE_NUMBER_INT);

		  echo '<div class="wrap">';
		  echo '<h2>Formatori Tutor</h2>';

		// Form di ricerca da aggiungere prima della tabella
		 // indicare i campi hidden che si vogliono conservare
		  if(isset($_REQUEST['k']) and !empty($_REQUEST['k'])){
			  $Campo=$_REQUEST['k'];			  
		  }
		 echo '<form method="get" style="float:left;">';
		 echo '<select name="k" id="k" >'
		 . '<option value="Cognome" '.($Campo=="Cognome"?"selected":"").'>Cognome</option>'
		 . '<option value="Nome"'.($Campo=="Nome"?"selected":"").'>Nome</option>'
		 . '<option value="Scuola"'.($Campo=="Scuola"?"selected":"").'>Scuola</option>'
		 . '</select>';
		 echo '<input type="hidden" name="page" value="'.$page. '"/>';
		   $table->search_box( 'Ricerca','search_id');
		 echo '</form>';
		// Form per contenere la tabella con elenco records
		  // presenti nel database e campi definiti nella classe

		  echo '<form id="persons-table" method="GET">';
		  echo '<input type="hidden" name="paged" value="'.$paged.'"/>';
			$table->display(); // Metodo per visualizzare elenco records
		  echo '</form>';

		  echo '</div>';
	}
	public function GestioneFormatoriTutor(){
		if (filter_input(INPUT_GET,'op')) {	
			$scelta=filter_input(INPUT_GET,'op');
//			$event_id= filter_input(INPUT_GET, 'event_id');
//			$Corso=new Gestione_Corso($event_id);
			switch ($scelta){
				case "visform":
					$nonce = filter_input(INPUT_GET,'sec');
					$Id_utente = filter_input(INPUT_GET,'user_id');
					if ( ! wp_verify_nonce($nonce,"VisCorsiFormatore")) {
						wp_die( 'Qui c\'è qualcosa che non va!<br />Stai cercando di fare il furbo' ); 
					}else{
						$user_info = get_userdata($Id_utente);
						$this->Lista_Corsi(" in cui <strong>".ucwords($user_info->last_name)." ".ucwords($user_info->first_name)."</strong> è Formatore",$Id_utente,"_docenteCorso","all");
						
					}
					break;
				case "vistutor":
					$nonce = filter_input(INPUT_GET,'sec');
					$Id_utente = filter_input(INPUT_GET,'user_id');
					if ( ! wp_verify_nonce($nonce,"VisCorsiTutor")) {
						wp_die( 'Qui c\'è qualcosa che non va!<br />Stai cercando di fare il furbo' ); 
					}else{
						$user_info = get_userdata($Id_utente);
						$this->Lista_Corsi(" in cui <strong>".ucwords($user_info->last_name)." ".ucwords($user_info->first_name)."</strong> è Tutor",$Id_utente,"_tutorCorso","all");
						
					}
					break;				
				default:
					$this->Lista_FormatoriTutor();
					break;
			}
		}else{
			$this->Lista_FormatoriTutor();
		}	
	}	
	
	/**
	 * Pagina di gestione delle iscrizioni
	 */
	public function GestioneCorsi(){
	//TODO Simplify panel for events, use form flags to detect certain actions (e.g. submitted, etc)
		if (filter_input(INPUT_GET,'op')) {	
			$scelta=filter_input(INPUT_GET,'op');
			$event_id= filter_input(INPUT_GET, 'event_id');
			$Corso=new Gestione_Corso($event_id);
			if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
				switch ($scelta){
					case "assformtutor":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'TutorFormAss' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->ass_FormatoreTutor();
						}
						break;
					case "creacorso":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'CorsoCrea' ) ) {
							FUNZIONI::die_secur();
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->genera_Corso();
						}
						break;
					case "duplicacorso":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'DuplicaCorso' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->DuplicaCorso();
						}
						break;				
					case "duplicazionecorso":
						$NewName= filter_input(INPUT_GET,"NewName");
						$NewCod=filter_input(INPUT_GET,"NewCod");
						$MantieniFormatori=filter_input(INPUT_GET,"Formatori");
						if(is_null($MantieniFormatori))
							$MantieniFormatori=false;
						else
							$MantieniFormatori=true;
						$MantieniTutor=filter_input(INPUT_GET,"Tutor");
						if(is_null($MantieniTutor))
							$MantieniTutor=false;
						else
							$MantieniTutor=true;
						$IscrizioniAperte=filter_input(INPUT_GET,"Iscrizioni");
						if(is_null($IscrizioniAperte))
							$IscrizioniAperte=false;
						else
							$IscrizioniAperte=true;
						$MantieniDate=filter_input(INPUT_GET,"Date");
						if(is_null($MantieniDate))
							$MantieniDate=false;
						else
							$MantieniDate=true;
						$MantieniAttivita=filter_input(INPUT_GET,"Attivita");
						if(is_null($MantieniAttivita))
							$MantieniAttivita=false;
						else
							$MantieniAttivita=true;
						$TipoIscrizione=filter_input(INPUT_GET,"TipoIscrizione");
						$Corsisti=array();
						$IDAppo=$_GET['IDUM'];
						if(is_array($IDAppo))
							foreach($IDAppo as $IDUP){
								$Corsisti[]=$IDUP;
							}
						$NumGGAI=filter_input(INPUT_GET,"GGAI");
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'DuplicaCorso' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->EseguiDuplicazioneCorso($NewName,$NewCod,$MantieniFormatori,$MantieniTutor,$IscrizioniAperte,$NumGGAI,$MantieniDate,$MantieniAttivita,$TipoIscrizione,$Corsisti);
						}
						break;				
					case "gestiscritti":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'IscrittiGesti' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->gest_Iscritti();
						}
						break;				
					case "addscritti":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'IscrittiAdd' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->add_Iscritti();
						}
						break;				
					case "spostaiscritti":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'IscrittiSposta' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->move_Iscritti();
						}
						break;	
					case "duplicaiscrittiAC":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'IscrittiDuplica' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->duplica_Iscritti();
						}
						break;
					case "comunicazioni":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'Inoizacinumoc' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->gest_comunicazioni();
						}
						break;	
					case "createnewsletter":
						$newsLetter=new class_NewsLetter();
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'NewsLetterCreate' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$IdCorso= filter_input(INPUT_GET, 'event_id');
							$newsLetter->menu_Principale_NewsLetter($Corso);
						}
						break;
					case "analisiallineasocial":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'SocialSincro' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$IdGruppo=filter_input(INPUT_GET, 'group_id');
							$Corso->pre_AllineaGruppoSocial($IdGruppo);
						}
						break;
					case "allineagrupposocial":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'SocialSincroCorsisti' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corsisti=filter_input(INPUT_GET, 'corsisti');
							$Gruppo=filter_input(INPUT_GET, 'gruppo');
							$Operazione=filter_input(INPUT_GET, 'operazione');
							$Corso->AllineaGruppoSocial($Corsisti,$Gruppo,$Operazione);
						}
						break;
					case "createsocial":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'SocialCreate' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$IdCorso= filter_input(INPUT_GET, 'event_id');
							$Corso->CreaGruppoSocial();
						}
						break;
					case "generanewsletter":
						$newsLetter=new class_NewsLetter();
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'CreaNewsLetter' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$IdCorso= filter_input(INPUT_GET, 'event_id');
							$newsLetter->create_newsletter($Corso);
						}
						break;
					case "registro":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'Ortsiger' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->registro_Corso();
						}
						break;
					case "registroattestati":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'AttestatiFrequenza' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->registroattestati_Corso();
						}
						break;
					case "generaattestati":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'Ortsiger' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->registro_Corso();
						}
						break;
					case "analisiallinealezioni":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'LezioniAllinea' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->pre_AllineaLezioni();
						}
						break;		
					case "allinealezioni":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'AllineaLezioni' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->AllineaLezioni();
						}
						break;		
					case "analisiallineacorsisti":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'CorsistiAllinea' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->pre_AllineaCorsisti();
						}
						break;		
					case "allineacorsisti":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'AllineaCorsisti' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->AllineamentoCorsisti();
						}
						break;		
					case "corsisti":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'Itsisroc' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->ElencoCorsisti();
						}
						break;					
					case "spostadata":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'spostadata' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->move_DataLezione();
						}
						break;
					case "deletedata":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'deletedata' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->delete_DataLezione();
						}
						break;
					case "deleteDataLezione":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'deleteDataLezione' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Data= filter_input(INPUT_GET, "data");
							$Corso->remove_DataLezione($Data);
						}
						break;
					case "eliminacorso":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'EliminaCorso' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->pre_remove_Corso();
						}
						break;
					case "deleteCorso":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'removeCorso' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or  $Corso->isMy("Non hai il diritto di gestire questo corso")){
							$Corso->remove_Corso();
						}
						break;
					case "sconsolidadata":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'riapridata' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMy("Non hai il diritto di gestire questo corso")){							$Data= filter_input(INPUT_GET, "datacorso");
							remove_query_arg(array('op','event_id','secur','datacorso'));		
							$Corso->CambiaStatoData($Data,0);
							$Corso->registro_Corso();
						}
						break;
					case "consolidadata":
						$nonce = $_REQUEST['secur'];
						if ( ! wp_verify_nonce( $nonce, 'consolida' ) ) {
							FUNZIONI::die_secur(); 
							break;
						} 
						if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $Corso->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $Corso->isMy("Non hai il diritto di gestire questo corso")){							$Data= filter_input(INPUT_GET, "datacorso");
							remove_query_arg(array('op','event_id','secur','datacorso'));		
							$Corso->CambiaStatoData($Data,1);
							$Corso->registro_Corso();
						}
						break;
					default:
						$this->Lista_Corsi();
						break;
				}
			}
		}else{
			$this->Lista_Corsi();
		}
	}
	/**
	 * Pagina Principale del Plugin  
	 *	Page Name: Bacheca
	 * @since    1.0.0
	 */
	public function Bacheca(){
		switch (filter_input( INPUT_POST,"Submit" )){
			case "Salva ID consenso ricezione NewsLetter":
				update_option('formazione_IDconsensoNewsLetter',filter_input( INPUT_POST,"IDconsensoNewsLetter" ));
				break;
			case "Salva Testo Footer Email Inviate":
				update_option('formazione_testofootermail',filter_input( INPUT_POST,"TestoFooterMail" ));
				break;
			case "Salva Impostazioni logo":
				update_option('gest_c_logo',filter_input( INPUT_POST,"logo" ));
				break;
			case "Salva Impostazioni email di sistema":
				update_option('sender_name',filter_input( INPUT_POST,"sendername" ));
				update_option('sender_email',filter_input( INPUT_POST,"senderemail" ));
				break;
			case "Salva periodi formazione":
				$Da= filter_input(INPUT_POST, "DataInizioPeriodo");
				$A= filter_input(INPUT_POST, "DataFinePeriodo");
				$Date=array($Da,$A);
				update_option('PeriodoFormazione',serialize($Date));
				break;
			case "Salva dati Formazione OnLine":
				$AttivaFOL= filter_input(INPUT_POST, "AttivaFOL");
				$OreFOL= filter_input(INPUT_POST, "OreFOL");
				$FOL=array("Attiva" => $AttivaFOL,"Ore" => $OreFOL);
				update_option('formazione_online',serialize($FOL));
				break;
			case "Salva Firma Attestati":
				$FirmaAttestati= filter_input(INPUT_POST, "FirmaAttestati");
				$Luogo=filter_input(INPUT_POST, "luogo");
				update_option('formazione_luogo_emissione',$Luogo);
				update_option('formazione_firma',$FirmaAttestati);
				break;
			case "Salva Prefisso Tabelle Social":
				$PrefissoTabella= filter_input(INPUT_POST, "tabprefix");
				update_option('formazione_prefissotabsocial',$PrefissoTabella);
				break;
			case "Salva Url Sito Social":
				$SitoSocial= filter_input(INPUT_POST, "tabprefix");
				update_option('formazione_sitosocial',$SitoSocial);
				break;
			case "Salva Attiva Scuola":
				$GestioneScuole= filter_input(INPUT_POST, "AttivaScuole");
				update_option('gestione_scuole',$GestioneScuole);
				break;
		}
		$StatoML=$this->isInstalledML?"<spam style=\"background-color:green;color:white;padding:5px;\"> Attivo </spam>":"<spam style=\"background-color:red;color:white;padding:5px;\"> Non Attivo </spam>";
		$StatoEM=$this->isInstalledEM?"<spam style=\"background-color:green;color:white;padding:5px;\"> Attivo </spam>":"<spam style=\"background-color:red;color:white;padding:5px;\"> Non Attivo </spam>";
		$StatoRW=$this->isInstalledRW?"<spam style=\"background-color:green;color:white;padding:5px;\"> Attivo </spam>":"<spam style=\"background-color:red;color:white;padding:5px;\"> Non Attivo </spam>";
		$StatoLM=$this->isInstalledLM?"<spam style=\"background-color:green;color:white;padding:5px;\"> Attivo </spam>":"<spam style=\"background-color:red;color:white;padding:5px;\"> Non Attivo </spam>";
?>
 
		<div class="wrap">
			<h2>Bacheca</h2>
			<div id="welcome-panel" class="welcome-panel"> 
				<h3>Applicativo per la gestione dei corsi di formazione</h3>
				<p><em>By Ignazio Scimone</em></p>
				<ul>
					<li>Stato Plugin Event Manager (utilizzato per le iscrizioni)                   : <?php echo $StatoEM;?></li>
					<li>Stato Plugin Alo Easy Mail (utilizzato per la mailing)                      : <?php echo $StatoML;?></li>
					<li>Stato Plugin Re-Welcome    (utilizzato per re-inviare la mail di benvenuto) : <?php echo $StatoRW;?></li>
					<li>Stato Plugin Email Log     (utilizzato per il logging delle mail di sistema): <?php echo $StatoLM;?></li>
				</ul>
			</div>
<?php	if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') ){?>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Logo</h3>
				<form method="post" name="options" target="_self">
<?php
					if (get_option('gest_c_logo') != '') { 
						$UrlImg=get_option('gest_c_logo'); 
						echo '
						<div>
							<img src="'.$UrlImg.'" class="wp-post-image" alt="" style="height:100px;"/>
						</div>';
					} else { 
						$UrlImg='http://'; 
					}
?>
					<input id="logo" type="text" size="100" name="logo" value="<?php echo $UrlImg; ?>" />
					<input id="logo_upload" class="button" type="button" value="Carica" />
					<p></p>
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Impostazioni logo" /></p>
				</form>
			</div>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Gestione Scuole</h3>
				<form method="post" name="options" target="_self">
<?php
				$Scuole= get_option('gestione_scuole'); 
?>
					<label for="AttivaScuole">Attiva la gestione delle scuole negli Utenti e nell'Applicazione</label>
						<input id="AttivaScuole" type="checkbox" name="AttivaScuole" value="Si" <?php echo ($Scuole=="Si"?"checked":""); ?> />
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Attiva Scuola" /></p>
				</form>
			</div>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Impostazioni email di sistema</h3>
				<form method="post" name="options" target="_self">
<?php
				$SenderName=get_option('sender_name'); 
				$SenderEmail= get_option('sender_email');
?>
					<label for="sendername">Indicare il Mittente della mail di sistema al posto di <strong>Wordpress</strong></label>
						<input id="sendername" type="text" size="100" name="sendername" value="<?php echo $SenderName; ?>" />
						<br />
					<label for="senderemail">Indicare la mail di sistema al posto di <strong>wordpress@<?php echo preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));?></strong></label>
						<input id="senderemail" type="text" size="100" name="senderemail" value="<?php echo $SenderEmail; ?>" />
						<br />
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Impostazioni email di sistema" /></p>
				</form>
			</div>
<?php	
				$Periodi= unserialize(get_option('PeriodoFormazione'));
				if($Periodi){
					$Da=$Periodi[0];
					$AData=$Periodi[1];
				}
?>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Impostazioni periodi formazione</h3>
				<form method="post" name="options" target="_self">
<?php
				$Periodi= unserialize(get_option('periodi_formazione')); 
?>
					<label for="DataInizioPeriodo">Data inizio periodo formazione</label>
						<input id="DataInizioPeriodo" type="text" size="8" name="DataInizioPeriodo" class="calendario" value="<?php echo $Da; ?>" />
						<br />
					<label for="DataFinePeriodo">Data fine periodo formazione</label>
						<input id="DataFinePeriodo" type="text" size="8" name="DataFinePeriodo" class="calendario" value="<?php echo $AData; ?>" />
						<br />
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva periodi formazione" /></p>
				</form>
			</div>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Impostazioni certificazione Formazione OnLine</h3>
				<form method="post" name="options" target="_self">
<?php
				$OnLine= unserialize(get_option('formazione_online')); 
?>
					<label for="AttivaFOL">Gestione Formazione OnLine Globale</label>
						<input id="AttivaFOL" type="checkbox" name="AttivaFOL" value="Si" <?php echo ($OnLine['Attiva']=="Si"?"checked":""); ?> />
						<br />
					<label for="OreFOL">Ore riconosciute Formazione OnLine Globale</label>
						<input id="OreFOL" type="text" size="3" name="OreFOL" value="<?php echo $OnLine['Ore']; ?>" />
						<br />
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva dati Formazione OnLine" /></p>
				</form>
			</div>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Firma certificati</h3>
				<form method="post" name="options" target="_self">
<?php
				$FirmaAttestati= get_option('formazione_firma'); 
				$Luogo=get_option('formazione_luogo_emissione');
?>
					<textarea id="FirmaAttestati" name="FirmaAttestati" style="width:100%;height:250px;" ><?php echo $FirmaAttestati; ?></textarea>
					<br />
					<label for="luogo">Luogo emissione</label>
						<input id="luogo" type="text" size="50" name="luogo" value="<?php echo $Luogo; ?>" />
						<br />
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Firma Attestati" /></p>
				</form>
			</div>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Testo da aggiungere in coda alle mail in uscita</h3>
				<form method="post" name="options" target="_self">
<?php
				$TestoFooterMail= get_option('formazione_testofootermail'); 
?>
					<textarea id="TestoFooterMail" name="TestoFooterMail" style="width:100%;height:100px;" ><?php echo $TestoFooterMail; ?></textarea>
					<em>Lasciare vuoto per non aggiungere nulla</em>
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Testo Footer Email Inviate" /></p>
				</form>
			</div>
			<div id="welcome-panel" class="welcome-panel">
			<h3>Prefisso tabelle DB Social incpluso nel DB corrente</h3>
				<form method="post" name="options" target="_self">
<?php
				$PrefissoTabella= get_option('formazione_prefissotabsocial'); 
?>
					<input type="text" id="tabprefix" name="tabprefix" value="<?php echo $PrefissoTabella; ?>" /><br />
					<em>Lasciare vuoto se non usi il Portale Social in BuddyPress</em>
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Prefisso Tabelle Social" /></p>
				</form>
			</div>	
			<div id="welcome-panel" class="welcome-panel">
			<h3>Sito Social</h3>
				<form method="post" name="options" target="_self">
<?php
				$SitoSocial= get_option('formazione_sitosocial'); 
?>
					<input type="text" id="tabprefix" name="tabprefix" value="<?php echo $SitoSocial; ?>" size="100"/><br />
					<em>Lasciare vuoto se non usi il Portale Social in BuddyPress</em>
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Url Sito Social" /></p>
				</form>
			</div>					
			<div id="welcome-panel" class="welcome-panel">
			<h3>ID consenso ricezione NewsLetter</h3>
				<form method="post" name="options" target="_self">
<?php
				$IDconsensoNewsLetter= get_option('formazione_IDconsensoNewsLetter'); 
?>
					<input type="text" id="IDconsensoNewsLetter" name="IDconsensoNewsLetter" value="<?php echo $IDconsensoNewsLetter; ?>" /><br />
					<em>indica l'ID della scheda di consenso del plugin GDPR per la ricezione delle NewsLetter</em>
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva ID consenso ricezione NewsLetter" /></p>
				</form>
			</div>	
			<div id="welcome-panel" class="welcome-panel">
			<h3>Sito Social</h3>
				<form method="post" name="options" target="_self">
<?php
				$SitoSocial= get_option('formazione_sitosocial'); 
?>
					<input type="text" id="tabprefix" name="tabprefix" value="<?php echo $SitoSocial; ?>" size="100"/><br />
					<em>Lasciare vuoto se non usi il Portale Social in BuddyPress</em>
					<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Salva Url Sito Social" /></p>
				</form>
			</div>									
<?php	} ?>
		</div>
<?php			
	}
	
	public function export_ElencoCorsisti($IdCorso=0){
		global $wpdb;
		$Utenti=new Utenti();
		$DaFiltrare="";
		if ( ! function_exists( 'get_userdata' ) ){
	 		require_once( ABSPATH . 'wp-includes/pluggable.php' );			
		}
		if($Utenti->is_Organizzatore( get_current_user_id())){
			$DaFiltrare=$Utenti->get_CorsiOrganizzati(get_current_user_id(),"event_id");
			$DaFiltrare=" And ".EM_BOOKINGS_TABLE.".event_id in(".implode(",",$DaFiltrare).")";
		}
		$Stato=="";
		$StatoT="";
		$ValoriUnici="DISTINCT";
		if($IdCorso!=0){
			$DaFiltrare=" And ".EM_BOOKINGS_TABLE.".event_id =".$IdCorso." ";
			$Stato=",booking_status ";
			$StatoT=",\"Stato\"";
			$ValoriUnici="";
		}
		$Sql="SELECT $ValoriUnici person_id ".$Stato
		. "FROM ".EM_BOOKINGS_TABLE." "
		. "WHERE 1 $DaFiltrare";
			$Corsisti=$wpdb->get_results($Sql, ARRAY_A);
		$Scuole=new Scuole();
		//$Testo="\"ID Utente\",\"Nome\",\"Cognome\",\"Email\",\"Scuola\"$StatoT\n";
		$DatiC=array();
		foreach($Corsisti as $Corsista){
			$user_info = get_userdata($Corsista['person_id']);
			$Scuola=$Scuole->get_Scuola(get_user_meta($Corsista['person_id'], "Scuola", TRUE));
			$email=$user_info->user_email;
			if($Stato==""){
				$DatiCorsisti=array($Corsista['person_id'],
									  ucwords(strtolower($user_info->last_name)),
									  ucwords(strtolower($user_info->first_name)),
									  strtolower($user_info->user_email),
									  $Scuola);
			}else{
				switch($Corsista['booking_status']){
					case 0:
						$StatoValore="In attesa";
						break;
					case 1:
						$StatoValore="Approvata";
						break;
					case 2:
						$StatoValore="Respinta";
						break;
					case 3:
						$StatoValore="Cancellata";
						break;
					default:
						$StatoValore="Non definita";
						break;
				}
				$DatiCorsisti=array($Corsista['person_id'],
									  ucwords(strtolower($user_info->last_name)),
									  ucwords(strtolower($user_info->first_name)),
									  strtolower($user_info->user_email),
									  $Scuola,
									  $StatoValore);
			}
			//$Testo.=implode(",",$DatiCorsisti)."\n";
			$DatiC[]=$DatiCorsisti;
		}
		return $DatiC;//$Testo;
	}

	public function export_ScadenziarioCorsi(){
		global $wpdb;
		if ( ! function_exists( 'wp_get_current_user' ) ){
	 		require_once( ABSPATH . 'wp-includes/pluggable.php' );			
		}
 		$Corsi=$wpdb->get_results($Sql,ARRAY_N );
		$events = EM_Events::get( array('scope'=>'future', 'limit'=>0, 'offset' => 0, 'order'=>'ASC', 'bookings'=>true, 'owner' => false) );
//		echo "<pre>";print_r($Corsi);echo "</pre>";die();
		$Testo="ID Corso;Titolo;Docenti;Tutor;Date\n";
		foreach($events as $Corso){
			$DatiCorso=new Gestione_Corso($Corso->event_id);
			if($DatiCorso->isMyCourse() OR $DatiCorso->isMy()){
				$SeriaLezioni=get_post_meta($Corso->post_id, "_lezioniCorso",TRUE);
				$DocTut=$DatiCorso->get_DocentiTutorCorso();
				$Date=($SeriaLezioni?unserialize( $SeriaLezioni):array());
	//			echo "<pre>";print_r($Lezioni);echo "</pre>";die();
				$Testo.=$Corso->post_id.";".$Corso->event_name.";";
				$Testo.=$DocTut["Docenti"].";".$DocTut["Tutor"].";";
				foreach($Date as $D){
					$Testo.=$D[0].";";
				}
				if(substr($Testo, -1, 1)==";"){
					$Testo=substr($Testo, 0, strlen($Testo)-1);
				}
				$Testo.="\n";			
			}
		}
		return $Testo;
	}
	
	protected function DownloadFile($file_path){
			$chunksize	= 2*(1024*1024);
			$stat 		= @stat($file_path);
			$etag		= sprintf('%x-%x-%x', $stat['ino'], $stat['size'], $stat['mtime'] * 1000000);
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', FALSE);
			header('Content-Type: application/force-download', FALSE);
			header('Content-Type: application/octet-stream', FALSE);
			header('Content-Type: application/download', FALSE);
			header('Content-Disposition: attachment; filename="'.basename($file_path).'";');
			header('Content-Transfer-Encoding: binary');
			header('Last-Modified: ' . date('r', $stat['mtime']));
			header('Etag: "' . $etag . '"');
			header('Content-Length: '.$stat['size']);
			header('Accept-Ranges: bytes');
			ob_flush();
			flush();
			if ($stat['size'] < $chunksize) {
				@readfile($file_path);
			}
			else {
				$handle = fopen($file_path, 'rb');
				while (!feof($handle)) {
					echo fread($handle, $chunksize);
					ob_flush();
					flush();
				}
				fclose($handle);
			}		
		exit();
	}
	
	public function ScadenziarioCorsi(){

  ?>
 	<div class="wrap">
		<h2>Scadenziario Corsi</h2>
			<a href="<?php echo admin_url();?>admin.php?page=scadenziariocorsi&op=exportcorsi&secur=<?php echo wp_create_nonce("Corsitocsv");?>" title="Scarica scadenziario in Csv"><i class="fa fa-download fa-2x" aria-hidden="true"></i></a>
		<div id="welcome-panel" class="welcome-panel">
		<?php $this->CalendarioCorsi();?>
		</div>
	</div>
<?php			
	}
	protected function crea_Calendario_Corsi($Mese=0,$Anno=0){
		global $wpdb;
		if ($Mese==0 Or ! is_numeric( $Mese ) Or ($Mese<-1 And $Mese>12)){
			$Mese=date("m");
		}
		if ($Anno==0 Or ! is_numeric( $Anno ) Or ($Anno<2015 And $Anno>3000)){
			$Anno=date("Y");
		}
		if(strlen($Mese)==1) $Mese="0".$Mese;
		$Utenti=new Utenti();
		$DaFiltrare="";
		if(current_user_can('corsi_gest_ass')){
			$DaFiltrare=$Utenti->get_CorsiPersonali(get_current_user_id());
			$DaFiltrare=" And ".$wpdb->postmeta.".post_id in(".implode(",",$DaFiltrare).")";
		}
		if(current_user_can('corsi_organizzatore')){
			$DaFiltrare=$Utenti->get_CorsiOrganizzati(get_current_user_id());
			$DaFiltrare=" And ".$wpdb->postmeta.".post_id in(".implode(",",$DaFiltrare).")";
		}
		$Sql="SELECT post_id FROM $wpdb->postmeta WHERE meta_key=\"_lezioniCorso\"$DaFiltrare;";
		$Corsi=$wpdb->get_results($Sql,ARRAY_N );
		//echo "<pre>";print_r($Corsi);echo "</pre>";die();
		$CalendarioCorsi=array();
		$CalendarioCorsiTutti=array();
		foreach($Corsi as $Corso){
			$SeriaLezioni=get_post_meta($Corso[0], "_lezioniCorso",TRUE);
			$Date=($SeriaLezioni?unserialize( $SeriaLezioni):array());
//			echo "<pre>";print_r($Lezioni);echo "</pre>";die();
			$CalendarioCorsiTutti[]=array($Corso[0],get_the_title($Corso[0]),$Date);
			foreach($Date as $D){
				$DataLetta=Funzioni::FormatDataSeriale($D[0]);
//				echo $DataLetta." ".$Anno.$Mese." ".intval(substr($DataLetta,6,2));die();
				if(substr($DataLetta,0,6)==$Anno.$Mese Or ($Mese==-1 And $Anno==-1)){
					$CalendarioCorsi[intval(substr($DataLetta,6,2))][]=array($Corso[0],get_the_title($Corso[0]));
				}
				
			}
		}
//		echo "<pre>";print_r($CalendarioCorsiTutti);echo "</pre>";die();
		return($CalendarioCorsi);
	}
	
	protected function CalendarioCorsi($m=0,$y=0) 
	{        
		if ($m==0 Or ! is_numeric( $m ) Or ($m<-1 And $m>12)){
			$m=date("m");
		}
		if ($y==0 Or ! is_numeric( $y ) Or ($y<2015 And $y>3000)){
			$y=date("Y");
		}
//		var_dump($Calendario);
		if(isset($_POST['dp'])){ 
			$m = (int)date( "m" ,(int)$_POST['precedente']);  
			$y = (int)date( "Y" ,(int)$_POST['precedente']); 
		} 
		 elseif(isset($_POST['ds'])){ 
				$m = (int)date( "m" ,(int)$_POST['successivo']);  
				$y = (int)date( "Y" ,(int)$_POST['successivo']); 
				$m = $m; 
			    $y = $y; 
		 } 
		 elseif(isset($_POST['giorno'])){ 
			 $giorno = $_POST['giorno']; 
		 } 
		 else{ 
			   $m = $m; 
			   $y = $y; 
		 }     
	  if(strlen($m)==1) $m="0".$m;
	  $Calendario=$this->crea_Calendario_Corsi($m,$y);
	  $precedente = mktime(0, 0, 0, $m -1, 1, $y); 
	  $successivo = mktime(0, 0, 0, $m +1, 1, $y); 
	  $nomi_mesi = array( "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre" ); 
	  $nomi_giorni = array( "Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom" ); 

		// Imposto le colonne del calendario 
	  $cols = 7; 
	  // Credo i giorni 
	  $days = @date("t",@mktime(0, 0, 0, $m, 1, $y));  
	  // Ricavo lunedi 
	  $lunedi= @date("w",@mktime(0, 0, 0, $m, 1, $y)); 
	  // controllo del lunedi (Banale) 
	  if($lunedi==0) $lunedi = 7; 
	  // Inizializzo la tabella 
	  echo "<form  method=\"post\">\n
	  <div class=\"month\">      
		<ul>
		    <li class=\"prev\">
				<input type=\"submit\" id=\"form_calendario\" name=\"dp\" value=\"❮\"/> 
				<input type=\"hidden\" name=\"precedente\" value=\"".$precedente."\"/> 
		    </li>
			<li class=\"next\">	  
				<input type=\"submit\" id=\"form_calendario\" name=\"ds\" value=\"❯\"/> 
				<input type=\"hidden\" name=\"successivo\" value=\"".$successivo."\"/> 
			</li>
		  <li style=\"text-align:center\">".$nomi_mesi[$m-1]." ".$y ."</li>
		</ul>
	  </div>
	  </form> 
	  <table class=\"widefat calendario\" > 
		  <tr>\n"; 
	  // ricavo i giorni con un for_each  
	  foreach($nomi_giorni as $v){ 
		echo "<th id=\"td_calendario\">".$v."</th>\n"; 
	  } 
	  echo "</tr>"; 
		// Ciclo for che è il cuore della tabella molto scolastico ma preciso , mi crea la tabella tenendo conto dell incremento dei giorni e dei lunedi di ogni mese  
	  for($j = 1; $j<$days+$lunedi; $j++) 
	  { 
		if($j%$cols+1==0) 
		{ 
		  echo "<tr>\n"; 
		} 
		// Controllo per vedere se devo riepire o meno delle celle  
		if($j<$lunedi) 
		{ 
		  echo "<td class=\"vuoto\"></td>\n"; 
		}else{ 
			$day= $j-($lunedi-1); 
			$data = @strtotime(@date($y."-".$m."-".$day)); 
			$oggi = @strtotime(@date("Y-m-d")); 
			// Mando in post i giorni cosi posso passarli ad altre pagine se necessario 
			$Impegni="<ul style=\"display:inline;background-color: #f1f1f1;\">";
//			var_dump($Calendario);
			if(is_array($Calendario[$day])){
				foreach ($Calendario[$day] as $Giorno){
					$Impegni.="<li><a href=\"?page=corsi&idCorso=".$Giorno[0]."\">".$Giorno[1]."</a></li>";
				}
			}
			$Impegni.="</ul>";
			echo "<td class=\"pieno\">".$day." ".$Impegni."</td>"; 
		} 
		// Se le colonne sono finite chiudo  
		if($j%$cols==0) 
		{ 
		  echo "</tr>"; 
		} 
	  } 
	  // Chiudo la tabella e il form 
	  echo "<tr></tr>"; 
	  echo "</table>"; 
	  echo "</form>";   

	} 
	// Function to change email address

	public function wpb_sender_email( $original_email_address ) {
		return get_option('sender_email');
	}

	// Function to change sender name
	public function wpb_sender_name( $original_email_from ) {
		return get_option('sender_name');
	}
	public function mod_CalendarWidget($calendar_array, $args){
		$Categorie=explode(",",$args["category"]);
		if (in_array("-1",$Categorie))
			return $calendar_array;
		foreach($calendar_array['cells'] as $Key =>$Giorno){
//			echo "<pre>";var_dump($args);echo "</pre>";
//		var_dump($calendar_array['cells'][$Key]['events']);
			$calendar_array['cells'][$Key]['events']=array();
			$calendar_array['cells'][$Key]['link_title']="";
			$calendar_array['cells'][$Key]['link']="";
			$calendar_array['cells'][$Key]['events_count']=0;
//			var_dump($Giorno);
//			echo $Key;
		}
		$Calendario=$this->crea_Calendario_Corsi($args['month'],$args['year']);
		if(strlen($args['month'])==1){
			$Mese="0".$args['month'];
		} else {
			$Mese=$args['month'];
		}
		$Data=$args['year']."-".$Mese."-";
		foreach($Calendario as $Giorno=>$G){
			if(strlen($Giorno)==1){
				$Giorno="0".$Giorno;
			}
			$calendar_array['cells'][$Data.$Giorno]['events_count']=count($G);
			$Titolo="";
			$Corsi="";
			foreach($G as $EvD){
				$Titolo.=$EvD[1]."\n";
				$Corsi.=$EvD[0].";";
			}
			$calendar_array['cells'][$Data.$Giorno]['link_title']=$Titolo;
			$calendar_array['cells'][$Data.$Giorno]['link']=get_home_url()."/calendario/?corsi=".substr($Corsi,0,-1)."&giorno=".FUNZIONI::FormatDataItaliano($Data.$Giorno)."&secur=". wp_create_nonce("IdDeiCorsiPassati");
		}
//		var_dump($calendar_array);
		return $calendar_array;
	}
	
	private function VerificaEmailUtenti(){
		$Users=get_users();
		$StaoEmail="";
		foreach($Users as $Utente){
//		echo $Utente->data->user_email."<br/>";	
			$email=$Utente->data->user_email;
			if(!FUNZIONI::email_exist($email)){
				$SemaforoStaoEmail="<spam style=\"color:red;\"><i class=\"fas fa-unlink\"></i>";
			}else{
				$SemaforoStaoEmail="<spam style=\"color:green;\"><i class=\"fas fa-link\"></i>";
			}
			$StaoEmail.="<li>".$SemaforoStaoEmail.$email."</span></li>";
		}
		return $StaoEmail;
	}

	private function CondivisioneUtentiPiattaformaSocial($TablePrefix){
		global $wpdb;
		$Sql="SELECT ID,user_nicename FROM $wpdb->users ;";
		$Users = $wpdb->get_results($Sql);  
		$Risultato="<table>"
				. "<tr>"
				. "		<th>Utente</th>"
				. "		<th style=\"padding-right:3em;\">".$TablePrefix."_capabilities</th>"
				. "		<th style=\"padding-right:3em;\">".$TablePrefix."_user_level</th>"
				. "</tr>";	
		foreach($Users as $User) {
			$Sql="SELECT * FROM $wpdb->usermeta Where $wpdb->usermeta.meta_key in(\"".$wpdb->prefix."capabilities\",\"".$wpdb->prefix."user_level\") And $wpdb->usermeta.user_id=$User->ID Order By $wpdb->usermeta.user_id=$User->ID;";
			$ParsUser = $wpdb->get_results($Sql);
			if(!is_null( $ParsUser )){
				$Risultato.="<tr>"
						. "	<td>$User->user_nicename</td>";
				$Capacita="<td></td>";
				$Livello="<td></td>";
				foreach ($ParsUser as $ParUser){
					$MetaDato=$TablePrefix.substr($ParUser->meta_key,strpos($ParUser->meta_key,"_"));
					$NomeMD=substr($ParUser->meta_key,strpos($ParUser->meta_key,"_")+1);
					$Npar = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta where user_id=$ParUser->user_id and meta_key='$MetaDato'" );
					if($Npar>0){
						if($NomeMD=="capabilities")
							$Capacita="<td><span style=\"color:red;\">Meta Dato esistente</span></td>";
						else
							$Livello="<td><span style=\"color:red;\">Meta Dato esistente</span></td>";
					}else{
						if(false!==$wpdb->insert($wpdb->usermeta,array("user_id" =>$ParUser->user_id, 
															 "meta_key" =>$MetaDato, 
															 "meta_value" =>$ParUser->meta_value),
														array("%d","%s","%s"))){
							if($NomeMD=="capabilities")
								$Capacita="<td><span style=\"color:green;\">Meta Dato Duplicato</span></td>";
							else
								$Livello="<td><span style=\"color:green;\">Meta Dato Duplicato</span></td>";
						}
					}
				}
				$Risultato.=$Capacita.$Livello."</tr>";
			}
//					echo $Risultato."  SELECT COUNT(*) FROM $wpdb->usermeta where user_id=$ParUser->user_id and meta_key='$Capacit'    " . $Npar;wp_die();
		}
		$Risultato.="</table>";
		return $Risultato;
	}
	public function Utility(){
		global $wpdb;
		$StatoMail="";
		if (isset($_REQUEST['op']))
			switch($_REQUEST['op']){
				case "migraanip":
					if ( ! isset( $_GET['SecurUtility'] ) || ! wp_verify_nonce( $_GET['SecurUtility'], 'VerificaSicurezzaMAP' )) {
					  wp_die("Errore di Sicurezza");
					}
					echo "<input type=\"hidden\" name=\"SelectTab\" id=\"SelectTab\" value=\"2\" />";
					global $wpdb;
					$Sql="Select post_id,meta_value From ".$wpdb->postmeta." Where ".$wpdb->postmeta.".meta_key='_oreOnLineIndividualizzate'";
					$OreOnLineInd=$wpdb->get_results($Sql);
					$OreIndividualizzate=array();
					foreach ( $OreOnLineInd as $OreOnLineI ){
						$OreIndividualizzate[$OreOnLineI->post_id]=$OreOnLineI->meta_value;
					}					
					$Sql="Select post_id,meta_value From ".$wpdb->postmeta." Where ".$wpdb->postmeta.".meta_key='_oreOnLine'";
					$OreOnLine=$wpdb->get_results($Sql);
					foreach ( $OreOnLine as $OreOnLineSingle ){
						echo $OreOnLineSingle->post_id."  -  ".$OreOnLineSingle->meta_value."  -  ".$OreIndividualizzate[$OreOnLineSingle->post_id]."<br />";
						$Attivita=array();
						$Attivita[]=array("OnLine",$OreOnLineSingle->meta_value,$OreIndividualizzate[$OreOnLineSingle->post_id]);
						update_post_meta( $OreOnLineSingle->post_id, '_attivita', serialize($Attivita));
					}
					break;
				case "condivisioneutentisocial":
					if ( ! isset( $_GET['SecurUtility'] ) || ! wp_verify_nonce( $_GET['SecurUtility'], 'VerificaSicurezzaCPS' )) {
					  wp_die("Errore di Sicurezza");
					}
					$PrefissoTabella= get_option('formazione_prefissotabsocial');
					if (isset($PrefissoTabella) and  $PrefissoTabella!=""){
						
						$OperazioniCondivisione=$this->CondivisioneUtentiPiattaformaSocial($PrefissoTabella);
					}else{
						$OperazioniCondivisione="Operazione non eseguita perchè non hai specificato il prefisso della tabella";
					}
					break;
				case "verificaemail":
					if ( ! isset( $_GET['SecurUtility'] ) || ! wp_verify_nonce( $_GET['SecurUtility'], 'VerificaSicurezzaVM' )) {
					  wp_die("Errore di Sicurezza");
					}
					$StatoMail=$this->VerificaEmailUtenti();
					if( strlen( $StatoMail)>0){
						$StatoMail= "<ul>"
						. $StatoMail
						. "</ul>";				
					}else{
						$StatoMail= "<em><strong>Non ci sono email errate per gli utenti codificati</strong></em>";
					}
					break;
			}
	?>
		<div class="wrap">
			<h2 style="font-size:2em;">Utility</h2>
			<div id="utility-tabs-container"  style="margin-top:20px;">
				<ul>
					<li><a href="#utility-tab-1">Verifica Email</a></li>
					<li><a href="#utility-tab-2">Condividi utenti con la piattaforma Social</a></li>
					<li><a href="#utility-tab-3">Migra Attività non in presenza</a></li>
				</ul>
				<div id="utility-tab-1" style="margin-bottom:20px;">
					<?php  
					if( $StatoMail!=""){
						echo $StatoMail;
					}else{
?>						
					<form action="" method="get">
						<input type="hidden" name="page" value="utility" />
						<input type="hidden" name="op" value="verificaemail" />
						<?php wp_nonce_field( 'VerificaSicurezzaVM','SecurUtility' ); ?>
						<button type="submit">Avvia verifica email</button>
					</form>
<?php					}?>
				</div>		
				<div id="utility-tab-2" style="margin-bottom:20px;">
					<?php  
					if( $OperazioniCondivisione!=""){
						echo $OperazioniCondivisione;
					}else{
?>						
					<form action="" method="get">
						<input type="hidden" name="page" value="utility" />
						<input type="hidden" name="op" value="condivisioneutentisocial" />
						<?php wp_nonce_field( 'VerificaSicurezzaCPS','SecurUtility' ); ?>
						<button type="submit">Avvia Condivisione utenti con piattaforma Social</button>
					</form>
<?php					}?>
				</div>
				<div id="utility-tab-3" style="margin-bottom:20px;">
					<form action="" method="get">
						<input type="hidden" name="page" value="utility" />
						<input type="hidden" name="op" value="migraanip" />
						<?php wp_nonce_field( 'VerificaSicurezzaMAP','SecurUtility' ); ?>
						<button type="submit">Avvia migrazione</button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
