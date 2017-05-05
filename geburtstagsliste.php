<?php
/**
 ***********************************************************************************************
 * Geburtstagsliste
 *
 * Version 2.3.0
 *
 * Dieses Plugin erzeugt fuer einen bestimmten Zeitraum eine Geburtstags- und Jubilaeumsliste der Mitglieder.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 3.3
 *
 * @copyright 2004-2017 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
  
//$gNavigation ist zwar definiert, aber in bestimmten Faellen in diesem Script nicht sichtbar
global $gNavigation;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$plugin_folder = '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1);

// Einbinden der Sprachdatei
$gL10n->addLanguagePath(ADMIDIO_PATH . FOLDER_PLUGINS . $plugin_folder . '/languages');

$pPreferences = new ConfigTablePGL();

// Initialisierung und Anzeige des Links nur, wenn vorher keine Deinstallation stattgefunden hat
// sonst waere die Deinstallation hinfaellig, da hier wieder Default-Werte der config in die DB geschrieben werden
// Zweite Voraussetzung: Ein User muss erfolgreich eingeloggt sein
if (strpos($gNavigation->getUrl(), 'preferences_function.php?mode=3') === false && $gValidLogin)
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
	if (check_showpluginPGL($pPreferences->config['Pluginfreigabe']['freigabe']))
	{
		if (isset($pluginMenu))
		{
			// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, 
			// dann innerhalb dieses Menues anzeigen
			$pluginMenu->addItem('birthdaylist_show', FOLDER_PLUGINS . $plugin_folder .'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menues anzeigen
			$moduleMenu->addItem('birthdaylist_show', FOLDER_PLUGINS . $plugin_folder .'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
	}
}		
