<?php
/**
 * Zeigt im Menue Einstellungen ein Popup-Fenster mit Hinweisen an
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:	keine
 *
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');

// set headline of the script
$headline = $gL10n->get('PLG_FORMFILLER_CONFIGURATIONS');

header('Content-type: text/html; charset=utf-8');

echo '
<div class="modal-header">
    <h4 class="modal-title">'.$headline.'</h4>
</div>
<div class="modal-body">
	<strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_DESC').'</strong><br>
    '.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_DESC_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COLUMN_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COLUMN_SELECTION_DESC').'<br><br>		
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_SEL').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_SEL_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_VALUES').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_VALUES_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_SUFFIX').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_COL_SUFFIX_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_AGE_OR_ANNIVERSARY_NOT_SHOW').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_AGE_OR_ANNIVERSARY_NOT_SHOW_DESC').'<br><br>	
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_ROLE_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_ROLE_SELECTION_CONF_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_CAT_SELECTION').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_CAT_SELECTION_CONF_DESC').'<br><br>
    <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_NOTIFICATION_MAIL_TEXT').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_NOTIFICATION_MAIL_TEXT_DESC').'<br><br>
   <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_SHOW_CALENDAR_YEAR').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_SHOW_CALENDAR_YEAR_DESC').'<br><br>
   <strong>'.$gL10n->get('PLG_GEBURTSTAGSLISTE_YEARS_OFFSET').'</strong><br>
	'.$gL10n->get('PLG_GEBURTSTAGSLISTE_YEARS_OFFSET_DESC').'	
</div>';
