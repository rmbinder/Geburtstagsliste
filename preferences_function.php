<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Einstellungen des Admidio-Plugins Geburtstagsliste
 *
 * @copyright 2004-2017 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * mode     : 1 - Save preferences
 *            2 - show  dialog for deinstallation
 *            3 - deinstall
 * form     : The name of the form preferences that were submitted.
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePGL();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!check_showpluginPGL($pPreferences->config['Pluginfreigabe']['freigabe_config']))
{
	$gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'numeric', array('defaultValue' => 1));
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

// in ajax mode only return simple text on error
if ($getMode == 1)
{
    $gMessage->showHtmlTextOnly(true);
}

switch ($getMode)
{
case 1:
	
	try
	{
		switch($getForm)
    	{            	
            case 'configurations':
				unset($pPreferences->config['Konfigurationen']);
				$konf_neu = 0;
				
    			for ($conf = 0; isset($_POST['col_desc'. $conf]); $conf++)
    			{
    				if (empty($_POST['col_desc'. $conf]))	
    				{
    					continue;
    				}
    				else 
    				{
    					$text = new TableText($gDb);
    					$text->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrganization->getValue('org_id')));
						$text->setValue('txt_text', $_POST['col_mail'. $konf_neu]);
            			$text->save();
            			
            			$konf_neu++;
    				}
        			$pPreferences->config['Konfigurationen']['col_desc'][] = $_POST['col_desc'. $conf];
    				$pPreferences->config['Konfigurationen']['col_sel'][] = $_POST['col_sel'. $conf];
    				$pPreferences->config['Konfigurationen']['col_values'][] = trim(preg_replace('![^0-9,]!', '', $_POST['col_values'. $conf]),',');
    				$pPreferences->config['Konfigurationen']['col_suffix'][] = $_POST['col_suffix'. $conf]; 
    				$pPreferences->config['Konfigurationen']['calendar_year'][] = isset($_POST['calendar_year'. $conf]) ? 1 : 0 ;
    				$pPreferences->config['Konfigurationen']['suppress_age'][] = isset($_POST['suppress_age'. $conf]) ? 1 : 0 ;
    				$pPreferences->config['Konfigurationen']['years_offset'][] = isset($_POST['years_offset'. $conf]) ? $_POST['years_offset'. $conf] : 0 ;
                    $pPreferences->config['Konfigurationen']['relation'][] = $_POST['relationtype_id'. $conf];
                    
    				$pPreferences->config['Konfigurationen']['selection_role'][] = isset($_POST['selection_role'. $conf]) ? trim(implode(',',$_POST['selection_role'. $conf]),',') : ' ';
    				$pPreferences->config['Konfigurationen']['selection_cat'][] = isset($_POST['selection_cat'. $conf]) ? trim(implode(',',$_POST['selection_cat'. $conf]),',') : ' ';

    				$allColumnsEmpty = true;

    				$fields = '';
    				for ($number = 1; isset($_POST['column'.$conf.'_'.$number]); $number++)
    				{
        				if (strlen($_POST['column'.$conf.'_'.$number]) > 0)
        				{
        					$allColumnsEmpty = false;
            				$fields .= $_POST['column'.$conf.'_'.$number].',';
        				}
    				}
    			
    				if ($allColumnsEmpty)
    				{
    					$gMessage->show($gL10n->get('SYS_FIELD_EMPTY', $gL10n->get('PLG_GEBURTSTAGSLISTE_COLUMN')));
    				}

    				$pPreferences->config['Konfigurationen']['col_fields'][] = substr($fields,0,-1);	
    			}
    			    				
    			// die Fokusstandardeinstellung darf nicht groesser sein, als die max. Anzahl der Konfigurationen
    			if ($pPreferences->config['Optionen']['config_default'] > $konf_neu-1)
    			{
    				$pPreferences->config['Optionen']['config_default'] = $konf_neu-1;
    			}
    			
    			// wenn $konf_neu immer noch 0 ist, dann wurden alle Konfigurationen geloescht (was nicht sein darf)
    			if ($konf_neu == 0)
    			{
    				$gMessage->show($gL10n->get('PLG_GEBURTSTAGSLISTE_ERROR_MIN_CONFIG'));
    			}
    			else 
    			{
    				// nicht mehr benoetigte E-Mail-Texte in der DB loeschen (ansonsten bleiben Leichen!!)
            		$sql = 'DELETE FROM '.TBL_TEXTS.'
            				      WHERE txt_name LIKE \'PGLMAIL_NOTIFICATION%\'
            				        AND txt_name >= \'PGLMAIL_NOTIFICATION'. $konf_neu. '\'
            				        AND txt_org_id = '.$gCurrentOrganization->getValue('org_id').' ';
    				$gDb->query($sql);
    			}
            	break;
            	
        	case 'options':
   				$pPreferences->config['Optionen']['vorschau_tage_default'] = intval($_POST['vorschau_tage_default']>0) ? intval($_POST['vorschau_tage_default']) : 365;	    			
    			$pPreferences->config['Optionen']['vorschau_liste'] = explode(',',preg_replace('/[,]{2,}/', ',', trim(preg_replace('![^0-9,-]!', '', $_POST['vorschau_liste']),',')));		
 	        	$pPreferences->config['Optionen']['config_default'] = $_POST['config_default'];	
 	        	$pPreferences->config['Optionen']['configuration_as_header'] = isset($_POST['configuration_as_header']) ? 1 : 0 ;
            	break; 
                
        	case 'plugin_control':
            	if (isset($_POST['freigabe']))
            	{
    				$pPreferences->config['Pluginfreigabe']['freigabe'] = $_POST['freigabe'];
            	}
            	if (isset($_POST['freigabe_config']))
            	{
    				$pPreferences->config['Pluginfreigabe']['freigabe_config'] = $_POST['freigabe_config'];
            	}
    			break;
            
        	default:
           		$gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    	}
	}
	catch(AdmException $e)
	{
		$e->showText();
	}    
    
	$pPreferences->save();
	echo 'success';
	break;

case 2:
	
	$headline = $gL10n->get('PLG_GEBURTSTAGSLISTE_DEINSTALLATION');
	 
	// create html page object
    $page = new HtmlPage($headline);
    
    // add current url to navigation stack
    $gNavigation->addUrl(CURRENT_URL, $headline);
    
    // create module menu with back link
    $organizationNewMenu = new HtmlNavbar('menu_deinstallation', $headline, $page);
    $organizationNewMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
    $page->addHtml($organizationNewMenu->show(false));
    
    $page->addHtml('<p class="lead">'.$gL10n->get('PLG_GEBURTSTAGSLISTE_DEINSTALLATION_FORM_DESC').'</p>');

    // show form
    $form = new HtmlForm('deinstallation_form', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/preferences_function.php?mode=3', $page);
    $radioButtonEntries = array('0' => $gL10n->get('PLG_GEBURTSTAGSLISTE_DEINST_ACTORGONLY'), '1' => $gL10n->get('PLG_GEBURTSTAGSLISTE_DEINST_ALLORG') );
    $form->addRadioButton('deinst_org_select',$gL10n->get('PLG_GEBURTSTAGSLISTE_ORG_CHOICE'),$radioButtonEntries);    
    $form->addSubmitButton('btn_deinstall', $gL10n->get('PLG_GEBURTSTAGSLISTE_DEINSTALLATION'), array('icon' => THEME_URL .'/icons/delete.png', 'class' => ' col-sm-offset-3'));
    
    // add form to html page and show page
    $page->addHtml($form->show(false));
    $page->show();
    break;
    
case 3:
    
	$gNavigation->addUrl(CURRENT_URL);
	$gMessage->setForwardUrl($gHomepage);		

	$gMessage->show($gL10n->get('PLG_GEBURTSTAGSLISTE_DEINST_STARTMESSAGE').$pPreferences->delete($_POST['deinst_org_select']) );
   	break;
}
