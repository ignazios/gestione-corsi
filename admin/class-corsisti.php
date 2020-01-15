<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!class_exists('WP_List_Table')) {
 require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}
/**
 * Description of class_corsisti
 *
 * @author ignaz
 */
class class_corsisti  extends WP_List_Table{
	//put your code here
	function __construct() {
		parent::__construct(array('singular'=>'Cliente','plural'=>'Clienti'));
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
		usort($records, $this->build_sorter($field,$reverse));
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
    $orderby = $_REQUEST['orderby']; else $orderby = 'cognome';

    if (isset($_REQUEST['order'])
        and in_array($_REQUEST['order'],array('asc','desc')))
    $order = $_REQUEST['order']; else $order = 'asc';

    // Calcolo le variabili che contengono il numero dei record totali
    // e l'elenco dei record da visualizzare per una singola pagina
	$Utenti=new Utenti();
	$DaFiltrare="";
	if($Utenti->is_Organizzatore( get_current_user_id())){
		$DaFiltrare=$Utenti->get_CorsiOrganizzati(get_current_user_id(),"event_id");
		$DaFiltrare=" And ".EM_BOOKINGS_TABLE.".event_id in(".implode(",",$DaFiltrare).")";
	}	
	$DatiCorsisti=array();
	$Sql="SELECT DISTINCT person_id "
			. "FROM ".EM_BOOKINGS_TABLE." "
			. "WHERE 1 $DaFiltrare";
//	echo $Sql;
	$Corsisti=$wpdb->get_results($Sql, ARRAY_A);
	$Scuole=new Scuole();
	foreach($Corsisti as $Corsista){
		
		$user_info = get_userdata($Corsista['person_id']);
		$Scuola=$Scuole->get_Scuola(get_user_meta($Corsista['person_id'], "Scuola", TRUE));
		$Salta=False;
		if (isset($_REQUEST['s']) and !empty($_REQUEST['s'])){
			if(isset($_REQUEST['k']) and !empty($_REQUEST['k'])){
				$Campo=$_REQUEST['k'];	
				switch ($_REQUEST['k']){
					case "Cognome":
						if (strtolower($_REQUEST['s'])!= strtolower($user_info->last_name)){
							$Salta=TURE;
						}
						break;
					case "Nome":
						if (strtolower($_REQUEST['s'])!= strtolower($user_info->first_name)){
							$Salta=TURE;
						}
						break;			  
					case "Email":
						if (strtolower($_REQUEST['s'])!= strtolower($user_info->user_email)){
							$Salta=TURE;
						}
						break;			  
					case "Scuola":
//						echo $Scuola." ".$_REQUEST['s']." ".stristr($Scuola,$_REQUEST['s'])."<br />";
						if (strlen(stristr($Scuola,$_REQUEST['s']))==0){
							$Salta=TURE;
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
		$DatiCorsisti[]=array(  'IDUser'		=> "<a href=\"". home_url()."/wp-admin/user-edit.php?user_id=".$Corsista['person_id']."\" title=\"Modifica l'utente\">".$Corsista['person_id']."</a>",
								'cognome'		=> ucfirst(strtolower($user_info->last_name)),
								'nome'			=> ucfirst(strtolower($user_info->first_name)),
								'email'			=> strtolower($user_info->user_email),
						        'scuola'		=> $Scuola,
								'numcorsi'		=> $NumC,
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

  function get_columns()
  {
	$NumC="<table>"
		. "<tr>"
		. "	<td><i class=\"fa fa-spinner fa-pulse fa-fw\"></i></td>"
		. "	<td><i class=\"fa fa-handshake-o\" aria-hidden=\"true\" style=\"color:green;\"></i></td>"
		. "	<td><i class=\"fa fa-hand-paper-o\" aria-hidden=\"true\" style=\"color:red;\"></i></td>"
		. "	<td><i class=\"fa fa-eraser\" aria-hidden=\"true\" style=\"color:red;\"></i></td>"
		."<tr>"
		. "</table>";
    $columns = array(
      'IDUser'          => 'ID Utente',
      'cognome'         => 'Cognome',
      'nome'			=> 'Nome',
	  'email'			=> 'Email',
	  'scuola'			=> 'Scuola',
	  'numcorsi'		=> 'Numero Corsi<br />'.$NumC,
      'funzioni'		=> 'Operazioni',
    );
    return $columns;
  }

  // Funzione per la definizione dei campi che possono
  // essere utilizzati per eseguire la funzione di ordinamento

  function get_columns_sortable()
  {
    $sortable_columns = array(
      'cognome'       => array('cognome',true),
      'nome'          => array('nome',true),
	  'email'         => array('email',true),
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

}
