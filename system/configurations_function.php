<?php
/**
 ***********************************************************************************************
 * Preferences functions for the admidio plugin birthday list
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form     - The name of the form preferences that were submitted.
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Entity\Text;
use Admidio\Infrastructure\Exception;
use Plugins\BirthdayList\classes\Config\ConfigTable;

try {
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
    $getForm = admFuncVariableIsValid($_GET, 'form', 'string');

    switch ($getForm) {
        case 'configurations':
        
			unset($pPreferences->config['Konfigurationen']);
			
   			for ($conf = 0; isset($_POST['col_desc'. $conf]); $conf++)
   			{
   				$text = new Text($gDb);
   				$text->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrgId));
   				$text->setValue('txt_text', $_POST['col_mail'. $conf]);
           		$text->save();
           			
       			$pPreferences->config['Konfigurationen']['col_desc'][] = $_POST['col_desc'. $conf];
   				$pPreferences->config['Konfigurationen']['col_sel'][] = $_POST['col_sel'. $conf];
   				
   				// die eingegebenen Werte überprüfen und bereinigt in der Datenbank ablegen
   				// 1.: alle nicht relevanten Zeichen entfernen
   				$_POST['col_values'. $conf] = preg_replace('![^0-9,-;]!', '', $_POST['col_values'. $conf]);
   				// 2.: erstes Zeichen muss numerisch sein, wenn nicht: entfernen
   				while (preg_match('/^[^1-9]/', $_POST['col_values'. $conf]))
   				{
   				    $_POST['col_values'. $conf] = substr($_POST['col_values'. $conf],1);
   				}
   				// 3.: letztes Zeichen muss numerisch sein, wenn nicht: entfernen
   				while (preg_match('/[^0-9]$/', $_POST['col_values'. $conf]))
   				{
   				    $_POST['col_values'. $conf] = substr($_POST['col_values'. $conf],0,-1);
   				}
   				// 4.: wenn nicht leer oder nicht im Format "x-y;z" (für Wertebereich), dann kann es nur eine Zahlenliste sein "x1,x2,x3,..."
   				if (!($_POST['col_values'. $conf] === '' || preg_match('/^[1-9][0-9]{0,}[-][1-9][0-9]{0,}?[;][1-9][0-9]{0,}$/', $_POST['col_values'. $conf])))
   				{
   				    $_POST['col_values'. $conf] = implode(',',preg_split('/[-;,]/', $_POST['col_values'. $conf], -1, PREG_SPLIT_NO_EMPTY ));
   				}
   				$pPreferences->config['Konfigurationen']['col_values'][] = $_POST['col_values'. $conf];    
   				
   				$pPreferences->config['Konfigurationen']['col_suffix'][] = $_POST['col_suffix'. $conf]; 
   				$pPreferences->config['Konfigurationen']['calendar_year'][] = isset($_POST['calendar_year'. $conf]) ? 1 : 0 ;
   				$pPreferences->config['Konfigurationen']['suppress_age'][] = isset($_POST['suppress_age'. $conf]) ? 1 : 0 ;
   				$pPreferences->config['Konfigurationen']['years_offset'][] = isset($_POST['years_offset'. $conf]) ? $_POST['years_offset'. $conf] : 0 ;
   				$pPreferences->config['Konfigurationen']['relation'][] = isset($_POST['relationtype_id'. $conf]) ? $_POST['relationtype_id'. $conf] : '' ;
                   
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
   					$gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('PLG_BIRTHDAYLIST_COLUMN'))));
   				}

   				$pPreferences->config['Konfigurationen']['col_fields'][] = substr($fields,0,-1);	
   			}
                    
            $pPreferences->save();

            echo json_encode(array('status' => 'success', 'message' => $gL10n->get('SYS_SAVE_DATA')));   
            break;

        default:
            throw new Exception('SYS_INVALID_PAGE_VIEW');
    }
} catch (Exception $e) {
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
