<?php
/**
 ***********************************************************************************************
  * Geburtstagsliste
 *
 * Version 2.3.3
 *
 * Dieses Plugin erzeugt fuer einen bestimmten Zeitraum eine Geburtstags- und Jubilaeumsliste der Mitglieder.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 3.3
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * mode   		: Output (html, print, csv-ms, csv-oo, pdf, pdfl)
 * full_screen  : 0 - (Default) show sidebar, head and page bottom of html page
 *                1 - Only show the list without any other html unnecessary elements
 * config		: Die gewaehlte Konfiguration (Alte Bezeichnung Fokus; die Standardeinstellung wurde über Einstellungen-Optionen festgelegt)
 * month		: Der gewaehlte Monat
 * previewdays	: Die vorauszuschauenden Tage (Default wurde in Optionen festgelegt)
 * previewmode	: days   - (Default) Die Anzeige einer bestimmten Anzahl von Tagen wurde gewaehlt
 * 				  months - Die Anzeige für einen Monat wurde gewaehlt
 * filter_enable: 0 - (Default) No filter option
 *                1 - Filter option is enabled
 * filter		: Filter string
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
require_once(__DIR__ . '/classes/genlist.php');

//$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/geburtstagsliste...
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

$getMode         = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl' )));
$getFullScreen   = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getMonth        = admFuncVariableIsValid($_GET, 'month', 'string', array('validValues' => array('00','01', '02', '03', '04', '05', '06','07','08','09','10','11','12' )));
$getFilterEnable = admFuncVariableIsValid($_GET, 'filter_enable', 'numeric');
$getFilter       = admFuncVariableIsValid($_GET, 'filter', 'string');

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
$filename = $g_organization.'-'.$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST');

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

$str_csv = '';   // enthaelt die komplette CSV-Datei als String

$numMembers = count($liste->listData);

//die Spaltenanzahl bestimmen
$columnCount = count($liste->headerData);

// if html mode and last url was not a list view then save this url to navigation stack
if ($getMode == 'html' && strpos($gNavigation->getUrl(), 'geburtstagsliste.php') === false)
{
    $gNavigation->addUrl(CURRENT_URL);
}

if ($getMode != 'csv')
{
    $datatable = false;
    $hoverRows = false;

    if ($getMode == 'print')
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
    elseif ($getMode == 'pdf')
    {
        if (ini_get('max_execution_time') < 300)
    	{
    		ini_set('max_execution_time', 300); //300 seconds = 5 minutes
    	}
        require_once(ADMIDIO_PATH. FOLDER_LIBS_SERVER .'/tcpdf/tcpdf.php');
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
        $datatable = true;
        $hoverRows = true;

        // create html page object
        $page = new HtmlPage($headline.'<h3>'.$subheadline.'</h3>');

        if ($getFullScreen == true)
        {
        	$page->hideThemeHtml();
        }

        $page->setTitle($title);
        $page->addJavascript('
            $("#previewList").change(function () {
                if($(this).val().length > 1) {
                    self.location.href = "'.safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                        'mode'          => 'html',
                        'month'         => $getMonth,
                        'filter'        => $getFilter,
                        'filter_enable' => $getFilterEnable,
                        'full_screen'   => $getFullScreen,
                        'config'        => $getConfig
                    )) . '&previewdays=" + $(this).val();
                }
            });
            $("#monthList").change(function () {
                if($(this).val().length > 0) {
                    self.location.href = "'.safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                        'mode'          => 'html',
                        'previewdays'   => $getPreviewDays,
                        'filter'        => $getFilter,
                        'filter_enable' => $getFilterEnable,
                        'full_screen'   => $getFullScreen,
                        'config'        => $getConfig
                    )) . '&month=" + $(this).val();
                }
            });
            $("#configList").change(function () {
            	if($(this).val().length > 1) {
                    self.location.href = "'.safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                        'mode'          => 'html',
                        'previewdays'   => $getPreviewDays,
                        'filter'        => $getFilter, 
                        'filter_enable' => $getFilterEnable, 
                        'full_screen'   => $getFullScreen,
                        'month'         => $getMonth
                    )) . '&config=" + $(this).val();
                }
            });
            $("#menu_item_print_view").click(function() {
                window.open("'.safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                    'previewdays'   => $getPreviewDays, 
                    'filter'        => $getFilter, 
                    'filter_enable' => $getFilterEnable, 
                    'month'         => $getMonth, 
                    'config'        => $getConfig, 
                    'mode'          => 'print'
                )) . '", "_blank");
            });
        ', true);
        
        // get module menu
        $listsMenu = $page->getMenu();
        
        if ($getFullScreen == true)
        {
            $listsMenu->addItem('menu_item_normal_picture', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                'mode'          => 'html',
                'previewdays'   => $getPreviewDays,
                'filter'        => $getFilter,
                'month'         => $getMonth,
                'full_screen'   => 0,
                'config'        => $getConfig,
                'filter_enable' => $getFilterEnable)),
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png'
            );
        }
        else
        {
            $listsMenu->addItem('menu_item_full_screen', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                'mode'          => 'html',
                'previewdays'   => $getPreviewDays,
                'filter'        => $getFilter,
                'month'         => $getMonth,
                'full_screen'   => 1,
                'config'        => $getConfig,
                'filter_enable' => $getFilterEnable)),
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png'
            );
        }
       
        // link to print overlay, exports, filter and preferences
        $listsMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
       
        if ($getFilterEnable == true)
        {
            $listsMenu->addItem('filter_disable', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                'mode'          => 'html',
                'previewdays'   => $getPreviewDays,
                'filter'        => $getFilter,
                'month'         => $getMonth,
                'full_screen'   => $getFullScreen,
                'config'        => $getConfig,
                'filter_enable' => 0)),
        		$gL10n->get('SYS_FILTER'), 'checkbox_checked.gif'
        	);
        }
        else
        {
            $listsMenu->addItem('filter_enable', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
                'mode'          => 'html',
                'previewdays'   => $getPreviewDays,
                'filter'        => $getFilter,
                'month'         => $getMonth,
                'full_screen'   => $getFullScreen,
                'config'        => $getConfig,
                'filter_enable' => 1)),
        		$gL10n->get('SYS_FILTER'), 'checkbox.gif'
            );
        	
        	// if filter is not enabled, reset filterstring
        	$getFilter = '';
        }
        
        $listsMenu->addItem('menu_item_export', '', $gL10n->get('LST_EXPORT_TO'), '', 'left');
        
        $listsMenu->addItem('admMenuItemExcel', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
            'previewdays'   => $getPreviewDays,
            'filter'        => $getFilter,
            'filter_enable' => $getFilterEnable,
            'month'         => $getMonth,
            'config'        => $getConfig,
            'mode'          => 'csv-ms')),
            $gL10n->get('LST_MICROSOFT_EXCEL').' ('.$gL10n->get('SYS_ISO_8859_1').')', '', 'left', 'menu_item_export'
        );
        $listsMenu->addItem('admMenuItemCsv', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
            'previewdays'   => $getPreviewDays,
            'filter'        => $getFilter,
            'filter_enable' => $getFilterEnable,
            'month'         => $getMonth,
            'config'        => $getConfig,
            'mode'          => 'csv-oo')),
            $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')', '', 'left', 'menu_item_export'
        );
        $listsMenu->addItem('admMenuItemPdf', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
            'previewdays'   => $getPreviewDays,
            'filter'        => $getFilter,
            'filter_enable' => $getFilterEnable,
            'month'         => $getMonth,
            'config'        => $getConfig,
            'mode'          => 'pdf')),
            $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_PORTRAIT').')', '', 'left', 'menu_item_export'
        );
        $listsMenu->addItem('admMenuItemPdfL', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', array(
            'previewdays'   => $getPreviewDays,
            'filter'        => $getFilter,
            'filter_enable' => $getFilterEnable,
            'month'         => $getMonth,
            'config'        => $getConfig,
            'mode'          => 'pdfl')),
            $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_LANDSCAPE').')', '', 'left', 'menu_item_export'
        );
        $listsMenu->addItem('admMenuItemICal', safeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/ical_export.php', array(
            'previewdays' => $getPreviewDays,
            'config'      => $getConfig,
            'month'       => $getMonth,
            'filter'      => $getFilter)),
            $gL10n->get('PLG_GEBURTSTAGSLISTE_ICAL'), '', 'left', 'menu_item_export'
        );
        
        if ($gCurrentUser->isAdministrator())
        {
        	// show link to pluginpreferences
        	$listsMenu->addItem('admMenuItemPreferencesLists', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php',
        			$gL10n->get('PLG_GEBURTSTAGSLISTE_SETTINGS'), 'options.png', 'right');
        }
        
        // input field for filter
        if ($getFilterEnable == true)
        {
        	// create filter menu
        	$filterNavbar = new HtmlNavbar('menu_list_filter', null, null, 'filter');
        	$form = new HtmlForm('navbar_filter_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', $page, array('type' => 'navbar', 'setFocus' => false));
        	$form->addInput('filter', '', $getFilter);
        	$form->addInput('previewdays', '', $getPreviewDays, array('property' => FIELD_HIDDEN));
        	$form->addInput('full_screen', '', $getFullScreen, array('property' => FIELD_HIDDEN));
        	$form->addInput('month', '', $getMonth, array('property' => FIELD_HIDDEN));
        	$form->addInput('config', '', $getConfig, array('property' => FIELD_HIDDEN));
        	$form->addInput('mode', '', 'html', array('property' => FIELD_HIDDEN));
        	$form->addInput('filter_enable', '',$getFilterEnable, array('property' => FIELD_HIDDEN));
        	$form->addSubmitButton('btn_send', $gL10n->get('SYS_OK'));
        	$filterNavbar->addForm($form->show(false));
        	$page->addHtml($filterNavbar->show());
        }
        
        $form = new HtmlForm('navbar_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
		
        if ($getFullScreen == false)
        {
        	$listsMenu->addForm($form->show(false));
        	
        	// in normal screen mode create extra menu with elements for selection of configuration, months, days
        	$selectionNavbar = new HtmlNavbar('menu_selection');
        	$form = new HtmlForm('navbar_selection_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/geburtstagsliste.php', $page, array('type' => 'navbar', 'setFocus' => false));
        }
        
        $selectBoxEntries = array(' ' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SELECT_CONFIGURATION').' ...');
        foreach ($pPreferences->config['Konfigurationen']['col_desc'] as $key => $item)
        {
        	$selectBoxEntries['X'.$key.'X'] = $item;
        }
        $form->addSelectBox('configList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array('' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SELECT_NUMBER_OF_DAYS').' ...');
        foreach ($pPreferences->config['Optionen']['vorschau_liste'] as $item)
        {
        	// eine 0 in der Vorschauliste wird nicht korrekt dargestellt, deshalb alle Werte maskieren
        	$selectBoxEntries['X'.$item.'X'] =  $item;
        }
        $form->addSelectBox('previewList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        $selectBoxEntries = array('' => $gL10n->get('PLG_GEBURTSTAGSLISTE_SELECT_MONTH').' ...');
        foreach ($monate as $key => $item)
        {
        	$selectBoxEntries[$key] =  $item;
        }
        $form->addSelectBox('monthList', null, $selectBoxEntries, array('showContextDependentFirstEntry' => false));
        
        if ($getFullScreen == true)
        {
        	$listsMenu->addForm($form->show(false));
        }
        else 
        {
        	$selectionNavbar->addForm($form->show(false));
        	$page->addHtml($selectionNavbar->show());
        }
       
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
            $str_csv = $str_csv. $valueQuotes. $gL10n->get('SYS_ABR_NO'). $valueQuotes;
        }
        $str_csv = $str_csv. $separator. $valueQuotes. $columnHeader['data']. $valueQuotes;
    }
    elseif ($getMode == 'pdf')
    {
    	if ($columnNumber == 1)
        {
        	$table->addColumn($gL10n->get('SYS_ABR_NO'), array('style' => 'text-align: left;font-size:14;background-color:#C7C7C7;'), 'th');
        }
        $table->addColumn($columnHeader['data'], array('style' => 'text-align: left;font-size:14;background-color:#C7C7C7;'), 'th');
    }
    elseif ($getMode == 'html' || $getMode == 'print')
    {
    	$columnValues[] = $columnHeader['data'];
    }
    $columnNumber++;
} 

if ($getMode == 'csv')
{
    $str_csv = $str_csv. "\n";
}
elseif ($getMode == 'html' || $getMode == 'print')
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
foreach ($liste->listData as $memberdata) 
{
	$columnValues = array();
	$tmp_csv = '';
	
    // Felder zu Datensatz
    for ($i = 1; $i < count($memberdata); $i++)
    {         
    	if ($getMode == 'html' || $getMode == 'print' || $getMode == 'pdf')
        {    
        	if ($i == 1)
            {
            	// die Laufende Nummer noch davorsetzen
                $columnValues[] = $listRowNumber;  
            }
        }
        else
        {
            if ($i == 1)
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
        	&& $getMode == 'csv'
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
        // create output in html layout
        else
        {            	
        	if ($usf_id != 0 && $gProfileFields->getPropertyById($usf_id, 'usf_type') != 'EMAIL')     //only profileFields without EMAIL
        	{
        		$content = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usf_id, 'usf_name_intern'), $content, $memberdata[0]);
        	}
        	
            // if empty string pass a whitespace
			if (strlen($content) > 0)
            {
        		if ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'EMAIL')
        	 	{
        			if ($gPreferences['enable_mail_module'] != 1)
					{
						$mail_link = 'mailto:'. $content;
					}
					else
					{
						$mail_link = safeUrl(ADMIDIO_URL.FOLDER_PLUGINS.PLUGIN_FOLDER.'/message_write.php', array('usr_id' => $memberdata[0], 'config' => trim($getConfig,'X'), 'configtext' => end($memberdata)));
					}
					$columnValues[] = '<a href="'.$mail_link.'">'.$content.'</a><br />';
        		}
        		elseif ($getMode === 'html'
        		 	&&  (  $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'LAST_NAME'
        		 		|| $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'FIRST_NAME')
        		 	&& substr($liste->headerData[$i]['id'], 0, 1) != 'r')
        		{
        			$columnValues[] = '<a href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_id' => $memberdata[0])).'">'.$content.'</a>';
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
    		$str_csv .= $tmp_csv. "\n";
    	}
   	 	else
    	{
        	$table->addRowByArray($columnValues, null, array('nobr' => 'true'));
    	}
    	$listRowNumber++;
     }
}  // End-For (jeder gefundene User)

// Settings for export file
if ($getMode == 'csv' || $getMode == 'pdf')
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

if ($getMode == 'csv')
{
    // nun die erstellte CSV-Datei an den User schicken
    header('Content-Type: text/comma-separated-values; charset='.$charset);

    if ($charset === 'iso-8859-1')
    {
        echo utf8_decode($str_csv);
    }
    else
    {
        echo $str_csv;
    }
}
// send the new PDF to the User
elseif ($getMode == 'pdf')
{
    // output the HTML content
    $pdf->writeHTML($table->getHtmlTable(), true, false, true, false, '');
    
    //Save PDF to file
    $pdf->Output(ADMIDIO_PATH. FOLDER_DATA .'/'.$filename, 'F');
    
    //Redirect
    header('Content-Type: application/pdf');

    readfile(ADMIDIO_PATH. FOLDER_DATA .'/'.$filename);
    ignore_user_abort(true);
    unlink(ADMIDIO_PATH. FOLDER_DATA .'/'.$filename);
}
elseif ($getMode == 'html' || $getMode == 'print')
{    
    // add table list to the page
    $page->addHtml($table->show(false));

    // show complete html page
    $page->show();
}
