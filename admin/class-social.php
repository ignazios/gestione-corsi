<?php

/*
 * Classe per la gestione dell'interfaccia con il sito Social realizzato in BuddyPress.
 *
 *
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/admin
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */

class Piattaforma_Social {

	protected $TablePrefix;
	protected $UrlSocial;
	protected $Is_social;
	
	public function __construct() {
		$this->TablePrefix=get_option('formazione_prefissotabsocial');
		$this->UrlSocial=get_option('formazione_sitosocial');
		$this->Is_social=($this->TablePrefix!="" And $this->UrlSocial!="");
//		echo $this->Is_social;wp_die();
	}
	
	public function IsSocial(){
		return $this->Is_social;
	}
	/**
	 *  Metodo che verifica se il Gruppo Social esiste.
	 *  La verifica viene fatta con lo slug del Gruppo che coincide con lo slug del corso
	 * 
	 * @param type $Corso oggetto corso da testare
	 */
	public function group_exists( $Slug) {
		global $wpdb;
		$NGroup = $wpdb->get_var( "SELECT COUNT(*) FROM ".$this->TablePrefix."_bp_groups where slug='$Slug'" );
		return ($NGroup>0?TRUE:FALSE);
	}
	/**
	 *  Metodo che permette di estrarre dal Gruppo di cui viene passato l'ID o lo Slug gli utenti assegnati alla tipologia
	 * @param type $Search ID numerico o Slug se stringa
	 * @param type $Cosa tipologia di soggetto da estrarre
	 * @return array di interi che rappresenta gli id degli utenti del Gruppo per tipologia
	 */
	public function get_Soggetti_Gruppo($Search,$Cosa=2){
		global $wpdb;$wpdb->show_errors();
		if( is_string( $Search )){
			$id=$this->get_group_id($Search);
		}else{
			$id=$Search;
		}
		$Admin=0;$Moderatori=0;
		switch ($Cosa){
			case 0: $Admin=1;break;
			case 1: $Moderatori=1;break;
		}
		$Soggetti = $wpdb->get_results("SELECT user_id FROM ".$this->TablePrefix."_bp_groups_members where group_id=$id And is_admin=$Admin And is_mod=$Moderatori" );
		$SoggettiRitorno=array();
		foreach($Soggetti as $Soggetto){
			$SoggettiRitorno[]=$Soggetto->user_id;
		}
		Return $SoggettiRitorno;
	}
	/**
	 *  Metodo che permette di sapere l'ID del Gruppo attraverso lo Slug del Gruppo che corrisponde allo Slug del Corso
	 * @param type $Slug	-del corso da ricercare
	 * @return ID del Gruppo
	 */
	public function get_group_id($Slug) {
		global $wpdb;
		$NGroup = $wpdb->get_results("SELECT id FROM ".$this->TablePrefix."_bp_groups where slug='$Slug'" );
		return ($NGroup[0]->id);
	}	
	
	public function add_Soggetto_Group($Soggetto,$TipoSoggetto=2,$Gruppo){
		global $wpdb;
		$Admin=0;$Mod=0;
		$Titolo="Prof./ssa";
		switch($TipoSoggetto){
			case 0:$Admin=1;$Titolo="Amministratori Gruppo";break;
			case 1:$Mod=1;$Titolo="Moderatori Gruppo";break;
		}
		$N_SogGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_members",
											array(
												"group_id"	=> $Gruppo,
												"user_id"	=> $Soggetto,
												"user_title"=> $Titolo,
												"is_admin"	=> $Admin,
												"is_mod"	=> $Mod,
												"date_modified"=>date("Y-m-d H:i:s"),
												"is_confirmed"=>	1),
											array("%d","%d","%d","%s","%s","%d"));	
		return $N_SogGroupIns;
		}	
	public function del_Soggetto_Group($Soggetto,$Gruppo){
		global $wpdb;
		$N_SogGroupDel=$wpdb->delete( $this->TablePrefix."_bp_groups_members",
											array(
												"group_id"	=> $Gruppo,
												"user_id"	=> $Soggetto),
											array("%d","%d"));	
//		echo $wpdb->last_query;
		return $N_SogGroupDel;
		}		
	
	/**
	 *  Metodo per la creazione del Gruppo Social
	 * @param type $Nome				-Titolo del Gruppo che coincide con il Titolo del Corso
	 * @param type $Slug				-Slug del Gruppo che coincide con lo Slug del Corso
	 * @param type $Descrizione			-Descrizione del Gruppo 
	 * @param type $Soggetti			-Array bidimensionale che contiene i soggetti del Gruppo Amministratori, Moderatori e Membri
	 * @param type $Stato				-Stato del Grupo public/private/hidden
	 * @param type $ModInvitoNewUser	-Chi può invitare altri utenti nel Gruppo members/mods/admins
	 * @return array che indica il numero di elementi creati per tipo [MK_Gruppi][MK_Meta][MK_Admin][MK_Modera][Corsisti]
	 */
	public function Create_Group($Nome,$Slug,$Descrizione,$Soggetti=Array(),$Stato="private",$ModInvitoNewUser="mods"){
		global $wpdb;
		$Risultati=array("MK_Gruppi" =>0,"MK_Meta"=>0,"MK_Admin"=>0,"MK_Modera"=>0,"MK_Membri"=>0);
		if(count($Soggetti['Amministratori'])==0){
			return $Risultati;
		}
/**
 * Creo il gruppo nella tabella _bp_groups
 * punto di partenza del gruppo
 */
		$N_GroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups",
									array(
										"creator_id" => get_current_user_id(),
										"name"		 => $Nome,
										"slug"		 => $Slug,
										"description"=>$Descrizione,
										"status"	 =>$Stato,
										"date_created"=>date("Y-m-d H:i:s")),
									array("%d","%s","%s","%s","%s","%s"));	
		$Risultati['MK_Gruppi']=$N_GroupIns;
/**
 *  Memorizzo l'ID del gruppo appena creato
 */
		$IdLast=$wpdb->insert_id;
//		var_dump($IdLast);
/**
 * Creo il metadato del Gruppo invite_status che imposta il livello di chi può invitare altri utenti 
 * Impostazione fissa nel nostro caso possono invitare nuovi membri i Moderatori e gli Amministratori
 */
		$N_MetaGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_groupmeta",
										array(
											"group_id"	=> $IdLast,
											"meta_key"	=> "invite_status",
											"meta_value"=> $ModInvitoNewUser),
										array("%d","%s","%s"));	
		$Risultati['MK_Meta']=$N_MetaGroupIns;
/**
 * Creazione Amministratori del Gruppo
 * Impostazione fissa TUTTI gli Amministratori del Sito
 */
		foreach($Soggetti['Amministratori'] as $Amministratore){
			$N_MetaGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_members",
											array(
												"group_id"	=> $IdLast,
												"user_id"	=> $Amministratore,
												"is_admin"	=> 1,
												"user_title"=>"Amministratori Gruppo",
												"date_modified"=>date("Y-m-d H:i:s"),
												"is_confirmed"=>	1),
											array("%d","%d","%d","%s","%s","%d"));	
			$Risultati["MK_Admin"]++;
		}
/**
 * Creazione Moderatori del Gruppo
 * Impostazione fissa TUTTI i Fromatori e TUTTI i Tutor
 */
		foreach($Soggetti['Moderatori'] as $Moderatore){
			$N_MetaGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_members",
											array(
												"group_id"	=> $IdLast,
												"user_id"	=> $Moderatore,
												"is_mod"=> 1,
												"user_title"=>"Moderatori Gruppo",
												"date_modified"=>date("Y-m-d H:i:s"),
												"is_confirmed"=>1),
											array("%d","%d","%d","%s","%s","%d"));	
			$Risultati["MK_Modera"]++;
		}
/**
 * Creazione Membri del Gruppo
 * Impostazione fissa TUTTI gli iscritti al corso
 */
		foreach($Soggetti['Corsisti'] as $Corsista){
			$N_MetaGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_members",
											array(
												"group_id"	=> $IdLast,
												"user_id"	=> $Corsista,
												"user_title"=>"Prof./ssa",
												"date_modified"=>date("Y-m-d H:i:s"),
												"is_confirmed"=>1),
											array("%d","%d","%s","%s","%d"));	
			$Risultati["MK_Membri"]++;
		}
/**
 * Creo il metadato del Gruppo total_member_count che imposta il numero di membri codificati all'interno del Gruppo 
 */
		$N_MetaGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_groupmeta",
										array(
											"group_id"	=> $IdLast,
											"meta_key"	=> "total_member_count",
											"meta_value"=> $Risultati["MK_Membri"]),
										array("%d","%s","%s"));	
		$Risultati['MK_Meta']+=$N_MetaGroupIns;
/**
 * Creo il metadato del Gruppo last_activity che imposta la data e l'ora dell'ultima modifica del Gruppo 
 */
		$N_MetaGroupIns=$wpdb->insert( $this->TablePrefix."_bp_groups_groupmeta",
										array(
											"group_id"	=> $IdLast,
											"meta_key"	=> "last_activity",
											"meta_value"=> date("Y-m-d H:i:s")),
										array("%d","%s","%s"));	
		$Risultati['MK_Meta']+=$N_MetaGroupIns;		
		return $Risultati;
	}
}