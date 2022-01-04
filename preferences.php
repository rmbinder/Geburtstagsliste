<?php
/**
 ***********************************************************************************************
 * Modul Preferences (Einstellungen) fuer das Admidio-Plugin Geburtstagsliste
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 *
 * Hinweis:  preferences.php ist eine modifizierte Kombination der Dateien
 *           .../modules/lists/mylist.php und .../modules/preferences/preferences.php
 * 
 * Parameters:
 *
 * add     : add a configuration
 * delete  : delete a configuration
 * copy    : copy a configuration
 * 
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/login_valid.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// Initialize and check the parameters
$getAdd    = admFuncVariableIsValid($_GET, 'add', 'bool');
$getDelete = admFuncVariableIsValid($_GET, 'delete', 'numeric', array('defaultValue' => 0));
$getCopy   = admFuncVariableIsValid($_GET, 'copy', 'numeric', array('defaultValue' => 0));

$pPreferences = new ConfigTablePGL();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$configSelection = generate_configSelection();

$headline = $gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST');

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
    
    $textCopy = new TableText($gDb);
    $textCopy->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$getCopy-1, 'txt_org_id' => $gCurrentOrgId));
    $value = $textCopy->getValue('txt_text');
    $textCopy->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.count($pPreferences->config['Konfigurationen']['col_desc'])-1, 'txt_org_id' => $gCurrentOrgId));
    $textCopy->setValue('txt_text', $value);
    $textCopy->save();
}

$num_configs = count($pPreferences->config['Konfigurationen']['col_desc']);

if ( !StringUtils::strContains($gNavigation->getUrl(), 'preferences.php'))
{
    $gNavigation->addUrl(CURRENT_URL);
}

// create html page object
$page = new HtmlPage('plg-geburtstagsliste-preferences', $headline);

// open the module configurations if a configuration is added, deleted or copied
if ($getAdd || $getDelete > 0 || $getCopy > 0)
{
    $page->addJavascript('
        $("#tabs_nav_common").attr("class", "nav-link active");
        $("#tabs-common").attr("class", "tab-pane fade show active");
        $("#collapse_configurations").attr("class", "collapse show");
        location.hash = "#" + "panel_configurations";',
        true
    );
}
else
{
    $page->addJavascript('
        $("#tabs_nav_common").attr("class", "active");
        $("#tabs-common").attr("class", "tab-pane active");
    ', true);
}

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        var formAlert = $("#" + id + " .form-alert");
        formAlert.hide();

        // disable default form submit
        event.preventDefault();

        $.post({
            url: action,
            data: $(this).serialize(),
            success: function(data) {
                if (data === "success") {

                    formAlert.attr("class", "alert alert-success form-alert");
                    formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                } else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>" + data);
                }
            }
        });
    });',
    true
);

$javascriptCode = '';

// create an array with the necessary data
for ($conf = 0; $conf < $num_configs; $conf++)
{      
    if (!empty($pPreferences->config['Konfigurationen']['relation'][$conf]))
    {
    	$relationtype = new TableUserRelationType($gDb, $pPreferences->config['Konfigurationen']['relation'][$conf]);
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
            if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
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
        	if (($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers()) && $field->getValue('cat_name') == $gL10n->get('SYS_BASIC_DATA'))
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
            if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
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
$javascriptCode = '$(document).ready(function() { ';
	for ($conf = 0; $conf < $num_configs; $conf++)
	{
		$javascriptCode .= '  
    	for(var counter = 0; counter < '. count(explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$conf])). '; counter++) {
        	addColumn'. $conf. '();
    	}
    	';
	}     	
$javascriptCode .= ' }); ';
$page->addJavascript($javascriptCode, true);  

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @param string $body
 * @return string
 */
function getPreferencePanel($group, $id, $title, $icon, $body)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#accordion_preferences">
                <div class="card-body">
                    ' . $body . '
                </div>
            </div>
        </div>
    ';
    return $html;
}

$page->addHtml('
<ul id="preferences_tabs" class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a id="tabs_nav_common" class="nav-link" href="#tabs-common" data-toggle="tab" role="tab">'.$gL10n->get('SYS_SETTINGS').'</a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade" id="tabs-common" role="tabpanel">
        <div class="accordion" id="accordion_preferences">');

 // PANEL: CONFIGURATIONS

$formConfigurations = new HtmlForm('configurations_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'configurations')), $page, array('class' => 'form-preferences'));
  
$formConfigurations->addDescription($gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATIONS_HEADER'));
$formConfigurations->addDescription('<small>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATIONS_DESC').'</small>');
$formConfigurations->addLine();
$formConfigurations->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_configs; $conf++)
{
	$formConfigurations->openGroupBox('configurations_group',($conf+1).'. '.$gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATION'));
	$formConfigurations->addInput('col_desc'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_COL_DESC'), $pPreferences->config['Konfigurationen']['col_desc'][$conf], array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_COL_DESC_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
	$html = '
		<div class="table-responsive">
    		<table class="table table-condensed" id="mylist_fields_table">
        		<thead>
            		<tr>
                		<th style="width: 20%;">'.$gL10n->get('SYS_ABR_NO').'</th>
                		<th style="width: 37%;">'.$gL10n->get('SYS_CONTENT').'</th>   
            		</tr>
        		</thead>
                <tbody id="mylist_fields_tbody'.$conf.'">
            		<tr id="table_row_button">
                		<td colspan="2">
                    		<a class="icon-text-link" href="javascript:addColumn'.$conf.'()"><i class="fas fa-plus-circle"></i>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_ADD_ANOTHER_COLUMN').'</a>
                		</td>
            		</tr>
        		</tbody>
    		</table>
    	</div>
    ';
	$formConfigurations->addCustomContent($gL10n->get('PLG_GEBURTSTAGSLISTE_COLUMN_SELECTION'), $html, array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_COLUMN_SELECTION_DESC'));
	$formConfigurations->addSelectBox('col_sel'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_COL_SEL'), $configSelection, array('defaultValue' => $pPreferences->config['Konfigurationen']['col_sel'][$conf], 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_COL_SEL_DESC', 'showContextDependentFirstEntry' => false));
	$formConfigurations->addInput('col_values'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_COL_VALUES'), $pPreferences->config['Konfigurationen']['col_values'][$conf], array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_COL_VALUES_DESC'));
	$formConfigurations->addInput('col_suffix'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_COL_SUFFIX'), $pPreferences->config['Konfigurationen']['col_suffix'][$conf], array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_COL_SUFFIX_DESC'));
	$formConfigurations->addCheckbox('suppress_age'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_AGE_OR_ANNIVERSARY_NOT_SHOW'), $pPreferences->config['Konfigurationen']['suppress_age'][$conf], array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_AGE_OR_ANNIVERSARY_NOT_SHOW_DESC'));

    $sql = 'SELECT rol_id, rol_name, cat_name
              FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
             WHERE cat_id = rol_cat_id
               AND ( cat_org_id = '.$gCurrentOrgId.'
                OR cat_org_id IS NULL )
          ORDER BY cat_sequence, rol_name';
    $formConfigurations->addSelectBoxFromSql('selection_role'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_ROLE_SELECTION'), $gDb, $sql, array('defaultValue' => explode(',',$pPreferences->config['Konfigurationen']['selection_role'][$conf]), 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_ROLE_SELECTION_CONF_DESC', 'multiselect' => true));
                        	
	$sql = 'SELECT cat_id, cat_name
              FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
             WHERE cat_id = rol_cat_id
               AND ( cat_org_id = '.$gCurrentOrgId.'
                OR cat_org_id IS NULL )
          ORDER BY cat_sequence, cat_name';
	$formConfigurations->addSelectBoxFromSql('selection_cat'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_CAT_SELECTION'), $gDb, $sql, array('defaultValue' => explode(',',$pPreferences->config['Konfigurationen']['selection_cat'][$conf]), 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_CAT_SELECTION_CONF_DESC', 'multiselect' => true));
                        	
	$text[$conf] = new TableText($gDb);
    $text[$conf]->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrgId));

    //wenn noch nichts drin steht, dann vorbelegen
    if ($text[$conf]->getValue('txt_text') == '')
    {
        // convert <br /> to a normal line feed
        $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/',chr(13).chr(10),$gL10n->get('PLG_GEBURTSTAGSLISTE_PGLMAIL_NOTIFICATION'));
                    			
        $text[$conf]->setValue('txt_text', $value);
        $text[$conf]->save();
        $text[$conf]->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrgId));
    }
    $formConfigurations->addMultilineTextInput('col_mail'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_NOTIFICATION_MAIL_TEXT'), $text[$conf]->getValue('txt_text'), 7, array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_NOTIFICATION_MAIL_TEXT_DESC'));	
    $formConfigurations->addCheckbox('calendar_year'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_SHOW_CALENDAR_YEAR'), $pPreferences->config['Konfigurationen']['calendar_year'][$conf], array('helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_SHOW_CALENDAR_YEAR_DESC'));
    $formConfigurations->addInput('years_offset'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_YEARS_OFFSET'), $pPreferences->config['Konfigurationen']['years_offset'][$conf], array('type' => 'number',  'step' => 1, 'minNumber' => -99, 'maxNumber' => 99, 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_YEARS_OFFSET_DESC') );  
 
    if ($gSettingsManager->getInt('members_enable_user_relations') == 1)
    {
        // select box showing all relation types
        $sql = 'SELECT urt_id, urt_name
                  FROM '.TBL_USER_RELATION_TYPES.'
          		 ORDER BY urt_name';
        $formConfigurations->addSelectBoxFromSql('relationtype_id'.$conf, $gL10n->get('PLG_GEBURTSTAGSLISTE_RELATION'), $gDb, $sql,
            array('defaultValue' => $pPreferences->config['Konfigurationen']['relation'][$conf],'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_RELATION_DESC', 'multiselect' => false));
    } 
    
    $html = '<a id="copy_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('copy' => $conf+1)).'">
            <i class="fas fa-clone"></i> '.$gL10n->get('SYS_COPY_CONFIGURATION').'</a>';
    if($num_configs > 1)
    {
        $html .= '&nbsp;&nbsp;&nbsp;&nbsp;<a id="delete_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('delete' => $conf+1)).'">
            <i class="fas fa-trash-alt"></i> '.$gL10n->get('SYS_DELETE_CONFIGURATION').'</a>';
    }
    if(!empty($pPreferences->config['Konfigurationen']['col_desc'][$conf]))
    {
        $formConfigurations->addCustomContent('', $html);
    }    
    $formConfigurations->closeGroupBox();
}
$formConfigurations->addDescription('</div>');
$formConfigurations->addLine();
$html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('add' => 1)).'">
            <i class="fas fa-clone"></i> '.$gL10n->get('SYS_ADD_ANOTHER_CONFIG').'
        </a>';
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert">
                <i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('ORG_NOT_SAVED_SETTINGS_LOST').'
            </div>';
$formConfigurations->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc)); 
$formConfigurations->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));
                        
$page->addHtml(getPreferencePanel('common', 'configurations', $gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATIONS'), 'fas fa-cogs', $formConfigurations->show()));

// PANEL: OPTIONS                        
                        
$formOptions = new HtmlForm('options_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'options')),$page, array('class' => 'form-preferences'));                         
$formOptions->addInput('vorschau_tage_default', $gL10n->get('PLG_GEBURTSTAGSLISTE_PREVIEW_DAYS'), $pPreferences->config['Optionen']['vorschau_tage_default'], array('type' => 'number',  'step' => 1,'helpTextIdInline' => 'PLG_GEBURTSTAGSLISTE_PREVIEW_DAYS_DESC') );  
$formOptions->addInput('vorschau_liste', $gL10n->get('PLG_GEBURTSTAGSLISTE_PREVIEW_LIST'), implode(',',$pPreferences->config['Optionen']['vorschau_liste']), array('helpTextIdInline' => 'PLG_GEBURTSTAGSLISTE_PREVIEW_LIST_DESC'));     
$formOptions->addSelectBox('config_default', $gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATION'),$pPreferences->config['Konfigurationen']['col_desc'], array('defaultValue' => $pPreferences->config['Optionen']['config_default'], 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_GEBURTSTAGSLISTE_CONFIGURATION_DEFAULT_DESC'));
$formOptions->addCheckbox('configuration_as_header', $gL10n->get('PLG_GEBURTSTAGSLISTE_CONFIGURATION_AS_HEADER'), $pPreferences->config['Optionen']['configuration_as_header'], array('helpTextIdInline' => 'PLG_GEBURTSTAGSLISTE_CONFIGURATION_AS_HEADER_DESC'));
$formOptions->addSubmitButton('btn_save_options', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getPreferencePanel('common', 'options', $gL10n->get('PLG_GEBURTSTAGSLISTE_OPTIONS'), 'fas fa-cog', $formOptions->show()));

// PANEL: DEINSTALLATION
                             
$formDeinstallation = new HtmlForm('deinstallation_form', SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('mode' => 2)), $page);                     
$formDeinstallation->addSubmitButton('btn_save_deinstallation', $gL10n->get('PLG_GEBURTSTAGSLISTE_DEINSTALLATION'), array('icon' => 'fa-trash-alt', 'class' => 'offset-sm-3'));
$formDeinstallation->addCustomContent('', ''.$gL10n->get('PLG_GEBURTSTAGSLISTE_DEINSTALLATION_DESC'));
                   
$page->addHtml(getPreferencePanel('common', 'deinstallation', $gL10n->get('PLG_GEBURTSTAGSLISTE_DEINSTALLATION'), 'fas fa-trash-alt', $formDeinstallation->show()));

// PANEL: ACCESS_PREFERENCES
                    
$formAccessPreferences = new HtmlForm('access_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'access_preferences')), $page, array('class' => 'form-preferences'));

$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' AS cat, '.TBL_ROLES.' AS rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND ( cat.cat_org_id = '.$gCurrentOrgId.'
            OR cat.cat_org_id IS NULL )
      ORDER BY cat_sequence, rol.rol_name ASC';
$formAccessPreferences->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextIdInline' => 'PLG_GEBURTSTAGSLISTE_ACCESS_PREFERENCES_DESC', 'multiselect' => true));
$formAccessPreferences->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getPreferencePanel('common', 'access_preferences', $gL10n->get('PLG_GEBURTSTAGSLISTE_ACCESS_PREFERENCES'), 'fas fa-key', $formAccessPreferences->show()));

// PANEL: PLUGIN INFORMATIONS

$formPluginInformations = new HtmlForm('plugin_informations_preferences_form', null,$page, array('class' => 'form-preferences'));
$formPluginInformations->addStaticControl('plg_name', $gL10n->get('PLG_GEBURTSTAGSLISTE_PLUGIN_NAME'), $gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'));
$formPluginInformations->addStaticControl('plg_version', $gL10n->get('PLG_GEBURTSTAGSLISTE_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
$formPluginInformations->addStaticControl('plg_date', $gL10n->get('PLG_GEBURTSTAGSLISTE_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
                        
$html = '<a class="icon-text-link" href="https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:geburtstagsliste#geburtstagsliste" target="_blank">
        <i class="fas fa-external-link-square-alt"></i> '.$gL10n->get('PLG_GEBURTSTAGSLISTE_DOCUMENTATION_OPEN').'</a>';
$formPluginInformations->addCustomContent($gL10n->get('PLG_GEBURTSTAGSLISTE_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PLG_GEBURTSTAGSLISTE_DOCUMENTATION_OPEN_DESC'));
$page->addHtml(getPreferencePanel('common', 'plugin_informations', $gL10n->get('PLG_GEBURTSTAGSLISTE_PLUGIN_INFORMATION'), 'fas fa-info-circle', $formPluginInformations->show()));

$page->addHtml('
        </div>
    </div>
</div>');

$page->show();
