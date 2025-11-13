<?php
/**
 ***********************************************************************************************
 * Geburtstagsliste / BirthdayList
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
use Plugins\BirthdayList\classes\Config\ConfigTable;
use Admidio\Infrastructure\Exception;

// Fehlermeldungen anzeigen
error_reporting(E_ALL);

try {
    require_once (__DIR__ . '/../../system/common.php');
    require_once (__DIR__ . '/system/common_function.php');

    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $gNavigation->addStartUrl(CURRENT_URL);
    
    $pPreferences = new ConfigTable();
    if ($pPreferences->checkforupdate()) {
        $pPreferences->init();
    }
    
    $pPreferences->read();
    if ($pPreferences->config['install']['access_role_id'] == 0 || $pPreferences->config['install']['menu_item_id'] == 0) {
        
        $urlInst = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install.php';
        $gMessage->show($gL10n->get('PLG_BIRTHDAYLIST_INSTALL_UPDATE_REQUIRED', array(
            '<a href="' . $urlInst . '">' . $urlInst . '</a>'
        )));
    }
    
    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/birthday_list.php');
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
