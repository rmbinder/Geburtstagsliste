<?php
/******************************************************************************
 * Geburtstagsliste
 *
 * Version 2.0.1
 *
 * Dieses Plugin erzeugt für einen bestimmten Zeitraum eine Geburtstags- und Jubiläumsliste der Mitglieder.
 * 
 * Die erzeugte Liste wird am Bildschirm angezeigt und kann auch exportiert werden.
 *  
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * Autor		    : rmb 
 * 
 * Version		  : 2.0.1
 * Datum        : 02.11.2015
 * Änderung     : - Fehler (verursacht durch die Methode addHeadline) behoben 
 * 
 * Version		: 2.0.1
 * Datum        : 31.10.2015
 * Änderung     : - Anpassung an den mit Admidio 3.0.2 geänderten Funktionsaufruf (addHeadline -> setHeadline)
 * 
 * Version		: 2.0.0
 * Datum        : 26.05.2015
 * Änderung     : - Anpassung an Admidio 3.0
 * 				  - Deinstallationsroutine erstellt
 * 				  - Verfahren zum Einbinden des Plugins (include) geändert 
 *                - Berechnungsalgorithmus umgestellt (dadurch unbegrenzte Vorschau von Tagen möglich)
 *                - Menübezeichnungen angepasst (gleichlautend mit anderen Plugins) 
 * 				        - Nur Intern: Verwaltung der Konfigurationsdaten geändert
 *  
 * Version 		: 1.3.5
 * Datum        : 05.11.2014
 * Änderung    	: - Für den Export sind diverse Parameter jetzt im Setup einstellbar
 * 				        - Bei Namensgleichheit von Profilfeldern wird die Kategorie in Klammern angehängt
 * 				        - Die Option Kalenderjahr kann für jede Konfiguration separat eingestellt werden
 * 
 * Version 		: 1.3.4
 * Datum        : 15.05.2014
 * Änderung     : - Fehler ...indefined index:...beim ersten Aufruf des Plugins behoben
 *                - Default-Einstellungen um zusätzliche Einträge ergänzt
 *                - Aufruf des Plugins über die Klasse Menu realisiert
 * 				          (Systemanforderung jetzt Admidio Version 2.4.4 oder höher)
 * 				        - Anpassung von Menübezeichnungen
 * 				        - E-Mail-Texte für jede Konfiguration (Fokus) separat definierbar
 * 
 * Version 		: 1.3.3
 * Datum        : 09.01.2014
 * Änderung     : - Fehler ...indefined index:...language.php line 272... behoben
 *                - Vorschautage kleiner 10 benötigen keine führende Null mehr
 *                - negative Werte für die Vorschau möglich
 * 
 * Version 		: 1.3.2 
 * Datum        : 21.11.2013
 * Änderung     : - Kompatibilität zu PHP 5.4
 * 
 * Version 		: 1.3.1 
 * Datum        : 12.11.2013
 * Änderung     : - Default-Einstellung für Fokus
 * 				        - Diverse Überprüfungen eingearbeitet
 *                - Anzeigemodus überarbeitet
 *                - E-Mail-Modul eingearbeitet
 *                - Die Konfigurationen können auf bestimmte Rollen 
 *                  und/oder Kategorien einschränkt werden  
 * 
 * Version 		: 1.3.0 
 * Datum        : 01.04.2013
 * Änderung     : - Anpassung an Admidio 2.4
 * 				  - Konfigurationsdaten werden nicht mehr in einer config.ini gespeichert,
 * 				    sondern in der Admidio Datenbank abgelegt
 * 				  - Das Menü Einstellungen kann separat über Berechtigungen angezeigt werden
 * 				  - Aufgrund eines Wunsches im Forum kann der Beginn der Anzeige auf den 1. Januar
 * 					gesetzt werden (Kalenderjahr)
 * 				  - E-Mail-Adressen werden mit einem Link versehen (DieterB) 
 * 				  - Englische Sprachdatei erstellt 
 * 				  - Die Default-Einstellung der Pluginfreigabe wurde erweitert um die Rolle Mitglied 
 *  
 * Version 		: 1.2.3 
 * Datum        : 26.12.2012
 * Änderung     : - Alle Einstellungen sind menuegesteuert veraenderbar  
 *                - Eine deutsche Sprachdatei wurde erstellt
 *                - Das Plugin ist für mehrere Organisationen geeeignet 
 *                - Ein Fehler in der Berechnung des Zeitraumes wurde behoben (Für den Zeitraum...)
 *  
 * Version 		: 1.2.2 
 * Datum        : 01.03.2012
 * Änderung     : - Jubiläen können angezeigt werden 
 *                - die Jahre für die Jubliläen und runden Geburtstage können 
 *                  in der config.php definiert werden 
 *                - Die Spaltenüberschriften für Jubiläen und runde Geburtstage
 *                  sind frei definierbar   
 *                - Das Suffix in der Anzeige von Jubiläen und runden Geburtstagen
 *                  ist frei definierbar  
 *  
 * Version 		: 1.2.1 
 * Datum        : 23.02.2012
 * Änderung     : - über ein Pulldownmenü kann direkt ein bestimmter Monat gewählt werden 
 *                - das Jahr des Geburtsdatums kann angezeigt werden 
 *                - runde Geburtstage können angezeigt werden   
 *                 
 * Version 		: 1.2.0 
 * Datum        : 21.02.2012
 * Änderung     : - das Plugin ist jetzt Admidio 2.3 kompatibel 
 * 
 
 * Version 		: 1.1.2
 * Datum        : 08.12.2011
 * Änderung     : - das Standard-Datenbankpräfix (adm_) ist nicht mehr fest kodiert
 * 
 * Version 		: 1.1.1  
 * Datum        : 21.11.2011
 * Änderung     : - Bei Mitgliedern ohne Geburtsdatum wurde der 01.01. eingetragen,
 *                  dies wurde korrigiert. Mitglieder ohne Geburtsdatum werden 
 *                  nicht mehr in der Liste aufgeführt. 
 *                - Die Einschränkung in einer Abfrage in geburtstagsliste_show.php,
 *                  auf nur Mitglieder der Rolle "Mitglied" wurde aufgehoben. 
 *                - Die Default-Einstellung für die Vorschautage kann jetzt in
 *                  der config.php definiert werden.  
 *                - Beim Export wurden die Vorschautage nicht aktualisiert. 
 *                - Die Einträge im Pulldownmenü "Anzahl der Vorschautage..."
 *                  können jetzt in der config.php definiert werden  
 *                - Die Berechtigung das Plugin aufzurufen, wurde um 
 *                  Rollenmitgliedschaften erweitert.
 *
 * Version 		: 1.1.0  
 * Datum        : 26.10.2011
 * Änderung     : Für das Plugin wurde eine Weboberfläche erstellt.
 *                Die erzeugte CSV-Datei wird nicht mehr auf dem Server 
 *                zwischengespeichert, sie wird in der Listenansicht zum
 *                Download angeboten. Das zusätzliche Plugin downloadfile.php
 *                wird nicht mehr benötigt.      
 *                
 * Version 		: 1.0.0
 * Datum        : 11.07.2011  
 *                  
 *****************************************************************************/
  
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

// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

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
				$gL10n->get('PGL_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('birthdaylist_show', '/adm_plugins/'.$plugin_folder.'/geburtstagsliste_show.php?mode=html',
				$gL10n->get('PGL_BIRTHDAY_LIST'), '/icons/lists.png'); 
		}
	}
}		

?>