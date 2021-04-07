<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-documenti
 *
 * @author Ignazio
 */

class Gestione_Documenti{
//put your code here
	protected $Formato;
	protected $DocumentName;
	
	public function __construct($Formato="Pdf"){
	
		require_once('tcpdf/tcpdf.php');
		require_once('PHPExcel/PHPExcel.php');
		
	}
	
	protected function crea_CartellaExcel($Dir,$Permessi=0777){
		$Risultato=FALSE;
		if (!is_dir($Dir)){
			if(mkdir($Dir,(int)$Permessi,TRUE)){
				$Risultato=TRUE;
			}			
		}else{
			$Risultato=TRUE;
		}
	return $Risultato;
	}
	
	protected function CalcColonna($Col){
		$DoppiaLC="";	
		if($Col>26){
			$DoppiaLC=chr(64+intval($Col/26));
			$Col=intval($Col%27)+1;
		}
//		echo $DoppiaLC.chr(64+$Col)."<br />";
		return $DoppiaLC.chr(64+$Col);
	}
	
	protected function CreaStatisticheCorso($objPHPExcel,$Corso){
		$sheet = $objPHPExcel->createSheet();
		$Totali=$Corso->get_TempoLezioni();
		$OreMin=FUNZIONI::daMin_aOreMin($Totali[0]);
		$DateLezioni=$Totali[2];
		$Presenze=$Corso->StatisticheCorsoDettaglio($Totali,"Testo");
		$AttivitaNP=$Corso->get_AttivitaNP();
		$Riga=1;
		// Testata Statistica Corso
		$sheet->setCellValue($this->CalcColonna(1).$Riga, "Titolo Corso");
		$sheet->setCellValue($this->CalcColonna(2).$Riga++,$Corso->get_NomeCorso());
		$sheet->setCellValue($this->CalcColonna(1).$Riga, "Stato Corso");
		$sheet->setCellValue($this->CalcColonna(2).$Riga++,(FUNZIONI::CorsoConsolidato($Corso)?"Chiuso":"Aperto"));
		$sheet->setCellValue($this->CalcColonna(1).$Riga, 'Numero Lezioni');
		$sheet->setCellValue($this->CalcColonna(2).$Riga++,  $Corso->get_NumLezioni());
		$sheet->setCellValue($this->CalcColonna(1).$Riga, 'Totale Ore di Lezione');
		$sheet->setCellValue($this->CalcColonna(2).$Riga++, $OreMin['Ore'].":".$OreMin['Min']);
		$Col=1;
		$sheet->setCellValue($this->CalcColonna($Col++).$Riga,"Corsista");
		$sheet->setCellValue($this->CalcColonna($Col++).$Riga,"Frequenza");
		foreach($DateLezioni as $DataL){
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga,$DataL[0]);
		}
		foreach($AttivitaNP as $DataNP){
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga,$DataNP[0]." (".$DataNP[1].")");
		}
		// Dati Corsisti
		foreach($Presenze as $IdCorsista => $Presenza){
			$Col=1;
			$Riga++;
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga,$Presenza[0]);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga,$Presenza[1]."%");
			foreach($Presenza[2]as $DatiPresenza){
				$sheet->setCellValue($this->CalcColonna($Col++).$Riga,$DatiPresenza[2]." (".$DatiPresenza[1].")"); 
			}
		}
		$sheet->setTitle($Corso->get_CodiceCorso());
	}
	
	public function CreaStampaCorsiAperti($Formato="Pdf"){
		$ElencoCorsi=array();
		$CorsiPeriodo=FUNZIONI::get_CorsiPeriodoFormazione();
		foreach($CorsiPeriodo as $CorsoP){
			$Corso=new Gestione_Corso($CorsoP->event_id);
			$Lezioni=$Corso->get_Lezioni();
			$NumLezioniDB=$Corso->get_NumLezioniDB();
			$NumCorsistiDB=$Corso->get_NumCorsistiDB();
			$AttNP=$Corso->get_AttivitaNP();
			$ANP=0;
			$LezCons=0;
			$LezioniAperte=array();
			foreach($Corso->get_AttivitaNP() as $AttivitaNP){
				if($AttivitaNP[2]=="Si"){
					$Lezioni[]=array($ANP."0/00/0000");
					$ANP++;
				}
			}
//			var_dump($Lezioni);
			foreach($Lezioni as $Lezione){
				if($Corso->is_LezioneConsolidata( $Lezione[0])){
					$LezCons+=1;
				}else{
					$LezioniAperte[]=(substr($Lezione[0],1)=="0/00/0000"?$AttNP[substr($Lezione[0], 0, 1)][0]:$Lezione[0]);
				}
			}
			if($LezCons!=count($Lezioni) And $NumLezioniDB==count($Lezioni) And $NumCorsistiDB>0){
				$ElencoCorsi[]=array("ID"=>$CorsoP->event_id,
									"Titolo"=>$Corso->get_TitoloCorso(),
									"FormatoriTutor"=>$Corso->get_DocentiTutorCorso("ElencoTesto",True),
									"Date"=>$LezioniAperte);
			}
		}
		$Date= get_option('PeriodoFormazione');
		$Periodi= unserialize( $Date );
		switch($Formato){
			case "Xls":
				$Dir=get_home_path()."wp-content/GestioneCorsi/Excel";
				$Risultato=$this->crea_CartellaExcel($Dir,0711,"a");
				if (!$Risultato){
					die("Errore creazione directory del file Excel");			
				}
				$objPHPExcel = new PHPExcel();
				$objPHPExcel->getProperties()->setCreator("Corsi Aperti nel periodo ". $Periodi[0]." - ".$Periodi[1])
					->setLastModifiedBy("Gestione Corsi")
					->setTitle("Stampe corsi")
					->setSubject("Stampa corsi aperti per periodo ")
					->setDescription("Elenco dei corsi aperti nel periodo")
					->setKeywords("Formazione Stampe")
					->setCategory("");
				$Riga=$Col=1;
				$sheet = $objPHPExcel->getActiveSheet(0);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Corso");
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Formatori');
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Tutor');
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Lezioni Aperte');
				$Riga++;
				foreach($ElencoCorsi as $CorsoAperto){
					$Col=1;
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $CorsoAperto['Titolo']);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $CorsoAperto['FormatoriTutor']['Docenti']);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $CorsoAperto['FormatoriTutor']['Tutor']);
					$sheet->setCellValue($this->CalcColonna($Col++).$Riga, implode(" ;",$CorsoAperto['Date']));
					$Riga++;
				}					
				$sheet->setTitle('Corsi Aperti');
				$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
				$objWriter->save($Dir."/CorsiAperti.xlsx");					
				break;
			default:
				$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

				$pdf->SetCreator(PDF_CREATOR);
				$pdf->SetKeywords('Stampe, Corsi Aperti');
				$pdf->SetTitle("Elenco dei corsi aperti nel periodo ".$Periodi[0]." - ".$Periodi[1]);
				$pdf->SetAuthor('Wordpress Plugin Gestione Corsi');
				$pdf->SetHeaderData(PDF_HEADER_LOGO, 140, "", "",PDF_HEADER_TITLE.' 029', PDF_HEADER_STRING);

				// set header and footer fonts
				$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
				$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

				// set default monospaced font
				$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

				// set margins
				$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
				$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
				$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

				// set auto page breaks
				$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM-7);

				// set image scale factor
				$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

				// set some language-dependent strings (optional)
				$pdf->setLanguageArray('1');

				$preferences = array(
					'HideToolbar' => true,
					'HideMenubar' => true,
					'HideWindowUI' => true,
					'FitWindow' => true,
					'CenterWindow' => true,
					'DisplayDocTitle' => true,
					'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
					'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
					'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
					'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
					'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
					'PrintScaling' => 'AppDefault', // None, AppDefault
					'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
					'PickTrayByPDFSize' => true,
					'PrintPageRange' => array(1,1,2,3),
					'NumCopies' => 1
				);
				$pdf->setViewerPreferences($preferences);
				// ---------------------------------------------------------

				// set font
				$pdf->SetFont('helvetica', '', 12);
				$style = array(
					'border' => 0,
					'vpadding' => 'auto',
					'hpadding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => false, //array(255,255,255)
					'module_width' => 1, // width of a single module in points
					'module_height' => 1 // height of a single module in points
				);
				// add a page
				$pdf->AddPage();
				$pdf->SetFont('helvetica', '', 8);
				// close and output PDF document
				$Righe="<div>
					<h2>Elenco corsi non ancora consolidati nel periodo </h2>
					<h3>Periodo:".$Periodi[0]."-".$Periodi[1]."</h3>
				</div>
				<ul>";
				foreach($ElencoCorsi as $CorsoAperto){
					$Righe.="<li>
						<h4>Corso: ".$CorsoAperto['Titolo']."</h4>
						<h4>Formatori</h4>
						<ul>";
					$Docets=explode(",",$CorsoAperto['FormatoriTutor']['Docenti']);
					foreach($Docets as $Docet){
						$Righe.="<li>". $Docet."</li>";
					}
					$Righe.="</ul>
						<h4>Tutor</h4>
						<ul>";
					$Tutors=explode(",",$CorsoAperto['FormatoriTutor']['Tutor']);
					foreach($Tutors as $Tutor){
						$Righe.="<li>". $Tutor."</li>";
					}
					$Righe.="</ul>
						<h4>Lezioni Aperte</h4>
						<ul>";
					foreach($CorsoAperto['Date'] as $Data){
						$Righe.="<li>". $Data."</li>";
					}
					$Righe.="</ul>
					</li>";
				}
				$Righe.="</ul>";
				$pdf->writeHTML($Righe, true, false, true, false, '');
				$pdf->Output("Corsi_Aperti_periodo_".$Periodi[0]."-".$Periodi[1].'.pdf', 'I');
				// close and output PDF document
				
				break;
		}
		return $ElencoCorsi;
	}
	public function cellColor($cells,$Color="",$BGcolor=""){
	    global $objPHPExcel;
		
		if($BGcolor!="")
		    $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
		        'type' => PHPExcel_Style_Fill::FILL_SOLID,
		        'startcolor' => array('rgb' => $BGcolor),
		    ));
		if($Color!="")
		    $objPHPExcel->getActiveSheet()->getStyle($cells)->applyFromArray(array(		
				'font'  => array('color' => array('rgb' => $Color),),
		     ));
	}
	public function CreaStatistiche(){
		global $objPHPExcel;
		$Dir=get_home_path()."wp-content/GestioneCorsi/Excel";
		$Risultato=$this->crea_CartellaExcel($Dir,0711,"a");
		if (!$Risultato){
			die("Errore creazione directory del file Excel");			
		}
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("Gestione Corsi")
			->setLastModifiedBy("Gestione Corsi")
			->setTitle("Statistiche corsi")
			->setSubject("Statistiche periodo ")
			->setDescription("Dati statistici generali e per corso")
			->setKeywords("Formazione Statistica")
			->setCategory("");
		$CorsiPeriodo=FUNZIONI::get_CorsiPeriodoFormazione();
		$Riga=$Col=1;
		$sheet = $objPHPExcel->getActiveSheet(0);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Corso");
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Categoria");
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Codice");
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Formatori");
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Tutors");
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, "Stato");
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Num. Iscritti');
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Num. Corsisti');
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Num. Lezioni');
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Num. Lezioni Fatte');
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Totale Ore Lezioni');
			for($i=0;$i<=100;$i+=10){
				$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $i."%");
			}
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, 'Date Lezioni');
		$Riga++;
		foreach($CorsiPeriodo as $CorsoP){
			$Corso=new Gestione_Corso($CorsoP->event_id);
			$DatiCorso=$Corso->StatisticaCorso();
			$DocentiTutor=$Corso->get_DocentiTutorCorso();
			$CorsoConsolidato=FUNZIONI::CorsoConsolidato($Corso);
			$Col=1;//=COLLEG.IPERTESTUALE(SOSTITUISCI(CELLA(\"indirizzo\";$Corso->get_CodiceCorso()!A1);\"'\";\"\");\"$Corso->get_CodiceCorso()\")
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Nome_Corso']);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Categorie']);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $Corso->get_CodiceCorso());
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DocentiTutor["Docenti"]);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DocentiTutor["Tutor"]);
			$this->cellColor($this->CalcColonna($Col).$Riga,($CorsoConsolidato?"":"FFFFFF"),($CorsoConsolidato?"00FF00":"FF0000"));
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, ($CorsoConsolidato?"Chiuso":"Aperto"));
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Numero_Iscritti']);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Numero_Corsisti']);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Numero_Lezioni']);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['LezioniFatte']);
			$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Totale_Ore_Lezioni'].":00");
			for($i=0;$i<=10;$i++){
				$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $DatiCorso['Presenze%'][$i]);
			}
			foreach($DatiCorso['Lezioni'] as $Lezione){
				$sheet->setCellValue($this->CalcColonna($Col++).$Riga, $Lezione[0]."(".$Lezione[1].")");
			}
			$Riga++;
			$this->CreaStatisticheCorso($objPHPExcel,$Corso);	
		}					
		$sheet->setTitle('Dati Corsi');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($Dir."/StatisticheCorsi.xlsx");
	}
	public function Crea_Log_Email($Log,$email){

		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetKeywords('Log, email');
		$pdf->SetTitle("Log delle comunicazioni avvenute con la mail ".$email);
		$pdf->SetAuthor('Wordpress Plugin Gestione Corsi');
		$pdf->SetHeaderData(PDF_HEADER_LOGO, 140, "", "",PDF_HEADER_TITLE.' 029', PDF_HEADER_STRING);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM-7);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		$pdf->setLanguageArray('1');

		$preferences = array(
			'HideToolbar' => true,
			'HideMenubar' => true,
			'HideWindowUI' => true,
			'FitWindow' => true,
			'CenterWindow' => true,
			'DisplayDocTitle' => true,
			'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
			'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintScaling' => 'AppDefault', // None, AppDefault
			'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
			'PickTrayByPDFSize' => true,
			'PrintPageRange' => array(1,1,2,3),
			'NumCopies' => 1
		);
		$pdf->setViewerPreferences($preferences);
		// ---------------------------------------------------------

		// set font
		$pdf->SetFont('helvetica', '', 12);
		$style = array(
			'border' => 0,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);
		// add a page
		$pdf->AddPage();
		$pdf->SetFont('helvetica', '', 8);
		// close and output PDF document
		$pdf->writeHTML($Log, true, false, true, false, '');
		// close and output PDF document
		$pdf->Output('Log_email_'.$email.'.pdf', 'I');
		
	}
	public function Crea_Elenco_Firma_Corso($Corso){

		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetKeywords('Corso, '.$Corso->get_NomeCorso().', formazione, scuola, foglio firme');
		$pdf->SetTitle("Foglio firme corso:".$Corso->get_NomeCorso());
		$pdf->SetAuthor('Wordpress Plugin Gestione Corsi');
		$pdf->SetHeaderData(PDF_HEADER_LOGO, 140, "", "",PDF_HEADER_TITLE.' 029', PDF_HEADER_STRING);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM-7);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		$pdf->setLanguageArray('1');

		$preferences = array(
			'HideToolbar' => true,
			'HideMenubar' => true,
			'HideWindowUI' => true,
			'FitWindow' => true,
			'CenterWindow' => true,
			'DisplayDocTitle' => true,
			'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
			'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintScaling' => 'AppDefault', // None, AppDefault
			'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
			'PickTrayByPDFSize' => true,
			'PrintPageRange' => array(1,1,2,3),
			'NumCopies' => 1
		);
		$pdf->setViewerPreferences($preferences);
		// ---------------------------------------------------------

		// set font
		$pdf->SetFont('helvetica', '', 12);
		$style = array(
			'border' => 0,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);
		// add a page
		$pdf->AddPage();
		$pdf->write2DBarcode(site_url().'/wp-admin/admin.php?page=corsi&op=registro&event_id='. filter_input( INPUT_GET, "event_id" ).'&mod=stafogliofirma&data='.filter_input( INPUT_GET, "data" ), 'QRCODE,Q', 170, 0, 28, 28, $style, 'N');
		$Tabella=$Corso->registro_Corso();
		$pdf->SetFont('helvetica', '', 8);
		$DocentiTutor=$Corso->get_DocentiTutorCorso();
		$Docenti=explode(",",$DocentiTutor["Docenti"]);
		$Tutor=explode(",",$DocentiTutor["Tutor"]);
		$TestoDocenti="<p></p><table style=\"width:95%;\"><tr><td style=\"font-size:2em;font-weight: bold;width:15%;text-align: center;\">Docenti</td><td style=\"width:90%;\"><table>";
		foreach($Docenti as $D){
			$TestoDocenti.="<tr><td style=\"font-size:1.2em;font-weight: bold;text-align:right;\" height=\"40\">".$D."</td><td>  _______________________________________________</td></tr>";
		}
		$TestoDocenti.="</table></td></tr></table>";	
		$TestoTutor="<hr /><p></p><table style=\"width:95%;\"><tr><td style=\"font-size:2em;font-weight: bold;width:15%;vertical-align: middle;\">Tutor</td><td style=\"width:90%;\"><table>";
		foreach($Tutor as $T){
			$TestoTutor.="<tr><td style=\"font-size:1.2em;font-weight: bold;text-align:right;\"  height=\"40\">".$T."</td><td>  _______________________________________________</td></tr>";
		}
		$TestoTutor.="</table></td></tr></table>";	
		// close and output PDF document
		$pdf->writeHTML($Tabella.$TestoDocenti.$TestoTutor, true, false, true, false, '');
		// close and output PDF document
		$pdf->Output('Foglio_firma_corso_'.$Corso->get_NomeCorso().'.pdf', 'I');
		
	}
	public function Crea_Registro_Corso($Corso){
		
		$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetKeywords('Corso, '.$Corso->get_NomeCorso().', formazione, scuola, foglio firme');
		$pdf->SetTitle("Registro corso:".$Corso->get_NomeCorso());
		$pdf->SetAuthor('Wordpress Plugin Gestione Corsi');

		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, 140, "", "",PDF_HEADER_TITLE.' 029', PDF_HEADER_STRING);
		
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		$pdf->setLanguageArray($l);
		$preferences = array(
			'HideToolbar' => true,
			'HideMenubar' => true,
			'HideWindowUI' => true,
			'FitWindow' => true,
			'CenterWindow' => true,
			'DisplayDocTitle' => true,
			'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
			'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintScaling' => 'AppDefault', // None, AppDefault
			'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
			'PickTrayByPDFSize' => true,
			'PrintPageRange' => array(1,1,2,3),
			'NumCopies' => 1
		);
		$pdf->setViewerPreferences($preferences);
		// set font
		$pdf->SetFont('helvetica', '', 12);
		$style = array(
			'border' => 0,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);
		// add a page
		$pdf->AddPage();
		$pdf->write2DBarcode(site_url().'/wp-admin/admin.php?page=corsi&op=registro&event_id='. filter_input( INPUT_GET, "event_id" ), 'QRCODE,Q', 170, 0, 28, 28, $style, 'N');
		$Tabella=$Corso->registro_Corso();
		$pdf->SetFont('helvetica', '', 8);
		$pdf->writeHTML($Tabella, true, false, true, false, '');
		// close and output PDF document
		$pdf->Output('Registro_'.$Corso->get_NomeCorso().'.pdf', 'I');
		
	}
	public function CreaAttestato($Corso,$Utente,$Local=FALSE,$Dir=""){
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$pdf->set_Footer(FALSE);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetKeywords('Corso, '.$Corso->get_NomeCorso().', formazione, scuola, attestato partecipazione');
		$pdf->SetTitle("Attestato frequenza corso:".$Corso->get_NomeCorso());
		$pdf->SetAuthor('Wordpress Plugin Gestione Corsi');

		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, 140, "", "",PDF_HEADER_TITLE.' 029', PDF_HEADER_STRING);
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		$pdf->setLanguageArray('l');
		$preferences = array(
			'HideToolbar' => true,
			'HideMenubar' => true,
			'HideWindowUI' => true,
			'FitWindow' => true,
			'CenterWindow' => true,
			'DisplayDocTitle' => true,
			'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
			'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintScaling' => 'AppDefault', // None, AppDefault
			'Duplex' => 'DuplexFlipLongEdge', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
			'PickTrayByPDFSize' => true,
			'PrintPageRange' => array(1,1,2,3),
			'NumCopies' => 1
		);
		$pdf->setViewerPreferences($preferences);
		// set font
		$pdf->SetFont('helvetica', '', 12);
		$style = array(
			'border' => 0,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);
		// add a page
		$pdf->AddPage();
		$pdf->write2DBarcode($Corso->get_Permalink(), 'QRCODE,Q', 170, 0, 28, 28, $style, 'N');
		$Tabella=$Corso->attestato_Corso($Utente);
		$pdf->SetFont('helvetica', '', 8);
		$pdf->writeHTML($Tabella, true, false, true, false, '');
		// close and output PDF document
		if(!$Local)
			$pdf->Output('Attestato_Frequenza_'.$Corso->get_NomeCorso().'.pdf', 'I');
		else{
			$Utenti=new Utenti($Utente);
			$Docente=$Utenti->get_Descrizione();
			$FileName=$Docente['Nome']." ".$Docente['Cognome']."_".$Corso->get_NomeCorso();
			$pdf->Output($Dir.'/'.$FileName.'.pdf', 'F');
			if(is_file($Dir.'/'.$FileName.'.pdf'))
				return TRUE;
			else
				return FALSE;
		}
	}
	public function CreaAttestati($Corso){
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->set_Footer(FALSE);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetKeywords('Corso, '.$Corso->get_NomeCorso().', formazione, scuola, attestato partecipazione');
		$pdf->SetTitle("Attestato frequenza corso:".$Corso->get_NomeCorso());
		$pdf->SetAuthor('Wordpress Plugin Gestione Corsi');

		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, 140, "", "",PDF_HEADER_TITLE.' 029', PDF_HEADER_STRING);
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		$pdf->setLanguageArray('l');
		$preferences = array(
			'HideToolbar' => true,
			'HideMenubar' => true,
			'HideWindowUI' => true,
			'FitWindow' => true,
			'CenterWindow' => true,
			'DisplayDocTitle' => true,
			'NonFullScreenPageMode' => 'UseNone', // UseNone, UseOutlines, UseThumbs, UseOC
			'ViewArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'ViewClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintArea' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintClip' => 'CropBox', // CropBox, BleedBox, TrimBox, ArtBox
			'PrintScaling' => 'AppDefault', // None, AppDefault
			'Duplex' => 'Simplex', // Simplex, DuplexFlipShortEdge, DuplexFlipLongEdge
			'PickTrayByPDFSize' => true,
			'PrintPageRange' => array(1,1,2,3),
			'NumCopies' => 1
		);
		$pdf->setViewerPreferences($preferences);
		// set font
		$pdf->SetFont('helvetica', '', 12);
		$style = array(
			'border' => 0,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 1, // width of a single module in points
			'module_height' => 1 // height of a single module in points
		);
//		var_dump($Corso->get_Corsisti());
		$Corsisti=$Corso->get_Corsisti();
		$IDCorsista=array();
		foreach($Corsisti as $Utente=>$Corsista){
			$IDCorsista[]=array("Id"=>$Utente,"Nome"=> Funzioni::NomeUtente($Utente));
		}
		$Corsisti=Funzioni::SortArray($IDCorsista, "Nome");
		foreach($Corsisti as $Corsista){
		// add a page
			$pdf->AddPage();
			$pdf->write2DBarcode($Corso->get_Permalink(), 'QRCODE,Q', 170, 0, 28, 28, $style, 'N');
			$Tabella=$Corso->attestato_Corso($Corsista['Id']);
			$pdf->SetFont('helvetica', '', 8);
			$pdf->writeHTML($Tabella, true, false, true, false, '');
			// close and output PDF document
		}	
		$pdf->Output('Attestati_Frequenza_'.$Corso->get_NomeCorso().'.pdf', 'I');			
	}	
}
