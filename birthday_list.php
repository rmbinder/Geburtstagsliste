<?php
/**
 ***********************************************************************************************
 * Geburtstagsliste / birthday_list
 *
 * Version 3.4.0
 *
 * This plugin creates a birthday or anniversary list of members.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 4.3
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * mode   		     : Output (html, print, csv-ms, csv-oo, pdf, pdfl, xlsx)
 * config		     : Die gewaehlte Konfiguration (Alte Bezeichnung Fokus; die Standardeinstellung wurde über Einstellungen-Optionen festgelegt)
 * month		     : Der gewaehlte Monat
 * previewdays	     : Die vorauszuschauenden Tage (Default wurde in Optionen festgelegt)
 * previewmode	     : days   - (Default) Die Anzeige einer bestimmten Anzahl von Tagen wurde gewaehlt
 * 				       months - Die Anzeige für einen Monat wurde gewaehlt
 * export_and_filter : 0 - (Default) No filter and export menu
 *                     1 - Filter and export menu is enabled
 * filter		     : Filter string
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
require_once(__DIR__ . '/classes/genlist.php');

//$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/birthday_list...
$scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

// only authorized user are allowed to start this module
if (!isUserAuthorized($scriptName))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Konfiguration einlesen          
$pPreferences = new ConfigTablePGL();
if ($pPreferences->checkforupdate())
{
	$pPreferences->init();
}
else
{
	$pPreferences->read();
}

$monate = array('00' => $gL10n->get('PLG_GEBURTSTAGSLISTE_ALL_MONTHS'),
				'01' => $gL10n->get('PLG_GEBURTSTAGSLISTE_JANUARY'),
        		'02' => $gL10n->get('PLG_GEBURTSTAGSLISTE_FEBRUARY'),
        		'03' => $gL10n->get('PLG_GEBURTSTAGSLISTE_MARCH'),
                '04' => $gL10n->get('PLG_GEBURTSTAGSLISTE_APRIL'),
                '05' => $gL10n->get('PLG_GEBURTSTAGSLISTE_MAY'),
                '06' => $gL10n->get('PLG_GEBURTSTAGSLISTE_JUNE'),
                '07' => $gL10n->get('PLG_GEBURTSTAGSLISTE_JULY'),
                '08' => $gL10n->get('PLG_GEBURTSTAGSLISTE_AUGUST'),
                '09' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SEPTEMBER'),
                '10' => $gL10n->get('PLG_GEBURTSTAGSLISTE_OCTOBER'),
                '11' => $gL10n->get('PLG_GEBURTSTAGSLISTE_NOVEMBER'),
                '12' => $gL10n->get('PLG_GEBURTSTAGSLISTE_DECEMBER')   );

// Initialize and check the parameters
$validValues = array();
foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $dummy)
{
	$validValues[] = 'X'.$key.'X';
}
$getConfig = admFuncVariableIsValid($_GET, 'config', 'string', array('defaultValue' => 'X'.$pPreferences->config['Optionen']['config_default'].'X', 'validValues' => $validValues) );

$validValues = array(0=>'X'.$pPreferences->config['Optionen']['vorschau_tage_default'].'X');
foreach ($pPreferences->config['Optionen']['vorschau_liste'] as $item)
{
	$validValues[] =  'X'.$item.'X';
}
$getPreviewDays = admFuncVariableIsValid($_GET, 'previewdays', 'string', array('defaultValue' => 'X'.$pPreferences->config['Optionen']['vorschau_tage_default'].'X',  'validValues' => $validValues));
unset($validValues);

$getMode            = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl', 'xlsx' )));
$getMonth           = admFuncVariableIsValid($_GET, 'month', 'string', array('defaultValue' => '00', 'validValues' => array('00','01', '02', '03', '04', '05', '06','07','08','09','10','11','12' )));
$getFilter          = admFuncVariableIsValid($_GET, 'filter', 'string');
$getExportAndFilter = admFuncVariableIsValid($_GET, 'export_and_filter', 'bool', array('defaultValue' => false));

$liste = new GenList($getConfig, $getPreviewDays, $getMonth);
$liste->generate_listData();

// define title (html) and headline
$title = $gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST');

$subheadline = $gL10n->get('PLG_GEBURTSTAGSLISTE_FOR_THE_PERIOD', array(date("d.m.Y",strtotime('1 day', $liste->date_min)),
																		date("d.m.Y", $liste->date_max),
																		(trim($getPreviewDays,'X')<0 ? trim($getPreviewDays,'X') : '+'.trim($getPreviewDays,'X'))) );
$subheadline .= ($getMonth>0 ? ' - '.$monate[$getMonth] : '');

if ($pPreferences->config['Optionen']['configuration_as_header'])
{
	$headline = $pPreferences->config['Konfigurationen']['col_desc'][trim($getConfig,'X')];	
}
else 
{
	$headline = $gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST');
	$subheadline .= ' - '.$gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATION').': '.$pPreferences->config['Konfigurationen']['col_desc'][trim($getConfig,'X')];     	
}
        
// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$classTable  = '';
$orientation = '';
$filename = $gCurrentOrganization->getValue('org_shortname').'-'.$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST');

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
    case 'xlsx':
	    include_once(__DIR__ . '/libs/PHP_XLSXWriter/xlsxwriter.class.php');
	    $getMode     = 'xlsx';
	    break;
    default:
        break;
}

$csvStr = ''; 
$header = array();              //'xlsx'
$rows   = array();              //'xlsx'

$numMembers = count($liste->listData);

//die Spaltenanzahl bestimmen
$columnCount = count($liste->headerData);

if ($getMode === 'html' )
{
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'fa-birthday-cake');
}

if ($getMode != 'csv' && $getMode != 'xlsx' )
{
    $datatable = false;
    $hoverRows = false;

    if ($getMode == 'print')
    {
        $page = new HtmlPage('plg-birthday_list-main-print', $headline);
        $page->setPrintMode();
                
        $page->setTitle($title);
        $page->addHtml('<h3>'.$subheadline.'</h3>');
        
        $table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
    }
    elseif ($getMode == 'pdf')
    {
        if (ini_get('max_execution_time') < 300)
    	{
    		ini_set('max_execution_time', 300); //300 seconds = 5 minutes
    	}
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
        $pdf->SetHeaderData('', 0, $headline, '');
		
        // set font
        $pdf->SetFont('times', '', 10);

        // add a page
        $pdf->AddPage();
        
        // Create table object for display
		$table = new HtmlTable('adm_lists_table', null, $hoverRows, $datatable, $classTable);
        $table->addAttribute('border', '1');
        $table->addTableHeader();
        $table->addRow();
        $table->addAttribute('align', 'center');
        $table->addColumn($subheadline, array('colspan' => $columnCount + 1));
        $table->addRow();
    }
    elseif ($getMode == 'html')
    {
        if ($getExportAndFilter)
        {
            $datatable = false;
        }
        else
        {
            $datatable = true;
        }
        $hoverRows = true;

        // create html page object
        $page = new HtmlPage('plg-birthday_list-main-html');
        $page->setTitle($title);
        $page->setHeadline($headline);
        $page->addHtml('<h5>'.$subheadline.'</h5>');
        
        $page->addJavascript('
            $("#previewList").change(function () {
                if($(this).val().length > 1) {
                    self.location.href = "'.SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                        'mode'              => 'html',
                        'month'             => $getMonth,
                        'filter'            => $getFilter,
                        'export_and_filter' => $getExportAndFilter,
                        'config'            => $getConfig
                    )) . '&previewdays=" + $(this).val();
                }
            });
            $("#monthList").change(function () {
                if($(this).val().length > 0) {
                    self.location.href = "'.SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                        'mode'              => 'html',
                        'previewdays'       => $getPreviewDays,
                        'filter'            => $getFilter,
                        'export_and_filter' => $getExportAndFilter,
                        'config'            => $getConfig
                    )) . '&month=" + $(this).val();
                }
            });
            $("#configList").change(function () {
            	if($(this).val().length > 1) {
                    self.location.href = "'.SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                        'mode'              => 'html',
                        'previewdays'       => $getPreviewDays,
                        'filter'            => $getFilter, 
                        'export_and_filter' => $getExportAndFilter, 
                        'month'             => $getMonth
                    )) . '&config=" + $(this).val();
                }
            });
            $("#menu_item_lists_print_view").click(function() {
                window.open("'.SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                    'previewdays'       => $getPreviewDays, 
                    'filter'            => $getFilter, 
                    'export_and_filter' => $getExportAndFilter, 
                    'month'             => $getMonth, 
                    'config'            => $getConfig, 
                    'mode'              => 'print'
                )) . '", "_blank");
            });
            $("#export_and_filter").change(function() {
                $("#navbar_birthdaylist_form").submit();
            });
            $("#filter").change(function() {
                $("#navbar_birthdaylist_form").submit();
            });
        ', true);             
        
        if ($getExportAndFilter)
        {
            // links to print and exports
            $page->addPageFunctionsMenuItem('menu_item_lists_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
        
            // dropdown menu item with all export possibilities
            $page->addPageFunctionsMenuItem('menu_item_lists_export', $gL10n->get('SYS_EXPORT_TO'), '#', 'fa-file-download');
            $page->addPageFunctionsMenuItem('menu_item_lists_xlsx', $gL10n->get('SYS_MICROSOFT_EXCEL').' (XLSX)',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                    'previewdays'       => $getPreviewDays,
                    'filter'            => $getFilter,
                    'export_and_filter' => $getExportAndFilter,
                    'month'             => $getMonth,
                    'config'            => $getConfig,
                    'mode'              => 'xlsx')),
                'fa-file-excel', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_csv_ms', $gL10n->get('SYS_MICROSOFT_EXCEL').' (CSV)',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                    'previewdays'       => $getPreviewDays,
                    'filter'            => $getFilter,
                    'export_and_filter' => $getExportAndFilter,
                    'month'             => $getMonth,
                    'config'            => $getConfig,
                    'mode'              => 'csv-ms')),
                'fa-file-excel', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_pdf', $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_PORTRAIT').')',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                    'previewdays'       => $getPreviewDays,
                    'filter'            => $getFilter,
                    'export_and_filter' => $getExportAndFilter,
                    'month'             => $getMonth,
                    'config'            => $getConfig,
                    'mode'              => 'pdf')),
                'fa-file-pdf', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_pdfl', $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_LANDSCAPE').')',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                    'previewdays'       => $getPreviewDays,
                    'filter'            => $getFilter,
                    'export_and_filter' => $getExportAndFilter,
                    'month'             => $getMonth,
                    'config'            => $getConfig,
                    'mode'              => 'pdfl')),
                'fa-file-pdf', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_csv', $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array(
                    'previewdays'       => $getPreviewDays,
                    'filter'            => $getFilter,
                    'export_and_filter' => $getExportAndFilter,
                    'month'             => $getMonth,
                    'config'            => $getConfig,
                    'mode'              => 'csv-oo')),
                'fa-file-csv', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_ical', $gL10n->get('PLG_GEBURTSTAGSLISTE_ICAL'),
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/ical_export.php', array(
                    'previewdays' => $getPreviewDays,
                    'config'      => $getConfig,
                    'month'       => $getMonth,
                    'filter'      => $getFilter)),
                'fa-calendar', 'menu_item_lists_export');
        }
        else
        {
            // if filter is not enabled, reset filterstring
            $getFilter = '';
        }
        
        if (isUserAuthorizedForPreferences())
		{
    		// show link to pluginpreferences 
    		$page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('SYS_SETTINGS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php'),  'fa-cog');
		} 
        
		$form = new HtmlForm('navbar_birthdaylist_form', SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/birthday_list.php', array('headline' => $headline)), $page, array('type' => 'navbar', 'setFocus' => false));
        
        $selectBoxEntries = array(' ' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SELECT_CONFIGURATION').' ...');
        foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $item)
        {
        	$selectBoxEntries['X'.$key.'X'] = $item;
        }
        $form->addSelectBox('configList', '', $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array('' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SELECT_NUMBER_OF_DAYS').' ...');
        foreach ($pPreferences->config['Optionen']['vorschau_liste'] as $item)
        {
        	// eine 0 in der Vorschauliste wird nicht korrekt dargestellt, deshalb alle Werte maskieren
        	$selectBoxEntries['X'.$item.'X'] =  $item;
        }
        $form->addSelectBox('previewList', '', $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array('' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SELECT_MONTH').' ...');
        foreach ($monate as $key => $item)
        {
        	$selectBoxEntries[$key] =  $item;
        }
        $form->addSelectBox('monthList', '', $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        if ($getExportAndFilter)
        {
            $form->addInput('filter', $gL10n->get('SYS_FILTER'), $getFilter);
        }
        $form->addCheckbox('export_and_filter', $gL10n->get('PLG_GEBURTSTAGSLISTE_EXPORT_AND_FILTER'), $getExportAndFilter);
        
        //hidden fields
        $form->addInput('previewdays', '', $getPreviewDays, array('property' => HtmlForm::FIELD_HIDDEN));
        $form->addInput('month', '', $getMonth, array('property' => HtmlForm::FIELD_HIDDEN));
        $form->addInput('config', '', $getConfig, array('property' => HtmlForm::FIELD_HIDDEN));
        
        $page->addHtml($form->show());
        
        $table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
        if ($datatable)
        {
            // ab Admidio 4.3 verursacht setDatatablesRowsPerPage, wenn $datatable "false" ist, folgenden Fehler:
            // "Fatal error: Uncaught Error: Call to a member function setDatatablesRowsPerPage() on null"
            $table->setDatatablesRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        }
    }
	else
	{
		$table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
	}
}

$columnAlign  = array('left');
$columnValues = array($gL10n->get('SYS_ABR_NO'));
$columnNumber = 1;  
  
foreach ($liste->headerData as $columnHeader) 
{
	// bei Profilfeldern ist in 'id' die 'usf_id', ansonsten 0
	if (substr($columnHeader['id'], 0, 1) == 'r')          //relationship
	{
		$usf_id = (int) substr($columnHeader['id'], 1);
	}
	else 
	{
		$usf_id = (int) $columnHeader['id'];
	}
	
    if ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'CHECKBOX'
        || $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'GENDER')
    {
    	$columnAlign[] = 'center';
    }
    elseif ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'NUMBER'
        || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DECIMAL_NUMBER')
    {
        $columnAlign[] = 'right';
    }
    else
    {
    	$columnAlign[] = 'left';    
    }
	 
    if ($getMode == 'csv')
    {
    	if ($columnNumber == 1)
        {
        	// in der ersten Spalte die laufende Nummer noch davorsetzen
            $csvStr = $csvStr. $valueQuotes. $gL10n->get('SYS_ABR_NO'). $valueQuotes;
        }
        $csvStr = $csvStr. $separator. $valueQuotes. $columnHeader['data']. $valueQuotes;
    }
    elseif ($getMode == 'pdf')
    {
    	if ($columnNumber == 1)
        {
        	$table->addColumn($gL10n->get('SYS_ABR_NO'), array('style' => 'text-align: left;font-size:14;background-color:#C7C7C7;'), 'th');
        }
        $table->addColumn($columnHeader['data'], array('style' => 'text-align: left;font-size:14;background-color:#C7C7C7;'), 'th');
    }
    elseif ($getMode == 'xlsx')
    {
    	if ($columnNumber == 1)
        {
        	$header[$gL10n->get('SYS_ABR_NO')] = 'string';
        }
        $header[$columnHeader['data']] = 'string';
    }
    elseif ($getMode == 'html' || $getMode == 'print')
    {
    	$columnValues[] = $columnHeader['data'];
    }
    $columnNumber++;
} 

if ($getMode == 'csv')
{
    $csvStr = $csvStr. "\n";
}
elseif ($getMode == 'html' || $getMode == 'print')
{
    $table->setColumnAlignByArray($columnAlign);
    $table->addRowHeadingByArray($columnValues);
}
elseif ($getMode == 'xlsx')
{
    // nothing to do
}
else
{
    $table->addTableBody();
}

$listRowNumber = 1;    

// die Daten einlesen
foreach ($liste->listData as $memberdata) 
{
	$columnValues = array();
	$tmp_csv = '';
	
    // Felder zu Datensatz
    for ($i = 1; $i < count($memberdata); $i++)
    {
        if ($i === 1)
        {
            if (in_array($getMode, array('html', 'print', 'pdf', 'xlsx'), true))
            {    
            	// die Laufende Nummer noch davorsetzen
                $columnValues[] = $listRowNumber;  
            }
            else
            {
                // erste Spalte zeigt lfd. Nummer an
                $tmp_csv = $tmp_csv.$valueQuotes. $listRowNumber. $valueQuotes;
            }
        }
        
        /*****************************************************************/
        // create output format
       	/*****************************************************************/
        $content = $memberdata[$i];
        
        // format value for csv export
    	$usf_id = 0;
    	if (substr($liste->headerData[$i]['id'], 0, 1) == 'r')          //relationship
    	{
    		$usf_id = (int) substr($liste->headerData[$i]['id'], 1);
    	}
    	else 
    	{
    		$usf_id = (int) $liste->headerData[$i]['id'];
    	}
        
        if ($usf_id  != 0 
            && (in_array($getMode, array('xlsx', 'pdf', 'csv'), true))
         	&& $content > 0
         	&& ( $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DROPDOWN'
              || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'RADIO_BUTTON') )
        {
            // show selected text of optionfield or combobox
            $arrListValues = $gProfileFields->getPropertyById($usf_id, 'usf_value_list', 'text');
            $content       = $arrListValues[$content];
        }
        
        if ($getMode == 'csv')
        {
        	$tmp_csv = $tmp_csv. $separator. $valueQuotes. $content. $valueQuotes;
        }
        elseif (in_array($getMode, array('xlsx', 'pdf'), true))
        {
            $columnValues[] = $content;
        }
        // create output in html layout
        else
        {            	
        	if ($usf_id != 0 && $gProfileFields->getPropertyById($usf_id, 'usf_type') != 'EMAIL')     //only profileFields without EMAIL
        	{
        	    $content = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usf_id, 'usf_name_intern'), $content);
        	}
        	
            // if empty string pass a whitespace
			if (strlen($content) > 0)
            {
        		if ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'EMAIL')
        	 	{
        			if ($gSettingsManager->getInt('enable_mail_module') != 1)
					{
						$mail_link = 'mailto:'. $content;
					}
					else
					{
					    $mail_link = SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS.PLUGIN_FOLDER.'/message_write.php', array('user_uuid' => $memberdata[0]['usr_uuid'], 'config' => trim($getConfig,'X'), 'configtext' => end($memberdata)));
					}
					$columnValues[] = '<a href="'.$mail_link.'">'.$content.'</a><br />';
        		}
        		elseif ($getMode === 'html'
        		 	&&  (  $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'LAST_NAME'
        		 		|| $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'FIRST_NAME')
        		 	&& substr($liste->headerData[$i]['id'], 0, 1) != 'r')
        		{
        		    $columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $memberdata[0]['usr_uuid'])).'">'.$content.'</a>';
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
    }

    if ($getFilter == '' || ($getFilter <> '' && (stristr(implode('',$columnValues), $getFilter  ) || stristr($tmp_csv, $getFilter))))
    {
		if ($getMode == 'csv')
   	 	{
    		$csvStr .= $tmp_csv. "\n";
    	}
        elseif($getMode == 'xlsx')
    	{
        	$rows[] = $columnValues;
    	}
   	 	else
    	{
        	$table->addRowByArray($columnValues, '', array('nobr' => 'true'));
    	}
    	$listRowNumber++;
     }
}  // End-For (jeder gefundene User)

// Settings for export file
if ($getMode == 'csv' || $getMode == 'pdf'|| $getMode == 'xlsx')
{
	$filename = FileSystemUtils::getSanitizedPathEntry($filename) . '.' . $getMode;

    header('Content-Disposition: attachment; filename="'.$filename.'"');
    
    // neccessary for IE6 to 8, because without it the download with SSL has problems
    header('Cache-Control: private');
    header('Pragma: public');
}

if ($getMode == 'csv')
{
    // download CSV file
    header('Content-Type: text/comma-separated-values; charset='.$charset);

    if ($charset === 'iso-8859-1')
    {
        echo utf8_decode($csvStr);
    }
    else
    {
        echo $csvStr;
    }
}
// send the new PDF to the User
elseif ($getMode == 'pdf')
{
    // output the HTML content
    $pdf->writeHTML($table->getHtmlTable(), true, false, true);

    $file = ADMIDIO_PATH . FOLDER_DATA . '/' . $filename;

    // Save PDF to file
    $pdf->Output($file, 'F');

    // Redirect
    header('Content-Type: application/pdf');

    readfile($file);
    ignore_user_abort(true);

    try
    {
        FileSystemUtils::deleteFileIfExists($file);
    }
    catch (\RuntimeException $exception)
    {
        $gLogger->error('Could not delete file!', array('filePath' => $file));
        // TODO
    }
}
elseif ($getMode == 'xlsx')
{
    header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    $writer = new XLSXWriter();
    $writer->setAuthor($gCurrentUser->getValue('FIRST_NAME').' '.$gCurrentUser->getValue('LAST_NAME'));
    $writer->setTitle($filename);
    $writer->setSubject($gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'));
    $writer->setCompany($gCurrentOrganization->getValue('org_longname'));
    $writer->setKeywords(array($gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), $gL10n->get('PLG_GEBURTSTAGSLISTE_PATTERN')));
    $writer->setDescription($gL10n->get('PLG_GEBURTSTAGSLISTE_CREATED_WITH'));
    $writer->writeSheet($rows,'', $header);
    $writer->writeToStdOut();
}
elseif ($getMode == 'html' && $getExportAndFilter)
{ 
    $page->addHtml('<div style="width:100%; height: 500px; overflow:auto; border:20px;">');
    $page->addHtml($table->show(false));
    $page->addHtml('</div><br/>');
   
    $page->show();
}
elseif (($getMode == 'html' && !$getExportAndFilter) || $getMode == 'print')
{
    $page->addHtml($table->show(false));
    
    $page->show();
}
