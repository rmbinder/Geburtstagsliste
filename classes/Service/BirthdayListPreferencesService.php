<?php
namespace Plugins\BirthdayList\classes\Service;

use Admidio\Infrastructure\Exception;
use Plugins\BirthdayList\classes\Config\ConfigTable;

/**
 * @brief Class with methods to display the module pages.
 *
 * This class adds some functions that are used in the preferences module to keep the
 * code easy to read and short
 * 
 * BirthdayListPreferencesService is a modified (Admidio)PreferencesService
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class BirthdayListPreferencesService
{

    /**
     * Save all form data of the panel to the database.
     * @param string $panel Name of the panel for which the data should be saved.
     * @param array $formData All form data of the panel.
     * @return void
     * @throws Exception
     */
    public function save(string $panel, array $formData)
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gDb, $gCurrentOrgId, $gProfileFields, $gLogger;
        
        require_once(__DIR__ . '/../../system/common_function.php');
        $pPreferences = new ConfigTable();
        $pPreferences->read();
        
        $result =  $gL10n->get('SYS_SAVE_DATA');

        // first check the fields of the submitted form
        switch ($panel) {
            
            case 'Options':
                $pPreferences->config['Optionen']['vorschau_tage_default'] = intval($_POST['vorschau_tage_default']>0) ? intval($_POST['vorschau_tage_default']) : 365;	    			
   			    $pPreferences->config['Optionen']['vorschau_liste'] = explode(',',preg_replace('/[,]{2,}/', ',', trim(preg_replace('![^0-9,-]!', '', $_POST['vorschau_liste']),',')));		
	        	$pPreferences->config['Optionen']['config_default'] = $_POST['config_default'];	
	        	$pPreferences->config['Optionen']['configuration_as_header'] = isset($_POST['configuration_as_header']) ? 1 : 0 ;
           	break; 

            case 'Access':
                if (isset($formData['access_preferences']))
                {
                    $pPreferences->config['access']['preferences'] = array_values(array_filter($formData['access_preferences']));
                }
                else
                {
                    $pPreferences->config['access']['preferences'] = array();
                }
                break;

        }
        $pPreferences->save();
        return $result;

        // clean up
        $gCurrentSession->reloadAllSessions();
    }

}
