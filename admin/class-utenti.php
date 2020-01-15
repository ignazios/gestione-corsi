<?php
/*
 * Classe perla gestione degli utenti.
 *
 * Importazione Utenti
 * Creazione da Tabella
 * Gestione Campi Aggiuntivi
 *
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Utenti {
	
	protected $ID_Utente;

	/*
	 * Costruttore standard 
	 */
	public function __construct($IdUtente=0) {
		if($IdUtente==0){
			$this->ID_Utente=get_current_user_id();
		}else{
			$this->ID_Utente=$IdUtente;		
		}
	}
	
	private function get_Log_user_email($email){
		global $wpdb;
		$Tabella_Log= new EmailLog\Core\DB\TableManager();
		$table_name = $Tabella_Log->get_log_table_name();

		$query= 'SELECT * FROM ' . $table_name
			  . ' WHERE ( to_email LIKE "%'.$email.'%") '
			  . ' ORDER BY sent_date DESC';
		$Invii = $wpdb->get_results( $query ,ARRAY_N);
		if($Invii){
			$Log='<table cellspacing="0" cellpadding="1" border="1">
						<thead>
							<tr>
								<th style="width: 15%;height: 25px;text-align: center;font-size:1.5em;">Data</th>
								<th style="width: 85%;">Messaggio</th>
							</tr>
						</thead>
						<tbody>';
					foreach($Invii as $Invio){
						$Log.='	
							<tr>
								<td style="width: 15%;">'.FUNZIONI::FormatDataOraItaliano($Invio[6]).'</td>
								<td style="width: 85%;"><strong>Intestazione</strong>: '.$Invio[4].'<br />
								<strong>Oggetto</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.$Invio[2].'<br />
								<strong>Messaggio</strong>&nbsp;&nbsp;: '. nl2br(htmlentities($Invio[3])).'<br /d>
								<strong>Allegati</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: '.($Invio[5]==FALSE?$Invio[5]:"Nessun Allegato").'</td>
							</tr>';
					}
					$Log.='
						</tbody>
						</table>';	
			return $Log;
		}else{
			return FALSE;
		}
	}
	public function Export_log_user_email(){
		$Operazione= filter_input(INPUT_GET, "op");
		$email= filter_input(INPUT_GET, "email");
		$nonce=filter_input(INPUT_GET,"secur");
		$Msg="";
		if ($Operazione=="export_log_email" And wp_verify_nonce( $nonce, "export_log_email" )){
			$Pdf=new Gestione_Documenti();
			if(($Log=$this->get_Log_user_email($email))!==FALSE){
				$Pdf->Crea_Log_Email($Log,$email);
			}else{
				add_action( 'admin_notices', function() { 
					$Email= filter_input(INPUT_GET, "email");
					echo '<div class="updated">File con il log delle comunicazioni per la mail: '.$Email.' non è stato creato perchè non ci sono voci registrate per questa mail</div>'; 
				});
			}
		}
	}

	public function Log_user_email($actions, $user_object){
		$nonce = wp_create_nonce( 'export_log_email' ); 
		$link = admin_url( "admin.php?page=email-log&s=$user_object->user_email" );
		$actions['log_email'] = "<a href='$link'><span class=\"dashicons dashicons-visibility\"></span> log email</a>";
		$link = admin_url( "users.php?op=export_log_email&email=$user_object->user_email&secur=$nonce" );
		$actions['export_log_email'] = "<a href='$link'><span class=\"dashicons dashicons-download\"></span> log email</a>";
		return $actions;		
	}
	/**
	* Metodo che permette di estrarre le informazioni di uno specifico utente e restiuisce :
	* Id, Nome, Cognome, Codice FIscale, Email, Codice Meccanografico Scuola e Nome Scuola
	* @param type $IdUtente ID dell'utenteda cui estrarre le informazioni
	* @return array se l'utente viene trovato altrimenti FALSE
	*/
	public function get_Descrizione($IdUtente=0){
		if($IdUtente==0){
			$IdUtente=$this->ID_Utente;			
		}
		$DatiScuole=new Scuole();
		if ( ! function_exists( 'get_user_by' ) ){
	 		require_once( ABSPATH . 'wp-includes/pluggable.php' );			
		}
		$Utente= get_user_by("ID", $IdUtente);
		if($Utente){
			$Scuola=get_user_meta($IdUtente, "Scuola",TRUE);
			$Ritorno=array(
				"Id"		=>$IdUtente,
				"Nome"      => ucwords(strtolower($Utente->first_name)),
				"Cognome"   => ucwords(strtolower($Utente->last_name)),
				"CF"		=> get_user_meta($IdUtente, "CF",TRUE),
				"Email"		=> $Utente->user_email,
				"Scuola"    => $Scuola,
				"NomeScuola"=> $DatiScuole->get_Scuola($Scuola)
			);			
			return $Ritorno;
		}else{
			return FALSE;
		}
	}
	/**
	 * Pagina Che permette la gcreazione massiva degli utenti. Utile nel caso in cui gli iscritti ad un corso sono forniti dalle scuole e non si iscrivono autonomamente
	 *	Page Name: CreaUtenti
	 * @since    1.0.0
	 */	
	
	public function CreaUtenti(){
		$plugin_scuole = new Scuole();
	?>	
	<div class="wrap" id="FormCreaUtenti">
	    <h2>Utenti da importare</h2>
		<div id="tabcreautentis">
		<ul>
		  <li><a href="#tabcreautentis-1">Crea Utenti</a></li>
		  <li><a href="#tabcreautentis-2">Importa da Excel</a></li>
		</ul>
		<div id="tabcreautentis-1">		
			<form action="" method="post" id="ImportazioneUtenti">
			<?php echo '<input type="hidden" id="CodiciScuole" value="'. $plugin_scuole->getElencoScuole("String","","","").'"/>';?>
				<table id="GridUtenti"></table>
				<button type="button" id="verificaDati" >Verifica Dati</button>
				<button type="button" id="creaUtenti" disabled>Crea Utenti</button>
			<div style="font-weight: bold;">
				Stato dei dati inseriti:<br />
				<span style="color:#fff;background-color:red;">i campi con questo sfondo necessitano di modifica perchè non utilizzabili, basta cliccare sul campo e verrete rimandati al campo di input</span>
			</div>
			<div style="width: 200px;height: 200px;position: absolute;top: 50%;left: 50%; margin-top: -100px; margin-left: -100px;display:none;">
				<img src="<?php echo Home_Path_Gestione_Corsi . 'admin/css/images/ElaborazioneInCorso.gif'?>" id="ElaborazioneTabella" />
			</div>
			<div id="divRisultatoCreazione" style="border: 1px solid #0000ff;height: 200px;overflow: auto;width: 100%;background-color: #fff;">
			</div>
			</form>
		</div>
		<div id="tabcreautentis-2">
			<div style="width: 200px;height: 200px;position: absolute;top: 50%;left: 50%; margin-top: -100px; margin-left: -100px;display:none;" >
				<img src="<?php echo plugin_dir_url( __FILE__ ) . 'css/images/ElaborazioneInCorso.gif'?>" id="ElaborazioneExcel"/>
			</div>
			<p>Incollare nel sottostante campo la tabella copiata dal foglio di calcolo contenete le seguenti colonne: Nome - Cognome - Email - Scuola - Codice Fiscale<br />
				Incollare solo i valori e non la riga con i nomi dei campi
			</p>
			<textarea style="width:100%;height: 250px;" id="DatiExcelImportati"></textarea>
			<button type="button" id="verificaDatiExcel" >Verifica Dati</button>
			<button type="button" id="creaUtentiExcel" disabled>Crea Utenti</button>
			<div id="divRisultato" style="border: 1px solid #0000ff;height: 200px;overflow: auto;width: 100%;background-color: #fff;margin-top:5px;">
			</div>
		</div>
		</div>
	</div>
<?php
	}

	/**
	 * Aggiunge il campo Codice Scuola nel profilo utente
	 *
	 * @since    1.0.0
	 */

	public function utenti_crea_extra_profile_fields( $user ) { 

		$plugin_scuole = new Scuole();

		$Scuole=get_option("Corsi_CM_scuole","");
		$Lista=explode("\n",$Scuole);
		$TestoLista="<option value=''>Scuola non assegnata</option>";
		$CF= get_user_meta($user->ID,"CF",true);
		$SA=get_user_meta($user->ID,"Scuola",true);
	?>

		<h3>Informazioni aggiuntive Utente</h3>

		<table class="form-table">

			<tr>
				<th><label for="Scuola">Scuola di appartenenza</label></th>

				<td>
					<?php echo $plugin_scuole->getElencoScuole("Select","Scuola","Scuola",$SA);?>
					<br />
					<span class="description">Per favore inserisci il codice della scuola di appartenenza.</span>
				</td>
			</tr>
			<tr>
				<th><label for="Scuola">Codice Fiscale</label></th>

				<td><input name='CF' id='CF' maxlength="16" required value='<?php echo $CF;?>'>
					<br />
					<span class="description">Per favore inserisci il tuo Codice Fiscale.</span>
				</td>
			</tr>

		</table>
	<?php }

	/**
	 * Memorizza i Campi personalizzati degli utenti
	 *
	 * @since    1.0.0
	 */

	public function utenti_save_extra_profile_fields( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) )
			return false;

		update_user_meta( absint( $user_id ), 'Scuola',		wp_kses_post( $_POST['Scuola'] ) );
		update_user_meta( absint( $user_id ), 'CF',			wp_kses_post( $_POST['CF'] ) );
//		update_user_meta( absint( $user_id ), 'last_name',  wp_kses_post( $_POST['user_cognome'] ) );
		
	}	
	/**
	 *  Metodo che restituisce il numero di corsi a cui a cui l'utente è registrato per stato
	 * @global type $wpdb
	 * @return type Array[Stato] numero di corsi
	 */
	
	public function get_Stato_Iscrizioni($All=False){
		global $wpdb;
		$Stato=array( "0" => 0,
					  "1"   => 0,
					  "2"  => 0,
					  "3"  => 0);
		$DaFiltrare="";
		if($this->is_Organizzatore( get_current_user_id())And !$All){
			$DaFiltrare=$this->get_CorsiOrganizzati(get_current_user_id(),"event_id");
			$DaFiltrare=" And ".EM_BOOKINGS_TABLE.".event_id in(".implode(",",$DaFiltrare).")";
		}	
		$Sql="SELECT booking_status, count(booking_status) FROM ".EM_BOOKINGS_TABLE." WHERE person_id=".$this->ID_Utente."$DaFiltrare GROUP BY booking_status";
//		echo $Sql;
		$NunCorsi=$wpdb->get_results($Sql, ARRAY_N);
		foreach($NunCorsi as $NumCorso){
			$Stato[$NumCorso[0]]=$NumCorso[1];
		}
		return $Stato;
	}
	
	public function is_DocenteFormatore($IdUtente=0){
		if($IdUtente==0){
			$IdUtente=$this->ID_Utente;			
		}
		$user_info = get_userdata($IdUtente);
		return in_array("docente_corsi",$user_info->roles);
	}
	public function is_Organizzatore($IdUtente=0){
		if($IdUtente==0){
			$IdUtente=$this->ID_Utente;			
		}
		$user_info = get_userdata($IdUtente);
		return in_array("organizzatore_corsi",$user_info->roles);
	}
	function has_consent($consent, $IDUser=0 ){
		$consents = (array) get_user_meta( ($IDUser==0?$this->ID_Utente:$IDUser), 'gdpr_consents' );
		if ( isset( $consents ) && ! empty( $consents ) ) {
			if ( in_array( $consent, $consents ) ) {
				return true;
			}
		}
		return false;
	}
	public function is_CorsoForMe($IDCorso){
		global $wpdb;
		if(!$this->is_DocenteFormatore()){
			return False;
		}
		$IMieiCorsi=$this->get_CorsiPersonali();
		return in_array( $IDCorso,$IMieiCorsi );
	}
	public function get_Corsi_per_User($IdUtente){
		if($IdUtente==0){
			$IdUtente=$this->ID_Utente;			
		}
/*
 * Corsi come Docente o Tutor
 */
		global $wpdb;
		$Sql = "Select ".$wpdb->posts.".ID, ".$wpdb->posts.".post_title,".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." Inner Join ".$wpdb->posts." on ".$wpdb->postmeta.".post_id=".$wpdb->posts.".ID WHERE ".$wpdb->postmeta.".meta_key in (\"_docenteCorso\",\"_tutorCorso\") AND ".$wpdb->postmeta.".meta_value=".$IdUtente;
		$CorsiFormatoreTutor = $wpdb->get_results( $Sql  );
/*
 * Corsi come Corsista
 */
		global $wpdb;
		$Sql = "Select ".EM_EVENTS_TABLE.".event_id, ".EM_EVENTS_TABLE.".event_name FROM ".$wpdb->table_corsisti." Inner Join ".EM_EVENTS_TABLE." on ".EM_EVENTS_TABLE.".event_id=".$wpdb->table_corsisti.".IDCorso WHERE ".$wpdb->table_corsisti.".IDUser=".$IdUtente;
		$CorsiCorsista = $wpdb->get_results( $Sql  );
		return Array("FormatoreTutor"=>$CorsiFormatoreTutor,
					 "Corsista"      =>$CorsiCorsista);
	}
	public function get_CorsiPersonali($IdUtente=0){
		if($IdUtente==0){
			$IdUtente=$this->ID_Utente;			
		}
		global $wpdb;
		$Sql = "Select ".$wpdb->postmeta.".post_id FROM ".$wpdb->postmeta." WHERE ".$wpdb->postmeta.".meta_key in (\"_docenteCorso\",\"_tutorCorso\") AND ".$wpdb->postmeta.".meta_value=".$IdUtente;
		$res_CorsiPersonali = $wpdb->get_results( $Sql  );
		$CorsiPersonali=array();
		foreach($res_CorsiPersonali as $Res){
			$CorsiPersonali[]=$Res->post_id;
		}
		return $CorsiPersonali;
	}
	public function get_CorsiOrganizzati($IdUtente=0,$Ret="post_id"){
		if($IdUtente==0){
			$IdUtente=$this->ID_Utente;			
		}
		global $wpdb;
		$Sql = "Select ".EM_EVENTS_TABLE.".post_id,".EM_EVENTS_TABLE.".event_id FROM ".EM_EVENTS_TABLE." WHERE ".EM_EVENTS_TABLE.".event_owner=$IdUtente;";
		$res_CorsiOrganizzati = $wpdb->get_results( $Sql  );
		$CorsiOrganizzati=array();
		foreach($res_CorsiOrganizzati as $Res){
			switch($Ret){
				case "post_id":
					$CorsiOrganizzati[]=$Res->post_id;
					break;
				case "event_id":
					$CorsiOrganizzati[]=$Res->event_id;
					break;
			}
			
		}
		return $CorsiOrganizzati;
	}
}