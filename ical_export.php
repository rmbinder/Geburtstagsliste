<?php
/**
 ***********************************************************************************************
 * Modul iCal fuer das Admidio-Plugin birthday list
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * config		: Die gewaehlte Konfiguration
 * month		: Der gewaehlte Monat
 * previewdays	: Die vorauszuschauenden Tage
 * filter		: Filterstring
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
require_once(__DIR__ . '/classes/genlist.php');

// Konfiguration einlesen          
$pPreferences = new ConfigTablePGL();
$pPreferences->read();

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

$getMonth        = admFuncVariableIsValid($_GET, 'month', 'string', array('validValues' => array('00','01', '02', '03', '04', '05', '06','07','08','09','10','11','12' )));
$getFilter       = admFuncVariableIsValid($_GET, 'filter', 'string');

$liste = new GenList($getConfig, $getPreviewDays, $getMonth);
$liste->generate_listData();

$markerName = false;
$markerBirthday = false;
for ($i = 1; $i < count($liste->headerData); $i++)
{
    // bei Profilfeldern ist in 'id' die 'usf_id', ansonsten 0
    if (substr($liste->headerData[$i]['id'], 0, 1) == 'r')          //relationship
    {
        //Spalten mit Beziehungen überspringen
        continue;
    }
    else
    {
        $usf_id = (int) $liste->headerData[$i]['id'];
    }
    
    // Prüfung einbauen: folgende Profilfelder müssen vorhanden sein: Geburtstag und (Vorname oder Nachname) 
    if ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'LAST_NAME')
    {
        $markerName = true;
    }
    if ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'FIRST_NAME')
    {
        $markerName = true;
    }
    if ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'BIRTHDAY')
    {
        $markerBirthday = true;
    }
} 

if (!$markerName || !$markerBirthday || $pPreferences->config['Konfigurationen']['suppress_age'][$liste->conf])
{
    $gMessage->show($gL10n->get('PLG_GEBURTSTAGSLISTE_ICAL_MISSING_DATA'), $gL10n->get('SYS_PROCESS_CANCELED'));
}

$iCal = getIcalHeader();

// die Daten einlesen
foreach ($liste->listData as $memberdata) 
{
	$contentArray = array(
	    'USER_UUID'       => $memberdata[0]['usr_uuid'],
	    'LAST_NAME'       => '',
	    'FIRST_NAME'      => '',
	    'BIRTHDAY'        => '',
	    'CITY'            => '',
	    'LASTCOL_AGEONLY' => '',
	    'LASTCOL'         => '');
	
	$contentFilter = '';
	
    // Felder zu Datensatz
    for ($i = 1; $i < count($memberdata)-1; $i++)
    {         
        /*****************************************************************/
        // create output format
       	/*****************************************************************/
        $content = $memberdata[$i];
        
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
         	&& $content > 0
         	&& ( $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DROPDOWN'
              || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'RADIO_BUTTON') )
        {
            // show selected text of optionfield or combobox
            $arrListValues = $gProfileFields->getPropertyById($usf_id, 'usf_value_list', 'text');
            $content       = $arrListValues[$content];
        }
        if (($usf_id  != 0) && (substr($liste->headerData[$i]['id'], 0, 1) != 'r') )            //ohne Beziehungen
        {
            if ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'LAST_NAME')
            {
                $contentArray['LAST_NAME'] = $content;
            }
            elseif ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'FIRST_NAME')
            {
                $contentArray['FIRST_NAME'] = $content;
            }
            elseif ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'BIRTHDAY')
            {
                $contentArray['BIRTHDAY'] = $content;
            }
            elseif ( $gProfileFields->getPropertyById($usf_id, 'usf_name_intern') == 'CITY')
            {
                $contentArray['CITY'] = $content;
            }
        }
        $contentFilter .= $content;
    }
    
    //die letzte Spalte (nach der for-Schleife zeigt $i darauf) ist "hard coded", darin steht immer das Alter und ein evtl. vorhandener Ergänzungstext
    $contentArray['LASTCOL'] = $memberdata[$i];
    $contentArray['LASTCOL_AGEONLY'] = intval($memberdata[$i]);
    $contentFilter .= $memberdata[$i];
    
    if ($getFilter == '' || ($getFilter <> '' && stristr($contentFilter, $getFilter)))
    {
        $iCal .= getIcalVEvent(DOMAIN, $contentArray);
    }
}  // End-For (jeder gefundene User)

$iCal .= getIcalFooter();

$filename = FileSystemUtils::getSanitizedPathEntry($pPreferences->config['Konfigurationen']['col_desc'][trim($getConfig,'X')]) . '.ics';
    
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="'. $filename. '"');
    
// necessary for IE, because without it the download with SSL has problems
header('Cache-Control: private');
header('Pragma: public');
    
echo $iCal;
    
/**
 * gibt den Kopf eines iCalCalenders aus
 * aus der Admidio Klasse TableDate
 * @return string
 */
function getIcalHeader()
{
    $defaultTimezone = date_default_timezone_get();
    
    $icalHeader = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//www.admidio.org//Admidio' . ADMIDIO_VERSION . '//DE',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-TIMEZONE:' . $defaultTimezone,
        'BEGIN:VTIMEZONE',
        'TZID:' . $defaultTimezone,
        'X-LIC-LOCATION:' . $defaultTimezone,
        'BEGIN:STANDARD',
        'DTSTART:19701025T030000',
        'TZOFFSETFROM:+0200',
        'TZOFFSETTO:+0100',
        'TZNAME:CET',
        'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10',
        'END:STANDARD',
        'BEGIN:DAYLIGHT',
        'DTSTART:19700329T020000',
        'TZOFFSETFROM:+0100',
        'TZOFFSETTO:+0200',
        'TZNAME:CEST',
        'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3',
        'END:DAYLIGHT',
        'END:VTIMEZONE'
    );
    
    return implode("\r\n", $icalHeader) . "\r\n";
}
  
/**
 * gibt den Fuß eines iCalCalenders aus
 * aus der Admidio Klasse TableDate
 * @return string
 */
function getIcalFooter()
{
    return 'END:VCALENDAR';
}
    
/**
 * gibt einen einzelnen Termin im iCal-Format zurück
 * aus der Admidio Klasse TableDate (modifiziert)
 * @param string $domain
 * @param array $contentArray
 * @return string
 */
function getIcalVEvent($domain, $contentArray)
{
    $dateTimeFormat = 'Ymd\THis';
    
    $iCalVEvent = array(
        'BEGIN:VEVENT',
        'CREATED:' . date($dateTimeFormat)
    );
    
    $iCalVEvent[] = 'UID:' . date($dateTimeFormat) . '+' .$contentArray['USER_UUID']. '@' . $domain;
    $iCalVEvent[] = 'SUMMARY:' . preg_replace('/\s+/', ' ',escapeIcalText($contentArray['FIRST_NAME'].' '.$contentArray['LAST_NAME'].' ('.$contentArray['LASTCOL_AGEONLY'].')'));
    $iCalVEvent[] = 'DESCRIPTION:' . escapeIcalText($contentArray['LASTCOL']);
    $iCalVEvent[] = 'DTSTAMP:' . date($dateTimeFormat);
    $iCalVEvent[] = 'LOCATION:' . escapeIcalText($contentArray['CITY']);
    
    // das Ende-Datum bei mehrtaegigen Terminen muss im iCal auch + 1 Tag sein
    // Outlook und Co. zeigen es erst dann korrekt an
    $birthdayDate = \DateTime::createFromFormat('d.m.Y', $contentArray['BIRTHDAY']);
    $xYearOffset = new \DateInterval('P'.$contentArray['LASTCOL_AGEONLY'].'Y');
    $oneDayOffset = new \DateInterval('P1D');

    $iCalVEvent[] = 'DTSTART;VALUE=DATE:' . $birthdayDate->add($xYearOffset)->format('Ymd');
    $iCalVEvent[] = 'DTEND;VALUE=DATE:' . $birthdayDate->add($oneDayOffset)->format('Ymd');
        
    $iCalVEvent[] = 'END:VEVENT';
    
    return implode("\r\n", $iCalVEvent) . "\r\n";
}
    
/**
 * aus der Admidio Klasse TableDate
 * @param string $text
 * @return string
 */
function escapeIcalText($text)
{
    $replaces = array(
        '\\' => '\\\\',
        ';'  => '\;',
        ','  => '\,',
        "\n" => '\n',
        "\r" => '',
        '<br />' => '\n' // workaround
    );
    
    return trim(StringUtils::strMultiReplace($text, $replaces));
}
    
