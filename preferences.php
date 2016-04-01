<?php
/******************************************************************************
 * preferences.php
 * 
 * Modul Preferences (Einstellungen) für das Admidio-Plugin Geburtstagsliste
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Hinweis:
 * 
 * preferences.php ist eine modifizierte Kombination der Dateien
 * .../modules/lists/mylist.php und .../modules/preferences/preferences.php
 * 
 * Parameters:
 *
 * add	:	Anlegen einer weiteren Konfiguration (true or false)
 *
 *****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/../adm_program/system/login_valid.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Initialize and check the parameters
$getAdd = admFuncVariableIsValid($_GET, 'add', 'boolean', array('defaultValue' => false));

$pPreferences = new ConfigTablePGL();
$pPreferences->read();

$configSelection = generate_configSelection();

$headline = $gL10n->get('PGL_BIRTHDAY_LIST');

$num_configs	 = count($pPreferences->config['Konfigurationen']['col_desc']);
if($getAdd)
{
	foreach($pPreferences->config['Konfigurationen'] as $key => $dummy)
	{
		$pPreferences->config['Konfigurationen'][$key][$num_configs] = $pPreferences->config_default['Konfigurationen'][$key][0];
	}
	$num_configs++;
}

$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage($headline);

// open the module configurations if a new configuration is added 
if($getAdd)
{
    $page->addJavascript('$("#tabs_nav_common").attr("class", "active");
        $("#tabs-common").attr("class", "tab-pane active");
        $("#collapse_configurations").attr("class", "panel-collapse collapse in");
        location.hash = "#" + "panel_configurations";', true);
}
else
{
    $page->addJavascript('$("#tabs_nav_common").attr("class", "active");
     $("#tabs-common").attr("class", "tab-pane active");
     ', true);
}

$page->addJavascript('$("#tabs_nav_common").attr("class", "active");
     $("#tabs-common").attr("class", "tab-pane active");
     ', true);

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        $("#"+id+" .form-alert").hide();

        // disable default form submit
        event.preventDefault();
        
        $.ajax({
            type:    "POST",
            url:     action,
            data:    $(this).serialize(),
            success: function(data) {
                if(data == "success") {
                    $("#"+id+" .form-alert").attr("class", "alert alert-success form-alert");
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-ok\"></span><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    $("#"+id+" .form-alert").fadeIn("slow");
                    $("#"+id+" .form-alert").animate({opacity: 1.0}, 2500);
                    $("#"+id+" .form-alert").fadeOut("slow");
                }
                else {
                    $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                    $("#"+id+" .form-alert").fadeIn();
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span>"+data);
                }
            }
        });    
    });
    ', true);

$javascriptCode = '
    var arr_user_fields    = createProfileFieldsArray();
    ';
    
    // create a array with the necessary data
	for ($conf=0;$conf<$num_configs;$conf++)
    {      
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
        newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;'.$gL10n->get('LST_COLUMN').'&nbsp;:";
        
        // neue Spalte zur Auswahl des Profilfeldes
        var newCellField = newTableRow.insertCell(-1);
        htmlCboFields = "<select class=\"form-control\"  size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column'.$conf.'_" + fieldNumberShow + "\">" +
                "<option value=\"\"></option>";
        for(var counter = 1; counter < arr_user_fields.length; counter++)
        {   
            if(category != arr_user_fields[counter]["cat_name"])
            {
                if(category.length > 0)
                {
                    htmlCboFields += "</optgroup>";
                }
                htmlCboFields += "<optgroup label=\"" + arr_user_fields[counter]["cat_name"] + "\">";
                category = arr_user_fields[counter]["cat_name"];
            }

            var selected = "";
            
            // bei gespeicherten Listen das entsprechende Profilfeld selektieren
            // und den Feldnamen dem Listenarray hinzufügen
            if(arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.'])
            {
                if(arr_user_fields[counter]["id"] == arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["id"])
                {
                    selected = " selected=\"selected\" ";
                    arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["data"] = arr_user_fields[counter]["data"];
                }
            }
             htmlCboFields += "<option value=\"" + arr_user_fields[counter]["id"] + "\" " + selected + ">" + arr_user_fields[counter]["data"] + "</option>";
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
            for($number = 0; $number < count($fields); $number++)
            {          	
            		$javascriptCode .= '
                	default_fields['. $number. '] 		   = new Object();
                	default_fields['. $number. ']["id"]    = "'. $fields[$number]. '";
                	default_fields['. $number. ']["data"]  = "'. $user->getValue($gProfileFields->getPropertyById($number, 'usf_name')). '";
                	';
            }
        $javascriptCode .= '
        return default_fields;
    }    
    
    ';
    }       
    $javascriptCode .= '
    function createProfileFieldsArray()
    { 
        var user_fields = new Array(); ';
        $i = 1;
        foreach($gProfileFields->mProfileFields as $field)
        {    
            // add profile fields to user field array
            if($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
            {   
                $javascriptCode .= '
                user_fields['. $i. '] = new Object();
                user_fields['. $i. ']["cat_name"] = "'. strtr($field->getValue('cat_name'), '"', '\''). '";
                user_fields['. $i. ']["id"]   = "'. $field->getValue('usf_id'). '";
                user_fields['. $i. ']["data"] = "'. addslashes($field->getValue('usf_name')). '";
                ';
                $i++;
            }
        }        
   
        $javascriptCode .= '
        return user_fields;
    }
';
        
$page->addJavascript($javascriptCode);        
$javascriptCode = '$(document).ready(function() {   
';
	for($conf = 0; $conf < $num_configs; $conf++)
	{
		$javascriptCode .= '  
    	for(var counter = 0; counter < '. count(explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$conf])). '; counter++) {
        	addColumn'. $conf. '();
    	}
    	';
	}     	
$javascriptCode .= '
});
';
$page->addJavascript($javascriptCode, true);  

// create module menu with back link
$preferencesMenu = new HtmlNavbar('menu_dates_create', $headline, $page);
$preferencesMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
$page->addHtml($preferencesMenu->show(false));

$page->addHtml('
<ul class="nav nav-tabs" id="preferences_tabs">
  	<li id="tabs_nav_common"><a href="#tabs-common" data-toggle="tab">'.$gL10n->get('SYS_SETTINGS').'</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane" id="tabs-common">
        <div class="panel-group" id="accordion_common">
            <div class="panel panel-default" id="panel_configurations">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_configurations">
                            <img src="'.THEME_PATH.'/icons/application_form_edit.png" alt="'.$gL10n->get('PGL_CONFIGURATIONS').'" title="'.$gL10n->get('PGL_CONFIGURATIONS').'" />'.$gL10n->get('PGL_CONFIGURATIONS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_configurations" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?form=configurations', $page, array('class' => 'form-preferences'));
  
                        $form->addDescription($gL10n->get('PGL_CONFIGURATIONS_HEADER'));
                        $form->addDescription('<small>'.$gL10n->get('PGL_CONFIGURATIONS_DESC').'</small>');
                    	$form->addLine();
                        $form->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');
                        for ($conf=0;$conf<$num_configs;$conf++)
						{
							$form->openGroupBox('configurations_group',($conf+1).'. '.$gL10n->get('PGL_CONFIGURATION'));
							$form->addInput('col_desc'.$conf, $gL10n->get('PGL_COL_DESC'), $pPreferences->config['Konfigurationen']['col_desc'][$conf],array('helpTextIdLabel' => 'PGL_COL_DESC_DESC'));
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
                    							<a class="icon-text-link" href="javascript:addColumn'.$conf.'()"><img src="'. THEME_PATH. '/icons/add.png" alt="'.$gL10n->get('LST_ADD_ANOTHER_COLUMN').'" />'.$gL10n->get('LST_ADD_ANOTHER_COLUMN').'</a>
                							</td>
            							</tr>
        							</tbody>
    							</table>
    						</div>';
                        	$form->addCustomContent($gL10n->get('PGL_COLUMN_SELECTION'), $html, array('helpTextIdLabel' => 'PGL_COLUMN_SELECTION_DESC')); 
                       		$form->addSelectBox('col_sel'.$conf, $gL10n->get('PGL_COL_SEL'), $configSelection, array('defaultValue' => $pPreferences->config['Konfigurationen']['col_sel'][$conf], 'showContextDependentFirstEntry' => false, 'helpTextIdLabel' => 'PGL_COL_SEL_DESC'));
                        	$form->addInput('col_values'.$conf, $gL10n->get('PGL_COL_VALUES'), $pPreferences->config['Konfigurationen']['col_values'][$conf],array('helpTextIdLabel' => 'PGL_COL_VALUES_DESC'));
                        	$form->addInput('col_suffix'.$conf, $gL10n->get('PGL_COL_SUFFIX'), $pPreferences->config['Konfigurationen']['col_suffix'][$conf],array('helpTextIdLabel' => 'PGL_COL_SUFFIX_DESC'));

                        	$sql = 'SELECT rol_id, rol_name, cat_name
                                FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
                                WHERE cat_id = rol_cat_id
                                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                OR cat_org_id IS NULL )';
                       		$form->addSelectBoxFromSql('selection_role'.$conf, $gL10n->get('PGL_ROLE_SELECTION'), $gDb, $sql, array('defaultValue' => explode(',',$pPreferences->config['Konfigurationen']['selection_role'][$conf]),'multiselect' => true, 'helpTextIdLabel' => 'PGL_ROLE_SELECTION_CONF_DESC'));
                        	
				        	$sql = 'SELECT cat_id, cat_name
                                    FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.' 
                                    WHERE cat_id = rol_cat_id
                                    AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                    OR cat_org_id IS NULL )';
                       		$form->addSelectBoxFromSql('selection_cat'.$conf, $gL10n->get('PGL_CAT_SELECTION'), $gDb, $sql, array('defaultValue' => explode(',',$pPreferences->config['Konfigurationen']['selection_cat'][$conf]),'multiselect' => true, 'helpTextIdLabel' => 'PGL_CAT_SELECTION_CONF_DESC'));
                        	
							$text[$conf] = new TableText($gDb);
                    		$text[$conf]->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrganization->getValue('org_id')));

                    		//wenn noch nichts drin steht, dann vorbelegen
                    		if ($text[$conf]->getValue('txt_text')=='')
                    		{
        						// convert <br /> to a normal line feed
        						$value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/',chr(13).chr(10),$gL10n->get('PGL_PGLMAIL_NOTIFICATION'));
                    			
            					$text[$conf]->setValue('txt_text', $value);
            					$text[$conf]->save();
            					$text[$conf]->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$conf, 'txt_org_id' => $gCurrentOrganization->getValue('org_id')));
                    		}
				        	$form->addMultilineTextInput('col_mail'.$conf, $gL10n->get('PGL_NOTIFICATION_MAIL_TEXT'), $text[$conf]->getValue('txt_text'), 7,array('helpTextIdLabel' => 'PGL_NOTIFICATION_MAIL_TEXT_DESC'));	
                    		$form->addCheckbox('calendar_year'.$conf, $gL10n->get('PGL_SHOW_CALENDAR_YEAR'), $pPreferences->config['Konfigurationen']['calendar_year'][$conf], array('helpTextIdLabel' => 'PGL_SHOW_CALENDAR_YEAR_DESC'));
							$form->closeGroupBox();
						}
                        $form->addDescription('</div>');
                        $form->addLine();
                        $html = '<a id="add_config" class="icon-text-link" href="'. $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences.php?add=true"><img
                                    src="'. THEME_PATH. '/icons/add.png" alt="'.$gL10n->get('PGL_ADD_ANOTHER_CONFIG').'" />'.$gL10n->get('PGL_ADD_ANOTHER_CONFIG').'</a>';
                        $htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('ORG_NOT_SAVED_SETTINGS_LOST').'</div>';
                        $form->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc)); 
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>           

            <div class="panel panel-default" id="panel_options">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_options">
                            <img src="'.THEME_PATH.'/icons/options.png" alt="'.$gL10n->get('PGL_OPTIONS').'" title="'.$gL10n->get('PGL_OPTIONS').'" />'.$gL10n->get('PGL_OPTIONS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_options" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('options_preferences_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?form=options', $page, array('class' => 'form-preferences'));
                                               
                        $form->addInput('vorschau_tage_default', $gL10n->get('PGL_PREVIEW_DAYS'), $pPreferences->config['Optionen']['vorschau_tage_default'], array('type' => 'number',  'step' => 1,'helpTextIdInline' => 'PGL_PREVIEW_DAYS_DESC') );  
                        $form->addInput('vorschau_liste', $gL10n->get('PGL_PREVIEW_LIST'), implode(',',$pPreferences->config['Optionen']['vorschau_liste']), array('helpTextIdInline' => 'PGL_PREVIEW_LIST_DESC'));     
                        $form->addSelectBox('config_default', $gL10n->get('PGL_CONFIGURATION'),$pPreferences->config['Konfigurationen']['col_desc'], array('defaultValue' => $pPreferences->config['Optionen']['config_default'], 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PGL_CONFIGURATION_DEFAULT_DESC'));
				                                           
                        $html = '<a id="deinstallation" class="icon-text-link" href="'. $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?mode=2"><img
                                    src="'. THEME_PATH. '/icons/delete.png" alt="'.$gL10n->get('PGL_LINK_TO_DEINSTALLATION').'" />'.$gL10n->get('PGL_LINK_TO_DEINSTALLATION').'</a>';
                        $form->addCustomContent($gL10n->get('PGL_DEINSTALLATION'), $html, array('helpTextIdInline' => 'PGL_DEINSTALLATION_DESC'));
                        $form->addSubmitButton('btn_save_options', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default" id="panel_plugin_control">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_plugin_control">
                            <img src="'.THEME_PATH.'/icons/lock.png" alt="'.$gL10n->get('PGL_PLUGIN_CONTROL').'" title="'.$gL10n->get('PGL_PLUGIN_CONTROL').'" />'.$gL10n->get('PGL_PLUGIN_CONTROL').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_plugin_control" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('plugin_control_preferences_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences_function.php?form=plugin_control', $page, array('class' => 'form-preferences'));
  
                        $sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
                                FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
                                WHERE cat.cat_id = rol.rol_cat_id
                                AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                OR cat.cat_org_id IS NULL )';

                        $form->addSelectBoxFromSql('freigabe', $gL10n->get('PGL_ROLE_SELECTION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Pluginfreigabe']['freigabe'], 'helpTextIdInline' => 'PGL_ROLE_SELECTION_DESC','multiselect' => true));				                                                 
                        $form->addSelectBoxFromSql('freigabe_config', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['Pluginfreigabe']['freigabe_config'], 'helpTextIdInline' => 'PGL_ROLE_SELECTION_DESC2','multiselect' => true));
                        $form->addSubmitButton('btn_save_plugin_control_preferences', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>
            
            <div class="panel panel-default" id="panel_plugin_informations">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_common" href="#collapse_plugin_informations">
                            <img src="'.THEME_PATH.'/icons/info.png" alt="'.$gL10n->get('PGL_PLUGIN_INFORMATION').'" title="'.$gL10n->get('PGL_PLUGIN_INFORMATION').'" />'.$gL10n->get('PGL_PLUGIN_INFORMATION').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_plugin_informations" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // create a static form
                        $form = new HtmlForm('plugin_informations_preferences_form', null, $page);
                        
                        $form->addStaticControl('plg_name', $gL10n->get('PGL_PLUGIN_NAME'), $gL10n->get('PGL_BIRTHDAY_LIST'));
                        $form->addStaticControl('plg_version', $gL10n->get('PGL_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
                        $form->addStaticControl('plg_date', $gL10n->get('PGL_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
                        
                        $html = '<a class="icon-text-link" href="http://www.admidio.org/dokuwiki/doku.php?id=de:2.0:geburtstagsliste" target="_blank"><img
                                    src="'. THEME_PATH. '/icons/eye.png" alt="'.$gL10n->get('PGL_DOCUMENTATION_OPEN').'" />'.$gL10n->get('PGL_DOCUMENTATION_OPEN').'</a>';
                        $form->addCustomContent($gL10n->get('PGL_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PGL_DOCUMENTATION_OPEN_DESC'));
                        $page->addHtml($form->show(false));
                    	$page->addHtml('
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
');

$page->show();

?>