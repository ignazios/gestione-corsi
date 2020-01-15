<?php

/*
 * Classe per l'a gestione degli utenti'interfaccia dei singoli corsi.
 *
 * Estrai dati Corso
 *
 * @since      1.0.0
 * @package    Gestione_Corsi
 * @subpackage Gestione_Corsi/incudes
 * @author     Ignazio Scimone <ignazios@gmail.com>
 */

class Gestione_Corso {

	protected $ID_corso;
	protected $ID_post;
	protected $Nome_Corso;
	protected $Slug_Corso;
	protected $DataInizio;
	protected $DataFine;
	protected $Corso;
	protected $Docenti;
	protected $Tutor;
	protected $CodiceCorso;
	protected $Lezioni;
	protected $Permalink;
	protected $Owner;
	protected $TitoloCorso;
//	protected $OreOnLine;
//	protected $OreOnLineIndividualizzate;
	protected $OreLezione;
	protected $AttivitaNP;


	public function get_Categoria(){
		global $wpdb;
		$Sql="SELECT wforpzio_terms.name "
		   . "FROM wforpzio_term_relationships INNER JOIN wforpzio_term_taxonomy ON wforpzio_term_taxonomy.term_taxonomy_id=wforpzio_term_relationships.term_taxonomy_id "
		   . "INNER JOIN wforpzio_terms ON wforpzio_term_taxonomy.term_id=wforpzio_terms.term_id "
		   . "WHERE wforpzio_term_relationships.object_id=".$this->ID_post." AND wforpzio_term_taxonomy.taxonomy='event-categories'";
		$Cates=$wpdb->get_results($Sql);
		$Categorie=array();
		foreach($Cates as $Cate){
			$Categorie[]=$Cate->name;
		}
		return(implode(", ",$Categorie));
	}
	public function getAbstract(){
		 return get_the_excerpt($this->ID_post);
	}
/*	public function get_OreOnLineIndividualizzate(){
		return $this->OreOnLineIndividualizzate;
	}
*/
	public function get_CodicePost(){
		return $this->ID_post;
	}
	
/*	public function get_OreOnLine(){
		return get_post_meta($this->ID_post, "_oreOnLine",TRUE);
	}
*/
	public function get_OreLezioni(){
		return get_post_meta($this->ID_post, "_oreLezioni",TRUE);
	}
	public function get_TitoloCorso(){
		return $this->Corso->event_name;
	}

	public function get_SlugCorso(){
		return $this->Corso->event_slug;
	}
	
	public function get_IDCorso(){
		return $this->ID_corso;
	}
	public function get_Proprietario(){
		return $this->Owner;
	}

	public function get_NumLezioni($Tutte="S"){
		$Date=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));
		if( !is_array( $Date ))
			return 0;
//		$Online=(get_post_meta($this->ID_post, "_oreOnLineIndividualizzate",TRUE)=="Si"?1:0);
		$nANP=0;
		if($this->AttivitaNP!==FALSE And $Tutte=="S"){
			foreach($this->AttivitaNP as $ANP){
				if($ANP[2]=="Si"){
					$nANP++;
				}
			}
		}
		return count($Date)+$nANP;
	}	
	public function get_NumLezioniDB(){
		global $wpdb;
		$Sql="SELECT count($wpdb->table_lezioni.DataLezione) as Num "
			."FROM $wpdb->table_lezioni "
			."WHERE $wpdb->table_lezioni.IDCorso= %d ";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso);
		$NumLezioniDB=$wpdb->get_results($SqlFiltrato, ARRAY_A);
		return $NumLezioniDB[0]['Num'];
	}
	public function get_NumCorsistiDB(){
		global $wpdb;
		$Sql="SELECT Count(*) FROM $wpdb->table_corsisti "
			. "WHERE $wpdb->table_corsisti.IDCorso=".$this->ID_corso;
		return $wpdb->get_var( $Sql);
	}
	
	public function get_CorsistiDB(){
		global $wpdb;
		$Sql="SELECT * FROM $wpdb->table_corsisti "
			. "WHERE $wpdb->table_corsisti.IDCorso=".$this->ID_corso;
		return $wpdb->get_results($Sql, ARRAY_A);
	}

	public function ver_IscrittiCorsisti(){
		global $wpdb;
		$Sql="SELECT IDUser FROM $wpdb->table_corsisti "
			. "WHERE $wpdb->table_corsisti.IDCorso=".$this->ID_corso;
		$Iscritti=array();
		foreach($this->Corsisti as $Key => $Corsisti){
			$Iscritti[]=$Key;
		}
		$CorsistiDB=$wpdb->get_results($Sql);
		$Corsisti=array();
		foreach($CorsistiDB as $C){
			$Corsisti[]=(int)$C->IDUser;
		}
		if(empty(array_diff($Iscritti,$Corsisti)) And empty(array_diff($Corsisti,$Iscritti))){
			return TRUE;
		}else{
			return FALSE;
		}
	}

	public function get_NumCorsistiPresenze(){
		global $wpdb;
		$Sql="SELECT Count(*) FROM $wpdb->table_presenze INNER JOIN $wpdb->table_corsisti "
			. "ON $wpdb->table_presenze.IDCorsista=$wpdb->table_corsisti.IDCorsista "
			. "WHERE $wpdb->table_corsisti.IDCorso=".$this->ID_corso;
//			echo $Sql;
		return $wpdb->get_var( $Sql);
	}
	public function get_NumIscritti(){
		return count($this->Corsisti);
	}
	public function get_NumIscrittiMailingList(){
		$NumUtentiAML=0;
		$IDconsensoNewsLetter= get_option('formazione_IDconsensoNewsLetter');
		foreach($this->Corsisti as $Corsista){
			$Utente=new Utenti($Corsista->data->ID);
			//if($Utente->has_consent($IDconsensoNewsLetter))
				$NumUtentiAML++;
		}
		return $NumUtentiAML;
	}
	public function get_Permalink(){
		return $this->Permalink;
	}
	
	public function get_AttivitaNP(){
		return $this->AttivitaNP;
	}	

	public function get_Lezioni($Formato="Array",$SepRiga="\n"){
		switch ($Formato){
			case "Array":
				return $this->Lezioni;
				break;
			case "lista":
				$Lista="";
				if($this->Lezioni){
					foreach ($this->Lezioni as $Lezione){
						if(isset($Lezione[3]) and isset($Lezione[4]) And $Lezione[3]!="00:00" And $Lezione[4]!="00:00"){
							$Pomeriggio=" e ".$Lezione[3]."-".$Lezione[4];
						}else{
							$Pomeriggio="";
						}
						$Lista.=$Lezione[0]." ".$Lezione[1]."-".$Lezione[2].$Pomeriggio.$SepRiga;
					}					
				}else{
					$Lista="";
				}
				return $Lista;
				break;
		}
	}
	
	public function get_Docenti(){
		return $this->Docenti;
	}
	public function get_Tutor(){
		return $this->Tutor;
	}
	public function get_CodiceCorso(){
		return $this->CodiceCorso;
	}
	public function get_NomeCorso(){
		return $this->Nome_Corso;
	}
	public function get_DataInizio(){
		return $this->DataInizio;
	}
	public function get_DataFine(){
		return $this->DataFine;
	}
	public function get_Corsisti(){
		return $this->Corsisti;
	}
	private function get_user_list(){
		$Iscrizioni= new EM_Bookings($this->Corso);
		$users = array();
		foreach( $Iscrizioni->get_bookings()->bookings as $EM_Booking ){
			$users[$EM_Booking->person->ID] = $EM_Booking->person;
		}
		return $users;	
	}
	public function get_DocentiTutorCorso($Output="ElencoTesto",$Email=False){
		global $wpdb;
		$Sql="SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE (meta_key=\"_docenteCorso\" Or meta_key=\"_tutorCorso\") AND post_id=$this->ID_post And meta_value>0";
//		echo $Sql."<br />";
		$DocentiTutor=$wpdb->get_results($Sql,ARRAY_N );
		switch($Output){
			case "ElencoTesto":
				$Docenti="";
				$Tutor="";
				break;
			case "Array":
				$DT=array();
				break;
		}
		foreach($DocentiTutor as $DocTut){
			if($Email){
				$Email=" ".FUNZIONI::EmailUtente($DocTut[1]);
			}else{
				$Email="";
			}
			switch($Output){
				case "ElencoTesto":
					if($DocTut[0]=="_docenteCorso"){		
						$Docenti.=FUNZIONI::NomeUtente($DocTut[1]).$Email.", ";
					}else{
						$Tutor.=FUNZIONI::NomeUtente($DocTut[1]).$Email.", ";
					}
					break;
				case "Array":
					$DT[]=$DocTut[1];
					break;
			}
		}
		switch($Output){
			case "ElencoTesto":
				$DocTut=array("Docenti" => strlen($Docenti)>0?substr($Docenti,0,-2):"",
							  "Tutor"   => strlen($Tutor)>0?substr($Tutor,0,-2):"");
				return $DocTut;
				break;
			case "Array":
				return $DT;
				break;
		}
	}
	/**
	 *  Metodo che testa per gli organizzatori (Scuole) se il corso è di loro proprietà
	 * @param type $Msg
	 * @return boolean 
	 *			TRUE se il l'utente corrente è il proprietario del corso
	 *			FALSE Se $Msg è vuoto ed il corso non è di porprietà dell'utente corrente
	 *			Visualizza il messaggio $MSG, termina l'esecuzione e torna alla pagina dei corsi se $Msg non è vuoto ed il corso non è di porprietà dell'utente corrente
	 */
	public function isMy($Msg=""){
		if (current_user_can('corsi_organizzatore') And get_current_user_id()==$this->Owner){
			return TRUE;
		}else{
			if ($Msg!=""){
				echo '<div id="message" class="updated"><p><strong>Errore di sicurezza</strong><br />'.$Msg.'<br /></p></div>
		      <meta http-equiv="refresh" content="2;url=admin.php?page=corsi"/>';
				die();
			}else{
				return FALSE;		
			}
		}
	}
	
	public function isMyCourse($Msg=""){
		if ($this->isMy()){
			return TRUE;
		}
		if (current_user_can('corsi_gest_ass') And in_array(get_current_user_id(),$this->get_DocentiTutorCorso($Output="Array"))){
			return TRUE;
		}else{
			if ($Msg!=""){
				echo '<div id="message" class="updated"><p><strong>Errore di sicurezza</strong><br />'.$Msg.'<br /></p></div>
		      <meta http-equiv="refresh" content="2;url=admin.php?page=corsi"/>';
				die();
			}else{
				return FALSE;		
			}
		}
	}
	public function is_Duplicate_CodiceCorso(){
		global $wpdb;
		$Sql="SELECT count(post_id) FROM $wpdb->postmeta INNER JOIN $wpdb->posts on $wpdb->postmeta.post_id=$wpdb->posts.ID WHERE meta_key=\"_codiceCorso\" And meta_value=\"$this->CodiceCorso\" And $wpdb->posts.post_status=\"publish\"";
		if($wpdb->get_var($Sql)>1){
			return TRUE;
		}else{
			return FALSE;
		}		
	}
	public function Titolo_Duplicate_CodiceCorso(){
		global $wpdb;
		$Sql="SELECT post_id FROM $wpdb->postmeta INNER JOIN $wpdb->posts on $wpdb->postmeta.post_id=$wpdb->posts.ID WHERE meta_key=\"_codiceCorso\" And meta_value=\"$this->CodiceCorso\" And $wpdb->posts.post_status=\"publish\"";
		$Eventi=$wpdb->get_results($Sql);
		if($wpdb->num_rows>1){
			$EventiDuplicati="";
			foreach($Eventi as $Evento){
				if($Evento->post_id!=$this->ID_post){
					$EventiDuplicati.=get_the_title($Evento->post_id)."\n";
				}
			}
			return $EventiDuplicati;
		}else{
			return FALSE;
		}		
	}
	
	protected $Corsisti=array();
	/**
	 * Costruttore standard 
	 * @param type $ID_corso: Codice del corso che si vuole gestire
	 * @param type $PreLoad: Parametro che indica di non eseguire $this->Permalink=get_permalink($this->ID_post); che GENERA ERRORI nella CREAZIONE dei documenti
	 */	
	public function __construct($ID_corso=0,$PreLoad=FALSE) {
		$this->ID_corso=$ID_corso;
		$Corso=em_get_event($this->ID_corso);
		$this->ID_post=$Corso->post_id;
		$this->Nome_Corso=$Corso->event_name;
		$this->Slug_Corso=$Corso->event_slug;
		$this->DataInizio=$Corso->event_start_date;
		$this->DataFine=$Corso->event_end_date;
		$this->Corso=$Corso;
		$this->Corsisti=$this->get_user_list();
		$this->Docenti=get_post_meta($this->ID_post,"_docenteCorso");
		$this->Tutor=get_post_meta($this->ID_post, "_tutorCorso");
		$this->CodiceCorso=get_post_meta($this->ID_post, "_codiceCorso",TRUE);
		$this->Lezioni=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));
		$this->OreOnLineIndividualizzate=(get_post_meta($this->ID_post, "_oreOnLineIndividualizzate",TRUE)=="Si"?TRUE:FALSE);
		$this->AttivitaNP=unserialize(get_post_meta($this->ID_post, "_attivita",TRUE));
		if(!$PreLoad)
			$this->Permalink=get_permalink($this->ID_post);
		$this->Owner=$Corso->event_owner;
	}
	public function CambiaStatoData($Data,$Stato){
		global $wpdb;
		$DataL=FUNZIONI::FormatDataDB($Data);
		if($wpdb->update( $wpdb->table_lezioni, 	
				array( 'Consolidata' => $Stato), 
				array( 'IDCorso' => $this->ID_corso,
					   'DataLezione' => $DataL), 
				array('%d'), 
				array( '%d','%s' ))===FALSE){
			return FALSE;
		}else{
			return TRUE;
		}
	}
	public function is_LezioneConsolidata($Data){
		global $wpdb;
		$Sql="SELECT ".$wpdb->table_lezioni.".Consolidata FROM ".$wpdb->table_lezioni." WHERE ".$wpdb->table_lezioni.".IDCorso=%d And ".$wpdb->table_lezioni.".DataLezione=%s;";
		$Stato=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso,FUNZIONI::FormatDataDB($Data)), OBJECT );
		$Stato=$Stato[0];
//		echo $wpdb->prepare( $Sql,$this->ID_corso,FUNZIONI::FormatDataDB($Data));
		return ($Stato->Consolidata==1?True:False);
	}
	
	/**
	 * Metodo che verifica l'allineamento tra le date del dell'Evento e quelle del Corso
	 * @global type $wpdb
	 * @return mix: TRUE se le date sono uguali (Allineate)
	 *              -1 se nel Corso mancano date definite nell'Evento
	 *               1 se nel corso ci sono più date di quelle definite nell'Evento
	 */
	public function are_LezioniAllineate(){
		global $wpdb;
		$Sql="SELECT ".$wpdb->table_lezioni.".DataLezione FROM ".$wpdb->table_lezioni." WHERE ".$wpdb->table_lezioni.".IDCorso=%d;";
		$DTCorsi=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso), ARRAY_N );
		$SeriaLezioni=get_post_meta($this->ID_post, "_lezioniCorso",TRUE);
		if(!is_array($SeriaLezioni))
			return TRUE;
		$Lezioni=($SeriaLezioni?unserialize( $SeriaLezioni):array());
		$DateLezioni=array();
		foreach($Lezioni as $Lezione){
			$DateLezioni[]=FUNZIONI::FormatDataDB($Lezione[0]);
		}
		if($this->AttivitaNP){
			for($i=0;$i<count($this->AttivitaNP);$i++){
				if($this->AttivitaNP[$i][2]=="Si"){
					$DateLezioni[]="0000-00-".$i."0";
				}
			}
		}
		$DateCorsi=array();
		foreach($DTCorsi as $DTCorso){
			$DateCorsi[]=$DTCorso[0];
		}	
		foreach($DateCorsi as $DataCorso){
			if( !in_array( $DataCorso, $DateLezioni )){
				return -1;
			}			
		}
		foreach($DateLezioni as $DateLezione){
			if( !in_array( $DateLezione, $DateCorsi )){
				return 1;
			}			
		}
		return TRUE;
	}
	public function pre_AllineaGruppoSocial($IdGruppo){
//		var_dump($_REQUEST);
		$Corsisti=array();
		foreach ( $this->Corsisti as $Corsista ) {
			$Corsisti[]= $Corsista->ID;
		}		
		$Social=new Piattaforma_Social();
		$MembriGruppo=$Social->get_Soggetti_Gruppo(intval($IdGruppo));
		$Aggiungere=True;
		if(count($MembriGruppo)>$this->get_NumCorsistiDB()){
			$Aggiungere=False;
			$Differenze= array_diff($MembriGruppo, $Corsisti);
		}else{
			$Differenze= array_diff($Corsisti,$MembriGruppo);
		}
		
?>
<div class="wrap">
	<h2>Allineamento Gruppo Piattaforma Social</h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
<?php if($Aggiungere){?>
		<h3>Corsisti da Aggiungere al Gruppo Social per il corso: <?php echo $this->Nome_Corso;?></h3>
<?php }else{?>
		<h3>Corsisti da Cancellare dal Gruppo Social per il corso: <?php echo $this->Nome_Corso;?></h3>
<?php }?>
		<table class="widefat">
			<thead>
				<tr>
					<th style="text-align:center;">ID Cosista</th>
					<th style="text-align:center;">Nome Cognome Corsista</th>
				</tr>
			</thead>
			<tbody>
<?php		foreach($Differenze as $Corsista){?>
				<tr>
					<td style="text-align:center;"><?php echo $Corsista;?></td>
					<td style="text-align:center;"><?php echo FUNZIONI::NomeUtente($Corsista);?></td>
				</tr>					
<?php		}?>
			</tbody>
		</table>
	</div>
	<div>
		<form method="get" action="" id="AllineaGruppoSocial">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="allineagrupposocial"/>
			<input type="hidden" name="corsisti" value="<?php echo implode(",",$Differenze); ?>"/>
			<input type="hidden" name="gruppo" value="<?php echo $IdGruppo; ?>"/>
			<input type="hidden" name="operazione" value="<?php echo ($Aggiungere?"Aggiungere":"Eliminare");?>"/>
			<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'SocialSincroCorsisti' );?>" />
			<button class="button" id="ButtonSincroGruppo" style="color:red;margin-top: 10px; height:60px;">
<?php		if($Aggiungere){?>
				<i class="fas fa-cogs fa-2x"> Aggiungi Corsisti al Gruppo Social</i>
<?php		}else{?>
				<i class="fas fa-cogs fa-2x"> Rimuovi Corsisti dal Gruppo Social</i>
<?php		}?>				
			</button> 
		</form>		
	</div>
</div>
<?php		
	}	
	public function AllineaGruppoSocial($Corsisti,$Gruppo,$Operazione){
//		var_dump($_REQUEST);
		$Social=new Piattaforma_Social();
		$Corsisti=explode(",",$Corsisti);
		
?>
<div class="wrap">
	<h2>Allineamento Gruppo Piattaforma Social</h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
<?php if($Operazione=="Aggiungere"){?>
		<h3>Corsisti da Aggiungere al Gruppo Social per il corso: <?php echo $this->Nome_Corso;?></h3>
<?php }else{?>
		<h3>Corsisti da Cancellare dal Gruppo Social per il corso: <?php echo $this->Nome_Corso;?></h3>
<?php }?>
		<table class="widefat">
			<thead>
				<tr>
					<th style="text-align:center;">ID Cosista</th>
					<th style="text-align:center;">Nome Cognome Corsista</th>
					<th style="text-align:center;">Stato</th>
				</tr>
			</thead>
			<tbody>
<?php		foreach($Corsisti as $Corsista){?>
				<tr>
					<td style="text-align:center;"><?php echo $Corsista;?></td>
					<td style="text-align:center;"><?php echo FUNZIONI::NomeUtente($Corsista);?></td>
					<td style="text-align:center;">
<?php			If($Operazione=="Aggiungere"){
					if($Social->add_Soggetto_Group($Corsista,2,$Gruppo)>0)
						echo "Corsista Aggiunto";	
					else
						echo "Non sono riuscito ad Aggiungere il Corsista";
				}else{
					if($Social->del_Soggetto_Group($Corsista, $Gruppo)>0)
						echo "Corsista Rimosso";	
					else
						echo "Non sono riuscito a Rimuovere il Corsista";
				}
				?>	</td>	
			</tr>					
<?php		}?>
			</tbody>
		</table>
	</div>
</div>
<?php		
	}	
	public function CreaGruppoSocial(){
//		var_dump($_REQUEST);
		$Social=new Piattaforma_Social();
		$Administrators = get_users( array( 'fields' => array( 'ID'), 'role'=>'administrator' ));
		$Admins=array();
		foreach ( $Administrators as $Administrator ) {
			$Admins[]= $Administrator->ID;
		}
		$Corsisti=array();
		foreach ( $this->Corsisti as $Corsista ) {
			$Corsisti[]= $Corsista->ID;
		}		
		$Soggetti=array('Amministratori'=>$Admins,"Moderatori"=> array_merge($this->Tutor,$this->Docenti),"Corsisti"=>$Corsisti);
//		var_dump($Soggetti);wp_die();
		$Risultati=$Social->Create_Group( $this->Nome_Corso, $this->Slug_Corso, "Gruppo dedicato ai corsisiti che frequentano il corso: ".$this->Nome_Corso,$Soggetti );
?>
<div class="wrap">
	<h2>Creazione Gruppo Piattaforma Social</h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<h3>Stato creazione Gruppo per il corso: <?php echo $this->Nome_Corso;?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th>Gruppo</th>
					<th>Meta Dati</th>
					<th>Amministratori</th>
					<th>Moderatori</th>
					<th>Membri</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $Risultati["MK_Gruppi"];?></td>
					<td><?php echo $Risultati['MK_Meta'];?></td>
					<td><?php echo $Risultati['MK_Admin'];?></td>
					<td><?php echo $Risultati['MK_Modera'];?></td>
					<td><?php echo $Risultati['MK_Membri'];?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<?php		
	}
	/**
	 *  Metodo che crea l'attestato per il corso corrente per l'utente specificato
	 * @param type $Utente Id dell'utente di cui generare l'attestato
	 * @return type Testo contenente l'HTML dell'attestato 
	 */	
	public function attestato_Corso($Utente){
	$Utenti=new Utenti($Utente);
	$Docente=$Utenti->get_Descrizione();
	$Scuola=new Scuole();

	$Registro=$this->creaRegistro($Utente);
	$OreLezioni=array();
	$MinutiTotali=0;
	foreach($this->Lezioni as $LezioneCorso){
		$MinutiTotali+=$OreLezioni[FUNZIONI::FormatDataDB($LezioneCorso[0])]=FUNZIONI::CalcolaTempo($LezioneCorso);
	}
	$OreTotali=intval($MinutiTotali/60);
	$MinutiTotali=$MinutiTotali%60;
	$Registro=$Registro[0];//var_dump($OreLezioni);
//	$AttestatoT="";
	$OnLineGlobale=get_option('formazione_online');
	$DataUltimaLezione=end($this->get_Lezioni());
	$LuogoDataEmissione="<p style=\"text-align:left;\"><em>".esc_attr(get_option('formazione_luogo_emissione')).", ".$DataUltimaLezione[0]."</em></p>"; 
	$FirmaAttestato=nl2br(get_option('formazione_firma')); 
	$OnLineGlobale=unserialize($OnLineGlobale);
	
/*	
	if($this->AttivitaNP){
		for($i=0;$i<count($this->AttivitaNP);$i++){
			if($this->AttivitaNP[$i][2]=="Si"){
				$Date[]=array($i."0/00/0000");
			}
		}
	}
*/	
	
	
	if($Registro){
		$MinPresenzaTotali=0;
		$OreOnLineIndividualiNumero=0;
		$Attivita=array();
		foreach($Registro['Lezioni'] as $Lezione){
			if(substr($Lezione['Data'],0,7)!="0000-00"){
				$MinutiLezione=$OreLezioni[$Lezione['Data']];
				$OreLezione=intval($MinutiLezione/60);
				$MinutiLezione=$MinutiLezione-$OreLezione*60;
				if($Lezione['Presenza']==1){
					$MinPresenza=$OreLezioni[$Lezione['Data']]-$Lezione['AssenzaMin'];
					$MinPresenzaTotali+=$MinPresenza;
					$Ore=intval($MinPresenza/60);
					$Minuti=$MinPresenza-$Ore*60;
				}
/*				$AttestatoT.='<tr>
						<td>'.FUNZIONI::FormatDataItaliano($Lezione['Data']).'</td>'
						.'<td>'.$OreLezione.".".(strlen($MinutiLezione)==1?"0":"").$MinutiLezione.'</td>'
						.'<td>'.($Lezione['Presenza']==1?$Ore.".".(strlen($Minuti)==1?"0":"").$Minuti:"0").'</td>'
						.'<td>'.($Lezione['AssenzaMin']==0?"":$Lezione['AssenzaMin']).'</td>'
						.'<td>'.$Lezione['Note'].'</td>'
						. '</tr>';					
*/			}else{
				$Indice=(int)substr($Lezione['Data'],8,1);
				$Attivita[]=array("Attivita"=>$this->AttivitaNP[$Indice][0],
					              "MaxOre"  =>$this->AttivitaNP[$Indice][1],
								  "Ore"     =>$Lezione['AssenzaMin'],
								  "Note"    =>$Lezione['Note']);
//				$OreOnLineIndividualiNumero=$Lezione['AssenzaMin'];
//				$AttivitaOnLine=$Lezione['Note'];
			}
		}
	}
	$Ore=intval($MinPresenzaTotali/60);
	$Minuti=$MinPresenzaTotali-$Ore*60;			
//	$OnLine= unserialize(get_option('formazione_online'));
	
	
	
	if($OnLineGlobale["Attiva"]=="Si"){
		$Attivita=array();
		$Attivita[]=array("Attivita"=>"On line",
						  "MaxOre"  =>$OnLineGlobale["Ore"],
						  "Ore"     =>$OnLineGlobale["Ore"],
						  "Note"    =>"");
	}else{
		if($this->AttivitaNP){
			for($i=0;$i<count($this->AttivitaNP);$i++){
				if($this->AttivitaNP[$i][2]!="Si"){
				$Attivita[]=array("Attivita"=>$this->AttivitaNP[$i][0],
					              "MaxOre"  =>$this->AttivitaNP[$i][1],
								  "Ore"     =>$this->AttivitaNP[$i][1],
								  "Note"    =>"");
				}
			}
		}		
	}
	
	
/*	if($this->OreOnLineIndividualizzate){
		$OreOnLine=$OreOnLineIndividualiNumero;
		$OreOnLineMax=(int)get_post_meta($this->ID_post, "_oreOnLine",TRUE);
	}else{
		$OreOnLineMax=$OreOnLine=$this->get_OreOnLine();
		if ($OreOnLine==0){
			if($OnLineGlobale["Attiva"]=="Si"){
				$OreOnLineMax=$OreOnLine=$OnLineGlobale["Ore"];	
			}
		}
	}
*/
/*	$AttestatoT.='
		</tbody>
	</table>';
*/	$Attestato='
			<div style="font-family: \'Times New Roman\';margin:20px;font-size:12px;">
			<p style="text-align:center;"><em>Piano nazionale formazione docenti  2016-19 D.M. n. 797 del 16 ottobre 2016</em></p>
			<h1 style="text-align:center;">Attestato di frequenza</h1>
			<p>Corso: <strong><span  style="text-align:center;font-size:1.2em;">'.$this->Nome_Corso.'</span></strong></p>
			<p>Area: <strong><span  style="text-align:center;font-size:1.2em;">'.$this->get_Categoria().'</span></strong></p>
			<p>'.nl2br($this->getAbstract()).'</p>
			<p >Ore corso:
			<br /> - In presenza: <strong>'.$OreTotali.".".(strlen($MinutiTotali)==1?"0":"").$MinutiTotali."</strong>";
		foreach($Attivita as $SingAttivita){
			$Attestato.=' - '.$SingAttivita['Attivita'].': <strong>'.$SingAttivita['MaxOre'].'.'.(strlen($SingAttivita['MaxOre'])==2?"00":"")."</strong>";
		}

/*		if($OreOnLineMax>0){
			$Attestato.=' - OnLine: <strong>'.$OreOnLineMax.'.'.(strlen($OreOnLineMax)==2?"00":"")."</strong>";
		}
*/		$Attestato.='</p>
			<h1 style="text-align:center;">Si attesta che</h1>
			<p style="text-align:center;">
			Il/La Sig./Sig.ra <span style="font-size:1.5em;"><strong>'.$Docente['Nome']." ".$Docente['Cognome'].'</strong></span>'
				. '<br /><br />Scuola di servizio: <span style="font-size:1.1em;"><strong>'.$Scuola-> get_Scuola($Docente['Scuola']).'</strong></span>'
				. '</p>'
				. '<p>Ha frequentato il suddetto corso per ore: '
		        . '<ul>'
		        . '<li>In presenza <strong>'.$Ore.".".(strlen($Minuti)==1?"0":"").$Minuti.'</strong></li>';
		$TotOre=$Ore;
		foreach($Attivita as $SingAttivita){
			$Attestato.='<li>'.$SingAttivita['Attivita'].' su dichiarazione del formatore: <strong>'.$SingAttivita['Ore'].'.'.(strlen($SingAttivita['Ore'])<3?"00":"")."</strong>";
			$TotOre+=$SingAttivita['Ore'];
			if(strlen($SingAttivita['Note'])>0){				
				$Attestato.='<br />Attività svolta: <br />'.nl2br($SingAttivita['Note']);
			}	
			$Attestato.='</li>';
		}
		$Attestato.='</ul>';
		$TotOre	=$TotOre.".".(strlen($Minuti)==1?"00":$Minuti);
		$Attestato.= 'Per un totale di ore: <strong>'.$TotOre.'</strong>'
				. '</p>';

/*
			if($OreOnLine>0 And intval($Ore)>0){
				$Attestato.=' - OnLine, su dichiarazione del formatore <strong>'.$OreOnLine.'.'.(strlen($OreOnLineMax)==2?"00":"").'</strong>';
				if(strlen($Lezione['Note'])>0){				
					$Attestato.='<h3>Attività OnLine svolta:</h3><p style="text-align:left;font-size:1em;font-family: \'Times New Roman\';">'.nl2br($AttivitaOnLine).'</p>';
				}
			}
/*
 /*			$Attestato.='</p>
				<h2>Tabella riassuntiva incontri</h2>
				<table cellspacing="1" cellpadding="1" border="1">
				<thead>
					<tr style="background-color:#FFFF00;color:#0000FF;">
						<th style="height: 25px;text-align: center;font-weight: bold;">Data</th>
						<th style="height: 25px;text-align: center;font-weight: bold;">Ore Lezione</th>
						<th style="height: 25px;text-align: center;font-weight: bold;">Ore Presenza</th>
						<th style="height: 25px;text-align: center;font-weight: bold;">Assenza Parziale (min)</th>
						<th style="height: 25px;text-align: center;font-weight: bold;">Note</th>
					</tr>
				</thead>
				<tbody>';*/
//			return $Attestato.$AttestatoT.$FirmaAttestato;
			return $Attestato.$LuogoDataEmissione.$FirmaAttestato;
	}	
	
	public function get_TempoLezioni(){
		$Lezioni=$this->get_Lezioni();
		$DateLezioni=array();
		$MinutiTotali=0;
		$StatoCorso="C";
		foreach ($Lezioni as $Lezione){
			$StatoSingolaLezione="C";
			if(FUNZIONI::SeDate( $Lezione[0] )>0){
				$StatoCorso="A";
				$StatoSingolaLezione="A";
			}
			$DateLezioni[]=array($Lezione[0],FUNZIONI::CalcolaTempo($Lezione),$StatoSingolaLezione);
			if(substr($Lezione[0], 0, 4)!="0000")
				$MinutiTotali+=$OreLezioni[FUNZIONI::FormatDataDB($Lezione[0])]=FUNZIONI::CalcolaTempo($Lezione);
		}
		return array($MinutiTotali,$OreLezioni,$DateLezioni,$StatoCorso);
	}

	public function StatisticheCorsoDettaglio($Totali,$OutPut="Html"){
		$OreLezioni=$Totali[1];
		$MinutiTotali=$Totali[0];
		$Presenze=array();
		foreach($this->get_CorsistiDB() as $Corsisti){
			$Registro=$this->creaRegistro($Corsisti['IDUser']);
			if($Registro){
				$Registro=$Registro[0];//var_dump($OreLezioni);
				$MinPresenzaTotali=0;
				$PresenzeCorsista=array();
				foreach($Registro['Lezioni'] as $Lezione){
					if (FUNZIONI::SeDate( $Lezione['Data'] )>0){
						if($OutPut=="Html"){
							$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),0,"<span style=\"color:#000;background-color:#FFFF00;font-weight: bold;padding:5px;\">F</span>");							
						}else{
							$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),0,"F");						
						}
					}else{
						if($Lezione['Presenza']==1){
							if(substr($Lezione['Data'],0,4)=="0000"){
								$MinPresenza=$Lezione['AssenzaMin'];
								if($OutPut=="Html"){
									$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),$MinPresenza,"");
								}else{
									$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),$MinPresenza,"");	
								}								
							}else{
								$MinPresenza=$OreLezioni[$Lezione['Data']]-$Lezione['AssenzaMin'];
								$MinPresenzaTotali+=$MinPresenza;
								if($OutPut=="Html"){
									$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),($Lezione['AssenzaMin']>0?"<span style='color:#fff;background-color:red;font-weight: bold;padding:5px;'>":'').$MinPresenza.($Lezione['AssenzaMin']>0?"</span>":''),"<span style=\"color:#fff;background-color:#6B8E23;font-weight: bold;padding:5px;\">P</span>");
								}else{
									$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),$MinPresenza,"P");
								
							}
							}
						}else{
							if($OutPut=="Html"){
								$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),0,"<span style=\"color:#fff;background-color:red;font-weight: bold;padding:5px;\">A</span>");
							}else{
								$PresenzeCorsista[]=array(FUNZIONI::FormatDataItaliano($Lezione['Data']),0,"A");
							}
						}						
					}
				}
				$Presenze[$Corsisti['IDUser']]=array($Registro['Nome'],round(($MinPresenzaTotali/$MinutiTotali)*100),$PresenzeCorsista);		
			}
		}
		return FUNZIONI::SortArray($Presenze, 0);
	}
	
	public function StatisticaCorso(){
		$DateLezioni=array();
		$MinutiTotali=0;
		$LezioniFatte=0;
		foreach ($this->get_Lezioni() as $Lezione){
			$StatoSingolaLezione="C";
			if(FUNZIONI::SeDate( $Lezione[0] )>0){
				$StatoSingolaLezione="A";
			}else{
				$LezioniFatte++;
			}
			$DateLezioni[]=array($Lezione[0],FUNZIONI::CalcolaTempo($Lezione),$StatoSingolaLezione);
			$MinutiTotali+=$OreLezioni[FUNZIONI::FormatDataDB($Lezione[0])]=FUNZIONI::CalcolaTempo($Lezione);
		}
		$PercentP=array();
		for($i=0;$i<=10;$i++)
			$PercentP[$i]=0;
		foreach($this->get_CorsistiDB() as $Corsisti){
			$Registro=$this->creaRegistro($Corsisti['IDUser']);
			if($Registro){
				$Registro=$Registro[0];//var_dump($OreLezioni);
				$MinPresenzaTotali=0;
				foreach($Registro['Lezioni'] as $Lezione){
					$MinutiLezione=$OreLezioni[$Lezione['Data']];
					$OreLezione=intval($MinutiLezione/60);
					$MinutiLezione=$MinutiLezione-$OreLezione*60;
					if($Lezione['Presenza']==1){
						$MinPresenza=$OreLezioni[$Lezione['Data']]-$Lezione['AssenzaMin'];
						$MinPresenzaTotali+=$MinPresenza;
					}
				}
			}
			$PercentP[round(($MinPresenzaTotali/$MinutiTotali)*10)]++;
		}
		$OreMin=FUNZIONI::daMin_aOreMin($MinutiTotali);
		$NumLezioni=$this->get_NumLezioni("P");
		$DatiCorso=array("IDCorso"				=>$this->ID_corso,
						 "Nome_Corso"			=>$this->get_TitoloCorso(),
						 "Categorie"			=>$this->get_Categoria(),
						 "Numero_Iscritti"		=>$this->get_NumIscritti(),
						 "Numero_Corsisti"		=>$this->get_NumCorsistiDB(),
						 "Numero_Lezioni"		=>$NumLezioni,
						 "LezioniFatte"			=>$LezioniFatte,
						 "Totale_Ore_Lezioni"	=>$OreMin['Ore'].":".$OreMin['Min'],
						 "MinutiTotali"			=>$MinutiTotali,
						 "Lezioni"				=>$DateLezioni,
						 "Presenze%"			=>$PercentP);
		return $DatiCorso;
	}
	
	public function is_DataLezione($Data){
		foreach($this->Lezioni as $Date){
			if ( FUNZIONI::FormatDataDB($Date[0])==FUNZIONI::FormatDataDB( $Data)){
				return TRUE;
			}
		}
		return FALSE;
	}
	public function change_DataLezione($OldData,$NewData){
/*		$Lezioni=array();
//		var_dump($this->Lezioni);
		foreach($this->Lezioni as $Giorni){
//			echo $Giorni[0]." - ".$OldData." <br />";
			if($Giorni[0]==$OldData){
				$Valore=$NewData;
			}else{
				$Valore=$Giorni[0];
			}
				$Lezioni[]=array($Valore,$Giorni[1],$Giorni[2]);
		}
//		var_dump($Lezioni);return;
*/
		$Lezioni=array();
//		echo "<pre>";print_r($this->Lezioni);echo "</pre>";
		$SortDate=array();
		$SortedLezioni=array();
		foreach($this->Lezioni as $Giorni){
//			echo $Giorni[0]." - ".$OldData." <br />";
			if($Giorni[0]==$OldData){
				$Valore=$NewData;
			}else{
				$Valore=$Giorni[0];
			}
			$Lezioni[]=array($Valore,$Giorni[1],$Giorni[2],$Giorni[3],$Giorni[4]);
			$SortDate[]=FUNZIONI::FormatDataDB($Valore);	
		}
//		echo "<pre>";print_r($SortDate);echo "</pre>";
		sort($SortDate); 
//		echo "<pre>";print_r($SortDate);echo "</pre>";
		foreach($SortDate as $i => $data) { 
			$SortDate[$i] = FUNZIONI::FormatDataItaliano( $data); 
		}
		foreach ($SortDate as $Data){
			foreach($Lezioni as $Lezione){
				if($Lezione[0]==$Data)
					$SortedLezioni[]=$Lezione;
			}
		}
//		echo "<pre>";print_r($SortedLezioni);echo "</pre>";
		update_post_meta( $this->ID_post, '_lezioniCorso', serialize($SortedLezioni));
	}
	public function get_ArgomentiLezione($Data){
		global $wpdb;
		$Sql="SELECT ".$wpdb->table_lezioni.".Argomenti FROM ".$wpdb->table_lezioni." WHERE ".$wpdb->table_lezioni.".IDCorso=%d And ".$wpdb->table_lezioni.".DataLezione=%s;";
		$Argomento=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso,FUNZIONI::FormatDataDB($Data)), OBJECT );
		$Argomento=$Argomento[0];
		return html_entity_decode($Argomento->Argomenti);//$wpdb->prepare( $Sql,$this->ID_corso,FUNZIONI::FormatDataDB($Data));

	}
	public function is_Iscritto($IDuser){
		foreach($this->Corsisti as $Indice => $Dati){
			if ($IDuser==$Indice){
				return TRUE;
			}
		}
		return FALSE;
	}
	/** 
	 * Metodo che permette di estrarre l'elenco dei corsiti 
	 * @param	  nessuno
	 * @return    Gestione_Corsi_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_CorsistiData(){
		$IscrittiCorso=EM_Bookings::get(array("event"=>$this->ID_corso));
		if (!$IscrittiCorso Or ! is_object($IscrittiCorso) Or count($IscrittiCorso)==0){
			return FALSE;
		}
		foreach($IscrittiCorso->bookings as $Iscritto){
			$Corsisti[]= $Iscritto->person->data;
		}
		return $Corsisti;
	}
	
	private function crea_Lista_Utenti_Rifiutati($Output="li"){
		$DaIncludere=array();
		$Corso        = new EM_Event($this->ID_corso);
		$Prenotazioni =new EM_Bookings($Corso);
		$Prenotazioni=$Prenotazioni->get_rejected_bookings();
		//var_dump($Prenotazioni);
		foreach($Prenotazioni as $Key => $Valori){
			$DaIncludere[]=$Valori->person_id;
		}
		if(count($DaIncludere)>0){
			$args = array(	'role__not_in' => array("administrator"),
							'include'	   => $DaIncludere,
							'orderby'      => 'last_name,first_name',
							'order'        => 'ASC',
							'fields'       => 'ID',
							'who'          => ''
						 );	
						 //var_dump($args);
			$Utenti  = get_users( $args );			
		}else{
			$Utenti=array();
		}
		$UserData= new Utenti();
		$Scuole   = new Scuole(); 
		$Lista="";
		if($Output=="Array"){
			return $Utenti;
		}
		foreach($Utenti as $Utente){
			$DatiUtente=$UserData->get_Descrizione($Utente);
			if(($Scuola=$Scuole->get_Scuola($DatiUtente["Scuola"]))===FALSE){
				$Scuola=" Scuola non definita";
			}else{
				$Scuola=substr($Scuola,0,10);
			}
			foreach($Prenotazioni as $Key => $Valori){
				if($Valori->person_id==$Utente){
					$IDP=$Valori->booking_id;
					break;
				}
			}
			switch($Output){
				case "li":
					$Lista.="<li id=".$IDP."|".$Utente.">".ucwords($DatiUtente["Nome"])." ".ucwords($DatiUtente["Cognome"])." Codice Fiscale:".$DatiUtente["CF"]." Scuola:".$Scuola."</li>\n";
					break;
			}
		}
		return $Lista;
	}
	private function crea_Lista_Utenti_Prenotati($Output="li"){
		$DaIncludere=array();
		$Corso        = new EM_Event($this->ID_corso);
		$Prenotazioni =new EM_Bookings($Corso);
		$Prenotazioni=$Prenotazioni->get_pending_bookings();
		//var_dump($Prenotazioni);
		foreach($Prenotazioni as $Key => $Valori){
			$DaIncludere[]=$Valori->person_id;
		}
		if(count($DaIncludere)>0){
			$args = array(	'role__not_in' => array("administrator"),
							'include'	   => $DaIncludere,
							'orderby'      => 'last_name,first_name',
							'order'        => 'ASC',
							'fields'       => 'ID',
							'who'          => ''
						 );	
						 //var_dump($args);
			$Utenti  = get_users( $args );			
		}else{
			$Utenti=array();
		}
		$UserData= new Utenti();
		$Scuole   = new Scuole(); 
		$Lista="";
		if($Output=="Array"){
			return $Utenti;
		}
		foreach($Utenti as $Utente){
			$DatiUtente=$UserData->get_Descrizione($Utente);
			if(($Scuola=$Scuole->get_Scuola($DatiUtente["Scuola"]))===FALSE){
				$Scuola=" Scuola non definita";
			}else{
				$Scuola=substr($Scuola,0,10);
			}
			foreach($Prenotazioni as $Key => $Valori){
				if($Valori->person_id==$Utente){
					$IDP=$Valori->booking_id;
					break;
				}
			}
			switch($Output){
				case "li":
					$Lista.="<li id=".$IDP."|".$Utente.">".ucwords($DatiUtente["Nome"])." ".ucwords($DatiUtente["Cognome"])." Codice Fiscale:".$DatiUtente["CF"]." Scuola:".$Scuola."</li>\n";
					break;
			}
		}
		return $Lista;
	}
	/**
	 * Metodo privato per la creazione della lista degli utenti Disponibili o già assegnati al corso 
	 * @param type Boolean $NoPartecipanti; indica se l'elenco deve includere gli utenti già iscritti al corso
	 * @param type Boolean $SoloPArtecipanti; indica se l'elenco deve includere solo gli utenti già assegnati al corso
	 * @param type String $Output; formato dell'output Formati implementati Array, li(default)
	 * @return string
	 */
	public function crea_Lista_Utenti($NoPartecipanti=TRUE,$SoloPArtecipanti=FALSE,$Output="li",$CM=""){
		$DaEscludere=array();
		$DaIncludere=array();
			$Corso        = new EM_Event($this->ID_corso);
			$Prenotazioni =new EM_Bookings($Corso);
			//$Utenti=$Prenotazioni->get_user_list();	
			$Utenti = array();
			foreach( $Prenotazioni->get_bookings()->bookings as $EM_Booking ){
				$Utenti[$EM_Booking->person->ID] = $EM_Booking->person;
			}
		if($NoPartecipanti){
			foreach($Utenti as $Key => $Valori){
				$DaEscludere[]=$Key;
			}
		}elseif($SoloPArtecipanti){
			if(!$Utenti){
				return "";
			}
			foreach($Utenti as $Key => $Valori){
				$DaIncludere[]=$Key;
			}
		}
		$args = array(	'role__not_in' => array("administrator"),
						'include'	   => $DaIncludere,
						'exclude'      => $DaEscludere,
						'orderby'      => 'last_name',
						'order'        => 'ASC',
						'fields'       => 'ID',
						'who'          => ''
					 );	
					 //var_dump($args);
		$Utenti  = get_users( $args );
		$UserData= new Utenti();
		$Scuole   = new Scuole(); 
		$Lista="";
		$DatiUtentes=array();
		foreach($Utenti as $Utente){
			$TmpUtente=$UserData->get_Descrizione($Utente);
			$DatiUtentes[]=$TmpUtente;
		}
//		ksort($DatiUtentes);
		
		$DatiUtentes=FUNZIONI::MultiSort($DatiUtentes, array('Cognome'=>SORT_ASC, 'Nome'=>SORT_ASC));
		if($Output=="Array"){
			return $Utenti;
		}
		foreach($DatiUtentes as $DatiUtente){
//			$DatiUtente=$UserData->get_Descrizione($Utente);
			if($CM!="" And $CM!=$DatiUtente["Scuola"]){
				continue;
			}
			if(($Scuola=$Scuole->get_Scuola($DatiUtente["Scuola"]))===FALSE){
				$Scuola=" Scuola non definita";
			}else{
				$Scuola=substr($Scuola,0,10);
			}
			switch($Output){
				case "li":
					$Lista.="<li id=".$DatiUtente["Id"].">".$DatiUtente["Cognome"]." ".$DatiUtente["Nome"]." Codice Fiscale:".$DatiUtente["CF"]." Scuola:".$Scuola."</li>\n";
					break;
			}
		}
		return $Lista;
	}
	/**
	 * Metodo privato per la creazione della lista dei Formatori/Turor assegnati o disponibili all'assegnazione al corso 
	 * @param type Boolean $NonAssegnati; indica se l'elenco deve riportare i formatori/tutor già assegnati al corso (FALSE), o non assegnati(TRUE), o tutti (TUTTI)
	 * @param type Boolean $Docenti; indica se l'elenco deve riportare i Docenti o i Tutor
	 * @param type String $Output; formato dell'output Formati implementati Array, li(default)
	 * @return string
	 */
	public function crea_Lista_FormatoriTutor($NonAssegnati=TRUE,$Docenti=TRUE,$Output="li",$OutputName="",$OutputID="",$OutputClass="",$OutputDef=""){
		if($NonAssegnati!=="Tutti"){
			if($NonAssegnati){
					$FormatoriTutor=get_post_meta($this->ID_post, "_docenteCorso");
					$FormatoriTutor=array_merge($FormatoriTutor,get_post_meta($this->ID_post, "_tutorCorso"));
			}else{
				if($Docenti){
					$FormatoriTutor=get_post_meta($this->ID_post, "_docenteCorso");
				}else{
					$FormatoriTutor=get_post_meta($this->ID_post, "_tutorCorso");
				}	
			}
		}
		$args = array(	'role__in' => array("docente_corsi"),
						'meta_key' => 'last_name',
						'orderby'      => 'meta_value',
						'order'        => 'ASC',
						'fields'       => 'ID',
						'who'          => ''
					 );	
		//			 var_dump($args);
		$Utenti  = get_users( $args );
		$UserData= new Utenti();
		$Lista="";
		if($Output=="Array"){
			$ListaUtenti=array();
		}
		if($Output=="select"){
			$Stile=($OutputClass!=""?'class="'.$OutputClass.'"':"");
			$Lista='<select id="'.$OutputID.'" name="'.$OutputName.'" '.$Stile.'>'
					. '<option value="0">Non Assegnato</option>';
		}
//		var_dump($Utenti);
		foreach($Utenti as $Utente){
			if( $NonAssegnati==="Tutti" OR (!in_array($Utente,$FormatoriTutor) And $NonAssegnati===TRUE) OR (in_array($Utente,$FormatoriTutor) And $NonAssegnati===FALSE)){
				$DatiUtente=$UserData->get_Descrizione($Utente);
				switch($Output){
					case "Array":
						$ListaUtenti[]=array("Id"=>$Utente,"Cognome"=>ucwords($DatiUtente["Cognome"]),"Nome"=>ucwords($DatiUtente["Nome"]));
						break;
					case "li":
						$Lista.="<li id=".$Utente.">".ucwords($DatiUtente["Cognome"])." ".ucwords($DatiUtente["Nome"])."</li>\n";
						break;
					case "div":
						$Lista.="<div class=\"elemento\" id=\"".$Utente."\">".ucwords($DatiUtente["Cognome"])." ".ucwords($DatiUtente["Nome"])."</div>\n";
						break;
					case "select":
						if($Utente==$OutputDef){
							$Selected='selected';
						}else{
							$Selected='';
						}
						$Lista.='<option value="'.$Utente.'" '.$Selected.'>'.ucwords($DatiUtente["Cognome"])." ".ucwords($DatiUtente["Nome"]).'</option>';
						break;
				}
			}
		}
		switch($Output){
			case "select":
				$Lista.="</select>";
				break;	
			case "Array":
				$Lista=$ListaUtenti;
				break;
		}
		return $Lista;
	}	
	/**
	 *  Metodo che sposta la data di una lezione
	 * @global type $wpdb
	 * @param type $OldData vecchia data
	 * @param type $NewData nuova data
	 * @return string
	 */
	public function replace_DataLezione($OldData,$NewData){
		global $wpdb;
		$SqlUpDtLezione="UPDATE $wpdb->table_lezioni SET DataLezione=%s WHERE IDCorso=%d And DataLezione=%s";
//		echo $wpdb->prepare($SqlUpDtLezione,$NewData,$this->ID_corso,$OldData);
		$Res=$wpdb->query($wpdb->prepare($SqlUpDtLezione,$NewData,$this->ID_corso,$OldData));
		if($Res>0){
			$Ris="Data!sLezione!sAggiornata!sCorrettamente";
		}else{
			$Ris="Non!sho!spotuto!sAggiornare!sle!sDate!sdella!sLezione";
		}
		$SqlUpDtPresenze="UPDATE $wpdb->table_presenze SET DataLezione=%s WHERE IDCorsista in(SELECT IDCorsista FROM $wpdb->table_corsisti WHERE IDCorso=%d) And DataLezione=%s";
//		echo $wpdb->prepare($SqlUpDtPresenze,$NewData,$this->ID_corso,$OldData);die();
		$Res=$wpdb->query($wpdb->prepare($SqlUpDtPresenze,$NewData,$this->ID_corso,$OldData));
		if($Res>0){
			$Ris.="!bLe!sDate!sdelle!sPresenze!s(".$Res.")!ssono!sstate!sAggiornate!sCorrettamente";
		}else{
			$Ris.="!bNon!sho!spotuto!sAggiornare!sle!sDate!sdelle!sPresenze";
		}	
		return $Ris;
	}
	
	/**
	 *  Questo metodo presenta una tabella con tutte le date del corso e permette di specificare una nuova data e di conseguenza cambiare una di esse 
	 * @global type $wpdb
	 */
	public function move_DataLezione(){
		global $wpdb;
		$Sql="SELECT DataLezione FROM $wpdb->table_lezioni WHERE IDCorso=$this->ID_corso And DataLezione>\"0001-00-00\";";
		$DBLezioni=$wpdb->get_results($Sql);
//		var_dump($DBLezioni);
//		var_dump($this->Lezioni);
		$TabDate=array();
		foreach($this->Lezioni as $Lezione){
			$TabDate[]["DataEvento"]= Funzioni::FormatDataDB($Lezione[0]);
		}
		for($i=0;$i<count($DBLezioni);$i++){
			$TabDate[$i]["DataDB"]=$DBLezioni[$i]->DataLezione;
		}
//		var_dump($TabDate);
?>
<div class="wrap">
	<h2>Modifica date Lezioni Corso</h2>
	<strong>del corso</strong>:
	<em><?php echo $this->Nome_Corso;?></em><br />
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi&op=registro&event_id='.$this->ID_corso."&secur=".wp_create_nonce( 'Ortsiger' );?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<thead>
				<tr>
					<th>Date del Corso a catalogo</th>
					<th>Date del Corso nel registro del corso</th>
					<th>Sincronizza Da Catalogo</th>
					<th>Cambia Data</th>
					<th>Imposta</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach($TabDate as $Date){
?>				<tr>
					<td><?php echo Funzioni::FormatDataItaliano($Date["DataEvento"]);?></td>
					<td><?php echo Funzioni::FormatDataItaliano($Date["DataDB"]);?></td>
					<td><?php 
					if ($Date["DataEvento"]!=$Date["DataDB"]){
						echo "Da Sincronizzare";
					}
					?></td>
					<td>
						<form method="get" action="" id="MoveData[<?php echo $Date["DataDB"];?>]">
							<input type="hidden" name="page" value="corsi"/>
							<input type="hidden" name="op" value="moveData"/>
							<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
							<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'moveDataLezione' );?>" />
							<input class="calendario" type="text" placeholder="GG/MM/AAAA" id="datalezione[<?php echo $Date["DataDB"];?>]" name="datalezione[<?php echo $Date["DataDB"];?>]" size="10" >
					</td>				
					<td>
						<button class="button" id="ButtonSubmit[<?php echo $Date["DataDB"];?>]" style="vertical-align:middle;color:#A62426;">
							<i class="fa fa-cogs fa-2x" aria-hidden="true"></i>
						</button> 
						</form>
					</td>
					
				</tr>
<?php
	}
?>			</tbody>
		</table>
	</div>
</div>
<?php
	}
	/**
	 * Metodo che informa sui dati del corso che verranno cancellati in caso di conferma della cancellazione del corso
	 */
	public function pre_remove_Corso(){
		global $wpdb;
		$Sql="SELECT DataLezione FROM $wpdb->table_lezioni WHERE IDCorso=$this->ID_corso;";
		$DBLezioni=$wpdb->get_results($Sql);
?>
<div class="wrap">
	<h2>Rimozione Corso <em><?php echo $this->Nome_Corso;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url();?>/wp-admin/admin.php?page=corsi" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<caption style="font-size:1.5em;font-weight: bold;margin-bottom: 1em;">I Numeri del corso</caption>
			<thead>
				<tr>
					<th style="text-align:center;">Date del catalogo</th>
					<th style="text-align:center;">Date nel registro del corso</th>
					<th style="text-align:center;">Iscritti dalle Prenotazioni</th>
					<th style="text-align:center;">Iscritti nel Registro Corso</th>
					<th style="text-align:center;">Registrazioni presenze Corsisti</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="text-align:center;"><?php echo $this->get_NumLezioni();?></td>
					<td style="text-align:center;"><?php echo count($DBLezioni);?></td>
					<td style="text-align:center;"><?php echo $this->get_NumIscritti();?></td>
					<td style="text-align:center;"><?php echo $this->get_NumCorsistiDB();?></td>
					<td style="text-align:center;"><?php echo $this->get_NumCorsistiPresenze() ;?></td>
				</tr>	
			</tbody>
		</table>
	</div>
	<div>
		<form method="get" action="" id="removeCorso">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="deleteCorso"/>
			<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'removeCorso' );?>" />
			<button class="button" id="ButtonRemoveCorso" style="color:red;margin-top: 10px; height:60px;">
				<i class="fa fa-trash-alt fa-2x" aria-hidden="true"> Se sei sicuro di voler CANCELLARE il Corso cliccami</i>
			</button> 
		</form>		
	</div>
</div>
<?php
	}
	/**
	 * Metodo che cancella il corso corrente.
	 * Verranno cancellati tutti i dati del corso tranne l'evento
	 */
	public function remove_Corso(){
		global $wpdb;
?>
<div class="wrap">
	<h2>Rimozione Corso <em><?php echo $this->Nome_Corso;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url();?>/wp-admin/admin.php?page=corsi" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<caption style="font-size:1.5em;font-weight: bold;margin-bottom: 1em;">I dati del corso che sono stati cancellati</caption>
			<thead>
				<tr>
					<th style="text-align:center;">Date Lezioni nel registro del corso</th>
					<th style="text-align:center;">Corsisti nel Registro Corso</th>
					<th style="text-align:center;">Registrazioni presenze Corsisti</th>
				</tr>
			</thead>
<?php
		$Sql="DELETE $wpdb->table_presenze "
			."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
			."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
			."$wpdb->table_corsisti.IDCorsista=$wpdb->table_presenze.IDCorsista "
			."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione;";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso);
		if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
			$MsgPresenze="Si è verificato un errore";
		}else{
			$MsgPresenze=$ElementiCancellati;		
		}
		$Sql="DELETE FROM $wpdb->table_lezioni WHERE $wpdb->table_lezioni.IDCorso=%d;";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Data);
		if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
			$MsgLezioni="Si è verificato un errore";
		}else
			$MsgLezioni=$ElementiCancellati;
		$Sql="DELETE FROM $wpdb->table_corsisti WHERE $wpdb->table_corsisti.IDCorso=%d;";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Data);
		if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
			$MsgCorsisti="Si è verificato un errore";
		}else
			$MsgCorsisti=$ElementiCancellati;
?>
			<tbody>
				<tr>
					<td style="text-align:center;"><?php echo $MsgLezioni;?></td>
					<td style="text-align:center;"><?php echo $MsgCorsisti;?></td>
					<td style="text-align:center;"><?php echo $MsgPresenze;?></td>
				</tr>	
			</tbody>
		</table>
	</div>
<?php
	}
	/**
	 *  Questo metodo rimuove la lezione ed i dati dei corsisti relativi alla lezione
	 * @param type $IdCorso Codice del corso di cui cancellare la lezione
	 * @param type $Data Data della lezione del corso da cancellare
	 */
	public function remove_DataLezione($Data){
		global $wpdb;
		$DataLezione=FUNZIONI::FormatDataItaliano($Data);
		$Sql="DELETE $wpdb->table_presenze "
			."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
			."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
			."$wpdb->table_corsisti.IDCorsista=$wpdb->table_presenze.IDCorsista "
			."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_lezioni.DataLezione=%s AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Data);
		if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
			$MsgCorsisiti="Si è verificato un errore in fase di cancellazione dei corsisti per il corso ".$this->Nome_Corso." in data ".$DataLezione;
		}else{
			$MsgCorsisiti="Ho cancellato ".$ElementiCancellati." corsisti per il corso ".$this->Nome_Corso." in data ".$DataLezione;		
		}
		$Sql="DELETE FROM $wpdb->table_lezioni WHERE $wpdb->table_lezioni.IDCorso=%d And $wpdb->table_lezioni.DataLezione=%s;";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Data);
		if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
			$MsgLezione="Si è verificato un errore in fase di cancellazione della lezione del corso ".$this->Nome_Corso." data ".$DataLezione;
		}else
			$MsgLezione="Ho cancellato ".$ElementiCancellati." lezione per il corso ".$this->Nome_Corso." in data ".$DataLezione;
		$Corso=em_get_event($this->ID_corso);
		$SeriaLezioni=get_post_meta($Corso->post_id, "_lezioniCorso",TRUE);
		$Lezioni=($SeriaLezioni?unserialize( $SeriaLezioni):array());
		$i=0;
		foreach ( $Lezioni as $Lezione ) {
			if($Lezione[0]==$DataLezione){
				break;
			}
			$i++;
		}
		if($i<count($Lezioni)){
			unset($Lezioni[$i]);
			$DataCancellata="Ho cancellato la data dal calendario del corso";
			update_post_meta( $Corso->post_id, '_lezioniCorso', serialize($Lezioni));
		}else{
			$DataCancellata="Non è stato possibile cancellare la data dal calendario del corso";
		}
		
		echo '<div id="message" class="updated"><p>'.$MsgLezione.'<br />'.$MsgCorsisiti.'<br />'.$DataCancellata.'<br /></p></div>
		      <meta http-equiv="refresh" content="2;url=admin.php?page=corsi&op=registro&event_id='.$this->ID_corso."&secur=".wp_create_nonce( 'Ortsiger' ).'"/>';
	}

	/**
	 *  Metodo che visualizza i dati preliminari prima della cancellazione di una lezione del corso
	 * @global type $wpdb
	 */
	public function delete_DataLezione(){
		global $wpdb;
		$IDEvento= filter_input(INPUT_GET, "event_id");
		$Data= Funzioni::FormatDataDB(filter_input(INPUT_GET, "data"));
		$DataU=filter_input(INPUT_GET, "data");
		$Sql="SELECT $wpdb->table_lezioni.IDCorso, $wpdb->table_lezioni.DataLezione, $wpdb->table_lezioni.Argomenti, $wpdb->table_presenze.Presenza,$wpdb->table_presenze.AssenzaMin,$wpdb->table_presenze.Note "
			."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
			."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
			."$wpdb->table_corsisti.IDCorsista=wforpzio_corsi_presenze.IDCorsista "
			."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_lezioni.DataLezione=%s AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione";
		$SqlFiltrato=$wpdb->prepare($Sql,$IDEvento,$Data);
		$DBLezione=$wpdb->get_results($SqlFiltrato);
//		var_dump($DBLezione);die();
		$Risultati=array("Argomento"   =>"",
			             "Assenze"     =>0,
						 "AssenzaMin"  =>0,
						 "Note"        =>0);
		if($DBLezione[0]->Argomenti!=""){
			$Risultati["Argomento"]=$DBLezione[0]->Argomenti;
		}
		
		foreach($DBLezione as $LezioneUtente){
			if($LezioneUtente->Presenza!=1){
				$Risultati["Assenze"]++;
			}
			if($LezioneUtente->AssenzaMin!=0){
				$Risultati["AssenzaMin"]++;
			}
			if(!is_null($LezioneUtente->Note)){
				$Risultati["Note"]++;
			}
			
		}
//		var_dump($TabDate);
?>
<div class="wrap">
	<h2>Dati della Lezione del Corso <em><?php echo $this->Nome_Corso;?></em> del <em><?php echo $DataU;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi&op=registro&event_id='.$this->ID_corso."&secur=".wp_create_nonce( 'Ortsiger' );?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<thead>
				<tr>
					<th>Argomento</th>
					<th>Numero Corsisti</th>
					<th>Assenze</th>
					<th>Assenze in minuti</th>
					<th>Note corsisita</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $Risultati["Argomento"];?></td>
					<td><?php echo count($DBLezione);?></td>
					<td><?php echo $Risultati["Assenze"];?></td>
					<td><?php echo $Risultati["AssenzaMin"];?></td>
					<td><?php echo $Risultati["Note"];?></td>
				</tr>
			</tbody>
		</table>
	</div>
		<form method="get" action="" id="DeleteDataLezione">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="deleteDataLezione"/>
			<input type="hidden" name="event_id" value="<?php echo $IDEvento;?>"/>
			<input type="hidden" name="data" value="<?php echo $Data;?>" />
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'deleteDataLezione' );?>" />
				<button type="submit" class="btn btn-primary" id="ButtonSubmit" title="Conferma la cancellazione" style="display:block;width:100px;margin-left:auto;margin-right:auto;margin-top: 20px;height: 50px;color:#fff;">
				<i class="fa fa-trash-alt fa-2x" aria-hidden="true"></i>
			</button> 
		</form>
</div>
<?php
	}
	
	public function move_Iscritti(){
		$Trasferiti=explode(",",filter_input(INPUT_GET,"Trasferiti"));
		$IdEvento=filter_input(INPUT_GET,"event_id");
		$IdNewEvento=filter_input(INPUT_GET,"CorsoTrasferimento");
//		var_dump($_REQUEST);
		$Corso=em_get_event($IdEvento);
		$NomeOldCorso=$Corso->event_name;
		$Corso=em_get_event($IdNewEvento);
		$NomeNewCorso=$Corso->event_name;
?>
<div class="wrap">
	<h2>Trasferimento Utenti</h2>
	<strong>dal corso</strong>:<br />
	<em><?php echo $NomeOldCorso;?></em><br />
	<strong>al Corso</strong><br />
	<em><?php echo $NomeNewCorso;?></em>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
<?php 
	if($IdNewEvento){
?>
	<div class='table-wrap'>
		<table class="widefat">
			<caption>Stato assegnazione utenti al corso: <?php echo $this->Nome_Corso;?></caption>
			<thead>
				<tr>
					<th>Stato Aggiunta nuovo Corso</th>
					<th>Stato Cancellazione vecchio Corso</th>
					<th>Iscritto</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach($Trasferiti as $Trasferito){
		$Dati=explode("|",$Trasferito);
		$Trasferito=explode(";",$Dati[1]);
?>				<tr>
					<td><?php echo $this->Add_Iscrizione($Trasferito[0],$IdNewEvento,0);?></td>
					<td><?php 
					$Corso= new EM_Booking($Dati[0]);
					if ($Corso->delete()){
						echo "Cancellazione avvenuta con successo";
					}else{
						echo "Non sono riuscito a cancellare la vecchia prenotazione";
					}
?></td>
					<td><?php echo $Trasferito[1];?></td>
				</tr>
<?php
	}
?>			</tbody>
		</table>
	</div>
	<?php }else{ ?>
	<p style="color:red"><em>Errore Devi Selezionare il corso a cui traferire le iscrizioni</em></p>
	<?php } ?>
</div>
<?php
	}	

	public function ElencoCorsisti(){
		global $EM_Event,$EM_Person,$EM_Notices;
		//check that user can access this page
		if( is_object($EM_Event) && !$EM_Event->can_manage('manage_bookings','manage_others_bookings') ){
			?>
			<div class="wrap"><h2><?php esc_html_e('Unauthorized Access','events-manager'); ?></h2><p><?php esc_html_e('You do not have the rights to manage this event.','events-manager'); ?></p></div>
			<?php
			return false;
		}
		$localised_start_date = date_i18n('D d M Y', $EM_Event->start);
		$localised_end_date = date_i18n('D d M Y', $EM_Event->end);
		$header_button_classes = is_admin() ? 'page-title-action':'button add-new-h2';
	?>
	<div class='wrap'>
		<?php if( is_admin() ): ?><h1><?php else: ?><h2><?php endif; ?>		
  			Elenco Corsisti <a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a> 
  			<a href="<?php echo $EM_Event->get_permalink(); ?>" class="<?php echo $header_button_classes; ?>"><?php echo sprintf(__('View %s','events-manager'), __('Event', 'events-manager')) ?></a>
   			<?php do_action('em_admin_event_booking_options_buttons'); ?>
		<?php if( !is_admin() ): ?></h2><?php else: ?></h1><?php endif; ?>
  		<?php if( !is_admin() ) echo $EM_Notices; ?>  
		<div>
			<p><strong><?php esc_html_e('Event Name','events-manager'); ?></strong> : <?php echo esc_html($EM_Event->event_name); ?></p>
			<p>
				<strong><?php esc_html_e('Availability','events-manager'); ?></strong> : 
				<?php echo $EM_Event->get_bookings()->get_booked_spaces() . '/'. $EM_Event->get_spaces() ." ". __('Spaces confirmed','events-manager'); ?>
				<?php if( get_option('dbem_bookings_approval_reserved') ): ?>
				, <?php echo $EM_Event->get_bookings()->get_available_spaces() . '/'. $EM_Event->get_spaces() ." ". __('Available spaces','events-manager'); ?>
				<?php endif; ?>
			</p>
			<p>
				<strong><?php esc_html_e('Date','events-manager'); ?></strong> : 
				<?php echo $localised_start_date; ?>
				<?php echo ($localised_end_date != $localised_start_date) ? " - $localised_end_date":'' ?>
				<?php echo substr ( $EM_Event->event_start_time, 0, 5 ) . " - " . substr ( $EM_Event->event_end_time, 0, 5 ); ?>							
			</p>
			<p>
				<strong><?php esc_html_e('Location','events-manager'); ?></strong> :
<?php	   if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi')){?>
				<a class="row-title" href="<?php echo admin_url(); ?>post.php?action=edit&amp;post=<?php echo $EM_Event->get_location()->post_id ?>"><?php echo ($EM_Event->get_location()->location_name); ?></a> 
<?php       }else{?>
				<a class="row-title" href="<?php echo get_permalink($EM_Event->get_location()->post_id);?>"><?php echo ($EM_Event->get_location()->location_name); ?></a> 
<?php		}?>
			</p>
		</div>
		<h2><?php esc_html_e('Bookings','events-manager'); ?> <a href="<?php echo admin_url();?>admin.php?page=corsisti&op=exportcorsisti&event_id=<?php echo $this->ID_corso;?>&secur=<?php echo wp_create_nonce("Corsistitocsv");?>" title="Scarica elenco corsisti in Csv"><i class="fa fa-download fa-2x" aria-hidden="true"></i></a></h2>
		<?php
		$EM_Bookings_Table = new EM_Bookings_Table();
		if( current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi')){
			$EM_Bookings_Table->cols=array('user_name','event_name','booking_spaces','booking_status','actions');
		}else{
			$EM_Bookings_Table->cols=array('last_name','first_name','user_email','booking_status');			
		}
		$EM_Bookings_Table->status = 'all';
		$EM_Bookings_Table->limit='all';
		$EM_Bookings_Table->output();
  		?>
		<?php do_action('em_bookings_event_footer', $EM_Event); ?>
	</div>
<?php
	}
	/**
	 * Metodo che duplica il corso  
	 * @param 
	 * @return Nessuno
	 */
	public function DuplicaCorso(){
		$InfoUtente=new Utenti();
		$Prenotati=$this->crea_Lista_Utenti_Prenotati("Array");
		$Iscritti=array();
		foreach($Prenotati as $Prenotato){
			$DatiUtente=$InfoUtente->get_Descrizione((int)$Prenotato);
			$Iscritti[]=array("ID"		=> $Prenotato,
							  "Cognome" => $DatiUtente['Cognome'],
							  "Nome"	=> $DatiUtente['Nome'],
							  "Scuola"	=> $DatiUtente['NomeScuola'],
							  "Stato"	=> "Prenotato");
		}
		$Rifiutati=$this->crea_Lista_Utenti_Rifiutati("Array");
		foreach($Rifiutati as $Rifiutato){
			$DatiUtente=$InfoUtente->get_Descrizione((int)$Rifiutato);
			$Iscritti[]=array("ID"		=> $Rifiutato,
							  "Cognome" => $DatiUtente['Cognome'],
							  "Nome"	=> $DatiUtente['Nome'],
							  "Scuola"	=> $DatiUtente['NomeScuola'],
							  "Stato"	=> "Iscrizione Respinta");
		}
		$Partecipanti=$this->crea_Lista_Utenti(false, true,"Array");
		foreach($Partecipanti as $Partecipante){
			$DatiUtente=$InfoUtente->get_Descrizione((int)$Partecipante);
			$Iscritti[]=array("ID"		=> $Partecipante,
							  "Cognome" => $DatiUtente['Cognome'],
							  "Nome"	=> $DatiUtente['Nome'],
							  "Scuola"	=> $DatiUtente['NomeScuola'],
							  "Stato"	=> "Partecipante");
		}		
		$IdEvento=filter_input(INPUT_GET,"event_id");
?>
<div class="wrap">
	<h2>Duplicazione Corso</h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<form method="get" action="" id="DuplicaCorso">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="duplicazionecorso"/>
			<input type="hidden" name="event_id" value="<?php echo $IdEvento;?>"/>
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'DuplicaCorso' );?>" />		
		<h3>Corso da duplicare: <?php echo $this->Nome_Corso;?></h3>
		<table border="0">
			<tbody>
				<tr style="text-align:right;">
					<th><label name="NewName">Nome del corso duplicato</label></th>
					<td><input	type="text"	id="NewName" name="NewName" value="" size="100" maxlength="100"/></td>
				</tr>
				<tr style="text-align:right;">
					<th><label name="NewCod">Codice corso</label></th>
					<td style="text-align:left;color:red;font-weight: bold;">(<?php echo $this->CodiceCorso;?>) <input type="text"	id="NewCod" name="NewCod" value="" size="20" maxlength="20"/></td>
				</tr>
				<tr>
					<th style="text-align:right;"><label name="Formatori">Mantieni i Formatori</label></th>
					<td><input	type="checkbox"	id="Formatori" name="Formatori"/></td>
				</tr>
				<tr>
					<th style="text-align:right;"><label name="Tutor">Mantieni i Tutor</label></th>
					<td><input	type="checkbox"	id="Tutor" name="Tutor"/></td>
				</tr>
				<tr>
					<th style="text-align:right;"><label name="Iscrizioni">Iscrizioni Aperte</label></th>
					<td><input	type="checkbox"	id="Iscrizioni" name="Iscrizioni"/> Per N&deg; giorni: <input	type="number" id="GGAI" name="GGAI" min="0" max="30"/></td>
				</tr>
				<tr>
					<th style="text-align:right;"><label name="Date">Mantieni date lezioni</label></th>
					<td><input	type="checkbox"	id="Date" name="Date"/></td>
				</tr>
				<tr>
					<th style="text-align:right;"><label name="Attivita">Mantieni attività</label></th>
					<td><input	type="checkbox"	id="Attivita" name="Attivita"/></td>
				</tr>
			</tbody>
		</table>
		<h4>Iscritti e Prenotati al corso </h4>
		<div>
			<strong>Tipo Iscrizione corsisti selezionati</strong> 
			<input type="radio" name="TipoIscrizione" value="InCoda" checked> <em>inserimento in coda</em> 
			<input type="radio" name="TipoIscrizione" value="Iscrizione"> <em>iscrizione diretta</em>
		</div>
		<br />
		<table class="widefat">
			<thead>
				<tr>
					<th>ID</th>
					<th>Cognome</th>
					<th>Nome</th>
					<th>Scuola</th>
					<th>Stato Iscrizione</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach($Iscritti as $Iscritto){
?>				<tr>
					<td><input type="checkbox" id="<?php echo $Iscritto['ID'];?>" name="IDUM[]" value="<?php echo $Iscritto['ID'];?>" /> <?php echo $Iscritto['ID'];?></td>
					<td><strong><?php echo $Iscritto['Cognome'];?></strong></td>
					<td><strong><?php echo $Iscritto['Nome'];?></strong></td>
					<td><?php echo $Iscritto['Scuola'];?></td>
					<td><strong><?php echo $Iscritto['Stato'];?></strong></td>
				</tr>
<?php
	}
?>			</tbody>
		</table>
		<button class="button" id="ButtonDuplicaCorso" style="margin-top:5px; color:blue;font-size:1.5em;">
			<i class="fas fa-magic" aria-hidden="true"> Duplica Corso</i>
		</button>
		</form>
	</div>
</div>
<?php
	}	
	public function EseguiDuplicazioneCorso($NewName,$NewCod,$MantieniFormatori,$MantieniTutor,$IscrizioniAperte,$NumGGAI,$MantieniDate,$MantieniAttivita,$TipoIscrizione,$Corsisti){
?>	
	<div class="wrap">
		<h2>Duplicazione Corso</h2>
		<div class="tornaindietro" style="margin-bottom:10px;">
			<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
<?php
		$Evento=new EM_Event($this->ID_corso);
		$NuovoEvento=$Evento->duplicate();
?>		
			<a href="<?php echo site_url().'/wp-admin/post.php?post='.$NuovoEvento->post_id.'&action=edit';?>" class="add-new-h2" style="font-size:1.3em;"><i class="far fa-eye"></i> Visualizza Evento</a>
		</div>	
		<div class='table-wrap'>
			<table class="widefat" border="0">
				<thead>
					<tr>
						<th>Operazione</th>
						<th>Stato</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Evento</td>
						<td>
<?php
		$IDPNuovoEvento=$NuovoEvento->post_id;
		$NuovoEvento->event_name=$NewName;
		if(!$NuovoEvento->save())
			echo "Non duplicato"			
			. "						</td>
					</tr>";
		else{
			echo "Duplicato con nome <strong>".$NewName."</strong>";
?>							
						</td>
					</tr>
					<tr>
						<td>Codice Corso</td>
						<td>
<?php
		update_post_meta($IDPNuovoEvento,"_codiceCorso",$NewCod);
		echo "<strong>".$NewCod."</strong>";
?>									
						</td>
					</tr>
					<tr>
						<td>Stato Evento</td>
						<td>
<?php
		$NuovoEvento->set_status(1,TRUE);
		switch( $NuovoEvento->get_status()){
			case  0:echo 'Bozza';	break;
			case  1:echo 'Pubblicato';	break;
			case -1:echo 'Cancellato'; break;
		}
?>
						</td>
					</tr>
					<tr>
						<td>Iscrizioni</td>
						<td>
<?php
		$Tickets=$NuovoEvento->get_tickets();
		if($IscrizioniAperte){
			$Start=date("Y-m-d 00:00:00");
			$End= Funzioni::DateAdd(date("Y-m-d"),$NumGGAI)." 00:00:00";
		}else{
			$Start=date("0000-00-00 00:00:00");
			$End=$Start;			
		}
		foreach($Tickets->tickets as $Ticket){
			$Ticket->__set("ticket_start",$Start);
			$Ticket->__set("ticket_end",$End);
			$Ticket->save();
		}
		echo "Inizio: ".$Start." Fine: ".$End;
?>						
						</td>
					</tr>
					<tr>
						<td>Docenti</td>
						<td>
<?php
		if(!$MantieniFormatori){
			delete_post_meta($IDPNuovoEvento,"_docenteCorso");
			echo "Rimossi";
		}else{
			echo "Mantenuti";
		}
?>							
						</td>
					</tr>
					<tr>
						<td>Tutor</td>
						<td>
<?php
		if(!$MantieniTutor){
			delete_post_meta($IDPNuovoEvento,"_tutorCorso");
			echo "Rimossi";
		}else{
			echo "Mantenuti";
		}
?>								
						</td>
					</tr>
					<tr>
						<td>Lezioni</td>
						<td>
<?php
		if(!$MantieniDate){
			delete_post_meta($IDPNuovoEvento,"_lezioniCorso");
			echo "Rimosse";
		}else{
			echo "Mantenute";
		}
?>		
						</td>
					</tr>
					<tr>
						<td>Attività</td>
						<td>
<?php
		if(!$MantieniAttivita){
			delete_post_meta($IDPNuovoEvento,"_attivita");
			echo "Rimosse";
		}else{
			echo "Mantenute";
		}
?>		
						</td>
					</tr>
					<tr>
						<td>Corsisti</td>
						<td>
							<table class="widefat" border="0">
								<thead>
									<tr>
										<th>Corsista</th>
										<th>Stato</th>
									</tr>
								</thead>
								<tbody>
<?php
		if($TipoIscrizione=="InCoda"){
			$Stato=0;
		}else{
			$Stato=1;
		}
		$InfoUtente=new Utenti();
		foreach($Corsisti as $Iscritto){
?>				
									<tr>
										<td><strong><?php $DatiUtente=$InfoUtente->get_Descrizione($Iscritto);echo $DatiUtente['Cognome']." ".$DatiUtente['Nome']." ".$DatiUtente['NomeScuola'];?></strong></td>
										<td><em><?php echo  $this->Add_Iscrizione($Iscritto,$NuovoEvento->event_id,$Stato);?><em></td>
									</tr>
		<?php	}?>
								</tbody>
							</table>
						</td>
<?php		
		}
?>
				</tbody>
			</table>
		</div>
	</div>
<?php
		//echo "<pre>";var_dump($Tickets->tickets[99]);echo "</pre>";
	}
	/**
	 * Metodo che crea le iscrizioni al corso in risposta della form del metodo gest_Iscritti 
	 * @param String $_GET[iscritti] elenco degli iscritti nel formato IdUtente;Descrizione come da elenco utilizzato in get_Iscritti,......
	 * @return Nessuno
	 */
	public function add_Iscritti(){
//		var_dump($_REQUEST);
		$Iscritti=explode(",",filter_input(INPUT_GET,"Iscritti"));
//		die();
		$IdEvento=filter_input(INPUT_GET,"event_id");
		$GStato=filter_input(INPUT_GET,"InCoda");
		if($GStato=="on"){
			$Stato=0;
		}else{
			$Stato=1;
		}
//		var_dump($Iscritti);die();
?>
<div class="wrap">
	<h2>Assegnazione Utenti al Corso</h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<h3>Stato assegnazione utenti al corso: <?php echo $this->Nome_Corso;?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th>Stato Operazione</th>
					<th>Iscritto</th>
				</tr>
			</thead>
			<tbody>
<?php
	$InfoUtente=new Utenti();
	foreach($Iscritti as $Iscritto){
?>				<tr>
					<td><em><?php echo $this->Add_Iscrizione($Iscritto,0,$Stato);?><em></td>
					<td><strong><?php $DatiUtente=$InfoUtente->get_Descrizione($Iscritto);echo $DatiUtente['Cognome']." ".$DatiUtente['Nome']." ".$DatiUtente['NomeScuola'];?></strong></td>
				</tr>
<?php
	}
?>			</tbody>
		</table>
	</div>
</div>
<?php
	}	
	/**
	 * Metodo che duplica le iscrizioni del corso corrente su un altro corso selezionato 
	 * @param String $_GET[Selezionati] elenco degli iscritti nel formato IdUtente;Descrizione come da elenco utilizzato in get_Iscritti,......
	 * @return Nessuno
	 */
	public function duplica_Iscritti(){
//		var_dump($_REQUEST);
		$Iscritti=explode(",",filter_input(INPUT_GET,"Selezionati"));
		$GStato=filter_input(INPUT_GET,"DestDuplica");
		if($GStato=="on"){
			$Stato=0;
		}else{
			$Stato=1;
		}		$IDCorsoDestinazione=filter_input(INPUT_GET,"CorsoDestinazione");
		$CorsoDest=new Gestione_Corso($IDCorsoDestinazione);
//		var_dump($Iscritti);echo $Stato;wp_die();
?>
<div class="wrap">
	<h2>Assegnazione Utenti al Corso</h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<h3>Stato assegnazione utenti al corso: <?php echo $CorsoDest->Nome_Corso;?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th>Stato Operazione</th>
					<th>Iscritto</th>
				</tr>
			</thead>
			<tbody>
<?php
	$InfoUtente=new Utenti();
	foreach($Iscritti as $Iscritto){
		$Iscritto=explode(";",$Iscritto);
?>				<tr>
					<td><em><?php echo $CorsoDest->Add_Iscrizione($Iscritto[0],0,$Stato);?><em></td>
					<td><strong><?php $DatiUtente=$InfoUtente->get_Descrizione($Iscritto[0]);echo $DatiUtente['Cognome']." ".$DatiUtente['Nome']." ".$DatiUtente['NomeScuola'];?></strong></td>
				</tr>
<?php
	}
?>			</tbody>
		</table>
	</div>
</div>
<?php
	}		
	private function crea_RegistroLezioni($Data){
		global $wpdb;
		if ( false === $wpdb->insert($wpdb->table_lezioni,array('IDCorso' => $this->ID_corso,'DataLezione'=> $Data),array('%d','%s'))){
// echo "Sql==".$wpdb->last_query ."    Ultimo errore==".$wpdb->last_error;exit;
			$StatoOperazioni= '<i class="fa fa-thumbs-down" aria-hidden="true" style="color:red;"></i>'.$wpdb->last_query .' Ultimo errore=='.$wpdb->last_error."</br />";
		}else{
			$StatoOperazioni='<i class="fa fa-thumbs-up" aria-hidden="true" style="color:lime;"></i>';
		}	
		return $StatoOperazioni;
	}
	/**
	 * Metodo che imlementa la form per la gestione degli iscritti al corso
	 * @param Nessuno
	 * @return Nessuno
	 */
	public function genera_Corso(){
//		var_dump( $this->Corsisiti);
?>
<div class="wrap">
	<h2>Creazione Corso: <?php echo $this->Nome_Corso;?></h2>
	<div class="tornaindietro">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
<?php
	$Date=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));

/*	if($this->OreOnLineIndividualizzate){
		$Date[]=array("0000-00-00");
	}
*/	if($this->AttivitaNP){
		for($i=0;$i<count($this->AttivitaNP);$i++){
			if($this->AttivitaNP[$i][2]=="Si"){
				$Date[]=array($i."0/00/0000");
			}
		}
	}
//	var_dump( $Date);wp_die();
?>
	<div style="margin-top: 10px;">
		<h3>Creazione registro lezioni</h3>
		<div style="width:100%;overflow-x: scroll;">
		<table class="widefat creacorso" style="border:1px;">
			<thead>
				<tr>
<?php 
		foreach($Date as $Data){
?>				
					<th style="width: 150px;"><?php echo (substr($Data[0],1)=="0/00/0000"?$this->AttivitaNP[substr($Data[0], 0, 1)][0]:$Data[0]);?></th>				
<?php } ?>	
				</tr>
			</thead>
			<tbody>
				<tr>
<?php
		foreach($Date as $Data){
//			echo Funzioni::FormatDataDB($Data[0])."<br />";
?>				
					<td  style="width: 150px;"><?php echo $this->crea_RegistroLezioni(Funzioni::FormatDataDB($Data[0]));?></td>
<?php } ?>				
				</tr>

			</tbody>
		</table>
		</div>
		<h3>Creazione registro presenze</h3>
		<div style="width:100%;height:400px;overflow-x: scroll;overflow-y: scroll;">
		<table class="widefat" style="border:1px;">
			<thead>
				<tr>
					<th style="background-color: #0024ff;color:#FFF;min-width: 100px;">Codice Utente</th>
					<th style="background-color: #0024ff;color:#FFF;min-width: 100px;">Corsista</th>
					<th>Creazione Registro Lezione</th>
<?php
		foreach($Date as $Data){
?>				
					<td><?php echo (substr($Data[0],1)=="0/00/0000"?$this->AttivitaNP[substr($Data[0],0, 1)][0]:$Data[0]);?></td>				
<?php } ?>	
				</tr>
			</thead>
			<tbody>
<?php
		global $wpdb,$table_prefix;
		$wpdb->EM_BOOKINGS_TABLE=$table_prefix . "em_bookings";
		$Prenotazione=new EM_Booking();
		foreach($this->Corsisti as $Corsista){
			$Prenotazione->get(array("event_id" => $this->ID_corso,"person_id" => $Corsista->data->ID));
			$Risultati=$this->Create_Register( $Corsista->data->ID,$Prenotazione->booking_id, $Date );
?>				
				<tr>
					<td style="color: #0024ff;min-width: 100px;"><?php echo $Corsista->data->ID;?></td>
					<td style="color: #0024ff;min-width: 100px;"><?php echo $Corsista->data->display_name;?></td>
<?php
			foreach($Risultati as $Risultato){
					if($Risultato===TRUE){
						echo '<td><i class="fa fa-check fa-2x" aria-hidden="true" style="color:green;"></i></td>';
					}elseif($Risultato===FALSE){
						echo '<td><span class="fa-stack fa-lg">
								<i class="fa fa-puzzle-piece fa-stack-1x"></i>
								<i class="fa fa-ban fa-stack-2x" style="color:red;"></i>
								</span></td>';
					}else{
						if(substr($Risultato,0,8)=="Registro"){
							echo '<td style="color:red;"><span title="'.substr($Risultato,8,strlen($Risultato)).'">Registro per l\'Utente gi&agrave; creato</span></td>';
						}else{
							echo '<td style="color:red;"><span title="'.$Risultato.'">Utente gi&agrave; registrato</span></td>';
						}
					}
			} 
?>					
				</tr>
<?php } ?>
			</tbody>
		</table>	
		</div>
	</div></div>	
<?php
	}	

/**
 * Metodo che permette la gestione delle comunicazioni con i corsisti del corrente corso
 * In particolare permette la creazione della MailingList e la sottoscrizione dei corsisti alla MailingList.
 * Il nome della MailingList ha lo stesso codicedel corso
 */
	public function gest_comunicazioni(){
		$IdNL=Funzioni::get_MailingListID($this->CodiceCorso);
		$Stato="";
		if ($this->get_NumIscritti()!=count(alo_em_get_recipients_subscribers($IdNL))){
			if ( function_exists("alo_em_get_mailinglists")){
				if ($IdNL!==FALSE) {
					$mailinglists = alo_em_get_mailinglists ( 'hidden,admin,public' );
					unset ( $mailinglists [$IdNL] );
					if ( alo_em_save_mailinglists ( $mailinglists ) && alo_em_delete_all_subscribers_from_lists ($IdNL) ) {	
						$Stato.='Vecchia Mailing List Svuotata -- ';
					}else{
						$Stato.='Vecchia Mailing List Non Svuotata -- ';
					}
				}
				$mailinglists = alo_em_get_mailinglists ( 'hidden,admin,public' );
				$list_name	= array();
				$list_name["it"] = stripslashes(sanitize_text_field( $this->CodiceCorso.$lang ));
				if ( empty($mailinglists) ) { // if 1st list, skip index 0
					$mailinglists [] = array ( "name" => "not-used", "available" => "deleted", "order" => "");
				}	
				$mailinglists [] = array ( "name" => $list_name, "available" => "admin", "order" => 0);
				if ( alo_em_save_mailinglists ( $mailinglists ) ) {
					$Stato.='Mailing List Creata';
				} else {
					$Stato.='Errore durante la creazione della Mailing List';
				}					
			}
		}
?>
	<div class="wrap">
		<h2>Gestione Comunicazioni corso: <em><?php echo $this->Nome_Corso;?></em></h2>
		<h3>Codice Corso: <em><?php echo $this->CodiceCorso;?></em></h3>
		<div class="tornaindietro">
			<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
		</div>
		<div class="welcome-panel">
<?php
		if ( function_exists("alo_em_get_mailinglists")){
			echo "<h4>Mailing List: ".$Stato ;
			if( Funzioni::is_MailingList($this->CodiceCorso)){
				echo ' <i class="fa fa-thumbs-up" aria-hidden="true" style="color:lime;"></i>';
			}else{
				echo ' <i class="fa fa-thumbs-down" aria-hidden="true" style="color:red;"></i> <a href="?page=corsi&op=comunicazioni&event_id='.$this->ID_corso.'&task=CreaML&secur='.wp_create_nonce("Inoizacinumoc").'">Crea</a>';
			}
			echo "</h4>";
		}
?>
		<div style="margin-top: 10px;">
		<table class="widefat" style="border:1px;">
			<thead>
				<tr>
					<th style="background-color: #0024ff;color:#FFF;">Codice Utente</th>
					<th style="background-color: #0024ff;color:#FFF;">Corsista</th>
					<th>Iscrizione utente</th>
					<th>Sottoscrizione Mailing List</th>
			</thead>
			<tbody>
<?php
//		var_dump(Funzioni::get_User_MailingList($this->CodiceCorso));
//		var_dump($this->Corsisti);
		$utenti=new Utenti();
		$IDconsensoNewsLetter= get_option('formazione_IDconsensoNewsLetter');
		$mailinglists = alo_em_get_mailinglists( 'hidden,admin' );
		foreach($this->Corsisti as $Corsista){
			$DatiCorsista=$utenti->get_Descrizione( $Corsista->data->ID );
			echo "<tr>"
				. "<td>".$Corsista->data->ID."</td>"
				. "<td>".$DatiCorsista['Cognome']." ".$DatiCorsista['Nome']."</td>"
				. "<td>";
//			$UserConsentML=$utenti->has_consent($IDconsensoNewsLetter,$Corsista->data->ID);
			$UserConsentML=TRUE;
//			var_dump($UserConsentML);
			if($UserConsentML){
				if(($IdML=alo_em_is_subscriber($Corsista->data->user_email))==0 ){
					$fields['email'] = $Corsista->data->user_email; 	
					$fields['name'] = $DatiCorsista['Cognome']." ".$DatiCorsista['Nome']; 
					if ( alo_em_add_subscriber( $fields, 1, $lang ) == "OK" ) {
						echo '<i class="fas fa-save fa-2x" aria-hidden="true" style="color:lime;"></i>';
					}else{
						echo '<i class="fas fa-save fa-2x" aria-hidden="true" style="color:red;" title="Errore nell\'iscrizione dell\'utente></i>';
					}
				}else{
					echo '<i class="fa fa-thumbs-up fa-2x" aria-hidden="true" style="color:lime;"></i>';			
				}				
			}else{
				echo '<i class="fas fa-frown fa-2x" style="color:red;"></i>';	
			}
			echo "</td>"
			. "<td>";
			if($UserConsentML){
				if ( (Funzioni::is_MailingList($this->CodiceCorso))){
					$IDML=Funzioni::get_MailingListID($this->CodiceCorso);
					if(($IdML=alo_em_is_subscriber($Corsista->data->user_email))==0){
						echo '<i class="fas fa-save fa-2x" aria-hidden="true" style="color:red;" title="Errore nell\'assegnazione dell\'utente alla Mailing List Utente Inesistente></i>';
					}else{
						if(alo_em_add_subscriber_to_list ( $IdML, $IDML )===FALSE){
							echo '<i class="fas fa-save fa-2x" aria-hidden="true" style="color:red;" title="Errore nell\'assegnazione dell\'utente alla Mailing List Utente Inesistente></i>';
						}else{
							echo '<i class="fas fa-save fa-2x" aria-hidden="true" style="color:lime;"></i>';
						}
					}
				}
			}else{
				echo '<i class="fas fa-frown fa-2x" style="color:red;"></i>';	
			}
			echo "</td></tr>";
		}
		echo "<tbody>"
					. "</table>"
					. "</div>";
/*				$fields['email'] = $user->user_email; //edit : added all this line
				
		$fields['name'] = $name; //edit : added all this line

		//alo_em_add_subscriber( $fields, 1, $lang ); //edit : orig : alo_em_add_subscriber( $user->user_email, $name , 1, $lang );
		if ( alo_em_add_subscriber( $fields, 1, $lang ) == "OK" ) {
			do_action ( 'alo_easymail_new_subscriber_added', alo_em_get_subscriber( $user->user_email ), $user_id );
		}
 */
?>
		</div>
</div>
<?php
	}
	

/**
 * Metodo che visualizza il registro del corso 
 */
	public function registro_Corso(){
//		var_dump( $this->Corsisiti);
//		var_dump($_REQUEST);
//		var_dump($this->Lezioni);die();
	if ( ! function_exists( 'wp_get_current_user' ) ){
		require_once( ABSPATH . 'wp-includes/pluggable.php' );			
	}
	if(	current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi') Or $this->isMyCourse("Non hai i permessi per eseguire questa Operazione in questo Corso") Or $this->isMy("Non hai il diritto di gestire questo corso")){
		$Tabella="";
		$Scuola=new Scuole();
		if (get_option('gest_c_logo') != '') { 
			$UrlImg=get_option('gest_c_logo'); 
		} else { 
			$UrlImg='http://'; 
		}
		if ( isset($_GET['message']) ){
//			var_dump($_GET);die();
			$Msg=str_replace("!b","<br />",$_GET['message']);
			$Msg=str_replace("!s"," ",$Msg);
			echo '<div id="message" class="updated"><p>'.$Msg.'</p></div>
				  <meta http-equiv="refresh" content="2;url=admin.php?page=corsi&op=registro&event_id='.$this->ID_corso."&secur=".wp_create_nonce( 'Ortsiger' ).'"/>';
				  return;
		} 
		switch (filter_input( INPUT_GET, "mod" )){
			case "stafogliofirma":
				$Tabella='
				<div>
					<h2>Foglio firme corso: '.$this->Nome_Corso.'</h2>
					<h3>Data incontro:'.filter_input( INPUT_GET, "data" ).'</h3>
				</div>
				<table cellspacing="0" cellpadding="1" border="1">
					<thead>
						<tr style="background-color:#FFFF00;color:#0000FF;">
							<th style="width: 10%;height: 25px;text-align: center;font-size:1.5em;">Codici</th>
							<th style="width: 25%;height: 25px;text-align: center;font-size:1.5em;">Corsista</th>
							<th style="width: 30%;height: 25px;text-align: center;font-size:1.5em;">Scuola</th>
							<th style="width: 35%;height: 25px;text-align: center;font-size:1.5em;">Firma</th>
						</tr>
					</thead>
					<tbody>';
				$Registro=$this->creaRegistro();//echo "<pre>";var_export($Registro);echo "</pre>";
				foreach($Registro as $Alunno){
					$Tabella.='	
						<tr style="line-height:20px;">
							<td style="width: 10%;">'.$Alunno['IDUser']."/".$Alunno['IDCorsista'].'</td>
							<td style="width: 25%;font-size:1.5em;font-weight: bold;">'.ucwords($Alunno['Nome']).'</td>
							<td style="width: 30%;">'.$Scuola->get_Scuola(get_user_meta($Alunno['IDUser'],"Scuola",true)).'</td>
							<td style="width: 35%;"></td>
						</tr>';
				}
				$Tabella.='
					</tbody>
					</table>';	
				break;
			case "staregistro":
				$Tabella='
				<h1>Registro presenze corso: '.$this->Nome_Corso.'</h1>
				<div><h2>Argomenti delle lezioni</h2>
				<ul>';
				$Argomenti=$this->creaRegistroArgomenti();
				foreach($Argomenti as $Argomento){
					$Argomento->Argomenti=str_replace("\n",'<br />', $Argomento->Argomenti);
					$Tabella.='<li><em>'.FUNZIONI::FormatDataItaliano($Argomento->DataLezione).'</em><br />'
							. $Argomento->Argomenti.'</li>';
				}
				$Tabella.='</ul></div><div  style="page-break-after: always;"></div>
					<h2>Tabella riassuntiva presenze</h2>
					<table cellspacing="1" cellpadding="1" border="1">
					<thead>
						<tr style="background-color:#FFFF00;color:#0000FF;">
							<th style="height: 25px;text-align: center;font-weight: bold;">Codici<br /><span style="text-align:left">Utente<br />Corsista</span></th>
							<th style="height: 25px;text-align: center;font-weight: bold;">Corsista</th>
							<th style="height: 25px;text-align: center;font-weight: bold;">Scuola</th>';
				$Date=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));
//				var_dump($Date);die();
				foreach($Date as $Data){
					if(isset($Data[1]) And isset($Data[2])){
						$OrePT=date_diff(date_create($Data[2]),date_create($Data[1]));  
					}else{
						$OrePT=date_diff(date_create("00:00"),date_create("00:00"));;
					}	
					if(isset($Data[3]) And isset($Data[4])){
						$OreST=date_diff(date_create($Data[4]),date_create($Data[3]));  
					}else{
						$OreST=date_diff(date_create("00:00"),date_create("00:00"));
					}
					$Ore=intval($OrePT->format("%H"));
					$Minuti=intval($OrePT->format("%i"));
					$MinutiPT=$Ore*60+$Minuti;
					$Ore=intval($OreST->format("%H"));
					$Minuti=intval($OreST->format("%i"));
					$MinutiST=$Ore*60+$Minuti;
					$MinutiTot=$MinutiPT+$MinutiST;
					$OreLezione=intval($MinutiTot/60);
					$MinutiLezione=$MinutiTot-$OreLezione*60;
					$Tabella.='
							<th style="height: 25px;text-align: center;font-weight: bold;">'.$Data[0].' Ore: '.$OreLezione.':'.$MinutiLezione.'</th>';				
				}
				$Tabella.='
						</tr>
					</thead>
					<tbody>';
					$Registro=$this->creaRegistro();//echo "<pre>";var_export($Registro);echo "</pre>";
					foreach($Registro as $Alunno){
						$Tabella.='
							<tr>
								<td >'.$Alunno[IDUser]."<br />".$Alunno['IDCorsista'].'</td>
								<td>'.ucwords($Alunno[Nome]).'</td>
								<td>'.$Scuola->get_Scuola(get_user_meta($Alunno['IDUser'],"Scuola",true)).'</td>';
						foreach($Alunno['Lezioni'] as $Lezione){
							if($Lezione['Data']<=date("Y-m-d")){
								$Tabella.="<td style=\"color:";
								if($Lezione['Presenza']=="1"){
									$TabellaP="<strong>Presente</strong>";
									$Colore="green";
								}else{
									$TabellaP="<strong>Assente</strong>";
									$Colore="red";
								}	
								if($Lezione['Note']!=Null){
									$TabellaP.="<br />".$Lezione['Note'];
								}		
								$Tabella.=$Colore.";\">".$TabellaP."</td>";
							}else{
								$Tabella.="<td></td>";
							}
						}
						$Tabella.='
						</tr>';
					}				
					$Tabella.='
					</tbody>
				</table>';		
				break;
			default:
//				$OreOnLine=(int)get_post_meta($this->ID_post, "_oreOnLine",TRUE);
	?>
	<div class="wrap">
		<img src="<?php echo $UrlImg; ?>" class="Centrato"/>
		<div id="InfoForm" title="Form informativa" style="display:none;">
			<h2 id="TitoloForm"></h2>
			<p id="MsgForm"></p>
		</div>
		<div id="dialog-form" title="Nota Corsista" style="display:none;">  
			<div style="font-size: 12px;font-weight:bold;color: #ff0000;text-align:center;">Nota Corsista</div>
			<textarea rows="4" cols="60" id="note" name="note"></textarea>
		</div>
		<div id="dialog-form2" title="Minuti di assenza" style="display:none;">
			<label for="AssenzaMin">Minuti di assenza: </label>
			<input type="text" id="AssenzaMin" name="AssenzaMin" readonly style="border:0; color:#f6931f; font-weight:bold;width: 50px;">
			<div id="slider"></div>
		</div>
		<div id="dialog-form3" title="Attività " style="display:none;">
			<label for="OreOnLine">Ore</label>
			<input type="text" id="OreOnLine" name="OreOnLine" style="border:0; color:#f6931f; font-weight:bold;width: 50px;">
			<div id="sliderol"></div>
		</div>
		<div id="loading"></div>
		<div id="dialog-form-lezione" title="Argomento della lezione" class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-dialog-buttons">  
			<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
				<span class="ui-dialog-title">Argomenti Lezione</span>
			</div>
			<?php /*wp_editor( "", 'argomenti',
					array( 'wpautop'=>true,
						  'textarea_name' => 'argomenti',
						  'textarea_rows' => 40,
						  'editor_height' =>150,
						  'teeny' => TRUE,
						  'media_buttons' => false)
			);*/?>
			<textarea id="argomenti" name="argomenti" style="width: 100%;height: 70%;"></textarea>
			<input type="hidden" id="IDCorso" value="<?php echo $this->ID_corso;?>">
			<input type="hidden" id="DataLezione" value="">
			<div style="padding-top:15px;margin:auto;width:300px;">
				<button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" title="Memorizza argomenti della lezione" id="MemorizzaArgomenti"><span class="ui-button-text">Memorizza</span></button>
				<button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" title="Cancella argomenti della lezione" id="CancellaArgomenti"><span class="ui-button-text">Cancella</span></button>
				<button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" title="Esci senza memorizzare le modifiche"id="AnnullaArgomenti"><span class="ui-button-text">Annulla</span></button>
			</div>
		</div>
	<?php
			$Date=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));
			if($this->AttivitaNP){
				for($i=0;$i<count($this->AttivitaNP);$i++){
					if($this->AttivitaNP[$i][2]=="Si"){
						$Date[]=array($i."0/00/0000");
					}
				}
			}
/*			if($this->OreOnLineIndividualizzate){
				$Date[]=array("00/00/0000");
			}
*/			$ColVis= filter_input( INPUT_GET, "data" );
			if (!isset($ColVis) Or $ColVis=="Tutte"){
				$DimeTab="width: ".((count($Date)*100)+400)."px;";		
			}else{
				$DimeTab="";
			}
			$Attestabile=0;
			$Lezioni=$this->get_Lezioni();
			if($this->AttivitaNP){
				for($i=0;$i<count($this->AttivitaNP);$i++){
					if($this->AttivitaNP[$i][2]=="Si"){
						$Lezioni[]=array($i."0/00/0000");
					}
				}
			}
/*			if($this->get_OreOnLineIndividualizzate()){
				$Lezioni[]=array("00/00/0000");
			}
*/			foreach($Lezioni as $Lezione){
				if($this->is_LezioneConsolidata( $Lezione[0])){
					$Attestabile+=1;
				}
			}
	?>
		<h2>Registro Presenze Corso: <?php echo $this->Nome_Corso;?></h2>
		<div class="tornaindietro">
			<a href="<?php echo admin_url().'admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
			<div style="display:inline;float:right;margin-right: 15px;">
				<label for="ColVis">Colonna Data</label>
			<select id="ColVis" name="ColVis"  onchange="document.location.href=this.options[this.selectedIndex].value;">
				<option value="">Seleziona Data</option>
				<option value="<?php echo admin_url()."admin.php?page=corsi&op=registro&event_id=".$this->ID_corso."&secur=".wp_create_nonce("Ortsiger");?>&data=Tutte">Tutte</option>
	<?php //var_dump($Date);die();
			foreach($Date as $Data){
				echo "<option value=\"".admin_url()."admin.php?page=corsi&op=registro&event_id=".$this->ID_corso."&secur=".wp_create_nonce("Ortsiger")."&data=".$Data[0]."\">".(substr($Data[0],1)=="0/00/0000"?$this->AttivitaNP[substr($Data[0], 0, 1)][0]:$Data[0])."</option>";
			}
	?>
			</select>
			</div>
			<div style="display:inline;float:right;margin-right: 15px;">
				<a href="?page=corsi&op=registro&event_id=<?php echo $this->ID_corso;?>&mod=staregistro" title="Stampa registro corso"><i class="fa fa-print fa-2x Tooltip" aria-hidden="true"></i></a>
	<?php
			if($Attestabile==count($Lezioni)){
				?><p style="padding-left:10px;display: inline;"><a style="color:#ab2d32;" href="<?php echo admin_url();?>admin.php?page=gestione_corsi&op=registro&event_id=<?php echo $this->ID_corso;?>&mod=stattestati&secur=<?php echo wp_create_nonce("AttestatoFrequenza");?>"><i class="fa fa-graduation-cap fa-2x attestatoCorso" aria-hidden="true" title="Crea Attestati Corso" id="<?php echo $this->ID_corso;?>"></i></a></p>
		<?php	} ?>
			</div>
		</div>
		<div style="margin-top: 10px;overflow: auto;width: 100%;">
		<div style="margin-top: 10px;height:61vh ;overflow-y:auto;">
			<table class="widefat creacorso" style="border:1px;<?php echo $DimeTab;?>" id="TabellaCorsisti" >
				<thead>
					<tr>
						<th style="background-color: #0024ff;color:#FFF;width:80px;">Codici (Ut./Cors.)</th>
						<th style="background-color: #0024ff;color:#FFF;width:200px;">Corsista</th>
	<?php
	//		$Date=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));
			$Orelezioni=$this->get_OreLezioniPianificate();
	//		var_dump($Orelezioni);
	//		var_dump($Date);
			$StatoDateLezioni=array();
			foreach($Date as $Data){
				$Argomenti=$this->get_Argomenti($Data[0]);
				$DataConsolidata=$this->is_LezioneConsolidata($Data[0]);
				$StatoDateLezioni[$Data[0]]=$DataConsolidata;
				if($DataConsolidata){
					$ColoreData="background-color:red;";
					$DTConsolidata="Data Consolidata Non si possono più cambiare i dati";
				}else{
					$ColoreData="";
					$DTConsolidata="";
				}
				echo '<div>';
				if(!isset($ColVis) Or $ColVis=="Tutte" Or $ColVis==$Data[0]){
	?>				
						<th>
							<div class="btn-group" id="Corso<?php echo $this->ID_corso;?>" style="margin:auto;width:85px;">
								<input type="hidden" id="OreLez<?php echo FUNZIONI::FormatDataDB($Data[0]);?>" value="<?php echo $Orelezioni[$Data[0]];?>" />
								<span  title="<?php echo $DTConsolidata;?>" class="btn btn-primary" style="width:45px;padding-left:2px;<?php echo $ColoreData?>"><?php echo "<span style=\"font-size:0.8em;\">".(substr($Data[0],1)=="0/00/0000"?$this->AttivitaNP[substr($Data[0], 0, 1)][0]:FUNZIONI::DataFormat($Data[0],"gg/mm/aa"))."</span>";?></span>
								<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" href="#" style="width:5px;<?php echo $ColoreData?>" title="<?php echo $DTConsolidata;?>">
								  <span class="fa fa-caret-down" title="Toggle dropdown menu"></span>
								</a>
								<ul class="dropdown-menu">
					<?php	if(substr($Data[0],1)!="0/00/0000"){ ?>
									<li>
										<a class="Tooltip" href="?page=corsi&op=registro&event_id=<?php echo $this->ID_corso;?>&mod=stafogliofirma&data=<?php echo $Data[0];?>" title="Stampa Foglio Firma dell'incontro del <?php echo $Data[0];?>"><i class="fa fa-print" aria-hidden="true"></i> Stampa Fogli presenza</a>
									</li>
					<?php	} ?>
									<li>
										<a class="Tooltip registro" href="#" title="Argomenti della lezione"  id="L;<?php echo $this->ID_corso.";".$Data[0];?>"> <i class="fa fa-edit Tooltip" aria-hidden="true" ></i> Registro lezione</a>
									</li>
					<?php	if( (current_user_can('corsi_admin') Or current_user_can('corsi_gest_corsi')Or current_user_can('corsi_organizzatore')) And  (substr($Data[0],1)!="0/00/0000")){
?>								<li>
										<a class="Tooltip" href="?page=corsi&op=deletedata&event_id=<?php echo $this->ID_corso;?>&data=<?php echo $Data[0];?>&secur=<?php echo wp_create_nonce( 'deletedata' );?>" title="Cancella la data della lezione"  id="L;<?php echo $this->ID_corso.";".$Data[0];?>"> <i class="fa fa-trash-alt" aria-hidden="true"></i> Cancella data lezione</a>
									</li>								
	<?php						}
								if(!$DataConsolidata and FUNZIONI::SeDate($Data[0])<=0){?>
									<li>
										<a class="Tooltip" href="?page=corsi&op=consolidadata&event_id=<?php echo $this->ID_corso;?>&datacorso=<?php echo $Data[0];?>&secur=<?php echo wp_create_nonce( 'consolida' );?>" title="Chiude la data del corso, dopo questa operazione non sarà possibile modificare le presenze"  id="L;<?php echo $this->ID_corso.";".$Data[0];?>"> <i class="fa fa-toggle-on" aria-hidden="true"></i> Consolida la Data</a>
									</li>
	<?php						}
								if( $DataConsolidata And (current_user_can('corsi_admin') Or current_user_can('corsi_organizzatore'))){?>
									<li>
										<a class="Tooltip" href="?page=corsi&op=sconsolidadata&event_id=<?php echo $this->ID_corso;?>&datacorso=<?php echo $Data[0];?>&secur=<?php echo wp_create_nonce( 'riapridata' );?>" title="Riapre la data del corso, dopo questa operazione sarà possibile di nuovo modificare le presenze"  id="L;<?php echo $this->ID_corso.";".$Data[0];?>"> <i class="fa fa-toggle-off" aria-hidden="true"></i> Riapre la Data</a>
									</li>
	<?php						}?>							</ul>
							</div>

						</th>				
	<?php		} 
			}?>	
					</tr>
				</thead>
				<tbody style="height:50vh ;overflow-y: scroll;">
	<?php
				$Registro=$this->creaRegistro();//echo "<pre>";var_export($Registro);echo "</pre>";
				foreach($Registro as $Alunno){
	?>
					<tr>
						<th style="color: #0024ff;width:80px;"><?php echo $Alunno['IDUser']."/".$Alunno['IDCorsista'];?></th>
						<th style="color: #0024ff;width:200px;"><?php echo ucwords(Funzioni::NomeUtente($Alunno['IDUser']));?></th>
	<?php			//var_dump($StatoDateLezioni);
				foreach($Alunno['Lezioni'] as $Lezione){
	//				$DataAperta=$this->is_LezioneConsolidata(FUNZIONI::FormatDataItaliano($Lezione['Data']));
					$DataConsolidata=$StatoDateLezioni[FUNZIONI::FormatDataItaliano($Lezione['Data'])];
//					echo $Lezione['Data']."  - ".substr($Lezione['Data'],0,7)." ; ";;var_dump($DataConsolidata);echo "<br />";
					if(!isset($ColVis) Or $ColVis=="Tutte" Or $ColVis==FUNZIONI::FormatDataItaliano($Lezione['Data']) or ($ColVis==$Lezione['Data'])){
						if(($Lezione['Data']<=date("Y-m-d") Or substr($Lezione['Data'],0,7)=="0000-00") And !$DataConsolidata){
							$Abilitato='data-enabled="Si"';
							$CssAbilitato="";
						}else{
							$Abilitato='data-enabled="No"';
							if(!$DataConsolidata){
								$CssAbilitato="Disabilitato";
							}else{
								$CssAbilitato="Consolidato";
							}
						}
						echo "<td style=\"width:86px;\">";
						if(substr($Lezione['Data'],0,7)!="0000-00"){
							if($Lezione['Presenza']==0){
								echo '<i class="fa fa-user fa-2x Assente Tooltip '.$CssAbilitato.'" aria-hidden="true" id="0x'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.' title="Clicca per segnare la presenza"></i> ';
							}else{
								echo '<i class="fa fa-user fa-2x Presente Tooltip '.$CssAbilitato.'" aria-hidden="true" id="1x'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.' title="Clicca per segnare l\'assenza"></i> ';
							}							
						}
						if($Lezione['Note']){
							echo '<i class="fa fa-info-circle fa-2x Nota Tooltip '.$CssAbilitato.'" aria-hidden="true" title="'.$Lezione['Note'].'" data-Nota="'.$Lezione['Note'].'" id="Nx'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.'></i>';
						}else{
							echo '<i class="fa fa-info-circle fa-2x '.$CssAbilitato.'" aria-hidden="true" id="Nx'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.'></i>';
						}
						if(substr($Lezione['Data'],0,7)!="0000-00"){
							if($Lezione['AssenzaMin']>0){
								echo ' <i class="fa fa-clock fa-2x '.$CssAbilitato.' AssenzaMin Tooltip" aria-hidden="true" title="'.$Lezione['AssenzaMin'].'" data-AssenzaMin="'.$Lezione['AssenzaMin'].'" id="Mx'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.'></i>';
							}else{
								echo ' <i class="fa fa-clock fa-2x '.$CssAbilitato.' Tooltip" aria-hidden="true" title="'.$Lezione['AssenzaMin'].'" data-AssenzaMin="'.$Lezione['AssenzaMin'].'" id="Mx'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.'></i>';
							}
						}else{
							$IdLezione=(int)substr($Lezione['Data'],8,1);
							$OreAttivita=$this->AttivitaNP[$IdLezione][1];
							if($Lezione['AssenzaMin']==$OreAttivita){
								echo ' <i class="fa fa-cloud fa-2x '.$CssAbilitato.' OreOnLine Tooltip" aria-hidden="true" title="'.$Lezione['AssenzaMin'].'" data-MaxOre="'.$OreAttivita.'" data-OreOnLine="'.$Lezione['AssenzaMin'].'" id="Mx'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.'></i>';
							}else{
								echo ' <i class="fa fa-cloud fa-2x '.$CssAbilitato.' Tooltip" aria-hidden="true" title="'.$Lezione['AssenzaMin'].'" data-MaxOre="'.$OreAttivita.'" data-OreOnLine="'.$Lezione['AssenzaMin'].'" id="Mx'.$Alunno['IDCorsista'].'x'.$Lezione['Data'].'" '.$Abilitato.'></i>';
							}
							echo '<div id="'.$IdLezione.'0'.$Alunno['IDCorsista'].'" class="OreOnLine">'.$Lezione['AssenzaMin'].'</div>';
						}
						echo "</td>";
					}
				}
	?>					
					</tr>
	<?php } ?>
				</tbody>
			</table>	
		</div>
		<div id="risultato"></div>
	</div>	
	<?php	break;
			}
			return $Tabella;
		}
	}	
/**
* Metodo che analizza i corsisti codificati rispetto agli iscritti al corso.
* Si possono avere delle discrepanze nel momento in cui un utente viene cancellato o un corsista viene spostato dopo aver Generato il Corso
* 
* @return
*/
	public function pre_AllineaCorsisti(){
		global $wpdb;
		$Sql="SELECT ".$wpdb->table_corsisti.".IDUser FROM ".$wpdb->table_corsisti." WHERE IDCorso=%d;";
		$Corsisti=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso,$ID_User),ARRAY_N );
		$ArrCorsisti=array();
		foreach($Corsisti as $Corsista){
			$ArrCorsisti[]=$Corsista[0];
		}
		$ArrIscritti=array();
		foreach($this->Corsisti as $ID => $Iscritto){
			$ArrIscritti[]=$ID;
		}		
		$DaEliminare=array_diff($ArrCorsisti,$ArrIscritti);

?>
<div class="wrap">
	<h2>Analisi Corsisti non Iscritti al Corso <em><?php echo $this->Nome_Corso;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url();?>/wp-admin/admin.php?page=corsi" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<caption style="font-size:1.5em;font-weight: bold;margin-bottom: 1em;">Dati dei Corsisti Non Iscritti al Corso</caption>
			<thead>
				<tr>
					<th style="text-align:center;">ID Utente</th>
					<th style="text-align:center;">Nome Cognome Corsista</th>
					<th style="text-align:center;">Numero Presenze</th>
				</tr>
			</thead>
			<tbody>
<?php		foreach($DaEliminare as $Ele){
				$Sql="Select count($wpdb->table_presenze.DataLezione) "
					."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
					."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
					."$wpdb->table_corsisti.IDCorsista=$wpdb->table_presenze.IDCorsista "
					."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_corsisti.IDUser= %d AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione;";
				$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Ele);
				$MsgPresenze=$wpdb->get_var($SqlFiltrato);
?>				<tr>
					<td style="text-align:center;"><?php echo $Ele;?></td>
					<td style="text-align:center;"><?php echo FUNZIONI::NomeUtente($Ele);?></td>
					<td style="text-align:center;"><?php echo $MsgPresenze;?></td>
				</tr>	
<?php 		}?>
			</tbody>
		</table>
	</div>
	<div>
		<form method="get" action="" id="AllineaCorsisti">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="allineacorsisti"/>
			<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'AllineaCorsisti' );?>" />
			<button class="button" id="ButtonRemoveCorso" style="color:red;margin-top: 10px; height:60px;">
				<i class="fas fa-cogs fa-2x" aria-hidden="true"> Esegui Rimozione Corsisti dal Corso</i>
			</button> 
		</form>		
	</div>
</div>
<?php
	}	
/**
* Metodo che Allinea Il DB la tabella dei Corsisti e delle Presenze con gli Iscritti al Corso
* Vengono cancellati i record delle presense dell'utente eliminato dal corso ed il record del corsista dalla relativa tabella
* 
* @return
*/	
	public function AllineamentoCorsisti(){
		global $wpdb;
		$Sql="SELECT ".$wpdb->table_corsisti.".IDUser FROM ".$wpdb->table_corsisti." WHERE IDCorso=%d;";
		$Corsisti=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso,$ID_User),ARRAY_N );
		$ArrCorsisti=array();
		foreach($Corsisti as $Corsista){
			$ArrCorsisti[]=$Corsista[0];
		}
		$ArrIscritti=array();
		foreach($this->Corsisti as $ID => $Iscritto){
			$ArrIscritti[]=$ID;
		}		
		$DaEliminare=array_diff($ArrCorsisti,$ArrIscritti);
?>
<div class="wrap">
	<h2>Rimozione Corsisti non iscritti al corso <em><?php echo $this->Nome_Corso;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url();?>/wp-admin/admin.php?page=corsi" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<caption style="font-size:1.5em;font-weight: bold;margin-bottom: 1em;">I dati dei Corsisti che sono stati cancellati</caption>
			<thead>
				<tr>
					<th style="text-align:center;">ID Cosista</th>
					<th style="text-align:center;">Nome Cognome Corsista</th>
					<th style="text-align:center;">Numero Presenze Cancellate</th>
					<th style="text-align:center;">Cancellazione Corsista</th>
				</tr>
			</thead>
			<tbody>
<?php		foreach($DaEliminare as $Ele){
				$Sql="DELETE $wpdb->table_presenze "
					."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
					."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
					."$wpdb->table_corsisti.IDCorsista=$wpdb->table_presenze.IDCorsista "
					."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_corsisti.IDUser= %d AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione;";
				$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Ele);
			if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
				$MsgPresenze="Si è verificato un errore";
			}else{
				$MsgPresenze=$ElementiCancellati;		
			}
			$Sql="DELETE FROM $wpdb->table_corsisti WHERE $wpdb->table_corsisti.IDCorso=%d AND $wpdb->table_corsisti.IDUser= %d ;";
			$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Ele);
			if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
				$MsgCorsisti="Si è verificato un errore";
			}else
				$MsgCorsisti=$ElementiCancellati;
			?>				
				<tr>
					<td style="text-align:center;"><?php echo $Ele;?></td>
					<td style="text-align:center;"><?php echo FUNZIONI::NomeUtente($Ele);?></td>
					<td style="text-align:center;"><?php echo $MsgPresenze;?></td>
					<td style="text-align:center;"><?php echo $MsgCorsisti;?></td>
				</tr>	
<?php 		}?>
		</table>
	</div>
<?php
	}	
/**
* Metodo che analizza i corsisti codificati rispetto agli iscritti al corso.
* Si possono avere delle discrepanze nel momento in cui un utente viene cancellato o un corsista viene spostato dopo aver Generato il Corso
* 
* @return
*/
	public function pre_AllineaLezioni(){
		global $wpdb;
		$Sql="SELECT $wpdb->table_lezioni.DataLezione "
			."FROM $wpdb->table_lezioni "
			."WHERE $wpdb->table_lezioni.IDCorso= %d ";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso);
		$DateLezioniDB=$wpdb->get_results($SqlFiltrato,ARRAY_N );
		$ArrDateLezioniDB=array();
		foreach($DateLezioniDB as $DataLezioneDB){
			$ArrDateLezioniDB[]=$DataLezioneDB[0];
		}
		$ArrDateLezioni=array();
		foreach($this->Lezioni as $DataLezione){
			$ArrDateLezioni[]=FUNZIONI::FormatDataDB($DataLezione[0]);
		}	
		if($this->AttivitaNP){
			for($i=0;$i<count($this->AttivitaNP);$i++){
				if($this->AttivitaNP[$i][2]=="Si"){
					$ArrDateLezioni[]="0000-00-0".$i;
				}
			}
		}
		$DaEliminare=array_diff($ArrDateLezioniDB,$ArrDateLezioni);
/*		echo "<pre>";print_r($ArrDateLezioniDB);echo "</pre>";
		echo "<pre>";print_r($ArrDateLezioni);echo "</pre>";
		echo "<pre>";print_r($DaEliminare);echo "</pre>";
*/
?>
<div class="wrap">
	<h2>Analisi Date Lezioni del Corso <em><?php echo $this->Nome_Corso;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url();?>/wp-admin/admin.php?page=corsi" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<caption style="font-size:1.5em;font-weight: bold;margin-bottom: 1em;">Dati delle Lezioni eliminate dal Corso</caption>
			<thead>
				<tr>
					<th style="text-align:center;">Data in più nel Data Base</th>
					<th style="text-align:center;">Presenze da cancellare</th>
				</tr>
			</thead>
			<tbody>
<?php		foreach($DaEliminare as $Ele){
				$Sql="Select count($wpdb->table_presenze.DataLezione) "
				."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
				."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
				."$wpdb->table_corsisti.IDCorsista=$wpdb->table_presenze.IDCorsista "
				."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_lezioni.DataLezione= %s AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione;";
			$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Ele);
			$MsgPresenze=$wpdb->get_var($SqlFiltrato);			
?>				<tr>
					<td style="text-align:center;"><?php echo FUNZIONI::FormatDataItaliano($Ele);?></td>
					<td style="text-align:center;"><?php echo $MsgPresenze;?></td>
				</tr>	
<?php 		}?>
			</tbody>
		</table>
	</div>
	<div>
		<form method="get" action="" id="AllineaCorsisti">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="allinealezioni"/>
			<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
			<input type="hidden" name="daEliminare" value="<?php echo implode(";", $DaEliminare);?>" />
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'AllineaLezioni' );?>" />
			<button class="button" id="ButtonRemoveCorso" style="color:red;margin-top: 10px; height:60px;">
				<i class="fa fa-magnet fa-2x" aria-hidden="true"> Esegui allineamento Lezioni</i>
			</button> 
		</form>		
	</div>
</div>
<?php
	}	
	public function AllineaLezioni(){
		global $wpdb;
/*		$Sql="SELECT $wpdb->table_lezioni.DataLezione "
			."FROM $wpdb->table_lezioni "
			."WHERE $wpdb->table_lezioni.IDCorso= %d ";
		$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso);
		$DateLezioniDB=$wpdb->get_results($SqlFiltrato,ARRAY_N );
		$ArrDateLezioniDB=array();
		foreach($DateLezioniDB as $DataLezioneDB){
			$ArrDateLezioniDB[]=$DataLezioneDB[0];
		}
		$ArrDateLezioni=array();
		foreach($this->Lezioni as $DataLezione){
			$ArrDateLezioni[]=FUNZIONI::FormatDataDB($DataLezione[0]);
		}		
		if($this->AttivitaNP){
			for($i=0;$i<count($this->AttivitaNP);$i++){
				if($this->AttivitaNP[$i][2]=="Si"){
					$ArrDateLezioni[]="0000-00-0".$i;
				}
			}
		}
*/		$DA=filter_input( INPUT_GET, "daEliminare" );
		$DaEliminare=explode(";",$DA);//array_diff($ArrDateLezioniDB,$ArrDateLezioni);
/*		echo "<pre>";print_r($ArrDateLezioniDB);echo "</pre>";
		echo "<pre>";print_r($ArrDateLezioni);echo "</pre>";
		echo "<pre>";print_r($DaEliminare);echo "</pre>";
*/
?>
<div class="wrap">
	<h2>Rimozione dal Data Base delle Date Lezioni del Corso <em><?php echo $this->Nome_Corso;?></em></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url();?>/wp-admin/admin.php?page=corsi" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div class='table-wrap'>
		<table class="widefat">
			<caption style="font-size:1.5em;font-weight: bold;margin-bottom: 1em;">Date delle Lezione del Corso RIMOSSE</caption>
			<thead>
				<tr>
					<th style="text-align:center;">Data da in più nel Data Base</th>
					<th style="text-align:center;">Lezione Cancellata</th>
					<th style="text-align:center;">Presenze Cancellate</th>
				</tr>
			</thead>
			<tbody>
<?php		foreach($DaEliminare as $Ele){
				$Sql="DELETE $wpdb->table_presenze "
				."FROM ( $wpdb->table_lezioni INNER JOIN $wpdb->table_corsisti ON "
				."$wpdb->table_corsisti.IDCorso=$wpdb->table_lezioni.IDCorso) INNER JOIN $wpdb->table_presenze ON "
				."$wpdb->table_corsisti.IDCorsista=$wpdb->table_presenze.IDCorsista "
				."WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_lezioni.DataLezione= %s AND $wpdb->table_lezioni.DataLezione=$wpdb->table_presenze.DataLezione;";
				$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Ele);
				if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
					$MsgPresenze="Si è verificato un errore";
				}else{
					$MsgPresenze=$ElementiCancellati;		
				}
				$Sql="DELETE FROM $wpdb->table_lezioni WHERE $wpdb->table_lezioni.IDCorso= %d AND $wpdb->table_lezioni.DataLezione=\"$Ele\";";
				$SqlFiltrato=$wpdb->prepare($Sql,$this->ID_corso,$Ele);
				if(($ElementiCancellati=$wpdb->query($SqlFiltrato))==="FALSE"){
					$MsgLezioni="Si è verificato un errore";
				}else
					$MsgLezioni=$ElementiCancellati;
?>				<tr>
					<td style="text-align:center;"><?php echo FUNZIONI::FormatDataItaliano($Ele);?></td>
					<td style="text-align:center;"><?php echo $MsgLezioni;?></td>
					<td style="text-align:center;"><?php echo $MsgPresenze;?></td>
				</tr>	
<?php 		}?>
			</tbody>
		</table>
	</div>
</div>
<?php
	}
/**
 * Metodo che implementa la form per la gestione degli iscritti al corso
 * @param Nessuno
 * @return Nessuno
 */
	public function gest_Iscritti(){
		$Scuole=new Scuole();
?>
<div class="wrap">
	<h2>Gestione Iscrizoni al Corso: <?php echo $this->Nome_Corso;?></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div id="tabsgestiscritti">
		<ul>
		  <li><a href="#tabsgestiscritti-1">Gestisci Iscrizioni</a></li>
		  <li><a href="#tabsgestiscritti-2">Gestisci Coda d'Attesa</a></li>
		  <li><a href="#tabsgestiscritti-3">Duplica iscritti su altro corso</a></li>
		</ul>
		<div id="tabsgestiscritti-1">
			<i class="fa fa-filter fa-2x" aria-hidden="true" style="color:red;"></i> <?php echo $Scuole->getElencoScuole("Select","scuole","filtroscuole");?>
			<form method="get" action="?page=corsi" id="FormIscritti">
				<input type="hidden" name="page" value="corsi"/>
				<input type="hidden" name="op" value="addscritti"/>
				<input type="hidden" name="secur" value="<?php echo wp_create_nonce("IscrittiAdd");?>"/>				
				<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
				<h3 class="rwd-toggle" id="HB">Gestione Iscritti al Corso 
					<button class="button" id="ButtonSubmit" style="vertical-align:middle;color:#A62426;">
						<span><i class="fa fa-user-plus fa-2x" aria-hidden="true"></i></span>
					</button> 
					<label for="InCoda">  Aggiungi i docenti selezionati in Attesa di Approvazione</label> <input type="checkbox" id="InCoda" name="InCoda" />
				</h3>
				<div class="rwd-container" id="contenitoreRp">
					<div class="rwd-block">
						<div class="grid" id="datiHomeBoxes">
							<div id="bloccoSx">
								<h3>Utenti disponibili</h3>
								<div id="bloccoSxlista">
									<ul id="UtentiDisp"> 
		<?php								echo $this->crea_Lista_Utenti();?>
									</ul>
								</div>						    
							</div>
							<div id="bloccoDx">
								<h3>Utenti Inseriti</h3>
								<ul id="UtentiAss">	
		<?php								echo $this->crea_Lista_Utenti(FALSE,TRUE);?>
								</ul>
							</div>
						</div>
					</div><!-- end of .rwd-block -->
				</div><!-- end of .rwd-container -->
			</form>
		</div>
		<div id="tabsgestiscritti-2">
			<form method="get" action="?page=corsi" id="FormMigraIscritti">
				<input type="hidden" name="page" value="corsi"/>
				<input type="hidden" name="op" value="spostaiscritti"/>
				<input type="hidden" name="secur" value="<?php echo wp_create_nonce("IscrittiSposta");?>"/>	
				<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>" id="Idcorso"/>
				<h3 class="rwd-toggle" id="HB">Gestione Iscritti al Corso 
					<button class="button" id="ButtonSubmitMigra" style="vertical-align:middle;color:#A62426;">
						<span><i class="fa fa-user-plus fa-2x" aria-hidden="true"></i></span>
					</button> 
				</h3>
				<div class="rwd-container" id="contenitoreRpTra">
					<div class="rwd-block">
						<div class="grid" id="datiHomeBoxesTra">
							<div id="bloccoSxTra">
								<h3>Utenti prenotati e non assegnati</h3>
								<div id="bloccoSxlista">
									<ul id="UtentiAtt"> 
		<?php								echo $this->crea_Lista_Utenti_Prenotati();?>
									</ul>
								</div>						    
							</div>
							<div id="bloccoDxTra">
								<h3>Utenti da trasferire</h3>
								<ul id="UtentiTra">	
		<?php								//echo $this->crea_Lista_Utenti(FALSE,TRUE);?>
								</ul>
							</div>
							<div id="bloccoCorsi">
								<h3>Corsi disponibili</h3>
		<?php								echo Funzioni::ListaCorsi("future",$this->ID_corso,"radio","CorsiDisponibili","CorsoTrasferimento");?>
							</div>
						</div>
					</div><!-- end of .rwd-block -->
				</div><!-- end of .rwd-container -->
			</form>
		</div>
		<div id="tabsgestiscritti-3">
			<form method="get" action="?page=corsi" id="FormDuplicaIscritti">
				<input type="hidden" name="page" value="corsi"/>
				<input type="hidden" name="op" value="duplicaiscrittiAC"/>
				<input type="hidden" name="secur" value="<?php echo wp_create_nonce("IscrittiDuplica");?>"/>	
				<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>" id="Idcorso"/>
				<h3 class="rwd-toggle" id="HB">Duplica Iscritti al Corso 
					<button class="button" id="ButtonSubmitDuplica" style="vertical-align:middle;color:#A62426;">
						<span><i class="fa fa-user-plus fa-2x" aria-hidden="true"></i></span>
					</button> 
					<label for="DestDuplica">  Aggiungi i docenti selezionati in Attesa di Approvazione</label> 
					<input type="checkbox" id="DestDuplica" name="DestDuplica" />
				</h3>
				<div class="rwd-container" id="contenitoreRpMig">
					<div class="rwd-block">
						<div class="" id="datiHomeBoxesMig">
							<div id="bloccoSxMig">
								<h3>Utenti iscritti a questo corso</h3>
								<div id="bloccoSxlistaMig">
									<ul id="UtentiCur"> 
		<?php								echo $this->crea_Lista_Utenti(FALSE,TRUE);?>
									</ul>
								</div>						    
							</div>
							<div id="bloccoDxMig">
								<h3>Utenti da duplicare</h3>
								<ul id="UtentiMig">	
		<?php								//echo $this->crea_Lista_Utenti(FALSE,TRUE);?>
								</ul>
							</div>
							<div id="bloccoCorsi">
								<h3>Corsi disponibili</h3>
		<?php								echo Funzioni::ListaCorsi("future",$this->ID_corso,"radio","CorsiDisponibili","CorsoDestinazione");?>
							</div>
						</div>
					</div><!-- end of .rwd-block -->
				</div><!-- end of .rwd-container -->
			</form>
		</div>
	</div>
<?php		
	}
/**
 *  Metodo he permette l'iscrizione di un utente al corso
 * @global type $log
 * @param type $ID_Utente codice Utente che deve essere iscritto al corso
 * @return type Mixed True se l'iscrizione avviene con successo;
 *                    Messaggio di errore come restituito da Bookings->feedback_message
 */
	public function Add_Iscrizione($ID_Utente,$ID_New_Corso=0,$Stato=1){
		global $log, $wpdb;
		$ID_Corso=($ID_New_Corso!=0?$ID_New_Corso:$this->ID_corso);
//		echo $ID_Corso;die;
		$Utente = get_user_by( 'id', $ID_Utente );
		$current_user = wp_get_current_user();
		$Valori=array("Amministratore" => $current_user->display_name);
		$Prenotazione = new EM_Booking();
		$Corso        = new EM_Event($ID_Corso);
		$Messaggi     = new EM_Notices();
		if( !$Corso->get_bookings()->has_booking($ID_Utente)){
			$Sql="SELECT booking_id FROM ".EM_BOOKINGS_TABLE." WHERE event_id = %d AND person_id = %d";
			$Sql=$wpdb->prepare($Sql,$ID_Corso,$ID_Utente);
			$Prenotazioni= $wpdb->get_results($Sql);
			if($Prenotazioni){
				foreach($Prenotazioni as $Prenotazione){
					$wpdb->delete( EM_TICKETS_BOOKINGS_TABLE, array( 'booking_id' => $Prenotazione->booking_id ), array( '%d' ) );
					$wpdb->delete( EM_BOOKINGS_TABLE, array( 'booking_id' => $Prenotazione->booking_id ), array( '%d' ) );
				}
			}		
			//$Prenotazione = em_get_booking(array('person_id'=>$ID_Utente, 'event_id'=>$Corso->event_id, 'booking_spaces'=>1)); //new booking
			$Prenotazione->event_id=$Corso->event_id;
			$Prenotazione->person_id=$ID_Utente;
			$Biglietto = $Corso->get_bookings()->get_tickets()->get_first();	
			//get first ticket in this event and book one place there. similar to getting the form values in EM_Booking::get_post_values()
			$Iscrizione_Corso = new EM_Ticket_Booking(array('ticket_id'=>$Biglietto->ticket_id, 'ticket_booking_spaces'=>1));
			$Prenotazione->tickets_bookings = new EM_Tickets_Bookings();
			$Prenotazione->tickets_bookings->booking = $Iscrizione_Corso->booking = $Prenotazione;
			$Prenotazione->tickets_bookings->add( $Iscrizione_Corso );
			//Now save booking
			$log->DatiIscrizione=array(
			  "Operazione"		=> "Iscrizione",
			  "IdUtente"		=> $Prenotazione->person_id,
			  "Utente"			=> $Utente->user_login,
			  "IDCorso"			=> $Prenotazione->event_id,
			  "IDPrenotazione"  => $Prenotazione->booking_id,
			  "NPosti"			=> $Prenotazione->booking_spaces,
			  "Stato"			=> $Stato, 
			  "Provenienza"		=> "Admin"
			);			
			if( $Corso->get_bookings()->add($Prenotazione) ){
				if($Stato==1){
					$Prenotazione->set_status(1);
					$result = "Iscrizione Avvenuta";
				}else{
					$result = "Iscrizione in Coda";
				}
				$Messaggi->add_confirm( $Corso->get_bookings()->feedback_message );		
				$Valori['FeedBack']=$Corso->get_bookings()->feedback_message;
				$log->DatiIscrizione["Dati"]= serialize($Valori);
			}else{
				$Messaggi->add_error( $Corso->get_bookings()->get_errors() );			
				$Messaggi->add_confirm( $Corso->get_bookings()->feedback_message );		
				$Errori=$Corso->get_bookings()->get_errors();
				foreach($Errori as $Errore){
					$Valori['FeedBack'].=$Errore."<br />";
				}
				
				//"Impossibile iscrivere l'utente ".$Corso->get_bookings()->feedback_message;
				$log->DatiIscrizione["Dati"]= serialize($Valori);
				$result = $Valori['FeedBack'];
			}
		}else{
			
			$Valori['FeedBack']=get_option('dbem_booking_feedback_already_booked');
			$log->DatiIscrizione=array(
			  "Operazione"		=> "Iscrizione",
			  "IdUtente"		=> $ID_Utente,
			  "Utente"			=> $Utente->user_login,
			  "IDCorso"			=> $Corso->event_id,
			  "IDPrenotazione"  => "Non prenotabile",
			  "NPosti"			=> 0,
			  "Stato"			=> "Operazione non consentita", 
			  "Provenienza"		=> "Admin",
			  "Dati"	    	=> serialize($Valori),
			);			
			$Messaggi->add_error( $Valori['FeedBack'] );
			$result =  $Valori['FeedBack'];
		}
		$log->ScriviLog("Iscrizioni");			
		return $result;
	}	
/**Metodo che estrae il codice del Corsista 
 * @global type $wpdb
 * @param type $IdUser Codice dell'utente
 * @return ID del Corsista
 */	
	public function get_IDCorsista($ID_User){
		global $wpdb;	
		$Sql="SELECT ".$wpdb->table_corsisti.".IDCorsista FROM ".$wpdb->table_corsisti." WHERE IDCorso=%d And IDUser=%d";
		$Corsista=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso,$ID_User), OBJECT );
		return $Corsista[0]->IDCorsista;
	}
	
/**Metodo per la creazione del registor delle presenze al singolo corso
 * @global type $wpdb
 * @param type $IdUser Codice dell'utente
 * @param type $IDIscrizione Codice dell'iscrizione
 * @param type $DateLezioni array con le date delle lezioni
 * @return Mixed   True se la creazione è avvenuta con successo sia per la tabella iscritti che per la tabella presenze; 
 *                 False se la tabella iscritti non può essere creata perchè l'utente è già iscritto, vale sia per la tabella iscritti che per la tabella presenze;
 *                 Messaggio di errore comprensivo di SQL che ha generato l'errore
 */
	public function Create_Register($IdUser,$IDIscrizione,$DateLezioni){
		global $wpdb;
		$StatoOperazioni=array();
		/**
		 * Creazione Iscrizione al corso
		 */
		
		
//		echo $IdUser." - ".$IDIscrizione." - ";		var_dump(array('IDCorso' => $this->ID_corso,'IDUser'=> $IdUser,'IDBooking' =>  $IDIscrizione));
//var_dump($DateLezioni);die();
		if ( false === $wpdb->insert($wpdb->table_corsisti,array('IDCorso' => $this->ID_corso,'IDUser'=> $IdUser,'IDBooking' =>  $IDIscrizione),array('%d','%d','%d'))){
// echo "Sql==".$wpdb->last_query ."    Ultimo errore==".$wpdb->last_error;exit;
			$StatoOperazioni[]= 'RegistroErrore Sql=='.$wpdb->last_query .' Ultimo errore=='.$wpdb->last_error."</br />";
			$IDCorsista=$this->get_IDCorsista($IdUser);
		}else{
			$StatoOperazioni[]=TRUE;
			$IDCorsista=$wpdb->insert_id;
		}
		if(($IDCorsista)>0){
			foreach($DateLezioni as $Data){
				if ($Data[0]=="0000-00-00"){
					$AssMin=(int)get_post_meta($this->ID_post, "_oreOnLine",TRUE);
				}else{
					$AssMin=0;
				}
				if ( false === $wpdb->insert($wpdb->table_presenze,array('IDCorsista' =>$IDCorsista,'DataLezione'=> Funzioni::FormatDataDB($Data[0]),'AssenzaMin' => $AssMin),array('%d','%s','%d'))){
//		 echo "Sql==".$wpdb->last_query ."    Ultimo errore==".$wpdb->last_error;exit;
					$StatoOperazioni[]= 'Errore Sql=='.$wpdb->last_query .' Ultimo errore=='.$wpdb->last_error."</br />";
				}else{
					$StatoOperazioni[]=TRUE;
				}
			}			
		}else{
			foreach($DateLezioni as $Data){
				$StatoOperazioni[]=FALSE;
			}
		}
		return $StatoOperazioni;
	}
	/**
	 *  Metodo per la creazione del registro per singolo utente
	 * @global type $wpdb
	 * @global type $table_prefix
	 * @param type $IDCorsista codice dell'utente/corsista di cui estrarre i dati del registro
	 * @return type
	 */
	public function creaRegistroCorsista($IDUtente){
		global $wpdb,$table_prefix;
		$Sql="SELECT Concat( ".$table_prefix."corsi_corsisti.IDUser,\"_\",DATE(DataLezione)) as ID,".$table_prefix."corsi_corsisti.IDCorsista, Presenza, Note FROM "
				. $table_prefix."corsi_corsisti inner join "
				. $table_prefix."corsi_presenze on "
				. "(".$table_prefix."corsi_corsisti.IDCorsista =".$table_prefix."corsi_presenze.IDCorsista)  WHERE IDCorso=%d And ".$table_prefix."corsi_corsisti.IDUser=".$IDUtente."";
		$RegistroDB=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso), OBJECT_K );
//		echo $wpdb->prepare( $Sql,$this->ID_corso);
//		var_dump($RegistroDB);
		$Registro=array();
		$Date=$this->Lezioni;
		
		$Corsista=$this->Corsisti[$IDUtente];
		$DateLezioni=array();
		foreach ($Date as $Data){
			$D=Funzioni::FormatDataDB($Data[0]);
			$DateLezioni[]= array("Data"     => $D,
								  "Presenza" => $RegistroDB[$Corsista->data->ID."_".$D]->Presenza,
								  "Note"     => $RegistroDB[$Corsista->data->ID."_".$D]->Note);
		}	
//			echo $RegistroDB[$Corsista->data->ID."_".$D]->IDCorsista." - ".$Corsista->data->ID."_".$D."<br />";
		$Registro=array("IDUser"		=> $Corsista->data->ID,
						"IDCorsista"	=> $RegistroDB[$Corsista->data->ID."_".$D]->IDCorsista,				
						"Nome"		    => $Corsista->data->display_name,
						"Lezioni"		=> $DateLezioni);
		return $Registro;
	}	
	/**
	 * Funzione privata per la memorizzazione dei Formatori
	 */
	protected function _memoFormatoriTutor($IDCorso,$Formatori,$Tutor){
		delete_post_meta($IDCorso, '_docenteCorso');
		delete_post_meta($IDCorso, '_tutorCorso');
		if($Formatori){
			foreach($Formatori as $Formatore){
				$Formatore= explode(";", $Formatore);			
				add_post_meta($IDCorso, "_docenteCorso", $Formatore[0]);
			}
		}
		if($Tutor){
			foreach($Tutor as $TutorS){
				$TutorS=explode(";",$TutorS);
				add_post_meta($IDCorso, "_tutorCorso", $TutorS[0]);
			}			
		}
	}
	/**
	 * Metodo per la creazione dell'interfaccia per la gestione dei Formatori/Tutor del corso
	 */
	public function ass_FormatoreTutor(){
		$Funzione= filter_input( INPUT_GET, "mod" );
		switch ($Funzione){
			case "memofor":
				$Formatori= filter_input(INPUT_GET, "Formatori");
				if($Formatori){
					$Formatori= explode(",", $Formatori);
				}else{
					$Formatori="";
				}
				
				$Tutor=filter_input(INPUT_GET,"Tutor");
				if($Tutor){
					$Tutor=explode(",",$Tutor);
				}else{
					$Tutor="";
				}
				$this->_memoFormatoriTutor($this->ID_post,$Formatori,$Tutor);
				break;
		}
?>
<div class="wrap">
	<h2>Gestione Formatori - Tutor Corso: <?php echo $this->Nome_Corso;?></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2" style="font-size:1.3em;"><i class="fas fa-hand-point-left"></i> Torna indietro</a>
	</div>
	<div  class="container">
		<form method="get" action="?page=corsi" id="FormatoriTutor">
			<input type="hidden" name="page" value="corsi"/>
			<input type="hidden" name="op" value="assformtutor"/>
			<input type="hidden" name="secur" value="<?php echo wp_create_nonce("TutorFormAss");?>"/>	
			<input type="hidden" name="mod" value="memofor"/>
			<input type="hidden" name="event_id" value="<?php echo $this->ID_corso;?>"/>
			<input type="hidden" name="post_id" value="<?php echo $this->ID_post;?>"/>
				<h3>Memorizza Formatori
					<button class="button" id="ButtonSubmitFormatori" style="vertical-align:middle;color:#007fff;width:100px;margin-left:20px;">
						<span><i class="fa fa-user-plus fa-2x" aria-hidden="true"></i></span>
					</button> 
				</h3>
			<div class="step" id="FormatoriTutor">
				<h3 class="title">Formatori disponibili</h3>
<?php				echo $this->crea_Lista_FormatoriTutor(TRUE,TRUE,"div");?>
			</div>
			<div class="step" id="Docenti">
				<h3 class="title">Formatori assegnati</h3>
<?php			echo $this->crea_Lista_FormatoriTutor(FALSE,TRUE,"div");?>
			</div>
			<div class="step" id="Tutor">
				<h3 class="title">Tutor Assegnati</h3>	
<?php			echo $this->crea_Lista_FormatoriTutor(FALSE,FALSE,"div");?>
			</div>
		</form>
	</div>
</div>
<?php		
	}
	public function creaRegistroArgomenti(){
		global $wpdb,$table_prefix;
		$Sql="SELECT DataLezione, Argomenti FROM "
				. $wpdb->table_lezioni ." WHERE IDCorso=%d ORDER BY DataLezione";
		return $wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso), OBJECT_K );
//		echo $wpdb->prepare( $Sql,$this->ID_corso);		
	}
	
	public function get_OreLezioniPianificate($Ret=""){
		$OreLezione=array();
//		var_dump($this->Lezioni);
		$TotMin=0;
		if (is_array($this->Lezioni)){
			foreach($this->Lezioni as $Lezione){
				$MinLezione=FUNZIONI::differenzaOra_Minuti($Lezione[1], $Lezione[2]);
				if(isset($Lezione[3]) And isset($Lezione[4])){
					$MinLezione+=FUNZIONI::differenzaOra_Minuti($Lezione[3], $Lezione[4]);
				}
				$TotMin+=$MinLezione;
				$OreLezione[$Lezione[0]]=$MinLezione;
			}			
		}else{
			$TotMin=$OreLezione=0;
		}
		
		if($Ret=="TotOre"){
			return $TotMin;
		}else{
			return $OreLezione;
		}
	}
	/**
	 *  Metodo che permette a creazione del registro per poi visualizzarlo in creaRegistro
	 * @global type $wpdb
	 * @global type $table_prefix
	 */
	public function creaRegistro($IdUtente=0){
		global $wpdb,$table_prefix;
		if($IdUtente!=0){
			$FiltroUtente=" And ".$table_prefix."corsi_corsisti.IDUser=".$IdUtente." ";
		}else{
			$FiltroUtente="";
		}
		$Sql="SELECT Concat( ".$table_prefix."corsi_corsisti.IDUser,\"_\",DATE(DataLezione)) as ID,".$table_prefix."corsi_corsisti.IDCorsista, Presenza, Note,AssenzaMin FROM "
				. $table_prefix."corsi_corsisti inner join "
				. $table_prefix."corsi_presenze on "
				. "(".$table_prefix."corsi_corsisti.IDCorsista =".$table_prefix."corsi_presenze.IDCorsista)  WHERE IDCorso=%d $FiltroUtente";
		$RegistroDB=$wpdb->get_results( $wpdb->prepare( $Sql,$this->ID_corso), OBJECT_K );
//	echo $wpdb->prepare( $Sql,$this->ID_corso);
//		var_dump($RegistroDB);
		$Registro=array();
		$Date=unserialize(get_post_meta( $this->ID_post, '_lezioniCorso',TRUE));
/*		if($this->OreOnLineIndividualizzate){
			$Date[]=array("0000-00-00");
		}
*/
		if($this->AttivitaNP){
			for($i=0;$i<count($this->AttivitaNP);$i++){
				if($this->AttivitaNP[$i][2]=="Si"){
					$Date[]=array($i."0/00/0000");
				}
			}
		}
		foreach($this->Corsisti as $Corsista){
			if($IdUtente==0 Or $IdUtente==$Corsista->data->ID){
				$DateLezioni=array();
//				$D="0000-00-00";
				foreach ($Date as $Data){
/*					if($Data[0]=="0000-00-00"){
						$D="0000-00-00";
					}else{
*/						$D=Funzioni::FormatDataDB($Data[0]);					
//					}
					$DateLezioni[]= array("Data"     => $D,	 
										  "AssenzaMin" => $RegistroDB[$Corsista->data->ID."_".$D]->AssenzaMin,
										  "Presenza" => $RegistroDB[$Corsista->data->ID."_".$D]->Presenza,
										  "Note"     => $RegistroDB[$Corsista->data->ID."_".$D]->Note);
				}	
	//			var_dump($DateLezioni);
	//			echo $RegistroDB[$Corsista->data->ID."_".$D]->IDCorsista." - ".$Corsista->data->ID."_".$D."<br />";
				$Registro[]=array("IDUser"		=> $Corsista->data->ID,
								  "IDCorsista"	=> $RegistroDB[$Corsista->data->ID."_".$D]->IDCorsista,				
								  "Nome"		=> Funzioni::NomeUtente($Corsista->data->ID),
								  "Lezioni"		=> $DateLezioni);
	//			var_dump($Registro);die();

			}				
			}

		$Registro=Funzioni::SortArray($Registro, "Nome");
		return $Registro;
	}
	public function set_Presenza($IdCorsista,$Data,$Stato=Null){
		global $wpdb;
		$risultato=($wpdb->update($wpdb->table_presenze, array("Presenza" => $Stato), 
							 array( 'IDCorsista' => $IdCorsista, 'DataLezione' => $Data), 
							 array("%d"),
				             array( '%d', '%s')));
		if (!$risultato){
			return  $wpdb->last_query;
		}
		return $risultato;
	}
	public function set_Nota($IdCorsista,$Data,$Note=Null){
		global $wpdb;
		$risultato=($wpdb->update($wpdb->table_presenze, array("Note" => ($Note==""?Null:$Note)), 
							 array( 'IDCorsista' => $IdCorsista, 'DataLezione' => $Data), 
							 array("%s"),
				             array( '%d', '%s')));
		if (!$risultato){
			return  $wpdb->last_query;
		}
		return $risultato;
	}	
	public function set_AssenzaMinuti($IdCorsista,$Data,$Minuti){
		global $wpdb;
		$risultato=($wpdb->update($wpdb->table_presenze, array("AssenzaMin" => $Minuti), 
							 array( 'IDCorsista' => $IdCorsista, 'DataLezione' => $Data), 
							 array("%d"),
				             array( '%d', '%s')));
		if (!$risultato){
			return  $wpdb->last_query;
		}
		return $risultato;
	}	
	public function set_OreOnLine($IdCorsista,$OreOL,$Data){
		global $wpdb;
		$risultato=($wpdb->update($wpdb->table_presenze, array("AssenzaMin" => $OreOL), 
							 array( 'IDCorsista' => $IdCorsista, 'DataLezione' => $Data), 
							 array("%d"),
				             array( '%d', '%s')));
		if (!$risultato){
			return  $wpdb->last_query;
		}
		return $risultato;
	}	
	public function set_Argomenti($Data,$Argomenti=Null){
		global $wpdb;
//		echo $Data."  ".$Argomenti;
		$risultato=($wpdb->update($wpdb->table_lezioni, array("Argomenti" => ($Argomenti==""?Null: wp_kses_post( $Argomenti ))), 
							 array( 'IDCorso' => $this->ID_corso, 'DataLezione' => Funzioni::FormatDataDB($Data)), 
							 array("%s"),
				             array( '%d', '%s')));
//		echo $wpdb->last_query;
		if (!$risultato){
			return  $wpdb->last_query;
		}
		return $risultato;
	}	
	public function get_Argomenti($Data){
		global $wpdb;
		$Sql="SELECT  Argomenti FROM $wpdb->table_lezioni WHERE IDCorso=%d AND DataLezione=%s";
		$Sql=$wpdb->prepare($Sql,$this->ID_corso,Funzioni::FormatDataDB($Data));
		$risultato=$wpdb->get_results($Sql);		
		if (!$risultato){
			return  $wpdb->last_query;
		}
		return $risultato[0];
	}	
}
