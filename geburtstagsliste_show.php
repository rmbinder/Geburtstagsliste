<?php
 /******************************************************************************
 * geburtstagsliste_show
 *
 * Hauptprogramm für das Admidio-Plugin Geburtstagsliste
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html  
 *   
 * Hinweis:
 * 
 * geburtstagsliste_show ist eine modifizierte lists_show
 * 
 * Parameters:
 *
 * mode   		  : Output (html, print, csv-ms, csv-oo, pdf, pdfl)
 * full_screen  : 0 - (Default) show sidebar, head and page bottom of html page
 *                1 - Only show the list without any other html unnecessary elements
 * config		    : Die gewählte Konfiguration (Alte Bezeichnung Fokus; die Standardeinstellung wurde über Einstellungen-Optionen festgelegt)
 * month		    : Der gewählte Monat
 * previewdays	: Die vorauszuschauenden Tage (Default wurde in Optionen festgelegt)
 * previewmode	: days   - (Default) Die Anzeige einer bestimmten Anzahl von Tagen wurde gewählt
 * 				        months - Die Anzeige für einen Monat wurde gewählt
 *****************************************************************************/
 
// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');  
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php');  
require_once($plugin_path. '/'.$plugin_folder.'/classes/genlist.php'); 

// Konfiguration einlesen          
$pPreferences = new ConfigTablePGL();
$pPreferences->read();

$monate = array('00' => $gL10n->get('PGL_ALL_MONTHS'),
				'01' => $gL10n->get('PGL_JANUARY'),
        		'02' => $gL10n->get('PGL_FEBRUARY'),
        		'03' => $gL10n->get('PGL_MARCH'),
                '04' => $gL10n->get('PGL_APRIL'),
                '05' => $gL10n->get('PGL_MAY'),
                '06' => $gL10n->get('PGL_JUNE'),
                '07' => $gL10n->get('PGL_JULY'),
                '08' => $gL10n->get('PGL_AUGUST'),
                '09' => $gL10n->get('PGL_SEPTEMBER'),
                '10' => $gL10n->get('PGL_OCTOBER'),
                '11' => $gL10n->get('PGL_NOVEMBER'),
                '12' => $gL10n->get('PGL_DECEMBER')   );

// Initialize and check the parameters
$validValues = array();
foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $dummy)
{
	$validValues[] = 'X'.$key.'X';
}
$getConfig 		= admFuncVariableIsValid($_GET, 'config', 'string', array('defaultValue' => 'X'.$pPreferences->config['Optionen']['config_default'].'X', 'validValues' => $validValues) );

$validValues = array(0=>'X'.$pPreferences->config['Optionen']['vorschau_tage_default'].'X');
foreach ($pPreferences->config['Optionen']['vorschau_liste'] as $item)
{
	$validValues[] =  'X'.$item.'X';
}
$getPreviewDays	= admFuncVariableIsValid($_GET, 'previewdays', 'string', array('defaultValue' => 'X'.$pPreferences->config['Optionen']['vorschau_tage_default'].'X',  'validValues' => $validValues));
unset($validValues);

$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('requireValue' => true, 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl' )));
$getFullScreen  = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getMonth 		= admFuncVariableIsValid($_GET, 'month', 'string', array('validValues' => array('00','01', '02', '03', '04', '05', '06','07','08','09','10','11','12' )));

$liste = new GenList($getConfig, $getPreviewDays, $getMonth);
$liste->generate_listData();

$subheadline= $gL10n->get('PGL_FOR_THE_PERIOD',date("d.m.Y",strtotime('1 day',$liste->date_min)),date("d.m.Y",$liste->date_max),(trim($getPreviewDays,'X')<0 ? trim($getPreviewDays,'X') : '+'.trim($getPreviewDays,'X')) );
$subheadline .= ($getMonth>0 ? ' - '.$monate[$getMonth] : '');
$subheadline .= ' - '.$gL10n->get('PGL_CONFIGURATION').': '.$pPreferences->config['Konfigurationen']['col_desc'][trim($getConfig,'X')];     
        
// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$classTable  = '';
$orientation = '';
$filename = $g_organization.'-'.$gL10n->get('PGL_BIRTHDAY_LIST');

switch ($getMode)
{
    case 'csv-ms':
        $separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
        $valueQuotes = '"';  // all values should be set with quotes
        $getMode     = 'csv';
        $charset     = 'iso-8859-1';
        break;
    case 'csv-oo':
        $separator   = ',';   // a CSV file should have a comma
        $valueQuotes = '"';   // all values should be set with quotes
        $getMode     = 'csv';
        $charset     = 'utf-8';
        break;
    case 'pdf':
        $classTable  = 'table';
        $orientation = 'P';
        $getMode     = 'pdf';
        break;
    case 'pdfl':
        $classTable  = 'table';
        $orientation = 'L';
        $getMode     = 'pdf';
        break;
    case 'html':
        $classTable  = 'table table-condensed';
        break;
    case 'print':
        $classTable  = 'table table-condensed table-striped';
        break;
    default:
        break;
}

$str_csv      = '';   // enthaelt die komplette CSV-Datei als String

$numMembers = count($liste->listData);

//die Spaltenanzahl bestimmen
$columnCount = count($liste->headerData);
    
// define title (html) and headline
$title = $gL10n->get('PGL_BIRTHDAY_LIST');
$headline = $gL10n->get('PGL_BIRTHDAY_LIST');

// if html mode and last url was not a list view then save this url to navigation stack
if($getMode == 'html' && strpos($gNavigation->getUrl(), 'geburtstagsliste_show.php') === false)
{
    $gNavigation->addUrl(CURRENT_URL);
}

if($getMode != 'csv')
{
    $datatable = false;
    $hoverRows = false;

    if($getMode == 'print')
    {
        // create html page object without the custom theme files
        $page = new HtmlPage($headline);
        $page->hideThemeHtml();
        $page->hideMenu();
        $page->setPrintMode();
                
        $page->setTitle($title);
        $page->addHtml('<h3>'.$subheadline.'</h3>');
        
        $table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
    }
    elseif($getMode == 'pdf')
    {
        if(ini_get('max_execution_time')<300)
    	{
    		ini_set('max_execution_time', 300); //300 seconds = 5 minutes
    	}
        require_once(SERVER_PATH. '/adm_program/libs/tcpdf/tcpdf.php');
        $pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Admidio');
        $pdf->setTitle($title);

        // remove default header/footer
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(false);
 		// set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(0);

        //headline for PDF
        $pdf->SetHeaderData('', '', $headline, '');
		
        // set font
        $pdf->SetFont('times', '', 10);

        // add a page
        $pdf->AddPage();
        
        // Create table object for display
		$table = new HtmlTable('adm_lists_table', $pdf, $hoverRows, $datatable, $classTable);
        $table->addAttribute('border', '1');
        $table->addTableHeader();
        $table->addRow();
        $table->addAttribute('align', 'center');
        $table->addColumn($subheadline, array('colspan' => $columnCount + 1));
        $table->addRow();
    }
    elseif($getMode == 'html')
    {
        $datatable = true;
        $hoverRows = true;

        // create html page object
        $page = new HtmlPage($headline.'<h3>'.$subheadline.'</h3>');

        if($getFullScreen == true)
        {
        	$page->hideThemeHtml();
        }

        $page->setTitle($title);
        $page->addJavascript('
            $("#export_list_to").change(function () {
                if($(this).val().length > 1) {
                    self.location.href = "'. $g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?" +
                        "previewdays='.$getPreviewDays.'&month='.$getMonth.'&config='.$getConfig.'&mode=" + $(this).val();
                }
            });
            $("#previewList").change(function () {
                if($(this).val().length > 1) {
                    self.location.href = "'. $g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?" +
                        "mode=html&month='.$getMonth.'&full_screen='.$getFullScreen.'&config='.$getConfig.'&previewdays=" + $(this).val();
                }
            });
            
            $("#monthList").change(function () {
                if($(this).val().length > 0) {
                    self.location.href = "'. $g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?" +
                        "mode=html&previewdays='.$getPreviewDays.'&full_screen='.$getFullScreen.'&config='.$getConfig.'&month=" + $(this).val();
                }
            });
            $("#configList").change(function () {
            	if($(this).val().length > 1) {
                    self.location.href = "'. $g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?" +
                        "mode=html&previewdays='.$getPreviewDays.'&full_screen='.$getFullScreen.'&month='.$getMonth.'&config=" + $(this).val();
                }
            });
            
            $("#menu_item_print_view").click(function () {
                window.open("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?" +
                 "previewdays='.$getPreviewDays.'&month='.$getMonth.'&config='.$getConfig.'&mode=print", "_blank");
            });', true);
        
        // get module menu
        $listsMenu = $page->getMenu();
        
        if($getFullScreen == true)
        {
            $listsMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?mode=html&amp;previewdays='.$getPreviewDays.'&amp;month='.$getMonth.'&amp;config='.$getConfig.'&amp;full_screen=0', 
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
        }
        else
        {
            $listsMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?mode=html&amp;previewdays='.$getPreviewDays.'&amp;month='.$getMonth.'&amp;config='.$getConfig.'&amp;full_screen=1', 
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
        }
        
        // link to print overlay and exports
        $listsMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
        
        if(check_showpluginPGL($pPreferences->config['Pluginfreigabe']['freigabe_config']))
		{
    		// show link to pluginpreferences 
    		$listsMenu->addItem('admMenuItemPreferencesLists', $g_root_path. '/adm_plugins/'.$plugin_folder.'/preferences.php', 
                        $gL10n->get('PGL_SETTINGS'), 'options.png');        
		}
         
        $form = new HtmlForm('navbar_export_to_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
       
        $selectBoxEntries = array('' => $gL10n->get('LST_EXPORT_TO').' ...', 'csv-ms' => $gL10n->get('LST_MICROSOFT_EXCEL').' ('.$gL10n->get('SYS_ISO_8859_1').')', 'pdf' => $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_PORTRAIT').')', 
                                  'pdfl' => $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_LANDSCAPE').')', 'csv-oo' => $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')');
		$form->addSelectBox('export_list_to', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
		$selectBoxEntries = array('' => $gL10n->get('PGL_SELECT_NUMBER_OF_DAYS').' ...');
    	foreach ($pPreferences->config['Optionen']['vorschau_liste'] as $item)
    	{
    		// eine 0 in der Vorschauliste wird nicht korrekt dargestellt, deshalb alle Werte maskieren
			$selectBoxEntries['X'.$item.'X'] =  $item;
		}
        $form->addSelectBox('previewList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array('' => $gL10n->get('PGL_SELECT_MONTH').' ...');
    	foreach ($monate as $key => $item)
    	{
			$selectBoxEntries[$key] =  $item;
		}
        $form->addSelectBox('monthList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array(' ' => $gL10n->get('PGL_SELECT_CONFIGURATION').' ...');
    	foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $item)
    	{
			$selectBoxEntries['X'.$key.'X'] =  $item;
		}
        $form->addSelectBox('configList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
         
        $listsMenu->addForm($form->show(false));
        
        $table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
        $table->setDatatablesRowsPerPage($gPreferences['lists_members_per_page']);
    }
	else
	{
		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
	}
}

$columnAlign  = array('left');
$columnValues = array($gL10n->get('SYS_ABR_NO'));
$columnNumber = 1;  
  
foreach($liste->headerData as $columnHeader) 
{
	// bei Profilfeldern ist in 'id' die 'usf_id', ansonsten 0
	$usf_id = $columnHeader['id'];
	
    if($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'CHECKBOX'
        || $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'GENDER')
    {
    	$columnAlign[] = 'center';
    }
    elseif($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'NUMBER'
        || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DECIMAL_NUMBER')
    {
        $columnAlign[] = 'right';
    }
    else
    {
    	$columnAlign[] = 'left';    
    }
	 
    if($getMode == 'csv')
    {
    	if($columnNumber == 1)
        {
        	// in der ersten Spalte die laufende Nummer noch davorsetzen
            $str_csv = $str_csv. $valueQuotes. $gL10n->get('SYS_ABR_NO'). $valueQuotes;
        }
        $str_csv = $str_csv. $separator. $valueQuotes. $columnHeader['data']. $valueQuotes;
    }
    elseif($getMode == 'pdf')
    {
    	if($columnNumber == 1)
        {
        	$table->addColumn($gL10n->get('SYS_ABR_NO'), array('style' => 'text-align: left;font-size:14;background-color:#C7C7C7;'), 'th');
        }
        $table->addColumn($columnHeader['data'], array('style' => 'text-align: left;font-size:14;background-color:#C7C7C7;'), 'th');
    }
    elseif($getMode == 'html' || $getMode == 'print')
    {
    	$columnValues[] = $columnHeader['data'];
    }
    $columnNumber++;
} 

if($getMode == 'csv')
{
    $str_csv = $str_csv. "\n";
}
elseif($getMode == 'html' || $getMode == 'print')
{
    $table->setColumnAlignByArray($columnAlign);
    $table->addRowHeadingByArray($columnValues);
}
else
{
    $table->addTableBody();
}

$listRowNumber = 1;    

// die Daten einlesen
foreach($liste->listData as $memberdata) 
{
	$columnValues = array();

    // Felder zu Datensatz
    $columnNumber=1;
    for($i=1; $i < count($memberdata); $i++)
    {         
    	if($getMode == 'html' || $getMode == 'print' || $getMode == 'pdf')
        {    
        	if($columnNumber == 1)
            {
            	// die Laufende Nummer noch davorsetzen
                $columnValues[] = $listRowNumber;  
            }
        }
        else
        {
            if($columnNumber == 1)
            {
                // erste Spalte zeigt lfd. Nummer an
                $str_csv = $str_csv. $valueQuotes. $listRowNumber. $valueQuotes;
            }
        }
        
        /*****************************************************************/
        // create output format
       	/*****************************************************************/
        $content = $memberdata[$i];
        
        // format value for csv export
    	$usf_id = 0;
        $usf_id = $liste->headerData[$i]['id'];
      
        if( $usf_id  != 0 
         && $getMode == 'csv'
         && $content > 0
         && ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DROPDOWN'
              || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'RADIO_BUTTON') )
        {
            // show selected text of optionfield or combobox
            $arrListValues = $gProfileFields->getPropertyById($usf_id, 'usf_value_list', 'text');
            $content       = $arrListValues[$content];
        }
        
        if($getMode == 'csv')
        {
        	$str_csv = $str_csv. $separator. $valueQuotes. $content. $valueQuotes;
        }
        // create output in html layout
        else
        {            	
        	if($usf_id!=0 && $gProfileFields->getPropertyById($liste->headerData[$i]['id'], 'usf_type') != 'EMAIL')     //only profileFields without EMAIL
        	{
        		$content = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usf_id, 'usf_name_intern'), $content, $memberdata[0]);
        	}
        	
            // if empty string pass a whitespace
			if(strlen($content) > 0)
            {
        		 if($gProfileFields->getPropertyById($liste->headerData[$i]['id'], 'usf_type') == 'EMAIL')
        		 {
        		 	if($gPreferences['enable_mail_module'] != 1)
					{
						$mail_link = 'mailto:'. $content;
					}
					else
					{
						$mail_link = $g_root_path.'/adm_plugins/'.$plugin_folder.'/message_write.php?usr_id='. $memberdata[0].'&config='. trim($getConfig,'X').'&configtext='.end($memberdata);			
					}
					$columnValues[] = '<a href="'.$mail_link.'">'.$content.'</a><br />';
        		 }
        		 else 
        		 {
        		 	$columnValues[] = $content;
        		 }   
			}
            else
            {
            	$columnValues[] = '&nbsp;';
            }
		}
		$columnNumber++;
    }

	if($getMode == 'csv')
    {
    	$str_csv = $str_csv. "\n";
    }
    elseif($getMode == 'html')
    {
        $table->addRowByArray($columnValues, null, array('style' => 'cursor: pointer', 'onclick' => 'window.location.href=\''. $g_root_path. '/adm_program/modules/profile/profile.php?user_id='. $memberdata[0]. '\''));
    }
    elseif($getMode == 'print' || $getMode == 'pdf')
    {
        $table->addRowByArray($columnValues, null, array('nobr' => 'true'));
    }

    $listRowNumber++;
}  // End-For (jeder gefundene User)

// Settings for export file
if($getMode == 'csv' || $getMode == 'pdf')
{
	$filename .= '.'.$getMode;
	
     // for IE the filename must have special chars in hexadecimal 
    if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT']))
    {
        $filename = urlencode($filename);
    }

    header('Content-Disposition: attachment; filename="'.$filename.'"');
    
    // neccessary for IE6 to 8, because without it the download with SSL has problems
    header('Cache-Control: private');
    header('Pragma: public');
}

if($getMode == 'csv')
{
    // nun die erstellte CSV-Datei an den User schicken
    header('Content-Type: text/comma-separated-values; charset='.$charset);

    if($charset == 'ISO-8859-1')
    {
        echo utf8_decode($str_csv);
    }
    else
    {
        echo $str_csv;
    }
}
// send the new PDF to the User
elseif($getMode == 'pdf')
{
    // output the HTML content
    $pdf->writeHTML($table->getHtmlTable(), true, false, true, false, '');
    
    //Save PDF to file
    $pdf->Output(SERVER_PATH. '/adm_my_files/'.$filename, 'F');
    
    //Redirect
    header('Content-Type: application/pdf');

    readfile(SERVER_PATH. '/adm_my_files/'.$filename);
    ignore_user_abort(true);
    unlink(SERVER_PATH. '/adm_my_files/'.$filename);  
}
elseif($getMode == 'html' || $getMode == 'print')
{    
    // add table list to the page
    $page->addHtml($table->show(false));

    // show complete html page
    $page->show();
}
?>