<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * Description of class_corsisti
 *
 * @author ignazio
 */
class class_NewsLetter{
	
	/**
	 *  Variabile privata che memorizza lo stato di attivazione di Alo Easy Mail
	 * @var type Boolean
	 */
	protected $Attiva;
	
	public function get_Stato(){
		return $Attiva;
	}
	
	/**
	 * Costruttore
	 */
	function __construct() {
		if ( function_exists("alo_em_get_mailinglists")){
			$this->Attiva=TRUE;
		}else{
			$this->Attiva=FALSE;
		}
	}
	/**
	 * Metodo protetto che visualizza il messaggio che Alo Easy Mail non è installato
	 */
	protected function display_NO_AloEasyMail(){
		echo '	<div class="wrap">
					<h2>Gestione Comunicazioni</h2>
					<div class="tornaindietro">
						<a href="'.site_url().'/wp-admin/admin.php?page=corsi" class="add-new-h2">Torna indietro</a>
					</div>
					<div class="welcome-panel">
						<p>... purtroppo non posso continuare, non risulta installato il plugin Alo Easy Mail </p>
						<p>Se vuoi installarlo, lo trovi sul repository di Wordpress.org al seguente <a href="https://wordpress.org/plugins/alo-easymail/" title="link al sito wordpress.org dove si può scaricare il plugin Alo Easy Mail">Link</a>
					</div>';
	}
	
	private function get_NumMailinListCorso($Corso){
		if ( function_exists("alo_em_get_mailinglists")){
			$Stato=0;
			$mailinglists = alo_em_get_mailinglists ( 'hidden,admin,public' );
			$ml = stripslashes(sanitize_text_field( $Corso->get_CodiceCorso() ));
			if ( empty($mailinglists) ) { 
				$Stato=1;
			}else{
				$Trovata=FALSE;
				foreach ($mailinglists as $Key =>$mailinglist){
					if($mailinglist['name']['it']==$ml){
						$Trovata=TRUE;
						$IndexML=$Key;
						break;
					}
				}
				if(!$Trovata){
					$Stato=3;			
				}else{
					$Stato=array(0,$IndexML);	
				}
			}
			return $Stato;
		}else{
			return 2;
		}
	}
	
	private function get_NewsLetter($Corso){
		global $wpdb;
		$ListaML=array();
		$StatoRet=$this->get_NumMailinListCorso($Corso);
		if( is_array( $StatoRet)){
			$IdML=$StatoRet[1];
		} else {
			echo "Nessuna MailingList definita per questo corso";
			return FALSE;
		}	
		$NewsLetters=$wpdb->get_results( "SELECT post_id,meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key='_easymail_recipients'" );
		foreach($NewsLetters as $NewsLetter){
			$Liste= unserialize($NewsLetter->meta_value);
			$Trovato=FALSE;
			if(isset($Liste['list'])){
				foreach($Liste['list'] as $Lista){
					if($Lista==$IdML){
						$StatoNL=get_post_status( $NewsLetter->post_id );
						if($StatoNL!="inherit" And $StatoNL!="trash" )
							$ListaML[]=$NewsLetter->post_id;
					}
				}
			}
		}
		return $ListaML;
	}
	private function get_Sottoscrittori($IdML){
		global $wpdb;
		$SottML=array();
		$Sottoscrittori=$wpdb->get_results( "SELECT name,email,lists FROM {$wpdb->prefix}easymail_subscribers WHERE 1" );
		foreach($Sottoscrittori as $Sottoscrittore){
			$Liste=explode("|",$Sottoscrittore->lists);
			if( in_array( $IdML, $Liste )){
				$SottML[]=$Sottoscrittore;
			}
		}
		return $SottML;
	}

	public function menu_Principale_NewsLetter($Corso){
		if(!$this->Attiva){
			$this->display_NO_AloEasyMail();
			return;
		}
?>
<div class="wrap">
	<h2>Gestione Nesletter Corso: <?php echo $Corso->get_NomeCorso();?></h2>
	<div class="tornaindietro" style="margin-bottom:10px;">
		<a href="<?php echo site_url().'/wp-admin/admin.php?page=corsi';?>" class="add-new-h2">Torna indietro</a>
	</div>
	<div id="welcome-panel" class="welcome-panel">
<?php		
		$StatoRet=$this->get_NumMailinListCorso($Corso);
		if( is_array( $StatoRet)){
			$Stato=$StatoRet[0];
			$Sottoscrittori=$this->get_Sottoscrittori($StatoRet[1]);
		} else {
			$Stato=$StatoRet;
		}	
		if(count($Sottoscrittori)==0){
			echo "<p><strong><em>Per proseguire con la creazione della NewsLetter bisogna assegnare i corsisti alla Mailing List, se sei un amministratore la puoi creare direttamente dal menu altrimenti devi chiedere ad un amministratore</em></strong></div>"
			. "</div>";
			return;			
		}
		If(($NLC=$this->get_NewsLetter($Corso))===FALSE){
			echo "<p><strong><em>Per proseguire con la creazione della NewsLetter bisogna creare la Mailing List, se sei un amministratore la puoi creare direttamente dal menu altrimenti devi chiedere ad un amministratore</em></strong></div>"
			. "</div>";
			return;
		}
			switch($Stato){
				case 1:	echo "<p>Non risulta installato Alo Easy Mail</p>";
					break;
				case 2: echo "<p>Non risultano MailingList create</p>";
					break;
				case 3: echo "<p>Non hai ancora creato la MailingList per questo corso</p>";
					break;
			}
?>
		<div id="tabsnl">
			<ul>
			  <li><a href="#tabs-1">Crea News Letter</a></li>
			  <li><a href="#tabs-2">Gestisci NewsLetter</a></li>
			</ul>
			<div id="tabs-1">
				<form method="get" action="" id="AllineaCorsisti">
					<input type="hidden" name="page" value="corsi"/>
					<input type="hidden" name="op" value="generanewsletter"/>
					<input type="hidden" name="event_id" value="<?php echo $Corso->get_IDCorso();?>"/>
					<input type="hidden" name="secur" value="<?php echo wp_create_nonce( 'CreaNewsLetter' );?>" />
					<button class="button" id="ButtonSubmit" style="vertical-align:middle;height: 3em;">
						<i class="fa fa-envelope-open fa-2x" aria-hidden="true"></i> Crea NewsLetter per questo corso
					</button> 
				</form>		
					<ul>
<?php
				foreach($Sottoscrittori as $Sottoscrittore){
					echo "<li>Utente: <strong>".$Sottoscrittore->name."</strong> Email: <strong>".$Sottoscrittore->email."</strong></li>";
				}
?>					
					</ul>
			</div>
			<div id="tabs-2">
				<div class='table-wrap'>
					<table class="widefat elenco">
						<tr>
							<th>Titolo/Oggetto</th>
							<th>Destinatari</th>
							<th>Stato</th>
							<th>Iniziata</th>
							<th>Autore</th>
						</tr>
<?php
			$ElencoNL=$this->get_NewsLetter($Corso);
			global $user_ID;
			foreach($ElencoNL as $ML){
				$recipients = alo_em_get_recipients_from_meta( $ML );
				$status = alo_em_get_newsletter_status($ML);
				$MsgRecipiente="";
				if ( $status == '' && empty( $recipients['total'] ) && empty( $recipients['estimated_total'] ) ) {
					if ( alo_em_user_can_edit_newsletter( $ML) ) $MsgRecipiente.= '<a href="'. get_edit_post_link( $ML ) . '">';
					$MsgRecipiente.= '<img src="'. ALO_EM_PLUGIN_URL. '/images/12-exclamation.png" alt="" /> <strong class="easymail-column-no-yet-recipients-'.$user_ID.'">' . __( 'No recipients selected yet', "alo-easymail").'</strong>';
					if ( alo_em_user_can_edit_newsletter( $ML ) ) $MsgRecipiente.= '</a>';
				} else {
					if ( $status == '' And alo_em_user_can_edit_newsletter( $ML ) ) $MsgRecipiente.= "<a href='#' class='easymail-toggle-short-summary' rel='{$ML}'>";
					$MsgRecipiente.= __( 'Total recipients', "alo-easymail") .": ";
					$MsgRecipiente.= alo_em_count_recipients_from_meta( $ML );

					if ( $status == '' And alo_em_user_can_edit_newsletter( $ML ) ) {
						$MsgRecipiente.= "</a><br />\n";
						//$MsgRecipiente.= "<div id='easymail-column-short-summary-{$post->ID}' class='easymail-column-short-summary'>\n". alo_em_recipients_short_summary ( $recipients ) ."</div>\n";
					}
				}
				$user_info = get_userdata(get_post_field( 'post_author', $ML ));
				$LinkNL=admin_url().'post.php?post='.$ML.'&action=edit';
				$NL = get_post($ML); 
				$current_user = wp_get_current_user();
				if($NL->post_author!=$current_user->ID){
					$LinkNL= get_permalink( $ML);
				}
 ?>					
					<tr>
						<td><a href="<?php echo $LinkNL;?>" title="Apri la NewsLetter" style="color:#5b9dd9;"><?php echo get_the_title($ML);?></a>
						<td><?php echo $MsgRecipiente;?></td>
						<td> 
<?php
							if ( $status == '' ) {
								echo "NewsLetter non ancora inviata";
							}else{
								echo __($status,"alo-easymail"); 
							}							
						?></td>
						<td><span class="entry-date"><?php echo get_the_date('d/m/Y H:i:s', $ML); ?></span>
						<td><?php echo $user_info->display_name; ?></td>
					</tr>
<?php
				}
?>					
					</table>
				</div>	
			</div>
		</div>
	</div>
<?php
	}
	/**
	 * Metodo per creare la newsletter per uno specifico corso
	 * viene associata la lista con lo stesso codice del corso passato per parametro
	 * @param type $IdCorso
	 * @return type Boolean TRUE se la newsletter è stata creata altrimenti FALSE
	 */
	public function create_newsletter($Corso){
		if(!$this->Attiva){
			$this->display_NO_AloEasyMail();
			return;
		}
//		var_dump($this);return;
		echo '	<div class="wrap">
					<h2>Creazione NewsLetter corso: <em>'.$Corso->get_NomeCorso().'</em></h2>
					<h3>Codice Corso: <em>'.$Corso->get_CodiceCorso().'</em></h3>
					<div class="tornaindietro">
						<a href="'.site_url().'/wp-admin/admin.php?page=corsi" class="add-new-h2">Torna indietro</a>
					</div>
					<div class="welcome-panel">';
		$StatoRet=$this->get_NumMailinListCorso($Corso);
		if( is_array( $StatoRet)){
			$Stato=$StatoRet[0];
			$IndexML=$StatoRet[1];
		} else {
			$Stato=$StatoRet;
		}
		if ($Stato==0){
			$my_post = array(
					'post_title'    => $Corso->get_NomeCorso(),
					'post_content'  => "<div><p>Ciao [USER-NAME]</p>..........</div>
			<p>Sito: [SITE-NAME]</p>
			<p>Corso: <a href='".$Corso->get_Permalink()."'>Link</a> ",
					'post_status'   => 'publish',
					'comment_status'   => 'closed',
					'ping_status' => 'closed',
					'post_author' => get_current_user_id(),
					'post_name' => $Corso->get_NomeCorso(),
					'post_type' => 'newsletter');
			$nl_id =wp_insert_post( $my_post,$errore );
			if($nl_id>0){
				$recipients=Array();
				$recipients['list'][] = strval($IndexML);
				$recipients['lang'][] = 'it';
				$recipients['lang'][] = 'UNKNOWN';
				add_post_meta ( $nl_id, "_easymail_recipients", $recipients );	
				add_post_meta ( $nl_id, "_placeholder_easymail_post",  $Corso->get_IDCorso());	
				add_post_meta ( $nl_id, "_placeholder_post_imgsize", 'thumbnail' );	
				add_post_meta ( $nl_id, "_placeholder_newsletter_imgsize", 'thumbnail' );	
				add_post_meta ( $nl_id, "_easymail_theme", 'campaignmonitor_elegant.html' );	
				if(function_exists("alo_em_count_recipients_from_meta")){
						alo_em_count_recipients_from_meta ( $nl_id);
				}
				echo "<p style='font-weight: bold;font-size: medium;color:green;'>NewsLetter Creata correttamente</p>
				<p style='font-weight: bold;font-style: italic;font-size: medium;'>Adesso dovete completare le operazioni di invio seguendo pochi e semplici passi:
				<ul style='list-style: circle outside;margin-left:20px;'>
					<li>Selezionare la gestione delle NewsLetter</li>
					<li>Controllare la NewsLetter appena creata puoi utilizzare il seguente <a href=\"". admin_url()."post.php?post=$nl_id&action=edit\"><span style=\"text-size:1.5em\">link</span></a></li>
					<li>Dall'elenco delle NewsLetter, sulla riga relativa alla NewsLetter cliccare su <em>Richiesto: Crea la lista dei destinatari</em></li>
				</ul>
				</p>";
				add_post_meta ( $Corso->get_CodiceCorso(), "_sendNewsLetter",date("d/m/y g:i O"));
			}else{
				echo "<p  style='font-weight: bold;font-size: medium;color:red;'>NewsLetter Non Creata correttamente, errore riportato:</p>";
						print_r($errore);			
			}			
		}else{
			echo "<p style='font-weight: bold;font-size: medium;color:red;'>Creazione NewsLetter Annullata</p> ";
			switch($Stato){
				case 1:	echo "<p>Non risulta installato Alo Easy Mail</p>";
					break;
				case 2: echo "<p>Non risultano MailingList create</p>";
					break;
				case 3: echo "<p>Non hai ancora creato la MailingList per questo corso</p>";
					break;
			}
		}
		echo "		</div>"
			. "</div>";
		return ($Stato==0?TRUE:FALSE);
	}

}
