<?php

/**
 * Classe che permette di gestire i corsisti di un corso
 *
 * @author ignaz
 */
if (!class_exists('WP_List_Table')) {
 require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}
/**
 * Description of class_corsisti
 *
 * @author ignaz
 */
class Corsisti_corso  extends WP_List_Table{
	
	private $ID_corso;	
	private $Consolidato=False;
	
	function __construct($ID_corso) {
		$this->ID_corso=$ID_corso;
		$Corso=new Gestione_Corso($this->ID_corso);
		if(FUNZIONI::CorsoConsolidato($Corso)){
			$this->Consolidato=TRUE;
		}
		parent::__construct(array('singular'=>'Corsista','plural'=>'Corsisti'));
	}
 // Funzione per la preparazione dei campi da visualizzare
  // e la query SQL principale che deve essere eseguita 

	protected function build_sorter($key,$reverse) {
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

	protected function record_sort($records, $field, $reverse=false)
	{
		if($records){
			usort($records, $this->build_sorter($field,$reverse));
		}
		return $records;
	}

	public function prepare_items()
	{
		global $wpdb;
		$per_page = 25; // Numero dei record presenti in una pagina

		// Calcolo elenco de dei campi per le differenti
		// sezioni e memorizzo tutto in array separati

		$columns  = $this->get_columns();
		$hidden   = $this->get_columns_hidden();
		$sortable = $this->get_columns_sortable();

		// Bisogna memorizzare tre array che devono contenere i campi da 
		// visualizzare, quelli nascosti e quelli per eseguire l'ordinamento

		$this->_column_headers = array($columns,$hidden,$sortable);

		// Preparazione delle variabili che devono essere utilizzate
		// nella preparazione della query con gli ordinamenti e la posizione

		if (!isset($_REQUEST['paged'])) $paged = 0;
		  else $paged = max(0,(intval($_REQUEST['paged'])-1)*25);

		if (isset($_REQUEST['orderby'])
			and in_array($_REQUEST['orderby'],array_keys($sortable)))
		$orderby = $_REQUEST['orderby']; else $orderby = 'corsista';

		if (isset($_REQUEST['order'])
			and in_array($_REQUEST['order'],array('asc','desc')))
		$order = $_REQUEST['order']; else $order = 'asc';

		$Corso = new Gestione_Corso($this->ID_corso);
		$Corsisti=array();
		foreach($Corso->get_Corsisti() as $key => $value){
			$Corsisti[]['person_id']=$key;
		}
		
		
		$Scuole=new Scuole();
		foreach($Corsisti as $Corsista){

			$user_info = get_userdata($Corsista['person_id']);
			$Scuola=$Scuole->get_Scuola(get_user_meta($Corsista['person_id'], "Scuola", TRUE));
			$Salta=False;
			if (isset($_REQUEST['s']) and !empty($_REQUEST['s'])){
				if(isset($_REQUEST['k']) and !empty($_REQUEST['k'])){
					$Campo=$_REQUEST['k'];	
					switch ($Campo){
						case "Corsista":
							if (strpos(strtolower($user_info->last_name . " ".$user_info->first_name), strtolower($_REQUEST['s'])) === false){
								$Salta=TRUE;
							}
							break;  
						case "Email":
							if (strtolower($_REQUEST['s'])!= strtolower($user_info->user_email)){
								$Salta=TRUE;
							}
							break;			  
						case "Scuola":
	//						echo $Scuola." ".$_REQUEST['s']." ".stristr($Scuola,$_REQUEST['s'])."<br />";
							if (strpos(strtolower($Scuola), strtolower($_REQUEST['s'])) === false){
								$Salta=TRUE;
							}
							break;		
					}
			  }
			}
			if($Salta){
				continue;
			}
			$Utente=new Utenti($Corsista['person_id']);
			$CorUt=$Utente->get_Stato_Iscrizioni();
			$NumC="<table>"
					. "<tr>";
				foreach($CorUt as $StatoCorsoUtente)
					$NumC.= "	<td style=\"width:18px;\">".$StatoCorsoUtente."</td>";
			$NumC.= "<tr>"
					. "</table>";
			$email=$user_info->user_email;
	/*	if(FUNZIONI::email_exist( $user_info->user_email)){
			$email=$user_info->user_email;
		}else{
			$email="<span class=\"SemaforoRosso\">".$user_info->user_email."</span>";
		}
	*/		$GestCorsi="<a href=\"". home_url()."/wp-admin/edit.php?post_type=event&page=events-manager-bookings&person_id=".$Corsista['person_id']."\" title=\"Gestisci i corsi dell'utente\"><i class=\"fa fa-eye\" aria-hidden=\"true\"></i> Gestione Corsi</a>";
			$DatiCorsisti[]=array(  'IDUser'		=> $Corsista['person_id'],
									'corsista'		=> "<a href=\"". home_url()."/wp-admin/user-edit.php?user_id=".$Corsista['person_id']."\" title=\"Modifica l'utente\">".ucfirst(strtolower($user_info->last_name))." ". ucfirst(strtolower($user_info->first_name))."</a>",
									'email'			=> strtolower($user_info->user_email),
									'scuola'		=> $Scuola,
									'funzioni'		=> $GestCorsi);
		}
		if($order=='asc'){
			$DatiCorsisti=$this->record_sort($DatiCorsisti,$orderby);
		}else{
			$DatiCorsisti=$this->record_sort($DatiCorsisti,$orderby,TRUE);
		}
		$CorsistiFiltrati=array();
	//	echo $paged."  ".$per_page."  ".count($DatiCorsisti);
		for($i=$paged;$i<$per_page+$paged And $i<=count($DatiCorsisti);$i++){
			$CorsistiFiltrati[]=$DatiCorsisti[$i];
		}
		$this->items = $CorsistiFiltrati;
		$total_items = count($DatiCorsisti);
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
	  ));
	}

  // Funzione per la definizione dei campi che devono
  // essere visualizzati nella lista da visualizzare

	function get_columns(){
		$columns = array(
			'cb'            => '<input type="checkbox"/>',
			'corsista'		=> 'Corsista',
			'email'			=> 'Email',
			'scuola'		=> 'Scuola',
			'funzioni'		=> 'Operazioni');
		return $columns;
	}

  // Funzione per la definizione dei campi che possono
  // essere utilizzati per eseguire la funzione di ordinamento

	function get_columns_sortable(){
		$sortable_columns = array(
		  'corsista'      => array('corsista',true),
		  'mail'          => array('mail',true),
		  'scuola'		  => array('scuola',false),
		);
		return $sortable_columns;
	}	

  // Funzione per la definizione dei campi che devono 
  // essere calcolati dalla query ma non visualizzati

	function get_columns_hidden() {
		return array();
	}

  // Funzione per reperire il valore di un campo in
  // maniera standard senza una personalizzazione di output

	function column_default($item,$column_name) { 
		return $item[$column_name]; 
	}
	// Funzione per la prima colonna che non sarà più l'ID del Corsista
	// ma un campo di checkbox per la selezione

	function column_cb($item) {
		return sprintf('<input type="checkbox" name="IDUser[]" value="%s"/>',$item["IDUser"]);
	}
	
	// Definire la nuova funzione per indicare le
	// azioni che devo essere presenti sul menu a tendina

	function get_bulk_actions() {
	  	$Azioni= array('mail_corsisti'	=> 'Mail Corsisti','invio_compiti' => 'Invio Compiti');
		if($this->Consolidato){
			$Azioni['invio_attestati']= "Invio Attestati di Frequenza";
			$Azioni['scarica_attestati']= "Scarica Attestati di Frequenza";
		}
		return $Azioni;
	}
}
