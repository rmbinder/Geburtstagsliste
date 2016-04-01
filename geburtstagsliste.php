<?php
/**
 ***********************************************************************************************
 * Geburtstagsliste
 *
 * Version 2.1.0
 *
 * Dieses Plugin erzeugt für einen bestimmten Zeitraum eine Geburtstags- und Jubiläumsliste der Mitglieder.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 3.1
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
  
//$gNaviagation ist zwar definiert, aber in bestimmten Fällen in diesem Script nicht sichtbar
global $gNavigation;

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');

$pPreferences = new ConfigTablePGL();

//Initialisierung und Anzeige des Links nur, wenn vorher keine Deinstallation stattgefunden hat
// sonst wäre die Deinstallation hinfällig, da hier wieder Default-Werte der config in die DB geschrieben werden
if(  strpos($gNavigation->getUrl(), 'preferences_function.php?mode=3') === false)
{
	if ($pPreferences->checkforupdate())
	{
		$pPreferences->init();
	}
	else 
	{
		$pPreferences->read();
	}

	// Zeige Link zum Plugin
	if(check_showpluginPGL($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		if (isset($pluginMenu))
		{
			// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, 
			// dann innerhalb dieses Menüs anzeigen
			$pluginMenu->addItem('birthdaylist_show', '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('birthdaylist_show', '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
	}
}		
