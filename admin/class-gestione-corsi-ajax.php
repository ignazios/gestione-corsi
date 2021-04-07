<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       eduva.org
 * @since      1.0.0
 *
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Gestione_Corsi_Ajax {

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

	}
	/**
	 * Convert a string to the file/URL safe "slug" form
	 *
	 * @param string $string the string to clean
	 * @param bool $is_filename TRUE will allow additional filename characters
	 * @return string
	 */
	function sanitize($string = '', $is_filename = FALSE,$is_username=FALSE,$replace_to="-")
	{
	 // Replace all weird characters with dashes
	 $string = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ($is_username ? '.' : ''). ']+/u', $replace_to, $string);

	 // Only allow one dash separator at a time (and make string lowercase)
	 return mb_strtolower(preg_replace('/--+/u', '-', $string), 'UTF-8');
	}

	/*
	 * Funzione per la pulizia dei valori serializzati dalla tabella .appendGrid
	 */
	protected function pulisciValori($Valore){
		$Valore= str_replace("+", " ", $Valore);
		$Valore= str_replace("\u0027", "'", $Valore);
		$Valore= str_replace("%40", "@", $Valore);
		return $Valore;
	}
	protected function convert_CaratteriAccentati($Testo,$SenzaAccento=FALSE){
		$search = array('%C3%AC', '%C3%A8', '%C3%A9%', '%C3%B2', '%C3%A0', '%C3%B9','%C3%A7'); 
		if ($SenzaAccento){
			$replace = array('i', 'e', 'e', 'o', 'a', 'u','c'); 
		}else{
			$replace = array('i\'', 'e\'', 'e\'', 'o\'', 'a\'', 'u\'', 'c'); 
		}
		return str_replace($search, $replace, $Testo); 
	}
	protected function convert_Spazi($Testo,$Carattere="_"){
		$search = array(' ','%20'); 
		return str_replace($search, $Carattere, $Testo); 
	}	
	
	function FiltroScuola(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$CMScuola= filter_input(INPUT_POST, "cmscuola");
		$IDCorso = filter_input(INPUT_POST, "idCorso");
		$Corso=new Gestione_Corso($IDCorso);
		echo $Corso->crea_Lista_Utenti(TRUE,FALSE,"li",$CMScuola);
		die();
	}	
	function FiltroCorsista()
	{
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Corsista=filter_input(INPUT_POST, "corsista");
		$Corso=new Gestione_Corso($IDCorso);
		echo $Corso->crea_Lista_Utenti_Filtro($Corsista,"li");
		die();
	}
	function AddUserByEmail(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$ElencoMail=explode("&", filter_input(INPUT_POST, 'elencomail'));
		$ElencoMail=$ElencoMail[0];
		$ElencoMail=explode("\r",urldecode(substr($ElencoMail,11,strlen($ElencoMail)-11)));
		$Corso=new Gestione_Corso($IDCorso);
		echo $Corso->crea_Lista_Utenti(TRUE,FALSE,$Output="li",$CM="",$ElencoMail);
		die();	
	}
		/**
	 * Funzione che verifica i dati degli utenti da creare incollati da foglio di calcolo Excel
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che rapprensenta gli utenti da importare con lo stato di importabiità.
	 *			   nel campo nascosto PSDati viene trasmessa la tabella codifita in json dei dati degli utenti da importare
	 */

	function VerificaNuoviUtentiExcel(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Valori= explode("\n", filter_input(INPUT_POST, 'valori'));
		$GestScuole= get_option('gestione_scuole'); 
		?>
			<table id="GridVerificaUtenti" class="ui-widget head body foot">
					<thead class="ui-widget-header">
					<tr>
						<td id="GridVerificaUtenti_caption_td" class="ui-state-active caption" colspan="9">Verifica Dati nuovi Utenti</td>
					</tr>
					<tr class="columnHead">
						<td class="ui-widget-header">Stato</td>
						<td class="ui-widget-header">User Name</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
			<?php	if($GestScuole=="Si"):?>
						<td class="ui-widget-header">Scuola</td>
			<?php   endif;?>						
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Utente Creato</td>
						<td class="ui-widget-header">Email Inviata</td>
					</tr>
					</thead>
					<tbody class="ui-widget-content">
<?php
				$SemaforoRosso="style=\"color:#fff;background-color:red;\"";
				$SemaforoVerde="style=\"color:#000;background-color:green;\"";
				$Creare=FALSE;
				$ValoriPassaggio=array();
				foreach($Valori as $Riga){
					$Validita="Valido";
					$Creabilita=TRUE;
					$Celle=explode("\t",$Riga);
//					var_dump($Celle);die();
					$Username=strtolower(str_replace(" ","_",trim(str_replace("?"," ",mb_convert_encoding($Celle[0],"ASCII")))).".".trim(str_replace("?"," ",mb_convert_encoding($Celle[1],"ASCII"))));
					if($Username=="."){
						$Username="Errore";
						$Validita="Errore";
					}else{
						$TestUsername=$this->sanitize($Username,FALSE,TRUE,"");
						$IdUserName=0;
						while ( username_exists( $TestUsername ) ) {
							$TestUsername=$Username.".".++$IdUserName;					
						}				
						$Username=$TestUsername;
					}
					$email=trim(mb_strtolower(urldecode( $Celle[2]), 'UTF-8'));
					$LabelEmail=$email;
					$LabelNome=ucfirst(trim(str_replace("?"," ",mb_convert_encoding($Celle[0],"ASCII"))));
					$LabelCognome=ucfirst(trim(str_replace("?"," ",mb_convert_encoding($Celle[1],"ASCII"))));
					$LabelScuola=$Celle[3] ;
					$LabelCF=$Celle[$GestScuole=="Si"?4:3] ;
					if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
						if( email_exists($email)) {
							$Creabilita=FALSE;
							$Validita="Errore";
							$LabelEmail="<span style=\"color:red;\">Email gia registrata per un altro utente</span>";
						}elseif(substr($email,strlen($email)-3,3)!="loc" And !FUNZIONI::email_exist($email)){
							$Creabilita=FALSE;
							$Validita="Errore";
							$LabelEmail="<span style=\"color:red;\">Email gia registrata per un altro utente</span>";
						}
					}else{
						$Creabilita=FALSE;
						$Validita="Errore";
						$LabelEmail="<span style=\"color:red;\">Email non valida</span>";					
					}
					if(!$Celle[0] ){
						$Creabilita=FALSE;
						$Validita="Errore";
						$LabelCognome="Cognome non definito";
					}
					if(!$Celle[1]){
						$Creabilita=FALSE;
						$Validita="Errore";
						$LabelNome="Nome non definito";
					}
					if($GestScuole=="Si" And !$Celle[3] ){
						$Creabilita=FALSE;
						$Validita="Errore";
						$LabelScuola="Codice Scuola non definito ";						
					}
					if(!$Celle[$GestScuole=="Si"?4:3]){
						$LabelCF="Codice Fiscale non definito";						
					}
					if($Creabilita){
						$Stato="SemaforoVerde";
						$StatoOperazione="Operazione non eseguita";
						$Creare=TRUE;
						if($GestScuole=="Si")
							$ValoriPassaggio[]=array(
								"Stato"         => $Validita,
								"UserName"      => trim($Username),
								"Nome"          => ucfirst(trim(str_replace("?"," ",mb_convert_encoding($Celle[0],"ASCII")))),
								"Cognome"       => ucfirst(trim(str_replace("?"," ",mb_convert_encoding($Celle[1],"ASCII")))),
								"Email"         => $email,
								"Scuola"        => trim(str_replace("?"," ",mb_convert_encoding($Celle[3],"ASCII"))),
								"CodiceFiscale" => trim(str_replace("?"," ",mb_convert_encoding($Celle[4],"ASCII")))
							);
						else
							$ValoriPassaggio[]=array(
								"Stato"         => $Validita,
								"UserName"      => trim($Username),
								"Nome"          => ucfirst(trim(str_replace("?"," ",mb_convert_encoding($Celle[0],"ASCII")))),
								"Cognome"       => ucfirst(trim(str_replace("?"," ",mb_convert_encoding($Celle[1],"ASCII")))),
								"Email"         => $email,
								"CodiceFiscale" => trim(str_replace("?"," ",mb_convert_encoding($Celle[$GestScuole=="Si"?4:3],"ASCII")))
							);							
					}else{
						$Stato="SemaforoRosso";
						$StatoOperazione="Operazione non eseguibile";
					}
					echo "<tr id='GridVerificaUtenti_Row_$i'>
							<td class='ui-widget-content first $Stato'></td>
							<td class='ui-widget-content'>".($Username=="Errore"?"<span style=\"color:red;\">".$Username."</span>":$Username)."</td>
							<td class='ui-widget-content'>".$LabelNome."</td>
							<td class='ui-widget-content'>".$LabelCognome."</td>
							<td class='ui-widget-content'>".$LabelEmail."</td>";
					if($GestScuole=="Si")
						echo "	<td class='ui-widget-content'>".$LabelScuola."</td>";
					echo "	<td  class='ui-widget-content'>".$LabelCF."</td>
							<td  class='ui-widget-content $Stato'>".$StatoOperazione."</td>	
							<td  class='ui-widget-content $Stato'>".$StatoOperazione."</td>
						</tr>";
				}
			?>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
	<?php
				echo "<input type=\"hidden\" id=\"PSDati\" name=\"PSDati\" value='".json_encode($ValoriPassaggio,JSON_HEX_APOS)."'>";
				if($Creare){
					echo "<input type=\"hidden\" id=\"PassaggioSuccessivo\" value=\"Si\">";
				}else{
					echo "<input type=\"hidden\" id=\"PassaggioSuccessivo\" value=\"No\">";
				}
					
		die();
	}

/* Funzione che verifica i CF degli utenti da aggiornare incollati da foglio di calcolo Excel
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che rapprensenta i CF degli utenti con lo stato di importabiità.
	 *			   nel campo nascosto PSDati viene trasmessa la tabella codifita in json dei dati degli utenti da importare
	 */

	function VerificaCFUtentiExcel(){
		check_ajax_referer('adminsecretmemostatusPrenotazione', 'security');
		$Valori= explode("\n", filter_input(INPUT_POST, 'valori'));
		?>
			<table id="GridVerificaCFUtenti" class="ui-widget head body foot">
					<thead class="ui-widget-header">
					<tr>
						<td id="GridVerificaUtenti_caption_td" class="ui-state-active caption" colspan="9">Verifica Dati CF Utenti</td>
					</tr>
					<tr class="columnHead">
						<td class="ui-widget-header">Stato</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Da Modificare</td>
					</tr>
					</thead>
					<tbody class="ui-widget-content">
<?php
				$Creare=FALSE;
				$ValoriPassaggio=array();
				foreach($Valori as $Riga){
					$Validita="Valido";
					$UtenteEsiste=False;
					$Celle=explode("\t",$Riga);
					$email=trim(mb_strtolower(urldecode( $Celle[0]), 'UTF-8'));
					$LabelEmail=$email;
					$user = get_user_by( 'email', $email );
					if($user!==FALSE){
						$LabelNome=$user->first_name;
						$LabelCognome=$user->last_name;
						$LabelCF=strtoupper($Celle[1]);
						$LabelEmail=$email;
						$UtenteEsiste=True;
						$CF=get_user_meta($user->ID,"CF",TRUE);
					}else{
						$LabelEmail="<span style=\"color:red;\">Utente Non trovato</span>";
						$LabelNome="";
						$LabelCognome="";
						$StatoOperazione="Operazione non eseguibile";
					}
					if($UtenteEsiste And strlen($LabelCF)==16 And $LabelCF!=$CF){
						$Stato="SemaforoVerde";
						$StatoOperazione="Aggiornabile";
						$Creare=TRUE;
						$ValoriPassaggio[]=array(
							"Stato"         => $Validita,
							"ID"			=> $user->ID,
							"Nome"          => ucfirst(trim($LabelNome)),
							"Cognome"       => ucfirst(trim($LabelCognome)),
							"Email"         => $email,
							"CodiceFiscale" => strtoupper(trim(str_replace("?"," ",mb_convert_encoding($Celle[1],"ASCII"))))
						);
					}else{
						$Stato="SemaforoRosso";
						if(strlen($LabelCF)!=16 )
							$StatoOperazione="CF non valido";
						if($LabelCF==$CF)
							$StatoOperazione="CF già assegnato";
					}
					echo "<tr id='GridVerificaCFUtenti_Row_$i'>
							<td class='ui-widget-content first $Stato'></td>
							<td class='ui-widget-content'>".$LabelNome."</td>
							<td class='ui-widget-content'>".$LabelCognome."</td>
							<td class='ui-widget-content'>".$LabelEmail."</td>
							<td  class='ui-widget-content'>".$LabelCF." - ".$CF."</td>
							<td  class='ui-widget-content $Stato'>".$StatoOperazione."</td>	
						</tr>";
				}
			?>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaCFUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
	<?php
				echo "<input type=\"hidden\" id=\"PSCF\" name=\"PSCF\" value='".json_encode($ValoriPassaggio,JSON_HEX_APOS)."'>";
				if($Creare){
					echo "<input type=\"hidden\" id=\"PassaggioCFSuccessivo\" value=\"Si\">";
				}else{
					echo "<input type=\"hidden\" id=\"PassaggioCFSuccessivo\" value=\"No\">";
				}
					
		die();
	}

/* Funzione che fonde due account spostando i corsi del primo utente nel secondo
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che rapprensenta i CF degli utenti con lo stato di importabiità.
	 *			   nel campo nascosto PSDati viene trasmessa la tabella codifita in json dei dati degli utenti da importare
	 */

	function FondiAccount(){
		check_ajax_referer('adminsecretmemostatusPrenotazione', 'security');
		$UserID1= filter_input(INPUT_POST, 'UID1');
		$UserID2= filter_input(INPUT_POST, 'UID2');
		global $wpdb;
			
		$updated = $wpdb->update( EM_BOOKINGS_TABLE, 
								  array('person_id'=> $UserID2),
								  array('person_id'=> $UserID1),
								  array("%d"),
 								  array("%d"));
 								  
		if ( false === $updated ) {
		    echo "Errore di aggiornamento della tabella ". EM_BOOKINGS_TABLE;
		    die();
		} 
		
		$updated = $wpdb->update( $wpdb->table_corsisti, 
								  array('IDUser'=> $UserID2),
								  array('IDUser'=> $UserID1),
								  array("%d"),
 								  array("%d"));
 								  
		if ( false === $updated ) {
		    echo "Errore di aggiornamento della tabella ". $wpdb->table_corsisti;
		    die();
		} 		
		
//		echo $email1." - ".$email2."<br />";
		$Utenti=new Utenti();
		$Utente1=$Utenti->get_Descrizione($UserID1);
		$Utente2=$Utenti->get_Descrizione($UserID2);
		
		$CorsiUtente1=$Utenti->get_Corsi_per_User($UserID1);
//		echo "<pre>";var_dump($Utente1);
//		var_dump($CorsiUtente1);
		$CorsiUtente2=$Utenti->get_Corsi_per_User($UserID2);
//		var_dump($Utente2);
//		var_dump($CorsiUtente2);echo "</pre>";
//		die();
		?>
			<input type="hidden" id="UserID1" value="<?php echo $Utente1["Id"];?>"/>
			<input type="hidden" id="UserID2" value="<?php echo $Utente2["Id"];?>"/>
			<table class="ui-widget head body foot GridDatiUtenti">
				<thead class="ui-widget-header">
					<tr class="columnHead">
						<td class="ui-widget-header">ID</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Scuola</td>
					</tr>
				</thead>
				<tbody class="ui-widget-content">
					<tr>
						<td class='ui-widget-content'><?php echo $Utente1["Id"];?></td>
						<td class='ui-widget-content'><?php echo $Utente1["Nome"];?></td>
						<td class='ui-widget-content'><?php echo $Utente1["Cognome"];?></td>
						<td class='ui-widget-content' style="color:red;font-weight:bold;"><?php echo $Utente1["Email"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente1["CF"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente1["NomeScuola"];?></td>	
					</tr>
					<tr>
						<td class='ui-widget-content' colspan="6">
<?php		if($CorsiUtente1["FormatoreTutor"]){?>
								<h3>Corsi come Formatore/Tutor</h3>
							<ul>
<?php			foreach($CorsiUtente1["FormatoreTutor"] as $Corsi){?>
								<li><?php echo "(".$Corsi->ID.") ".$Corsi->post_title;?></li>
<?php			}?>	
							</ul>
<?php		}
			if($CorsiUtente1["Corsista"]){?>
							<h3>Corsi come Corsista</h3>
							<ul>
<?php			foreach($CorsiUtente1["Corsista"] as $Corsi){?>
								<li class="listaCorsi"><?php echo "(".$Corsi->event_id.") ".$Corsi->event_name;?></li>
<?php			}?>	
							</ul>
<?php		}?>
						</td>
					</tr>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaCFUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
			<table class="ui-widget head body foot GridDatiUtenti">
				<thead class="ui-widget-header">
					<tr class="columnHead">
						<td class="ui-widget-header">ID</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Scuola</td>
					</tr>
				</thead>
				<tbody class="ui-widget-content">
					<tr>
						<td class='ui-widget-content'><?php echo $Utente2["Id"];?></td>
						<td class='ui-widget-content'><?php echo $Utente2["Nome"];?></td>
						<td class='ui-widget-content'><?php echo $Utente2["Cognome"];?></td>
						<td class='ui-widget-content' style="color:green;font-weight:bold;"><?php echo $Utente2["Email"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente2["CF"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente2["NomeScuola"];?></td>	
					</tr>
					<tr>
						<td class='ui-widget-content' colspan="6">
<?php		if($CorsiUtente2["FormatoreTutor"]){?>
								<h3>Corsi come Formatore/Tutor</h3>
							<ul>
<?php			foreach($CorsiUtente2["FormatoreTutor"] as $Corsi){?>
								<li><?php echo $Corsi->post_title;?></li>
<?php			}?>	
							</ul>
<?php		}
			if($CorsiUtente2["Corsista"]){?>
							<h3>Corsi come Corsista</h3>
							<ul>
<?php			foreach($CorsiUtente2["Corsista"] as $Corsi){?>
								<li class="listaCorsi"><?php echo "(".$Corsi->event_id.") ".$Corsi->event_name;?></li>
<?php			}?>	
							</ul>
<?php		}?>
						</td>
					</tr>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaCFUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
			<div>
				<p class="StatoOperazioni">
					Operazione eseguita con successo
				</p>
			</div>
	<?php
		die();
	}
/* Funzione che crea il file zippato degli attestati
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     percorso dell'archivio ZIP
	 */
	function CreaZIPAttestati(){
		check_ajax_referer('adminsecretmemostatusPrenotazione', 'security');
		$TitoloCorso= filter_input(INPUT_POST, 'TitoloCorso');
		$TitoloCorso=sanitize_file_name( $TitoloCorso );
		$Dir=get_home_path()."wp-content/GestioneCorsi/Tmp/".$TitoloCorso;
		$NomefileZip=get_home_path()."wp-content/GestioneCorsi/Tmp/".$TitoloCorso.".zip";
		require_once(get_home_path()."wp-content/plugins/gestione-corsi/includes/PclZip/pclzip.php");
		if (is_dir($Dir)){
			// Crea l'archivio
		 	$zip = new PclZip($NomefileZip);
			// Inizializzazione dell'iterator a cui viene passato 
			// l'iteratore ricorsivo delle directory a cui viene passata la directory da zippare
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Dir));
			// Ciclo tutti gli elementi dell'iteratore, i files estratti dall'iteratore
			foreach ($iterator as $key=>$value) {
				if (substr($key,-1)!="."){
					$zip->add(realpath($key),PCLZIP_OPT_REMOVE_PATH,dirname($key));
				}
			}
			if (is_dir($Dir)){
				$this->rmdir_recursive($Dir);
			}
			rmdir($Dir);
			echo $NomefileZip;
		}else
			echo "";
		die();
	}

/* Funzione che crea la cartella per la generazione massiva degli attestati
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     Si se la cartella è stata creata altrimenti No
	 */
	function CreaCartellaZip(){
		check_ajax_referer('adminsecretmemostatusPrenotazione', 'security');
		$TitoloCorso= filter_input(INPUT_POST, 'TitoloCorso');
		$TitoloCorso=sanitize_file_name( $TitoloCorso );
		$Dir=get_home_path()."wp-content/GestioneCorsi/Tmp";
		if (!is_dir($Dir)){
			if(mkdir($Dir,0711,TRUE)){
				echo "No";
				die();
			}			
		}
		$Dir=$Dir."/".$TitoloCorso;
		if (!is_dir($Dir)){
			if(!mkdir($Dir,0711,TRUE)){
				echo "No";
				die();
			}
		}else{
			$this->rmdir_recursive($Dir);
		}	
		mkdir($Dir);
		echo "Si";
		die();
	}




	
	private function rmdir_recursive($dir) {
	  foreach(scandir($dir) as $file) {
	    if ('.' === $file || '..' === $file) continue;
	    if (is_dir($dir.'/'.$file)) $this->rmdir_recursive($dir.'/'.$file);
	    else unlink($dir.'/'.$file);
	  }
	  rmdir($dir);
	}

/* Funzione che crea il singolo attestato
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     Si se l'attestato è creato No se l'attestato non è stato creato
	 */
	function CreaAttestatoSingolo(){
		check_ajax_referer('adminsecretmemostatusPrenotazione', 'security');
		$TitoloCorso= filter_input(INPUT_POST, 'TitoloCorso');
		$IDCorso= filter_input(INPUT_POST, 'Corso');
		$Utente= filter_input(INPUT_POST, 'User');
		$TitoloCorso=sanitize_file_name( $TitoloCorso );
		

		$Dir=get_home_path()."wp-content/GestioneCorsi/Tmp/".$TitoloCorso;
		$Utente=substr($Utente,0,strlen($Utente)-5);
		

		$Pdf=new Gestione_Documenti();
		$Corso=new Gestione_Corso($IDCorso,TRUE);
		if ($Pdf->CreaAttestato($Corso,$Utente,true,$Dir))
			echo "Si";
		else
			echo "No";
		die();
	}








/* Funzione che visualizza i dati dei corsi degli utenti da fondere
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che rapprensenta i CF degli utenti con lo stato di importabiità.
	 *			   nel campo nascosto PSDati viene trasmessa la tabella codifita in json dei dati degli utenti da importare
	 */

	function CaricaDatiDaFondere(){
		check_ajax_referer('adminsecretmemostatusPrenotazione', 'security');
		$email1= filter_input(INPUT_POST, 'account1');
		$email2= filter_input(INPUT_POST, 'account2');
//		echo $email1." - ".$email2."<br />";
		$Utenti=new Utenti();
		$Utente1=$Utenti->get_Descrizione(0,$email1);
		$Utente2=$Utenti->get_Descrizione(0,$email2);
		
		$CorsiUtente1=$Utenti->get_Corsi_per_User($Utente1["Id"]);
//		echo "<pre>";var_dump($Utente1);
//		var_dump($CorsiUtente1);
		$CorsiUtente2=$Utenti->get_Corsi_per_User($Utente2["Id"]);
//		var_dump($Utente2);
//		var_dump($CorsiUtente2);echo "</pre>";
//		die();
		?>
			<input type="hidden" id="UserID1" value="<?php echo $Utente1["Id"];?>"/>
			<input type="hidden" id="UserID2" value="<?php echo $Utente2["Id"];?>"/>
			<table class="ui-widget head body foot GridDatiUtenti">
				<thead class="ui-widget-header">
					<tr class="columnHead">
						<td class="ui-widget-header">ID</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Scuola</td>
					</tr>
				</thead>
				<tbody class="ui-widget-content">
					<tr>
						<td class='ui-widget-content'><?php echo $Utente1["Id"];?></td>
						<td class='ui-widget-content'><?php echo $Utente1["Nome"];?></td>
						<td class='ui-widget-content'><?php echo $Utente1["Cognome"];?></td>
						<td class='ui-widget-content' style="color:red;font-weight:bold;"><?php echo $Utente1["Email"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente1["CF"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente1["NomeScuola"];?></td>	
					</tr>
					<tr>
						<td class='ui-widget-content' colspan="6">
<?php		if($CorsiUtente1["FormatoreTutor"]){?>
								<h3>Corsi come Formatore/Tutor</h3>
							<ul>
<?php			foreach($CorsiUtente1["FormatoreTutor"] as $Corsi){?>
								<li><?php echo "(".$Corsi->ID.") ".$Corsi->post_title;?></li>
<?php			}?>	
							</ul>
<?php		}
			if($CorsiUtente1["Corsista"]){?>
							<h3>Corsi come Corsista</h3>
							<ul>
<?php			foreach($CorsiUtente1["Corsista"] as $Corsi){?>
								<li class="listaCorsi"><?php echo "(".$Corsi->event_id.") ".$Corsi->event_name;?></li>
<?php			}?>	
							</ul>
<?php		}?>
						</td>
					</tr>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaCFUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
			<table class="ui-widget head body foot GridDatiUtenti">
				<thead class="ui-widget-header">
					<tr class="columnHead">
						<td class="ui-widget-header">ID</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Scuola</td>
					</tr>
				</thead>
				<tbody class="ui-widget-content">
					<tr>
						<td class='ui-widget-content'><?php echo $Utente2["Id"];?></td>
						<td class='ui-widget-content'><?php echo $Utente2["Nome"];?></td>
						<td class='ui-widget-content'><?php echo $Utente2["Cognome"];?></td>
						<td class='ui-widget-content' style="color:green;font-weight:bold;"><?php echo $Utente2["Email"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente2["CF"];?></td>
						<td  class='ui-widget-content'><?php echo $Utente2["NomeScuola"];?></td>	
					</tr>
					<tr>
						<td class='ui-widget-content' colspan="6">
<?php		if($CorsiUtente2["FormatoreTutor"]){?>
								<h3>Corsi come Formatore/Tutor</h3>
							<ul>
<?php			foreach($CorsiUtente2["FormatoreTutor"] as $Corsi){?>
								<li><?php echo $Corsi->post_title;?></li>
<?php			}?>	
							</ul>
<?php		}
			if($CorsiUtente2["Corsista"]){?>
							<h3>Corsi come Corsista</h3>
							<ul>
<?php			foreach($CorsiUtente2["Corsista"] as $Corsi){?>
								<li class="listaCorsi"><?php echo "(".$Corsi->event_id.") ".$Corsi->event_name;?></li>
<?php			}?>	
							</ul>
<?php		}?>
						</td>
					</tr>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaCFUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
	<?php

		die();
	}



	/*
	 * Funzione che che aggiorna i CF degli utenti
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che riporta i CF con lo stato di importabilità.
	 */

	function CreaCFUtenti(){
		global $log;
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Valori= json_decode( stripslashes(filter_input(INPUT_POST, 'valori')));
//		echo "<pre>";print_r($Valori);echo "</pre>";die();
		?>
			<table id="GridVerificaCFUtenti" class="ui-widget head body foot">
					<thead class="ui-widget-header">
					<tr>
						<td id="GridVerificaCFUtenti_caption_td" class="ui-state-active caption" colspan="9">Operazioni di aggiornamento CF Utenti</td>
					</tr>
					<tr class="columnHead">
						<td class="ui-widget-header">Stato</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
						<td class="ui-widget-header">Codice Fiscale</td>
					</tr>
					</thead>
					<tbody class="ui-widget-content">
<?php
				$StatoOk="style=\"background:green;color:#fff;\"";
				$StatoNo="style=\"background:red;color:#fff;\"";
				foreach($Valori as $Riga){
					$Riga->Nome=     str_replace("?"," ",mb_convert_encoding($Riga->Nome,"ASCII"));
					$Riga->Cognome=  str_replace("?"," ",mb_convert_encoding($Riga->Cognome,"ASCII"));
					if($Riga->Stato=="Valido"){
						$Stato=update_user_meta($Riga->ID,"CF",strtoupper($Riga->CodiceFiscale));					
						if($Stato===False){
							$StatoOperazione="Non Aggiornato";
							$Ok=False;
						}
						if($Stato===true){
							$StatoOperazione="Aggiornato";
							$Ok=True;
						}
						if(is_numeric($Stato)){
							$StatoOperazione="Creato";
							$Ok=True;
						}
					}						
					echo "<tr id='GridVerificaUtenti_Row_$i'>
							<td class='ui-widget-content first' ".($Ok?$StatoOk:$StatoNo).">".$StatoOperazione."</td>
							<td class='ui-widget-content'>".$Riga->Nome."</td>
							<td class='ui-widget-content'>".$Riga->Cognome."</td>
							<td class='ui-widget-content'>".$Riga->Email."</td>
							<td  class='ui-widget-content'>".$Riga->CodiceFiscale."</td>
						</tr>";
				}
			?>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
	<?php
		die();
	}
	function VerificaNuoviUtenti(){
			check_ajax_referer('adminsecretmemostatusPrenotazione','security');
			$ValoriPassaggio=array();
			$ValoriPrimo= explode("&", filter_input(INPUT_POST, 'valori'));
			$Valori=array();
			$Indici=array();
			$Index=0;
			$I=1;
			$GestScuole= get_option('gestione_scuole'); 
			if($GestScuole=="Si"){
				$PrefTab="GridUtenti";
				$Numelementi=5;
			}else{
				$PrefTab="GridUtentiNoScuola";
				$Numelementi=4;
			}
			foreach($ValoriPrimo as $Valore){
				$Riga=explode("=",$Valore);
				$Valori[$Riga[0]]= $Riga[1];
				$CurIndex=substr($Riga[0], strripos($Riga[0],"_")+1);
				$Indici[$Index]= $CurIndex;
				if($I%$Numelementi==0){
					$Index++;
					$I=1;
				}else{
					$I++;
				}
			}
			$NumeroRighe=(count($Valori))/5;
			?>
			<table id="GridVerificaUtenti" class="ui-widget head body foot">
					<thead class="ui-widget-header">
					<tr>
						<td id="GridVerificaUtenti_caption_td" class="ui-state-active caption" colspan="7">Verifica Dati nuovi Utenti</td>
					</tr>
					<tr class="columnHead">
						<td class="ui-widget-header first" style="width:5%">Stato</td>
						<td id="GridVerificaUtenti_nome_td_head" class="ui-widget-header" style="width:20%">UserName</td>
						<td id="GridVerificaUtenti_nome_td_head" class="ui-widget-header" style="width:12%">Nome</td>
						<td id="GridVerificaUtenti_cognome_td_head" class="ui-widget-header" style="width:12%">Cognome</td>
						<td id="GridVerificaUtenti_email_td_head" class="ui-widget-header" style="width:20%">Email</td>
<?php if($GestScuole=="Si"):?>
				<td id="GridVerificaUtenti_scuola_td_head" class="ui-widget-header" style="width:10%">Scuola</td>
<?php endif;?>
						<td id="GridVerificaUtenti_codicefiscale_td_head" class="ui-widget-header" style="width:10%">Codice Fiscale</td>
					</tr>
					</thead>
					<tbody class="ui-widget-content">
			<?php
				$Creare=FALSE;
				$SemaforoRosso="style=\"color:#fff;background-color:red;\"";
				for($i=1;$i<=$NumeroRighe;$i++){
					$Validita="Valido";
					$Indice=$Indici[$i-1];
					$Creabilita=True;
//					var_dump($this->pulisciValori(urldecode($Valori[$PrefTab."_nome_".$Indice])));die();
					$email=$this->pulisciValori(urldecode($Valori[$PrefTab."_email_".$Indice]));
					$LabelEmail=$email;
					$LabelNome=$this->convert_CaratteriAccentati($this->pulisciValori($Valori[$PrefTab."_nome_".$Indice]));
					$LabelNome=$this->convert_Spazi($LabelNome," ");
					$LabelCognome=$this->convert_CaratteriAccentati($this->pulisciValori($Valori[$PrefTab."_cognome_".$Indice]));
					$LabelCognome=$this->convert_Spazi($LabelCognome," ");
					if($GestScuole=="Si")					
						$LabelScuola=$this->pulisciValori($Valori[$PrefTab."_scuola_".$Indice]);
					$LabelCF=$this->pulisciValori($Valori[$PrefTab."_codicefiscale_".$Indice]);
//					$Username= strtolower(str_replace(" ", "_", $this->convert_CaratteriAccentati($this->pulisciValori($Valori[$PrefTab."_nome_".$Indice]),TRUE)).".".str_replace("+", "_", $this->convert_CaratteriAccentati($this->pulisciValori($Valori[$PrefTab."_cognome_".$Indice]),TRUE)));
					$Username= str_replace("'", "", strtolower($this->convert_Spazi($LabelNome).".".$this->convert_Spazi($LabelCognome)),$Testo);
					if($Username=="."){
						$Username="Campo non calcolabile";
					}
					$TestUsername=$Username;
					$IdUserName=0;			
					while ( username_exists( $TestUsername ) ) {
						$TestUsername=$Username.".".++$IdUserName;					
					}
					$Username=$TestUsername;
					if( email_exists($email)) {
						$Creabilita=FALSE;
						$LabelEmail="<label for=\"".$PrefTab."_email_$Indice\" $SemaforoRosso>".$email."</label> ";
						$Validita="Errore";
					}
					if( !$email) {
						$Creabilita=FALSE;
						$LabelEmail="<label for=\"".$PrefTab."_email_$Indice\" $SemaforoRosso>Campo Vuoto</label> ";
						$Validita="Errore";
					}
					if(!$Valori[$PrefTab."_nome_".$Indice] Or strlen( $Valori[$PrefTab."_nome_".$Indice])<4 ){
						$Creabilita=FALSE;
						$LabelNome="<label for=\"".$PrefTab."_nome_$Indice\" $SemaforoRosso>".(!$Valori[$PrefTab."_nome_".$Indice]?"Campo Vuoto":$Valori[$PrefTab."_nome_".$Indice])."</label> ";						
						$Validita="Errore";
					}
					if(!$Valori[$PrefTab."_cognome_".$Indice] Or strlen( $Valori[$PrefTab."_cognome_".$Indice])<4 ){
						$Creabilita=FALSE;
						$LabelCognome="<label for=\"".$PrefTab."_cognome_$Indice\" $SemaforoRosso>".(!$Valori[$PrefTab."_cognome_".$Indice]?"Campo Vuoto":$Valori[$PrefTab."_cognome_".$Indice])."</label> ";						
						$Validita="Errore";
						
					}
					if($GestScuole=="Si" And (!$Valori[$PrefTab."_scuola_".$Indice] Or strlen( $Valori[$PrefTab."_scuola_".$Indice])<4) ){
						$Creabilita=FALSE;
						$LabelScuola="<label for=\"".$PrefTab."_scuola_$Indice\"  $SemaforoRosso>".(!$Valori[$PrefTab."_scuola_".$Indice]?"Campo Vuoto":$Valori[$PrefTab."_scuola_".$Indice])."</label> ";						
						$Validita="Errore";
					}
/*					if(!$Valori["GridUtenti_codicefiscale_$Indice"] ){
						$Creabilita=FALSE;
						$LabelCF="<label for=\"GridUtenti_codicefiscale_$Indice\" $SemaforoRosso>".(!$Valori["GridUtenti_codicefiscale_$Indice"]?"Campo Vuoto":$Valori["GridUtenti_codicefiscale_$Indice"])."</label> ";						
						$Validita="Errore";
					}
*/					if($Creabilita){
						$Stato='<i class="fa fa-thumbs-up" aria-hidden="true" style="color:green;"></i>';
						$Creare=TRUE;
						if($GestScuole=="Si"){
							$ValoriPassaggio[]=array(
								"Stato"         => $Validita,
								"UserName"      => $Username,
								"Nome"          => $Valori["GridUtenti_nome_$Indice"],
								"Cognome"       => $Valori["GridUtenti_cognome_$Indice"],
								"Email"         => $email,
								"Scuola"        => $Valori["GridUtenti_scuola_$Indice"],
								"CodiceFiscale" => $Valori["GridUtenti_codicefiscale_$Indice"]
							);
						}else{
							$ValoriPassaggio[]=array(
								"Stato"         => $Validita,
								"UserName"      => $Username,
								"Nome"          => $Valori["GridUtentiNoScuola_nome_$Indice"],
								"Cognome"       => $Valori["GridUtentiNoScuola_cognome_$Indice"],
								"Email"         => $email,
								"CodiceFiscale" => $Valori["GridUtentiNoScuola_codicefiscale_$Indice"]
							);
						}
					}else{
						$Stato='<i class="fa fa-thumbs-down" aria-hidden="true" style="color:red;"></i>';
					}
					echo "<tr id='GridVerificaUtenti_Row_$i'>
							<td class='ui-widget-content first'>$Stato</td>
							<td class='ui-widget-content'>".$Username."</td>
							<td class='ui-widget-content'>".$LabelNome."</td>
							<td class='ui-widget-content'>".$LabelCognome."</td>
							<td class='ui-widget-content'>".$LabelEmail."</td>";
					if($GestScuole=="Si"){
						echo "	<td class='ui-widget-content'>".$LabelScuola."</td>";
					}
					echo "	<td  class='ui-widget-content'>".$LabelCF."</td>
						</tr>";
				}
			?>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaUtenti_footer_td" colspan="7"></td>
						</tr>
					</tfoot>
				</table>
	<?php
			echo "<input type=\"hidden\" id=\"PSDatiL\" name=\"PSDatiL\" value='".json_encode($ValoriPassaggio,JSON_HEX_APOS)."'>";
				if($Creare){
					echo "<input type=\"hidden\" id=\"PassaggioSuccessivoL\" value=\"Si\">";
				}else{
					echo "<input type=\"hidden\" id=\"PassaggioSuccessivoL\" value=\"No\">";
				}
			
		die();
	}

	/**
	 * Funzione che crea gli utenti Controllati ed impostati nella funzione VerificaNuoviUtentiExcel
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che rapprensenta gli utenti da importare con lo stato di importabiità con lo stato delle operazioni.
	 */

	function CreaNuoviUtenti(){
		global $log;
		$GestScuole= get_option('gestione_scuole'); 
		if($GestScuole=="Si")
			$CS=7;
		else	
			$CS=6;
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Valori= json_decode( stripslashes(filter_input(INPUT_POST, 'valori')));
//		echo "<pre>";print_r($Valori);echo "</pre>";die();
		?>
			<table id="GridVerificaUtenti" class="ui-widget head body foot">
					<thead class="ui-widget-header">
					<tr>
						<td id="GridVerificaUtenti_caption_td" class="ui-state-active caption" colspan="9">Verifica Dati nuovi Utenti</td>
					</tr>
					<tr class="columnHead">
						<td class="ui-widget-header">Stato</td>
						<td class="ui-widget-header">User Name</td>
						<td class="ui-widget-header">Nome</td>
						<td class="ui-widget-header">Cognome</td>
						<td class="ui-widget-header">Email</td>
			<?php if($GestScuole=="Si"):?>
						<td class="ui-widget-header">Scuola</td>		
			<?php endif;?>				
						<td class="ui-widget-header">Codice Fiscale</td>
						<td class="ui-widget-header">Utente</td>
						<td class="ui-widget-header">Email</td>
					</tr>
					</thead>
					<tbody class="ui-widget-content">
<?php
				$StatoOk="style=\"background:green;color:#fff;\"";
				$StatoNo="style=\"background:red;color:#fff;\"";
				foreach($Valori as $Riga){
					$Riga->UserName= str_replace("?"," ",mb_convert_encoding($Riga->UserName,"ASCII"));
					$Riga->Nome=     str_replace("?"," ",mb_convert_encoding($Riga->Nome,"ASCII"));
					$Riga->Cognome=  str_replace("?"," ",mb_convert_encoding($Riga->Cognome,"ASCII"));
					$CreatoUtente=FALSE;
					$InviataEmail=FALSE;
					if($Riga->Stato=="Valido"){
						$Password=wp_generate_password( $length=12, $include_standard_special_chars=false );
						$userdata = array(
							'user_login'		=> $Riga->UserName,
							'user_nicename'		=> $Riga->UserName,
							'user_pass'			=>  $Password,
							'user_email'		=>  $Riga->Email,
							'first_name'		=>	$Riga->Nome,
							'last_name'			=>	$Riga->Cognome,
							'display_name'		=>	$Riga->Cognome
						);
						$user_id = wp_insert_user( $userdata ) ;
						if ( ! is_wp_error( $user_id ) ) {
							$log->DatiUtente=array( "Operazione"  => "Creazione Utenti",
													"IdUtente"    => $user_id,
													"Utente"      => $Riga->UserName,
													"Stato"       => "Utente Creato",
													"Provenienza" => "Admin",
													"Dati"        => serialize($userdata));
							$log->ScriviLog("Utenti");
							$CreatoUtente=TRUE;
							$Titolo=get_option( 'dbem_bookings_email_registration_subject' );
							$Testo=get_option( 'dbem_bookings_email_registration_body' );
							$Testo= str_replace("%username%", $Riga->UserName,$Testo);
							$Testo= str_replace("%password%", $Password,$Testo);
							if($GestScuole=="Si")
								add_user_meta( $user_id, 'Scuola', strtoupper($Riga->Scuola),true );
							add_user_meta( $user_id, 'CF', strtoupper($Riga->CodiceFiscale),true );
							$useremail=array(
								'Email'   =>$Riga->Email,
								'Oggetto' =>str_replace(PHP_EOL, '',$Titolo),
								'Testo'   =>str_replace(PHP_EOL, '',$Testo)
								);
							$headers[] = 'From: Amministrazione Formazione <ignazios@gmail.com>';
							if(wp_mail( $Riga->Email,$Titolo,$Testo,"Portale Prenotazione")){
								$log->DatiUtente=array( "Operazione"  => "Creazione Utenti",
														"IdUtente"    => $user_id,
														"Utente"      => $Riga->UserName,
														"Stato"       => "Email inviata",
														"Provenienza" => "Admin",
														"Dati"        => serialize($useremail));
								$log->ScriviLog("Utenti");
								$InviataEmail=TRUE;
							}else{
							    global $phpmailer;
								$emailError="";
							    if (isset($phpmailer)) {
									$emailError = $phpmailer->ErrorInfo;
								}
								$useremail['Errore'] = $emailError;
								$log->DatiUtente=array( "Operazione"  => "Creazione Utenti",
														"IdUtente"    => $user_id,
														"Utente"      => $Riga->UserName,
														"Stato"       => "Email non inviata",
														"Provenienza" => "Admin",
														"Dati"        => serialize($useremail));
								$log->ScriviLog("Utenti");
							}
						}else{
							$log->DatiUtente=array( "Operazione"  => "Creazione Utenti",
													"IdUtente"    => $user_id,
													"Utente"      => $Riga->UserName,
													"Stato"       => "NON è stato possibile creare l'Utente",
													"Provenienza" => "Admin",
													"Dati"        => serialize($userdata));
							$log->ScriviLog("Utenti");
						}						
					}						
					echo "<tr id='GridVerificaUtenti_Row_$i'>
							<td class='ui-widget-content first' ".($CreatoUtente?$StatoOk:$StatoNo)."></td>
							<td class='ui-widget-content'>".$Riga->UserName."</td>
							<td class='ui-widget-content'>".$Riga->Nome."</td>
							<td class='ui-widget-content'>".$Riga->Cognome."</td>
							<td class='ui-widget-content'>".$Riga->Email."</td>";
					if($GestScuole=="Si")
						echo "  <td class='ui-widget-content'>".$Riga->Scuola."</td>";
					echo "	<td  class='ui-widget-content'>".$Riga->CodiceFiscale."</td>
							<td  class='ui-widget-content' ".($CreatoUtente ?$StatoOk:$StatoNo).">".($CreatoUtente ?"Creato correttamente":$user_id->get_error_message())."</td>	
							<td  class='ui-widget-content' ".($InviataEmail?$StatoOk:$StatoNo).">".($InviataEmail ?"Inviata correttamente":$emailError)."</td>
						</tr>";
				}
			?>
					</tbody>
					<tfoot class="ui-widget-header">
						<tr>
							<td id="GridVerificaUtenti_footer_td" colspan="<?php echo $CS;?>"></td>
						</tr>
					</tfoot>
				</table>
	<?php			
		die();
	}
		
	/**
	 * Funzione che crea gli utenti Controllati ed impostati nella funzione VerificaNuoviUtentiExcel
	 *
	 * @since    1.0.0
	 * @param      nessuno.
	 * @return     tabela che rapprensenta gli utenti da importare con lo stato di importabiità con lo stato delle operazioni.
	 */
		public function ScriviLogStatoCorso(){
			global $log;
			
//			check_ajax_referer('adminsecretmemostatusPrenotazione','security');
			$Stati=array(
				"bookings_unapprove" => "Non Approvato",
				"bookings_approve"   => "Approvato",
				"bookings_reject"    => "Respinto",
				"bookings_delete"    => "Cancellato",
				"booking_cancel"     => "Cancellato",
				"bookings_unapprove" => "Non Approvato",
			);
			$Valori=explode("&",filter_input(INPUT_POST, 'valori'));
			unset($Valori[0]);
			$Parametri=array();
			foreach($Valori as $Elementi){
				$ele=explode("=",$Elementi);
				$Parametri[$ele[0]]=$ele[1];
			}
			$Prenotazione=new EM_Booking($Parametri["booking_id"]);
			if(isset($Parametri["action"])){
				$Stato=$Stati[$Parametri["action"]];
			}else{
				$Stato= "Modifica/Visualizza";		
			}
			$Utente=get_user_by("id",$Prenotazione->person_id);
			$log->DatiIscrizione=array(
	              "Operazione"		=> "Modifica Stato",
	 			  "IdUtente"		=> $Prenotazione->person_id,
	              "Utente"			=> $Utente->user_login,
	              "IDCorso"			=> $Prenotazione->event_id,
				  "IDPrenotazione"  => $Prenotazione->booking_id,
	              "NPosti"			=> $Prenotazione->booking_spaces,
	              "Stato"			=> $Stato, 
	              "Provenienza"		=>(isset($Parametri["page"]) && $Parametri["page"]=="events-manager-bookings"?"Admin":"Sito"),
	              "Dati"			=> serialize($Valori)
			);
			echo $log->ScriviLog("Iscrizioni");			
		die();
	}
		public function ScriviLogStatoCorsoPublic(){
			global $log;
			check_ajax_referer('publicsecretmemostatusPrenotazione','security');
			$Stati=array(
				"booking_cancel" => "Annullata Utente"
			);
			$Valori=explode("&",filter_input(INPUT_POST, 'valori'));
			$Valori[0]=substr($Valori[0], strpos($Valori[0],"?")+1);
			$Parametri=array();
			foreach($Valori as $Elementi){
				$ele=explode("=",$Elementi);
				$Parametri[$ele[0]]=$ele[1];
			}
			$Prenotazione=new EM_Booking($Parametri["booking_id"]);
			if(isset($Parametri["action"])){echo "ci passo".$Stati["booking_cancel"];
				$Stato=$Stati[$Parametri["action"]];
			}else{
				$Stato= "Modifica/Visualizza";		
			}
			$Utente=get_user_by("id",$Prenotazione->person_id);
			$log->DatiIscrizione=array(
	              "Operazione"		=> "Modifica Stato",
	 			  "IdUtente"		=> $Prenotazione->person_id,
	              "Utente"			=> $Utente->user_login,
	              "IDCorso"			=> $Prenotazione->event_id,
				  "IDPrenotazione"  => $Prenotazione->booking_id,
	              "NPosti"			=> $Prenotazione->booking_spaces,
	              "Stato"			=> $Stato, 
	              "Provenienza"		=>(isset($Parametri["page"]) && $Parametri["page"]=="events-manager-bookings"?"Admin":"Sito"),
	              "Dati"			=> serialize($Valori)
			);
//			echo "<pre>|";print_r($Stati);print_r($Parametri);print_r($log);echo"</pre>";
			echo $log->ScriviLog("Iscrizioni");			
		die();
	}
	public function CorsoSetPresenza(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Corso=new Gestione_Corso();
		$IdCorsista= filter_input(INPUT_POST,'idcorsista');
		$Data=filter_input(INPUT_POST,'data');
		$Corso->set_Presenza($IdCorsista,$Data,1);
		die();
	}
	public function CorsoSetAssenza(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Corso=new Gestione_Corso();
		$IdCorsista= filter_input(INPUT_POST,'idcorsista');
		$Data=filter_input(INPUT_POST,'data');
		$Corso->set_Presenza($IdCorsista,$Data,0);
		die();
	}
	public function CorsoSetNote(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Corso=new Gestione_Corso();
		$IdCorsista= filter_input(INPUT_POST,'idcorsista');
		$Data=filter_input(INPUT_POST,'data');
		$Nota= htmlentities(filter_input(INPUT_POST, 'nota'));
		$Corso->set_Nota($IdCorsista,$Data,$Nota);
		die();
	}	
	public function CorsoArgomentiLezione(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$IdCorso= filter_input(INPUT_POST,'idcorso');
		$Corso=new Gestione_Corso($IdCorso);
		$Data=filter_input(INPUT_POST,'data');
		$Argomenti= htmlentities(filter_input(INPUT_POST, 'argomenti'));
		echo $Corso->set_Argomenti($Data,$Argomenti);		
		die();	
	}
	public function GetArgomentiLezione(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$IdCorso= filter_input(INPUT_POST,'idcorso');
		$Corso=new Gestione_Corso($IdCorso);
		$Data=filter_input(INPUT_POST,'data');
		echo $Corso->get_ArgomentiLezione($Data);	
		die();	
	}
	public function CreaAttestato(){
		$IdCorso= filter_input(INPUT_POST, "corso");
		$IdUtente= filter_input(INPUT_POST, "utente");
		$locate=admin_url();
		$locate.="admin.php?page=corsi&amp;op=registro&amp;event_id=".$IdCorso."&amp;user_id=".$IdUtente."&amp;mod=stattestato";
		echo $locate;die();
		wp_redirect($locate);
		die();
	}
	public function CorsoSetAssenzaMinuti(){
		check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Corso=new Gestione_Corso();
		$MinAss=filter_input(INPUT_POST, "minass");
		$IdCorsista=filter_input(INPUT_POST, "idcorsista");
		$Data=filter_input(INPUT_POST, "data");
		$Corso->set_AssenzaMinuti($IdCorsista,$Data,$MinAss);
		die();
	}
	public function CorsoSetOreOnLine(){
    	check_ajax_referer('adminsecretmemostatusPrenotazione','security');
		$Corso=new Gestione_Corso();
		$OreOL=filter_input(INPUT_POST, "oreol");
		$IdCorsista=filter_input(INPUT_POST, "idcorsista");
		$Data=filter_input(INPUT_POST, "data");
		$Corso->set_OreOnLine($IdCorsista,$OreOL,$Data);
		die();
	}	
}
