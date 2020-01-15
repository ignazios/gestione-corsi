<?php

/*
 * Classe per la gestione degli shortdoce del plugin.
 *
 * Estrai dati Corso
 *
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/public/incudes
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */

class Shortcode_Corso {
	
	/*
	 * Costruttore standard 
	 * @param nessuno
	 */	
	public function __construct() {
		add_shortcode( 'incontri',			array($this,'DisplayIncontri') );
		add_shortcode( 'codice_evento',		array($this,'DisplayCodceEvento') );
		add_shortcode( 'formatore',			array($this,'DisplayFormatore') );
		add_shortcode( 'tutor',				array($this,'DisplayTutor') );
		add_shortcode( 'aula',				array($this,'DisplayAula') );
	}
	
	public function DisplayIncontri($Parametri){
		extract(shortcode_atts(array(
		'idev' => '0',), $Parametri));
		if(isset($Parametri['idev']) And $Parametri['idev']!=0){
			$ID=$Parametri['idev'];
		}else{
			$ID=get_the_ID();
		}		
		if( is_numeric($ID) And $ID>0){
			$SeriaLezioni=get_post_meta($ID, "_lezioniCorso",TRUE);
			$Lezioni= unserialize( $SeriaLezioni);
			if(count($Lezioni)==0){
				return "non ancora definite";
			}
			$Lista="<ul style='margin-bottom: 10px;'>";
			foreach($Lezioni as $Lezione){
				$Lista.="<li>".$Lezione[0]." dalle: ".$Lezione[1]." alle: ".$Lezione[2];
				if($Lezione[3] And $Lezione[3]!="00:00"){
					$Lista.=" e dalle: ".$Lezione[3]." alle: ".$Lezione[4];
				}
				$Lista.="</li>";
			}
			$Lista.="</ul>";
			return $Lista;	
		}
		return "";
	}
	public function DisplayCodceEvento(){
		$ID=get_the_ID();
		if( is_numeric($ID) And $ID>0){
			$Codice_corso=get_post_meta($ID, "_codiceCorso",TRUE);
			return $Codice_corso;
		}
		return "Non definito";
	}
	public function DisplayFormatore(){
		$ID=get_the_ID();
		if( is_numeric($ID) And $ID>0){
			$Formatori=get_post_meta($ID, "_docenteCorso");
			$Lista="<ul>";
			foreach($Formatori as $T){
				$utente=get_userdata( $T );
				if(isset($utente) AND $T>0){
					$Lista.="<li>".$utente->first_name." ".$utente->last_name."</li>";
				}
			}
			$Lista.="</ul>";
			return $Lista;
		}
		return "Non definito";
	}
	public function DisplayTutor(){
		$ID=get_the_ID();
		if( is_numeric($ID) And $ID>0){
			$Tutor=get_post_meta($ID, "_tutorCorso");
			$Lista="<ul>";
			foreach($Tutor as $T){
				$utente=get_userdata( $T );
				if(isset($utente) AND $T>0){
					$Lista.="<li>".$utente->first_name." ".$utente->last_name."</li>";
				}
			}
			$Lista.="</ul>";
			return $Lista;
		}
		return "Non definito";
	}
	public function DisplayAula(){
		$ID=get_the_ID();
		if( is_numeric($ID) And $ID>0){
			$Aula=get_post_meta($ID, "_aulaCorso",TRUE);
			return $Aula;
		}
		return "Non definita";
	}
}