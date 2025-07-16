<?php
/**
 ***********************************************************************************************
 * Geburtstagsliste / birthday_list
 *
 * Version 4.0.0
 *
 * This plugin creates a birthday or anniversary list of members.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 5
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Birthdaylist\Config\ConfigTable;

//Fehlermeldungen anzeigen
error_reporting(E_ALL);

try {
    require_once(__DIR__ . '/../../system/common.php');
    require_once(__DIR__ . '/system/common_function.php');

    //$scriptName muss derselbe Name sein, wie er im Menue unter URL eingetragen ist
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName)) 
    {
        //throw new Exception('SYS_NO_RIGHTS');                     // über Exception wird nur SYS_NO_RIGHTS angezeigt
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
    else
    {
        // Konfiguration initialisieren       
        $pPreferences = new ConfigTable();
        if ($pPreferences->checkforupdate())
        {
	       $pPreferences->init();
        }
        
        admRedirect(ADMIDIO_URL . FOLDER_PLUGINS. PLUGIN_FOLDER . '/system/birthday_list.php');
    }
                                
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
