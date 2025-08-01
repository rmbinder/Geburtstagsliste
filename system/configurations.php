<?php
/**
 ***********************************************************************************************
 * Modul Configurations of the admidio plugin BirthdayList
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * add     : add a configuration
 * delete  : delete a configuration
 * copy    : copy a configuration
 *
 ***********************************************************************************************
 */
use Admidio\Changelog\Service\ChangelogService;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Entity\Text;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;
use Admidio\Users\Entity\UserRelationType;
use Plugins\BirthdayList\classes\Config\ConfigTable;

try {
    require_once(__DIR__ . '/../../../system/common.php');
    require_once(__DIR__ . '/../../../system/login_valid.php');
    require_once(__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (!isUserAuthorizedForPreferences())
    {
        throw new Exception('SYS_NO_RIGHTS');
    }
    
    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // Initialize and check the parameters
    $getAdd    = admFuncVariableIsValid($_GET, 'add', 'bool');
    $getDelete = admFuncVariableIsValid($_GET, 'delete', 'numeric', array('defaultValue' => 0));
    $getCopy   = admFuncVariableIsValid($_GET, 'copy', 'numeric', array('defaultValue' => 0));

    $configSelection = generate_configSelection();
    $headline = $gL10n->get('SYS_CONFIGURATIONS');
    
    // add current url to navigation stack if last url was not the same page
    if (!str_contains($gNavigation->getUrl(), 'configurations.php')) {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }

    if ($getAdd) 
    {
 	  foreach($pPreferences->config['Konfigurationen'] as $key => $dummy)
	{
        if ($key == 'col_desc')
		{
            $pPreferences->config['Konfigurationen'][$key][] = '';
		}
		else
		{
            $pPreferences->config['Konfigurationen'][$key][] = $pPreferences->config_default['Konfigurationen'][$key][0];
		}
	}
    }

    if ($getDelete > 0) 
    {
 	$num_configs = count($pPreferences->config['Konfigurationen']['col_desc']);
	foreach($pPreferences->config['Konfigurationen'] as $key => $dummy)
	{
	    array_splice($pPreferences->config['Konfigurationen'][$key], $getDelete-1, 1);
	}
	
	$sql = 'DELETE FROM '.TBL_TEXTS.'
            	  WHERE txt_name = ?
            	    AND txt_org_id = ? ';
	$gDb->queryPrepared($sql, array('PGLMAIL_NOTIFICATION'. ($getDelete-1), $gCurrentOrgId));
	
	for ($i = $getDelete;  $i < $num_configs; $i++)
	{
		$sql = 'UPDATE '.TBL_TEXTS.'
                   SET  txt_name = ?
                 WHERE txt_name = ?
            	   AND txt_org_id = ? ';
		$gDb->queryPrepared($sql, array('PGLMAIL_NOTIFICATION'. ($i-1), 'PGLMAIL_NOTIFICATION'. $i, $gCurrentOrgId ));
	}
	
	// durch das Loeschen einer Konfiguration kann der Fall eintreten, dass es die eingestellte Standardkonfiguration nicht mehr gibt 
	// daher die Standardkonfiguration auf die erste Konfiguration im Array setzen
	$pPreferences->config['Optionen']['config_default'] = 0;
    $pPreferences->save();
    }

    if ($getCopy > 0) 
    {
	foreach($pPreferences->config['Konfigurationen'] as $key => $dummy)
	{
        if ($key == 'col_desc')
		{
            $pPreferences->config['Konfigurationen'][$key][] = createDesc($pPreferences->config['Konfigurationen'][$key][$getCopy-1]);
		}
		else
		{
            $pPreferences->config['Konfigurationen'][$key][] = $pPreferences->config['Konfigurationen'][$key][$getCopy-1];
		}
	}
    $pPreferences->save();
    
    $textCopy = new Text($gDb);
    $textCopy->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$getCopy-1, 'txt_org_id' => $gCurrentOrgId));
    $value = $textCopy->getValue('txt_text');
    $textCopy->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.count($pPreferences->config['Konfigurationen']['col_desc'])-1, 'txt_org_id' => $gCurrentOrgId));
    $textCopy->setValue('txt_text', $value);
    $textCopy->save();
    }


$num_configs = count($pPreferences->config['Konfigurationen']['col_desc']);

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-birthdaylist-configurations', $headline);
    $javascriptCode = 'var arr_user_fields = createProfileFieldsArray();';

    ChangelogService::displayHistoryButton($page, 'configurations', 'birthdaylist');

    // create an array with the necessary data
	$javascriptCode = '';
	
    // create a array with the necessary data
	for ($conf = 0;$conf < $num_configs; $conf++)
    {      
    if (!empty($pPreferences->config['Konfigurationen']['relation'][$conf]))
    {
    	$relationtype = new UserRelationType($gDb, $pPreferences->config['Konfigurationen']['relation'][$conf]);
    	$javascriptCode .= 'var arr_user_fields'.$conf.' = createProfileFieldsRelationArray("'.$relationtype->getValue('urt_name').'"); ';
    }
    else
    {
    	$javascriptCode .= 'var arr_user_fields'.$conf.' = createProfileFieldsArray(); ';
    }
    	
    $javascriptCode .= ' 
        var arr_default_fields'.$conf.' = createColumnsArray'.$conf.'();
        var fieldNumberIntern'.$conf.'  = 0;
                
    	// Funktion fuegt eine neue Zeile zum Zuordnen von Spalten fuer die Liste hinzu
    	function addColumn'.$conf.'() 
    	{        
        var category = "";
        var fieldNumberShow  = fieldNumberIntern'.$conf.' + 1;
        var table = document.getElementById("mylist_fields_tbody'.$conf.'");
        var newTableRow = table.insertRow(fieldNumberIntern'.$conf.');
        newTableRow.setAttribute("id", "row" + (fieldNumberIntern'.$conf.' + 1))
        //$(newTableRow).css("display", "none"); // ausgebaut wg. Kompatibilitaetsproblemen im IE8
        var newCellCount = newTableRow.insertCell(-1);
        newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;'.$gL10n->get('SYS_COLUMN').'&nbsp;:";
        
        // neue Spalte zur Auswahl des Profilfeldes
        var newCellField = newTableRow.insertCell(-1);
        htmlCboFields = "<select class=\"form-control\"  size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column'.$conf.'_" + fieldNumberShow + "\">" +
                "<option value=\"\"></option>";
        for(var counter = 1; counter < arr_user_fields'.$conf.'.length; counter++)
        {   
            if(category != arr_user_fields'.$conf.'[counter]["cat_name"])
            {
                if(category.length > 0)
                {
                    htmlCboFields += "</optgroup>";
                }
                htmlCboFields += "<optgroup label=\"" + arr_user_fields'.$conf.'[counter]["cat_name"] + "\">";
                category = arr_user_fields'.$conf.'[counter]["cat_name"];
            }

            var selected = "";
            
            // bei gespeicherten Listen das entsprechende Profilfeld selektieren
            // und den Feldnamen dem Listenarray hinzufuegen
            if(arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.'])
            {
                if(arr_user_fields'.$conf.'[counter]["id"] == arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["id"])
                {
                    selected = " selected=\"selected\" ";
                    arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["data"] = arr_user_fields'.$conf.'[counter]["data"];
                }
            }
             htmlCboFields += "<option value=\"" + arr_user_fields'.$conf.'[counter]["id"] + "\" " + selected + ">" + arr_user_fields'.$conf.'[counter]["data"] + "</option>";
        }
        htmlCboFields += "</select>";
        newCellField.innerHTML = htmlCboFields;

        $(newTableRow).fadeIn("slow");
        fieldNumberIntern'.$conf.'++;
    }
    
	function createColumnsArray'.$conf.'()
    {   
        var default_fields = new Array(); ';
            $fields = explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$conf]);
            $user = new User($gDb, $gProfileFields);
            for ($number = 0; $number < count($fields); $number++)
            {          	
            		$javascriptCode .= '
                	default_fields['. $number. '] 		  = new Object();
                	default_fields['. $number. ']["id"]   = "'. $fields[$number]. '";
                	default_fields['. $number. ']["data"] = "'. $user->getValue($gProfileFields->getPropertyById($number, 'usf_name')). '";
                	';
            }
        $javascriptCode .= '
        return default_fields;
    }    
    ';
}       
    
$javascriptCode .= '
    function createProfileFieldsRelationArray(relation)
    { 
        var user_fields = new Array(); ';
        $i = 1;
        foreach ($gProfileFields->getProfileFields() as $field)
        {    
            // add profile fields to user field array
            if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers())
            {   
                $javascriptCode .= '
                user_fields['. $i. ']             = new Object();
                user_fields['. $i. ']["cat_name"] = "'. strtr($field->getValue('cat_name'), '"', '\''). '";
                user_fields['. $i. ']["id"]       = "'. $field->getValue('usf_id'). '";
                user_fields['. $i. ']["data"]     = "'. addslashes($field->getValue('usf_name')). '";
                ';
                $i++;
            }
        }
        
        foreach ($gProfileFields->getProfileFields() as $field)
        {
        	// add profile fields to user field array
            if (($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers()) && $field->getValue('cat_name') == $gL10n->get('SYS_BASIC_DATA'))
        	{
        		$javascriptCode .= '
                user_fields['. $i. ']             = new Object();
                user_fields['. $i. ']["cat_name"] =  "'. strtr($field->getValue('cat_name'), '"', '\'').'" + ": " + relation ;
                user_fields['. $i. ']["id"]       = "r'. $field->getValue('usf_id'). '";    				//r wie Relationship(Beziehung)
                user_fields['. $i. ']["data"]     = "'. addslashes($field->getValue('usf_name')). '" + "*";
                ';
        		$i++;
        	}
        }
   
        $javascriptCode .= '
        return user_fields;
    }
        		
    function createProfileFieldsArray()
    { 
        var user_fields = new Array(); ';
        $i = 1;
        foreach ($gProfileFields->getProfileFields() as $field)
        {    
            // add profile fields to user field array
            if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers())
            {   
                $javascriptCode .= '
                user_fields['. $i. ']             = new Object();
                user_fields['. $i. ']["cat_name"] = "'. strtr($field->getValue('cat_name'), '"', '\''). '";
                user_fields['. $i. ']["id"]       = "'. $field->getValue('usf_id'). '";
                user_fields['. $i. ']["data"]     = "'. addslashes($field->getValue('usf_name')). '";
                ';
                $i++;
            }
        }        
   
        $javascriptCode .= '
        return user_fields;
    }
';

    $page->addJavascript($javascriptCode);
    $javascriptCodeExecute = '';

   	for ($conf = 0; $conf < $num_configs; $conf++)
	{
        $javascriptCodeExecute .= '
   	for(var counter = 0; counter < '. count(explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$conf])). '; counter++) {
        	addColumn'. $conf. '();
    	}
    	';
    }
    $page->addJavascript($javascriptCodeExecute, true);

    $formConfigurations = new FormPresenter(
        'adm_configurations_preferences_form',
        '../templates/configurations.plugin.birthdaylist.tpl',
        SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/BirthdayList/system/configurations_function.php', array('form' => 'configurations')),
        $page,
        array('class' => 'form-preferences')
    );

    $configurations = array();

    for ($conf = 0; $conf < $num_configs; $conf++)
    {
        $configuration = array(
            'key' => $conf,
            'col_desc' => 'col_desc' . $conf,
            'col_sel' => 'col_sel' . $conf,
            'col_values' => 'col_values' . $conf,
            'col_suffix' => 'col_suffix' . $conf,
            'suppress_age' => 'suppress_age' . $conf,
            'selection_role' => 'selection_role' . $conf,
            'selection_cat' => 'selection_cat' . $conf,
            'col_mail' => 'col_mail' . $conf,
            'calendar_year' => 'calendar_year' . $conf,
            'years_offset' => 'years_offset' . $conf,
            'relationtype_id' => 'relationtype_id' . $conf,
            'id' => 'id' . $conf
        );
        
        
        
	$formConfigurations->addInput('col_desc'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_COL_DESC'), $pPreferences->config['Konfigurationen']['col_desc'][$conf], array('property' => HtmlForm::FIELD_REQUIRED));
    	$formConfigurations->addSelectBox('col_sel'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_COL_SEL'), $configSelection, array('defaultValue' => $pPreferences->config['Konfigurationen']['col_sel'][$conf], 'showContextDependentFirstEntry' => false));
	$formConfigurations->addInput('col_values'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_COL_VALUES'), $pPreferences->config['Konfigurationen']['col_values'][$conf]);
	$formConfigurations->addInput('col_suffix'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_COL_SUFFIX'), $pPreferences->config['Konfigurationen']['col_suffix'][$conf]);
	$formConfigurations->addCheckbox('suppress_age'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_AGE_OR_ANNIVERSARY_NOT_SHOW'), $pPreferences->config['Konfigurationen']['suppress_age'][$conf]);
    
    $sql = 'SELECT rol_id, rol_name, cat_name
              FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
             WHERE cat_id = rol_cat_id
               AND ( cat_org_id = '.$gCurrentOrgId.'
                OR cat_org_id IS NULL )
          ORDER BY cat_sequence, rol_name';
    $formConfigurations->addSelectBoxFromSql('selection_role'.$conf, $gL10n->get('SYS_ROLE_SELECTION'), $gDb, $sql, array('defaultValue' => explode(',',$pPreferences->config['Konfigurationen']['selection_role'][$conf]), 'multiselect' => true));
                        	
	$sql = 'SELECT cat_id, cat_name
              FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
             WHERE cat_id = rol_cat_id
               AND ( cat_org_id = '.$gCurrentOrgId.'
                OR cat_org_id IS NULL )
          ORDER BY cat_sequence, cat_name';
	$formConfigurations->addSelectBoxFromSql('selection_cat'.$conf, $gL10n->get('SYS_CAT_SELECTION'), $gDb, $sql, array('defaultValue' => explode(',',$pPreferences->config['Konfigurationen']['selection_cat'][$conf]),  'multiselect' => true));
                        	
	$text[$conf] = new Text($gDb);
    $text[$conf]->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrgId));

    //wenn noch nichts drin steht, dann vorbelegen
    if ($text[$conf]->getValue('txt_text') == '')
    {
        // convert <br /> to a normal line feed
        $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/',chr(13).chr(10),$gL10n->get('PLG_BIRTHDAYLIST_PGLMAIL_NOTIFICATION'));
                    			
        $text[$conf]->setValue('txt_text', $value);
        $text[$conf]->save();
        $text[$conf]->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrgId));
    }
    $formConfigurations->addMultilineTextInput('col_mail'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_NOTIFICATION_MAIL_TEXT'), $text[$conf]->getValue('txt_text'), 7);	
    $formConfigurations->addCheckbox('calendar_year'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_SHOW_CALENDAR_YEAR'), $pPreferences->config['Konfigurationen']['calendar_year'][$conf]);
    $formConfigurations->addInput('years_offset'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_YEARS_OFFSET'), $pPreferences->config['Konfigurationen']['years_offset'][$conf], array('type' => 'number',  'step' => 1, 'minNumber' => -99, 'maxNumber' => 99) );  
 
 
           // select box showing all relation types
        $sql = 'SELECT urt_id, urt_name
                  FROM '.TBL_USER_RELATION_TYPES.'
          		 ORDER BY urt_name';
        $formConfigurations->addSelectBoxFromSql('relationtype_id'.$conf, $gL10n->get('PLG_BIRTHDAYLIST_RELATION'), $gDb, $sql,
            array('defaultValue' => $pPreferences->config['Konfigurationen']['relation'][$conf],'showContextDependentFirstEntry' => true, 'multiselect' => false));
  
    
        if ($num_configs > 1) 
        {
            $configuration['urlConfigDelete'] = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/BirthdayList/system/configurations.php', array('delete' => $conf + 1));
        }
        if (!empty('desc'.$conf)) 
        {
            $configuration['urlConfigCopy'] = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . '/BirthdayList/system/configurations.php', array('copy' => $conf + 1));
        }
        $configurations[] = $configuration;
    }
    $page->assignSmartyVariable('relations_enabled', $gSettingsManager->getInt('contacts_user_relations_enabled'));
    $page->assignSmartyVariable('configurations', $configurations);
    $page->assignSmartyVariable('urlConfigNew', SecurityUtils::encodeUrl(ADMIDIO_URL .FOLDER_PLUGINS . '/BirthdayList/system/configurations.php', array('add' => 1)));
    $page->assignSmartyVariable('urlPopupText',
        SecurityUtils::encodeUrl(
            ADMIDIO_URL . FOLDER_PLUGINS . '/BirthdayList/system/configurations_popup.php',
            array('message_id' => 'mylist_condition', 'inline' => 'true')
            )
        );
    $formConfigurations->addSubmitButton('adm_button_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'bi-check-lg'));

    $formConfigurations->addToHtmlPage();
    $gCurrentSession->addFormObject($formConfigurations);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
