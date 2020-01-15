<?php
/*
 * Classe perla gestione delle scuole.
 *
 * Gestione dati/elenco scuole
 *
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Scuole {
	
	/*
	 * Costruttore standard 
	 */
	public function __construct() {
	}

	/**
	 * Pagina Che permette la gestone del'elenco delle scuole 
	 *	Page Name: DatiScuole
	 * @since    1.0.0
	 */	
	public function DatiScuole(){
		if (filter_input(INPUT_POST,'memo_impostazioni_scuole')) {
			if (! isset( $_POST['CORSI_SCUOLA'] )|| ! wp_verify_nonce( $_POST['CORSI_SCUOLA'], 'focus-impostazioni' )) {
				echo "<div id='setting-error-settings_updated' class='updated settings-error'> 
					<p><strong>ATTENZIONE. Rilevato potenziale pericolo di attacco informatico, l'operazione &egrave; stata annullata.</strong></p></div>";	
			}else{
		//		print_r($_POST);die();
				update_option("Corsi_CM_scuole",$_POST['codici']);
			}
		}
/*		if (filter_input(INPUT_POST,'import-docenti')) {
			if (! isset( $_POST['Docenti'] )|| ! wp_verify_nonce( $_POST['Docenti'], 'importazione-docenti' )) {
				echo "<div id='setting-error-settings_updated' class='updated settings-error'> 
					<p><strong>ATTENZIONE. Rilevato potenziale pericolo di attacco informatico, l'operazione &egrave; stata annullata.</strong></p></div>";	
			}else{
				$this->ImportaDocenti();
			}
		}
 */
		if (filter_input(INPUT_POST,'docenti')) {
			$Utenti=wp_kses_post($_POST['docenti']);
		}else{
			$Utenti="";
		}
  ?>
 
		<div class="wrap">
			<h2>Dati Scuole</h2>
			<div id="welcome-panel" class="welcome-panel">
				<div class="welcome-panel-content">
					<h3>Codici Meccanografici Scuole</h3>
					<form id="codiciscuole" method="post" action="?page=dati_scuole">
					<?php wp_nonce_field('focus-impostazioni','CORSI_SCUOLA');?>
							<textarea id="codici" name="codici" rows="10" cols="100"><?php echo stripcslashes(get_option("Corsi_CM_scuole",""));?></textarea>
							<br />Inserire i valori nel seguente formato: <strong>Codice Meccanografico</strong> (Spazio)<strong>-</strong>(Spazio) <strong>Nome Scuola</strong>
						<p style="text-align:center;">
							<?php submit_button( 'Memorizza Elenco Scuole', 'primary','memo_impostazioni_scuole',false ); ?>
						</p>
					</form>
				</div>
			</div>
		</div>
<?php		
	}
	
	public function get_Scuola($CM){
		$Scuole=get_option("Corsi_CM_scuole","");
		$Lista=explode("\n",$Scuole);
		foreach($Lista as $Scuola){
			if ($CM==substr($Scuola,0,10)){
				return preg_replace('/[^A-Za-z0-9\- ()]/', '', $Scuola);
			}		
		}
		return FALSE;
	}
	/**
	 * PMetodo che estrae l'elenco delle scuoe 
	 *	
	 * @since    1.0.0
	 * 
	 * @param $Formato : parametro che indica il tpo di output del metodo 
	 *					 Valori ammessi; Array - Select - Datalist - String
	 * @param $Name    : parametro name del tag <select> o <datalist>
	 * @param $Id      : parametro id   del tag <select> o <datalist>
	 * @param $Selected: indica il valore da selezionare per i tag <select> o <datalist>
	 * 
	 */	

	public function getElencoScuole($Formato="Array",$Name="",$Id="",$Selected=""){
		$Scuole=get_option("Corsi_CM_scuole","");
		$Lista=explode("\n",$Scuole);
		switch ($Formato){
			case "Array":
				return $Lista;
				break;
			case "Select":
				$Testa="<select name=\"$Name\" id=\"$Id\">";
				$Piede="</select>";
				break;
			case "Datalist":
				$Testa="<datalist name=\"$Name\" id=\"$Id\">";
				$Piede="</datalist>";
				break;
			case "String":
				$Testa="";
				$Piede="";
				break;
			case "Option":
				$Testa="";
				$Piede="";
				break;
		}
		if ($Formato=="Array"){
			return $Lista;
		}
		if ($Formato=="String"){
				$TestoLista="";
		}else{
			$TestoLista="<option value=''>Scuola non assegnata</option>";
		}
		foreach($Lista as $Scuola){
			if ($Formato=="String"){
				$TestoLista.=substr($Scuola,0,10).",".preg_replace('/[^A-Za-z0-9\- ()]/', '', $Scuola).";";
			}else{
				$TestoLista.="<option value='".substr($Scuola,0,10)."' ".(substr($Scuola,0,10)==$Selected?"selected":"").">".stripslashes($Scuola)."</option>";		
			}
		}
		if ($Formato=="String"){
				$TestoLista=substr($TestoLista, 0,strlen( $TestoLista ));
		}
		return $Testa.$TestoLista.$Piede;
	}
}