<?php
/*
 * Classe perla gestione dei log.
 *
 * Interfaccia Gestione log
 * Inserimento Eventi
 * 
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
class Log {
	
	/*
	 * Array dei dati che verranno memorizzati per il log degli Utenti
	 * $DatiUtente
	 *             Data       - Data Operaizione
	 *             Operazione - Operazione effettuata (Creazione Utente)
	 *			   IdUtente   - Id dell'utente
	 *             Utente     - user_login dell'utente
	 *             Stato      - indica lo stato dell'operazione (Utente Creato,NON è stato possibile creare l'Utente,Email inviata,Email non inviata)
	 *             Provenienza- indica la provenienza dell'operazione (Admin,Sito)
	 *             Dati       - stringa proveniente dalla serializzazione dei dati dell'operazione
	 */
	public $DatiUtente=array();
	/*
	 * Array dei dati che verranno memorizzati per il log delle Iscrizioni
	 *             Data       - Data Operaizione
	 *             Operazione - Operazione effettuata (Richiesta Iscrizione)
	 *			   IdUtente   - Id dell'utente
	 *             Utente     - user_login dell'utente
	 *             IDCorso    - ID del corso
	 *             NPosti     - Numero posti prenotati
	 *             Stato      - indica lo stato dell'operazione 
	 *             Provenienza- indica la provenienza dell'operazione (Admin,Sito)
	 *             Dati       - stringa proveniente dalla serializzazione dei dati dell'operazion
	 */
	public $DatiIscrizione=array();
	
	/*
	 * Costruttore standard 
	 */
	public function __construct() {
	}
	/*
	 * Funzione che permette la creazione di una cartella e se necessario tutte le cartelle genitore necessarie
	 * @param  $Dir: Directory da creare nel formato stringa es. /htdocs/misito/wp-content/GestioneCorsi/log
	 * @param  $FileName: Nome del file di log da creare
	 * @param  $Permessi: Permessi da impostare alla cartella ed ai genitori che verranno creati 
	 *                    Formato UNIX 1 Read + 2 Wite + 4 Execute Max 7 formato ottale 
	 *                    Prima cifra  Proprietario 
	 *					  Seconda cifra Gruppo
	 *                    Terza cifra Altri
	 * @param  $Modalita: modalità di apertura del file cme da funzione fopen di PHP r - w - a
	 */

	function crea_Log($Dir,$FileName,$Permessi=0777,$Modalita="r"){
		$Risultato=array("Directory" => FALSE,
						 "File"      => FALSE,
						 "Pointer"   => NULL);
		$Dir=get_home_path()."wp-content/".$Dir;
		$FileName=$Dir."/".$FileName;
		if (!is_dir($Dir)){
			if(mkdir($Dir,(int)$Permessi,TRUE)){
				$Risultato["Directory"] = TRUE;
			}			
		}else{
			$Risultato["Directory"] = TRUE;
		}
		if($Risultato["Directory"]){
			if ($fp=fopen($FileName,$Modalita)){
				$Risultato["File"]=TRUE;
				$Risultato["Pointer"]=$fp;
			}
		}
	return $Risultato;
	}
	
	/*
	 * Funzione che scrive il file di log
	 * @param  $Log: indica in quale Log memorizzare i dati
	 * @param  $Cartella: percorso della sottocartella del file di log a partire da HomeDirectory/wp-content/
	 */

	function ScriviLog($Log,$Cartella="GestioneCorsi/log"){
		$Risultato=$this->crea_Log($Cartella,$Log.".log",0711,"a");
		if (!$Risultato["Directory"]){
			return "Errore creazione directory del file di log";			
		}else{
			if(!$Risultato["File"]){
				return "Errore creazione del file di log";			
			}
		}
		switch ($Log){
			case "Utenti":
				$Record=$this->DatiUtente;
				break;
			case "Iscrizioni":
				$Record=$this->DatiIscrizione;
		}
		$Riga= date("Y-m-d h:m:s")."\t|";
		foreach ($Record as $Campo){
			$Riga.=$Campo."\t|";
		}
		$Riga= substr($Riga,0,-1)."¶\r\n";
		if(fwrite($Risultato['Pointer'],$Riga)===FALSE){
			fclose($Risultato['Pointer']);
			return "Scrittura sul file di log non riuscita";
		}else{
			fclose($Risultato['Pointer']);
			return "Scrittura sul file di log riuscita";
		}
	}
	
	/*
	 * Funzione che legge il file di log
	 * @param  $Log: Nome del log da leggere, testo che corrisponde al nome del file senza estensione
	 * @param  $Cartella: Cartella in cui è memorizzatoil file di log di default /wp-content/GestioneCorsi/log
	 * @param  $FiltroData: Eventuale filtro sulla data dell'evento
	 * @param  $FiltroUtente: Eventuale filtro sull'Utente 
	 * @param  $FiltroOperazione: Eventuale filtro sul tipo di operazione 
	 */

	function LeggiLog($Log,$FiltroData=NULL,$FiltroUtente=NULL,$FiltroOperazione=NULL,$FiltroStato=NULL,$Cartella="GestioneCorsi/log"){
		$FileName=get_home_path()."wp-content/".$Cartella."/".$Log.".log";
		if ($fp=fopen($FileName,"r")){
			$Archivio=array();
			$Buffer=fread($fp,filesize($FileName));
			$Righe=explode("¶",$Buffer);
//			echo $FiltroUtente." ".$FiltroOperazione." ".$FiltroStato;die();
			foreach($Righe as $Riga){
				if(substr($Riga,0,2)=="\r\n"){
					$Riga= substr($Riga, 2, strlen($Riga)-2);
				}
				if (strlen($Riga)>0){
					$BufferRiga=explode("|",$Riga);
//					echo "|".trim($BufferRiga[2])."|".substr($BufferRiga[0],0,10)."|".$BufferRiga[0]."|".$buf;
					switch ($Log){
						case "Utenti":
							$Dati=array("Data"        => trim($BufferRiga[0]),
										"Operazione"  => trim($BufferRiga[1]),
										"IdUtente"    => trim($BufferRiga[2]),
										"Utente"      => trim($BufferRiga[3]),
										"Stato"       => trim($BufferRiga[4]),
										"Provenienza" => trim($BufferRiga[5]),
										"Dati"        => trim($BufferRiga[6]));
							break;
						case "Iscrizioni":
							$Dati=array("Data"        => trim($BufferRiga[0]),
														"Operazione"		=> trim($BufferRiga[1]),
													//	"IdUtente"    => trim($BufferRiga[2]),
														"Utente"			=> trim($BufferRiga[3]),
														"IDCorso"			=> trim($BufferRiga[4]),
														"IDPrenotazione"    => trim($BufferRiga[5]),	
														"NPosti"			=> trim($BufferRiga[6]),	
														"Stato"				=> trim($BufferRiga[7]),
														"Provenienza"		=> trim($BufferRiga[8]),
														"Dati"				=> trim($BufferRiga[9]));
							break;							
					}	
					$Filtrato=TRUE;
					if(isset($FiltroData) And substr($Dati['Data'],0,10)!=$FiltroData){
						$Filtrato=FALSE;
					}
					if(isset($FiltroUtente) And trim($Dati['Utente'])!=$FiltroUtente){
						$Filtrato=FALSE;
					}
					if(isset($FiltroOperazione) And trim($Dati['Operazione'])!=$FiltroOperazione){
						$Filtrato=FALSE;
					}
					if(isset($FiltroStato) And trim($Dati['Stato'])!=$FiltroStato){
						$Filtrato=FALSE;
					}
					if ($Filtrato){
						$Archivio[]=$Dati;
					}	
				}
			}
			return $Archivio;
		}else{
			return FALSE;
		}
	}	
	
	public function getListaLogs($Formato="Array",$Cartella="GestioneCorsi/log",$Id="ListaLogs",$Name="ListaLogs"){
		$Cartella=get_home_path()."wp-content/".$Cartella;
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Cartella));
		$Files=array();
		$Select='<select id="'.$Id.'" name="'.$Name.'">';
		foreach ($iterator as $key=>$value) {
			if (is_file(realpath($key)) And substr($key,-3)=="log"){
				$NomeLog=substr(basename($key),0,-4);
				switch ($Formato){
					case "Array":
						$Files[]=$NomeLog;
						break;
					case "Select":
						$Select.='<option value="'.$NomeLog.'">'.$NomeLog.'</option>';
						break;
				}
			}
		}
		switch ($Formato){
			case "Array":
				return $Files;
				break;
			case "Select":
				return $Select.'</select>';
				break;
		}
	return FALSE;
	}
	public function formattaLog($Formato="Tabella",$Log){
		$Cartella=get_home_path()."wp-content/".$Cartella;
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Cartella));
		$Files=array();
		$Select='<select id="'.$Id.'" name="'.$Name.'">';
		foreach ($iterator as $key=>$value) {
			if (is_file(realpath($key)) And substr($key,-3)=="log"){
				$NomeLog=substr(basename($key),0,-4);
				switch ($Formato){
					case "Array":
						$Files[]=$NomeLog;
						break;
					case "Select":
						$Select.='<option value="'.$NomeLog.'">'.$NomeLog.'</option>';
						break;
				}
			}
		}
		switch ($Formato){
			case "Array":
				return $Files;
				break;
			case "Select":
				return $Select.'</select>';
				break;
		}
	return FALSE;
	}
	
	/*
	 * Funzione privata che crea l'elenco dei valori univoci presenti nel Log assato alla funzione
	 * 
	 * @param $Valori  : array che contiene i dati del log di cui estrarre i valori univci di uno specifico campo
	 * @param $Colonna : nome della colonna di cui creare la lista di valori univoci
	 * @param $Id      : il parametro ID dell'elemento HTML da creare, lasciare vuoto se si vuole estrarre un Array di valori univoci
	 * @param $Name    : il parametro Name dell'elemento HTML da creare, lasciare vuoto se si vuole estrarre un Array di valori univoci
	 * @param Formato  : formato dei risultato; valori gestiti: Select - Array
	 * @param $Azione  : link da associare all'evento onChange dell'elento Select che viene eseguito ogni volta che si seleziona un elemento della lista     
	 * 
	 */
	
	private function getElencoElementi($Valori,$Colonna,$Id="",$Name="",$Formato="Select",$Azione="",$Parametri){
		$Lista=array();
		foreach($Valori as $Valore){
			$Lista[]=$Valore[$Colonna];
		}
		$Lista=array_unique($Lista);
		switch ($Formato){
			case "Select":
				if($Id){
					$Id=" id=\"$Id\" ";
				}
				if ($Name){
					$Name=" name=\"$Name\" ";
				}
				$Risultato="<select $Id $Name onchange=\"document.location.href=this.options[this.selectedIndex].value;\">"
				. "<option value=\"\">----------</option>\n";
				foreach($Lista as $Elemento){
					switch ($Colonna){
						case "Utente":
							$Parametri['Utente']=$Elemento;
							break;
						case "Operazione":
							$Parametri['Operazione']=$Elemento;							
							break;
						case "Stato":
							$Parametri['Stato']=$Elemento;
							break;
					}
					$Risultato.="<option value=\"$Azione&c=Utenti".$this->serializzaParametri($Parametri)."\">".$Elemento."</option>";
				}
				$Risultato.="</select>";
				return $Risultato;
				break;
			case "Array":
				return $Lista;
				break;
			}
	}
	
	private function serializzaParametri($Parametri){
		$Serializzazione="";
		foreach($Parametri as $Key => $Valore){
			if ($Valore){
				$Serializzazione.="&".$Key."=".$Valore;
			}else{
				$Serializzazione.="&".$Key."=";
			}
		}
		return $Serializzazione;
	}
	
	/*
	 * Metodo che visualizza la tabella del log Iscrizioni
	 */

	public function Iscrizioni($Utente=""){
		$Parametri=array();
		if ($F=filter_input(INPUT_GET,"Operazione")){
			$Parametri["Operazione"]=str_replace("+"," ",$F);
		}else
			$Parametri["Operazione"]=NULL;
		if ($F=filter_input(INPUT_GET,"Utente")){
			$Parametri["Utente"]=str_replace("+"," ",$F);
		}else
			$Parametri["Utente"]=NULL;	
		if ($F=filter_input(INPUT_GET,"Stato")){
			$Parametri["Stato"]=str_replace("+"," ",$F);
		}else
			$Parametri["Stato"]=NULL;
		$Righe=$this->LeggiLog("Iscrizioni",NULL,$Parametri["Utente"],$Parametri["Operazione"],$Parametri["Stato"]);
		echo $this->getElencoElementi($Righe,"Utente","SelezionaUtente","SelezionaUtente","Select","?page=logs",$Parametri);
		echo $this->getElencoElementi($Righe,"Operazione","SelezionaOperazione","SelezionaOperazione","Select","?page=logs",$Parametri);
		echo $this->getElencoElementi($Righe,"Stato","SelezionaStato","SelezionaStato","Select","?page=logs",$Parametri);
?>
		<table class="form-table" id="TabellaDatiLog">
			<tr>
				<th>Data - ora</th>
				<th>Operazione</th>
				<th>Utente</th>
				<th>Corso</th>
				<th>Prenotazione</th>
				<th>Posti</th>
				<th>Stato</th>
				<th>Provenienza</th>
				<th style="width:40%;">Dati</th>
			</tr>		
<?php
				$Righe=array_reverse($Righe);
				foreach ( $Righe as $Riga ) {
					$Dati=unserialize($Riga[Dati]);
					$ValoreDati="";
					foreach ($Dati as $key => $Value){
						$ValoreDati.="<strong>".$key."</strong>: ".$Value."<br />";
					}
					$Prenotazione=new EM_Bookings();
					$Prenotazione->event_id=$Riga[IDCorso];
					$DatiEvento=$Prenotazione->get_event();
					switch (trim(strtolower($Riga[Stato]))){
						case "approvato":
							$Semaforo="SemaforoVerde";
							break;
						case "non approvato":
						case "respinto":
						case "cancellato":
						case "annullata utente":
						case "operazione non consentita":
							$Semaforo="SemaforoRosso";
							break;
						case "in attesa":
							$Semaforo="SemaforoGiallo";
							break;							
					}
					
					echo  "<tr>"
					    . "<td>".date("d/m/y", strtotime($Riga[Data]))."</td>"
						. "<td>$Riga[Operazione]</td>"
						. "<td>$Riga[Utente]</td>"
						. "<td>$DatiEvento->event_name</td>"
						. "<td>$Riga[IDPrenotazione]</td>"
						. "<td>$Riga[NPosti]</td>"
						. "<td class=\"$Semaforo\">$Riga[Stato]</td>"
						. "<td>$Riga[Provenienza]</td>"
						. "<td>$ValoreDati</td>"
						. "</tr>";
				}
?>

		</table>		
<?php		
	}

	/*
	 * Metodo che visualizza la tabella del log Utenti
	 */
	
	public function Utenti($Utente=""){
		$Parametri=array();
		if ($F=filter_input(INPUT_GET,"Operazione")){
			$Parametri["Operazione"]=str_replace("+"," ",$F);
		}else
			$Parametri["Operazione"]=NULL;
		if ($F=filter_input(INPUT_GET,"Utente")){
			$Parametri["Utente"]=str_replace("+"," ",$F);
		}else
			$Parametri["Utente"]=NULL;	
		if ($F=filter_input(INPUT_GET,"Stato")){
			$Parametri["Stato"]=str_replace("+"," ",$F);
		}else
			$Parametri["Stato"]=NULL;
		$Righe=$this->LeggiLog("Utenti",NULL,$Parametri["Utente"],$Parametri["Operazione"],$Parametri["Stato"]);
		echo $this->getElencoElementi($Righe,"Utente","SelezionaUtente","SelezionaUtente","Select","?page=logs",$Parametri);
		echo $this->getElencoElementi($Righe,"Operazione","SelezionaOperazione","SelezionaOperazione","Select","?page=logs",$Parametri);
		echo $this->getElencoElementi($Righe,"Stato","SelezionaStato","SelezionaStato","Select","?page=logs",$Parametri);
?>
		<table class="form-table" id="TabellaDatiLog">

			<tr>
				<th>Data - ora</th>
				<th>Operazione</th>
				<th>Utente</th>
				<th>Stato</th>
				<th>Provenienza</th>
				<th style="width:40%;">Dati</th>
			</tr>		
<?php
				$Righe=array_reverse($Righe);
				foreach ( $Righe as $Riga ) {
					$Dati=unserialize($Riga[Dati]);
					$ValoreDati="";
					foreach ($Dati as $key => $Value){
						$ValoreDati.="<strong>".$key."</strong>: ".$Value."<br />";
					}
					if(strpos(strtolower($Riga[Stato])," non ")===FALSE){
						$Semaforo="SemaforoVerde";
					}else{
						$Semaforo="SemaforoRosso";
					}
					echo  "<tr>"
					    . "<td>".date("d/m/y", strtotime($Riga[Data]))."</td>"
						. "<td>$Riga[Operazione]</td>"
						. "<td>$Riga[Utente]</td>"
						. "<td class=\"$Semaforo\">$Riga[Stato]</td>"
						. "<td>$Riga[Provenienza]</td>"
						. "<td>$ValoreDati</td>"
						. "</tr>";
				}
?>

		</table>		
<?php		
	}
	
	/*
	 * Metodo che visualizza la struttura delle cartelle dei Log uno per ogni file presente nella cartella dei Log
	 */
	
	public function VisualizzaLog(){
		$Logs=$this->getListaLogs();
?>
		<div class="wrap">
			<h2>Logs</h2>
			<div id="tabs">
			<ul>
<?php
		$AttCart=0;
		$ParCart= filter_input(INPUT_GET,"c");
		$Utente=  filter_input(INPUT_GET,"Utente");
		$SetCart=0;
//		$x=new Gestione_Corsi_Ajax("","");
//		$x->ScriviLogStatoCorsoPublic();
		foreach($Logs as $Log){
			echo "<li><a href=\"#tabs-$Log\">". str_replace( "_", " ", $Log )."</a></li>";
		}
		echo "</ul>";
		foreach($Logs as $Log){
			echo "<div id=\"tabs-$Log\" class=\"CartelleLog\">";		
				if (method_exists($this,$Log)){
					$this->$Log($Utente);
					if($ParCart==$Log){
						$SetCart=$AttCart;
					}
				}
			$AttCart++;
			echo "</div>";
		}
		echo "<input type=\"hidden\" id=\"CartellaAttiva\" name=\"CartellaAttiva\" value=\"$SetCart\">";
		echo "</div>";		
	}
}