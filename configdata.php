<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin Geburtstagsliste
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

global $gL10n, $gProfileFields;

//Standardwerte einer Neuinstallation oder beim Anfügen einer zusätzlichen Konfiguration
$config_default['Pluginfreigabe']['freigabe'] 			= array(getRole_IDPGL($gL10n->get('SYS_WEBMASTER')),
															getRole_IDPGL($gL10n->get('SYS_MEMBER')) );    		
$config_default['Pluginfreigabe']['freigabe_config'] 	= array(getRole_IDPGL($gL10n->get('SYS_WEBMASTER')),
															getRole_IDPGL($gL10n->get('SYS_MEMBER')) );        		

$config_default['Konfigurationen'] = array('col_desc' 	=> array($gL10n->get('PLG_GEBURTSTAGSLISTE_PATTERN')),
										'col_sel' 		=> array('p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
										'col_values' 	=> array('50,60,70,80'),
										'col_suffix' 	=> array('. Geburtstag am #Day#.#Month#.#Year#'),
										'col_fields' 	=> array(	$gProfileFields->getProperty('FIRST_NAME', 'usf_id').','.
																	$gProfileFields->getProperty('LAST_NAME', 'usf_id').','.
																	$gProfileFields->getProperty('ADDRESS', 'usf_id').','.
																	$gProfileFields->getProperty('CITY', 'usf_id')),
										'calendar_year' => array(''),
										'years_offset'  => array('0'),
										'suppress_age'  => array('0'),																	
										'selection_role'=> array(' '),
										'selection_cat'	=> array(' ')  );

$config_default['Optionen']['vorschau_tage_default'] = 365;    		
$config_default['Optionen']['vorschau_liste'] = array(-14,0,14,31,365,1000);    		
$config_default['Optionen']['config_default'] = 0; 
$config_default['Optionen']['configuration_as_header'] = 0; 
      
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

/*
 *  Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert. 
 *  Müssen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung 
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten 
 *  nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuändern (z.B. in !-!)
 *  
 *  Achtung: Vor einer Änderung muss eine Deinstallation durchgeführt werden!
 *  Bereits gespeicherte Werte in der Datenbank können nach einer Änderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';  
