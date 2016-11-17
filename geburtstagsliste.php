<?php
/**
 ***********************************************************************************************
 * Geburtstagsliste
 *
 * Version 2.2.0
 *
 * Dieses Plugin erzeugt für einen bestimmten Zeitraum eine Geburtstags- und Jubiläumsliste der Mitglieder.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 3.2
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
  
//$gNaviagation ist zwar definiert, aber in bestimmten Fällen in diesem Script nicht sichtbar
global $gNavigation;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// Einbinden der Sprachdatei
$gL10n->addLanguagePath(ADMIDIO_PATH . FOLDER_PLUGINS . $plugin_folder . '/languages');

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
			$pluginMenu->addItem('birthdaylist_show', FOLDER_PLUGINS . $plugin_folder .'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('birthdaylist_show', FOLDER_PLUGINS . $plugin_folder .'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
	}
}		
