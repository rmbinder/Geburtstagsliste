<?php
/**
 ***********************************************************************************************
 * Uninstallation of the Admidio plugin Birthday list
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : html   - show dialog for uninstallation
 *            uninst - uninstallation procedure
 *
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Exception;
use Admidio\Menu\Entity\MenuEntry;
use Admidio\Roles\Entity\Role;
use Admidio\Roles\Entity\RolesRights;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\BirthdayList\classes\Config\ConfigTable;

try
{
	require_once(__DIR__ . '/../../../system/common.php');
	require_once(__DIR__ . '/common_function.php');

	// only authorized user are allowed to start this module
	if (!isUserAuthorizedForPreferences())
	{
	    throw new Exception('SYS_NO_RIGHTS');
	}
	
	$pPreferences = new ConfigTable();
	$pPreferences->read();

	// Initialize and check the parameters
	$getMode                       = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'uninst')));	
	$postUninstAccessRole          = admFuncVariableIsValid($_POST, 'uninst_access_role', 'bool');
	$postUninstAccessRoleOrgSelect = admFuncVariableIsValid($_POST, 'uninst_access_role_org_select', 'bool');
	$postUninstConfigData          = admFuncVariableIsValid($_POST, 'uninst_config_data', 'bool');
	$postUninstConfigDataOrgSelect = admFuncVariableIsValid($_POST, 'uninst_config_data_org_select', 'bool');
	$postUninstMenuItem            = admFuncVariableIsValid($_POST, 'uninst_menu_item', 'bool');

	switch ($getMode)
	{
		case 'html':
		
			global $gL10n;
			
			$title = $gL10n->get('PLG_BIRTHDAYLIST_UNINSTALLATION');
			$headline =$gL10n->get('PLG_BIRTHDAYLIST_UNINSTALLATION');
			
			$gNavigation->addUrl(CURRENT_URL, $headline);
			
			// create html page object
			$page = PagePresenter::withHtmlIDAndHeadline('plg-birthdaylist-uninstallation-html');
			$page->setTitle($title);
			$page->setHeadline($headline);
			
			$formUninstallation = new FormPresenter(
				'adm_preferences_form_uninstallation',
				'../templates/uninstallation.plugin.birthdaylist.tpl',
			    SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/uninstallation.php', array('mode' => 'uninst')),
				$page,
				array('class' => 'form-preferences')
			);
			
			$radioButtonEntries = array('0' => $gL10n->get('PLG_BIRTHDAYLIST_ACTORGONLY'), '1' => $gL10n->get('PLG_BIRTHDAYLIST_ALLORG') );
			
			$formUninstallation->addCheckbox('uninst_access_role', $gL10n->get('PLG_BIRTHDAYLIST_REMOVE_ACCESS_ROLE'));
			$formUninstallation->addRadioButton('uninst_access_role_org_select','',$radioButtonEntries, array('defaultValue' => '0', 'alertWarning' => $gL10n->get('PLG_BIRTHDAYLIST_UNINST_ACCESS_ROLE_ALERT')));
			
			$formUninstallation->addCheckbox('uninst_config_data', $gL10n->get('PLG_BIRTHDAYLIST_REMOVE_CONFIG_DATA'));
			$formUninstallation->addRadioButton('uninst_config_data_org_select','',$radioButtonEntries, array('defaultValue' => '0'));
			
			$formUninstallation->addCheckbox('uninst_menu_item', $gL10n->get('PLG_BIRTHDAYLIST_REMOVE_MENU_ITEM'), false, array('alertWarning' => $gL10n->get('PLG_BIRTHDAYLIST_UNINST_MENU_ITEM_ALERT')));
			
			$formUninstallation->addSubmitButton(
			    'adm_button_uninstallation',
			    $gL10n->get('PLG_BIRTHDAYLIST_UNINSTALLATION'),
			    array('icon' => 'bi-trash', 'class' => 'offset-sm-3')
			);
			
			$formUninstallation->addToHtmlPage(false);
			
			$page->show();

			break;
		
		case 'uninst':
		    
		    $result = $gL10n->get('PLG_BIRTHDAYLIST_UNINST_STARTMESSAGE');
		    
		    if ($postUninstAccessRole)
		    {
		        $result_role = true;
		        
		        $role = new Role($gDb);
		        
		        if ($postUninstAccessRoleOrgSelect)                                       //Zugriffsrollen in allen Orgs löschen
		        {
		            $access_roles = $pPreferences->getAllAccessRoles();                   //alle Zugriffsrollen org-übergreifend einlesen
		            foreach ($access_roles as $access_role)
		            {
		                $role->readDataById($access_role);
		                $result_role = $result_role && $role->delete();
		            }
		        }
		        else                                                                      //Zugriffsrolle nur in aktueller Org löschen
		        {
		            $role->readDataById($pPreferences->config['install']['access_role_id']);
		            $result_role = $result_role && $role->delete();
		        }
		        
		        $result  .= ($result_role ? $gL10n->get('PLG_BIRTHDAYLIST_UNINST_ACCESS_ROLE_SUCCESS') : $gL10n->get('PLG_BIRTHDAYLIST_UNINST_ACCESS_ROLE_ERROR') );
		    }
		    
		    if ($postUninstMenuItem)
		    {
		        $result_menu = false;
		        
		        $access_roles_prefs = $pPreferences->getAllAccessRoles();                 //Zugriffsrollen org-übergreifend einlesen
 
		        // den vorhandenen Menüpunkt einlesen und die Rollen die unter 'Sichtbar für' eingetragen sind, auslesen
		        $menu = new MenuEntry($gDb);
		        $menu->readDataByColumns(array('men_url' => FOLDER_PLUGINS. PLUGIN_FOLDER .'/index.php'));
		        $rightMenuView = new RolesRights($gDb, 'menu_view', $menu->getValue('men_id'));
		        $access_roles_menu = $rightMenuView->getRolesIds();
		        
		        $roles = array_intersect($access_roles_prefs, $access_roles_menu);
		        
		        if (count($roles) === 0)
		        {
		            $result_menu = $menu->delete();
		            $result .= ($result_menu ? $gL10n->get('PLG_BIRTHDAYLIST_UNINST_MENU_ITEM_SUCCESS') : $gL10n->get('PLG_BIRTHDAYLIST_UNINST_MENU_ITEM_ERROR') );
		        }
		        else
		        {
		            $result .= $gL10n->get('PLG_BIRTHDAYLIST_ACCESS_ROLES_STILL_PRESENT');
		        }
		    }
		    
		    if ($postUninstConfigData)
		    {
		        $result_data = false;
		        $result_db = false;
		        
		        if (!$postUninstConfigDataOrgSelect)                    //Konfigurationsdaten nur in aktueller Org loeschen
		        {
		            $sql = 'DELETE FROM '.$pPreferences->config['Plugininformationen']['table_name'].'
        			              WHERE plp_name LIKE ?
        			                AND plp_org_id = ? ';
		            $result_data = $gDb->queryPrepared($sql, array($pPreferences->config['Plugininformationen']['shortcut'].'__%', $gCurrentOrgId));
		        }
		        else                                                    //Konfigurationsdaten in allen Org loeschen
		        {
		            $sql = 'DELETE FROM '.$pPreferences->config['Plugininformationen']['table_name'].'
        			              WHERE plp_name LIKE ? ';
		            $result_data = $gDb->queryPrepared($sql, array($pPreferences->config['Plugininformationen']['shortcut'].'__%'));
		        }
		        
		        // wenn die Tabelle nur Eintraege dieses Plugins hatte, sollte sie jetzt leer sein und kann geloescht werden
		        $sql = 'SELECT * FROM '.$pPreferences->config['Plugininformationen']['table_name'].' ';
		        $statement = $gDb->queryPrepared($sql);
		        
		        if ($statement->rowCount() == 0)
		        {
		            $sql = 'DROP TABLE '.$pPreferences->config['Plugininformationen']['table_name'].' ';
		            $result_db = $gDb->queryPrepared($sql);
		        }
		        
		        $result .= ($result_data ? $gL10n->get('PLG_BIRTHDAYLIST_UNINST_DATA_DELETE_SUCCESS') : $gL10n->get('PLG_BIRTHDAYLIST_UNINST_DATA_DELETE_ERROR') );
		        $result .= ($result_db ? $gL10n->get('PLG_BIRTHDAYLIST_UNINST_TABLE_DELETE_SUCCESS') : $gL10n->get('PLG_BIRTHDAYLIST_UNINST_TABLE_DELETE_ERROR') );
		    }
		    
		    $gNavigation->clear();
		    $gMessage->setForwardUrl($gHomepage);
		    
		    $gMessage->show($result);
		    
			break;
	}

} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}