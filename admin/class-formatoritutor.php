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
 * Classe per la gestione dei Formatori Tutor
 *
 * @author ignaz
 */

/**
 * Description of class_corsisti
 *
 * @author ignaz
 */
class class_formatoritutor extends WP_List_Table{
	
	
	public function get_MyCorsi(){
		global $wpdb;
		$SqlElenco="SELECT $wpdb->postmeta.post_id "
					. "FROM $wpdb->postmeta "
					. "WHERE ($wpdb->postmeta.meta_key=\"_docenteCorso\" or $wpdb->postmeta.meta_key=\"_tutorCorso\") AND  $wpdb->postmeta.meta_value=%d";
		$ResultSet=$wpdb->get_results($wpdb->prepare($SqlElenco,get_current_user_id()),OBJECT );
		$NLFiltrate=array();
		foreach($ResultSet as $NL){
			$NLFiltrate[]=$NL->post_id;
		}
		$NLFiltrate=implode(",",$NLFiltrate);
		$SqlElenco="SELECT $wpdb->postmeta.meta_value "
					. "FROM $wpdb->postmeta "
					. "WHERE $wpdb->postmeta.post_id in(".$NLFiltrate.") And $wpdb->postmeta.meta_key=\"_codiceCorso\"";
		$ResultSet=$wpdb->get_results($SqlElenco,OBJECT );
		$NLFiltrate=array();
		foreach($ResultSet as $NL){
			$NLFiltrate[]=$NL->meta_value;
		}
		return($NLFiltrate);
	}
	
	function __construct() {
		parent::__construct(array('singular'=>'Formatore/Tutor','plural'=>'Formatori/Tutors'));
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
    $orderby = $_REQUEST['orderby']; else $orderby = 'cognomenome';

    if (isset($_REQUEST['order'])
        and in_array($_REQUEST['order'],array('asc','desc')))
    $order = $_REQUEST['order']; else $order = 'asc';

    // Calcolo le variabili che contengono il numero dei record totali
    // e l'elenco dei record da visualizzare per una singola pagina
	$DatiFormTut=array();
	$args = array(	'role__in' => array("docente_corsi"),
					'meta_key' => 'last_name',
					'orderby'      => 'meta_value',
					'order'        => 'ASC',
					'fields'       => 'ID',
					'who'          => ''
				 );	
	//			 var_dump($args);
	$FormTuts  = get_users( $args );
	$Scuole=new Scuole();
	$Utenti=new Utenti();
	$DaFiltrare="";
	if($Utenti->is_Organizzatore( get_current_user_id())){
		$DaFiltrare=$Utenti->get_CorsiOrganizzati(get_current_user_id());
		$DaFiltrare=" And ".$wpdb->posts.".ID in(".implode(",",$DaFiltrare).")";
	}	
	foreach($FormTuts as $FormTut){
		$user_info = get_userdata($FormTut);
		$Scuola=$Scuole->get_Scuola(get_user_meta($FormTut, "Scuola", TRUE));
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
		$SqlElenco="SELECT $wpdb->posts.post_title "
					. "FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID=$wpdb->postmeta.post_id "
					. "WHERE $wpdb->postmeta.meta_key=\"%s\" $DaFiltrare AND  $wpdb->postmeta.meta_value=%d";
		$Sql="SELECT COUNT(*) "
				. "FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID=$wpdb->postmeta.post_id "
				. "WHERE $wpdb->postmeta.meta_key=\"%s\" $DaFiltrare AND  $wpdb->postmeta.meta_value=%d";
		$NumCDoc=$wpdb->get_var($wpdb->prepare($Sql,array("_docenteCorso",$FormTut)));
		$NumCTut=$wpdb->get_var($wpdb->prepare($Sql,array("_tutorCorso",$FormTut)));
		if($NumCDoc>0){
			$ResultSet=$wpdb->get_results($wpdb->prepare($SqlElenco,"_docenteCorso",$FormTut),OBJECT );
			$ElencoCorsiDocente="Visualizza i corsi in cui sono Formatore:\n";
			foreach($ResultSet as $Rec){
				$ElencoCorsiDocente.=$Rec->post_title."\n";
			}			
		}else{
			$ElencoCorsiDocente="";
		}
		if($NumCTut>0){
			$ResultSet=$wpdb->get_results($wpdb->prepare($SqlElenco,"_tutorCorso",$FormTut),OBJECT );
			$ElencoCorsiTutor="Visualizza i corsi in cui sono Tutor:\n";
			foreach($ResultSet as $Rec){
				$ElencoCorsiTutor.=$Rec->post_title."\n";
			}			
		}else{
			$ElencoCorsiTutor="";
		}
		if(($NumCTut==0 And $NumCDoc==0) And $Utenti->is_Organizzatore( get_current_user_id())){
			continue;
		}
		$Utente=new Utenti($Corsista['person_id']);

		$DatiFormTut[]=array(  'IDUser'		=> "<a href=\"". admin_url()."/user-edit.php?user_id=".$FormTut."\" title=\"Modifica l'utente\">".$FormTut."</a>",
									'cognomenome'		=> ucfirst($user_info->last_name)." ".ucfirst($user_info->first_name),
									'email'			=> $user_info->user_email,
									'scuola'		=> $Scuola,
									'numcorsiD'	    => ($NumCDoc>0?"<a href=\"". admin_url()."/admin.php?page=formatoritutor&op=visform&sec=". wp_create_nonce("VisCorsiFormatore")."&user_id=".$FormTut."\" title=\"".$ElencoCorsiDocente."\"> <i class=\"fa fa-cubes\" aria-hidden=\"true\"></i> ".$NumCDoc."</a>":"0"),
									'numcorsiT'		=> ($NumCTut>0?"<a href=\"". admin_url()."/admin.php?page=formatoritutor&op=vistutor&sec=". wp_create_nonce("VisCorsiTutor")."&user_id=".$FormTut."\" title=\"".$ElencoCorsiTutor."\"> <i class=\"fa fa-cubes\" aria-hidden=\"true\"></i> ".$NumCTut."</a>":"0"));		
	}
	if($order=='asc'){
		$DatiFormTut=$this->record_sort($DatiFormTut,$orderby);
	}else{
		$DatiFormTut=$this->record_sort($DatiFormTut,$orderby,TRUE);
	}
	$DocentiTutorFiltrati=array();
//	echo $paged."  ".$per_page."  ".count($DatiCorsisti);
	for($i=$paged;$i<$per_page+$paged And $i<=count($DatiFormTut);$i++){
		$DocentiTutorFiltrati[]=$DatiFormTut[$i];
	}
	$this->items = $DocentiTutorFiltrati;
    $total_items = count($DatiFormTut);
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

    $columns = array(
      'IDUser'          => 'ID Utente',
      'cognomenome'     => 'Cognome Nome',
	  'email'			=> 'Email',
	  'scuola'			=> 'Scuola',
	  'numcorsiD'		=> 'Numero Corsi Docenti',
      'numcorsiT'		=> 'Numero Corsi Tutor',
    );
    return $columns;
  }

  // Funzione per la definizione dei campi che possono
  // essere utilizzati per eseguire la funzione di ordinamento

  function get_columns_sortable()
  {
    $sortable_columns = array(
      'cognomenome'       => array('cognomenome',true),
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
