<?php

/**
 * File che implementa la classe che implementa le funzioni di base
 * 
 * @since      1.0.0
 * @package    Gestione_Corso
 * @subpackage Gestione_Corso/includes
 * 
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */
 class Funzioni {
	
	/*
	 * Costruttore standard 
	 */
	public function __construct() {
	}
	Static function DateAdd($Data,$IncGG){
		if(strpos($Data,"/")===false){
			$rsl = explode("-",$Data);
			$SerialData=mktime(0,0,0,$rsl[1], $rsl[2],$rsl[0]);
		}else{
			$rsl = explode("/",$Data);
			$SerialData=mktime(0,0,0,$rsl[2], $rsl[1],$rsl[2]);			
		}
		$secondi=$SerialData+($IncGG*86400);
		return date("Y-m-d",$secondi);
	}
			
	Static function get_CorsiPeriodoFormazione(){
		global $wpdb;
		$Date= get_option('PeriodoFormazione');
		$Periodi= unserialize( $Date );
		$Sql="SELECT event_id FROM ".EM_EVENTS_TABLE.
			 "  WHERE (`recurrence`!=1 OR `recurrence` IS NULL) AND "
				  . "( event_start_date >= '".FUNZIONI::FormatDataDB( $Periodi[0])."' AND event_end_date <= '".FUNZIONI::FormatDataDB(  $Periodi[1] )."') "
				  . " AND event_rsvp=1 AND (`event_status` >= 0 )	
				GROUP BY wforpzio_em_events.post_id ORDER BY event_id";
		return $wpdb->get_results($Sql);	
	}
	
	static function is_Installed_EM(){
		if(class_exists('EM_Event')){
			return TRUE;
		}else{
			return FALSE;
		}
	}
	
	static function is_Installed_ML(){
		if( function_exists('alo_em_init_method')){
			return TRUE;
		}else{
			return FALSE;
		}
	}
	
	static function PulisciStringa($Stringa,$Trim=false){
		$NewStrina="";
		for($i=0;$i<strlen($Stringa);$i++){
			if(ord($Stringa[$i])==194){
				$i++;
				if(ord($Stringa[$i])==160)
					$NewStrina.=" ";
			}else{
				$NewStrina.=$Stringa[$i];
			}
		}
		if($Trim){
			$NewStrina= trim($NewStrina);
		}
		return $NewStrina;
	}
	/**
	 *  La funzione confronta due date
	 * @param type $DataDB1
	 * @param type $DataDB2 default = oggi
	 * @return type integer <0 se $DataDB1 < $DataDB2
	 *						=0 se $DataDB1 = $DataDB2
	 *						>0 se $DataDB1 > $DataDB2
	 */
	static function SeDate($DataDB1,$DataDB2="oggi"){
		if(strpos($DataDB1,"/")>0){
			$DataDB1=FUNZIONI::FormatDataDB($DataDB1);
		}
		if($DataDB2=="oggi"){
			$DataDB2=date("Y-m-d");
		}
//		echo $DataDB1." ".$DataDB2."<br />";
		$Time1=strtotime($DataDB1);
		$Time2= strtotime($DataDB2);
//		echo "1".$Time1." 2".$Time2;
		return $Time1-$Time2;
	}
	static function differenzaOra_Minuti($Prima,$Seconda){
		$ATime=explode(":",$Seconda);
		$SecMin=(intval($ATime[0])*60)+intval($ATime[1]);
		$ATime=explode(":",$Prima);
		$PrimMin=(intval($ATime[0])*60)+intval($ATime[1]);
		return $SecMin-$PrimMin;
	}
	
	static function NomeUtente($IDUser){
		require_once( ABSPATH . WPINC . '/pluggable.php' );
		$user_info = get_userdata($IDUser);
		return ucwords(strtolower($user_info->last_name))." ".ucwords(strtolower($user_info->first_name));
	}
	static function EmailUtente($IDUser){
		require_once( ABSPATH . WPINC . '/pluggable.php' );
		$user_info = get_userdata($IDUser);
		return $user_info->user_email;
	}
	static function ListaCorsi($Stato="future",$Escludere="",$Output="li",$IDHtml="",$Name=""){
		$events = EM_Events::get( array('scope'=>$Stato, 'bookings'=>true) );
		if($Output=="Array"){
			return $events;
		}
		$Lista="";
		foreach($events as $Corso){
			if( is_numeric( $Escludere)){
				if(is_array( $Escludere)){
					if(in_array($Corso->event_id,$Escludere)){
						continue;
					}			
				}else{
					if($Corso->event_id==$Escludere){
						continue;
					}
				}				
			}
			$Elemento="<strong>".$Corso->event_name."</strong><br />";
			$Elemento.="Prenotati: ". $Corso->get_bookings()->get_booked_spaces()."/".$Corso->get_spaces();
			if( get_option('dbem_bookings_approval') == 1 ){
				$Elemento.= "| In attesa: ".$Corso->get_bookings()->get_pending_spaces();
			}
			switch($Output){
				case "li":
					$Lista.="<li id=".$Corso->event_id.">".$Elemento."</li>\n";
					break;
				case "radio":
					$Lista.="<input type=\"radio\" name=\"$Name\" value=\"$Corso->event_id\">$Elemento<br>";
					break;
			}
		}
		return $Lista;		
	}
	static function ListaOre($Name,$Id,$Default,$Class=""){
		$LOre=array('00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30',
					'10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30',
					'20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30');
		$ListaOre='<select name="'.$Name.'" id="'.$Id.'" '.($Class!=""?'class="'.$Class.'"':'').'>';
		foreach($LOre as $Value){
			if($Value==$Default){
				$Selezionato=" selected ";
			} else {
				$Selezionato="";
			}
			$ListaOre.="<option value=\"$Value\" $Selezionato>".$Value."</option>\n";
		}
		$ListaOre.="</select>";
		return $ListaOre;
	}
	static function DataFormat($Data,$Formato="gg/mm/aaaa"){
		$FormatEle=explode("/",$Formato);
		$PartiData=explode("/",$Data);
		if($FormatEle[0]=="g"){
			$giorno=intval($PartiData[0]);
		}else{
			$giorno=$PartiData[0];
		}
		if($FormatEle[1]=="m"){
			$mese=intval($PartiData[1]);
		}else{
			$mese=$PartiData[1];
		}
		if($FormatEle[2]=="aa"){
			$anno= substr($PartiData[2],-2);
		}else{
			$anno=$PartiData[2];
		}
		return $giorno."/".$mese."/".$anno;
	}
	static function FormatDataDB($Data,$incGG=0,$incMM=0,$incAA=0){
		$d=explode("/",$Data);
		if (strlen($d[1])<2)
			$Mese="0".$d[1];
		else
			$Mese=$d[1];
		if (strlen($d[0])<2)
			$Giorno="0".$d[0];
		else
			$Giorno=$d[0];
		$Data=$d[2]."-".$Mese."-".$Giorno;
		if ($incAA>0)
			$Data=$d[2]+$incAA."-".$d[1]."-".$d[0];
		if ($incGG>0)
			$Data=date('Y-m-d', strtotime($Data. ' + '.$incGG.' days'));
		if ($incMM>0)
			$Data=date('Y-m-d', strtotime($Data. ' + '.$incMM.' months'));
		return $Data;
	}
	static function FormatDataOraItaliano($TimeStamp_DataOra){
		$DataOra=explode(" ",$TimeStamp_DataOra);
		$d=explode("-",$DataOra[0]);
		if (strlen($d[1])<2)
			$Mese="0".$d[1];
		else
			$Mese=$d[1];
		if (strlen($d[2])<2)
			$Giorno="0".$d[2];
		else
			$Giorno=$d[2];
		$Data=$Giorno."/".$Mese."/".$d[0];
		$o=explode(":",$DataOra[1]);
		if(count($o)==3){
			$Ora=$DataOra[1];
		}else{
			$Ora="00:00:00";
		}
		return $Data." ".$Ora;
	}	
	static function FormatDataItaliano($Data,$incGG=0,$incMM=0,$incAA=0){
		$d=explode("-",$Data);
		if (strlen($d[1])<2)
			$Mese="0".$d[1];
		else
			$Mese=$d[1];
		if (strlen($d[2])<2)
			$Giorno="0".$d[2];
		else
			$Giorno=$d[2];
		$Data=$Giorno."/".$Mese."/".$d[0];
		if ($incAA>0)
			$Data=$d[2]."/".$d[1]."/".$d[0]+$incAA;
		if ($incGG>0)
			$Data=date('d/m/Y', strtotime($Data. ' + '.$incGG.' days'));
		if ($incMM>0)
			$Data=date('d/m/Y', strtotime($Data. ' + '.$incMM.' months'));
		return $Data;
	}	
	static function FormatDataSeriale($Data,$incGG=0,$incMM=0,$incAA=0){
		return str_replace("-", "",self::FormatDataDB($Data,$incGG,$incMM,$incAA));
	}
	static function is_MailingList($NomeML){
		if ( function_exists("alo_em_get_mailinglists")){
			$MLs=alo_em_get_mailinglists('hidden,admin,public');
			if( is_array( $MLs )){
				foreach($MLs as $ML){
					if(is_array($ML['name']) And in_array( $NomeML, $ML['name'])){
						return TRUE;
					}elseif($ML['name']==$NomeML){
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}
	static function get_MailingListID($NomeML){
		if ( function_exists("alo_em_get_mailinglists")){
			$MLs=alo_em_get_mailinglists('hidden,admin,public');
//			var_dump($NomeML);wp_die();
			foreach($MLs as $Key => $ML){
				if(is_array($ML['name']) And in_array( $NomeML, $ML['name'])){
					return $Key;
				}elseif($ML['name']==$NomeML){
					return $Key;
				}
			}
		}
		return FALSE;
	}
	static function get_User_MailingList($ML,$Count=False){
		global $wpdb;
		$IDML=Funzioni::get_MailingListID($ML);
		$Sql =    "SELECT ID, name, email FROM {$wpdb->prefix}easymail_subscribers";
		$filter_list = '%|' . trim( $IDML ) . '|%';
		$Sql .= " WHERE lists LIKE '$filter_list' AND lang = 'it' ";
		$Sql .= ' ORDER BY name ASC';
//		echo $Sql;
		$Lista= $wpdb->get_results($Sql,ARRAY_N);
		if ($Count){
			return count($Lista);
		}else{
			return $Lista;
		}

	}
	
	static function build_sorter($key,$reverse) {
	    return function ($a, $b) use ($key,$reverse) {
	        $Ordine=strnatcmp($a[$key], $b[$key]);
	        if($reverse){
				if($Ordine<0){
					$Ordine=1;
				}else{
					$Ordine=-1;
				}
			}
			return $Ordine;
	    };
	}

	static function SortArray($records, $field, $reverse=false)
	{
		usort($records, self::build_sorter($field,$reverse));
		return $records;
	}
	/**
	 *  Funzione che ordina un array di record
	 * @param type $array Array multi campo da ordinare
	 * @param type $cols array con le colonne da ordinare, specificando il nome e il tipo di ordine
	 * @return type array l'array ordinato
	 * 
	 * es. MultiSort($Lezioni, array('datalezione'=>SORT_ASC));
	 */
	static function MultiSort($array, $cols) { 
	    $colarr = array(); 
	    foreach ($cols as $col => $order) { 
	        $colarr[$col] = array(); 
	        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); } 
	    } 
	    $eval = 'array_multisort('; 
	    foreach ($cols as $col => $order) { 
	        $eval .= '$colarr[\''.$col.'\'],'.$order.','; 
	    } 
	    $eval = substr($eval,0,-1).');'; 
	    eval($eval); 
	    $ret = array(); 
	    foreach ($colarr as $col => $arr) { 
	        foreach ($arr as $k => $v) { 
	            $k = substr($k,1); 
	            if (!isset($ret[$k])) $ret[$k] = $array[$k]; 
	            $ret[$k][$col] = $array[$k][$col]; 
	        } 
	    } 
	    return $ret; 
	} 
	
	static function email_exist($email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
			return false;
		}elseif(!checkdnsrr(array_pop(explode('@',$email)),'MX')){
			  return false;
		  }else{
			  return true;
		  }
	}
	
	static function die_secur($Msg="non hai i diritti per accedere a questa risorsa o eseguire questa funzione",$Redirect="page=corsi"){
		echo '<div id="message" class="updated"><h2>Plugin Gestione Corsi</h2><p><strong>Errore di sicurezza</strong><br />'.$Msg.'<br /></p></div>';
		if ($Redirect!=""){
			echo '<meta http-equiv="refresh" content="2;url=admin.php?'.$Redirect.'"/>';
		}else{
			die();
		}
	}
	static function CalcolaTempo($LezioneCorso){
//		var_dump($LezioneCorso);
		if(isset($LezioneCorso[1]) And isset($LezioneCorso[2])){
			$OrePT=date_diff(date_create($LezioneCorso[2]),date_create($LezioneCorso[1]));  
		}else{
			$OrePT="";
		}	
		if(isset($LezioneCorso[3]) And isset($LezioneCorso[4])){
			$OreST=date_diff(date_create($LezioneCorso[4]),date_create($LezioneCorso[3]));  
		}else{
			$OreST="";
		}
		$MinutiPT=$MinutiST=0;
		if(is_object($OrePT)){
			$Ore=intval($OrePT->format("%H"));
			$Minuti=intval($OrePT->format("%i"));
			$MinutiPT=$Ore*60+$Minuti;			
		}
		if(is_object($OreST)){
			$Ore=intval($OreST->format("%H"));
			$Minuti=intval($OreST->format("%i"));
			$MinutiST=$Ore*60+$Minuti;
		}
		$MinutiTot=$MinutiPT+$MinutiST;
		return $MinutiTot;
	}
	
	static function daMin_aOreMin($Minuti,$Formato="Array"){
		$Ore=intval($Minuti/60);
		$Minuti=abs($Minuti%60);
		if($Minuti<10){
			$Minuti="0".$Minuti;
		}
		switch($Formato){
			case "Stringa":
				return $Ore.":".$Minuti;			
				break;
			default:
				return array("Ore"	=>$Ore,
						  	 "Min"	=>$Minuti);
				break;
		}
	}
	static function daOreMin_aMin($OreMinuti){
		$Elementi=explode(":",$OreMinuti);
		$Ore=intval($Elementi[0],10 );
		$Minuti=intval($Elementi[1],10);
		return ($Ore*60)+$Minuti;
	}
	static function CorsoConsolidato($Corso){
		$Attestabile=0;
		$Lezioni=$Corso->get_Lezioni();
		if(!is_array($Lezioni) Or !is_array($Corso->get_AttivitaNP()))
			return FALSE;
		$ANP=0;
		foreach($Corso->get_AttivitaNP() as $AttivitaNP){
			if($AttivitaNP[2]=="Si"){
				$Lezioni[]=array($ANP."0/00/0000");
				$ANP++;
			}
		}
		foreach($Lezioni as $Lezione){
			if($Corso->is_LezioneConsolidata( $Lezione[0])){
				$Attestabile+=1;
			}
		}
		return $Attestabile==count($Lezioni);
	}
 }