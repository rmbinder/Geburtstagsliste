<?php
/**
 ***********************************************************************************************
 * E-Mails versenden aus dem Plugin Geburtstagsliste
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 * 
 * usr_id       : E-Mail an den entsprechenden Benutzer schreiben
 * configtext   : Text in der letzten Spalte (Konfigurationsspalte)
 * config       : die gewaehlte Konfiguration
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/classes/TableText.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
//if ($getMode === 'html' && !StringUtils::strContains($gNavigation->getUrl(), 'geburtstagsliste.php')) 
// only the main script can call and start this module
//if (strpos($gNavigation->getUrl(), 'geburtstagsliste.php') === false && strpos($gNavigation->getUrl(), 'message_send.php') === false)
if (!StringUtils::strContains($gNavigation->getUrl(), 'geburtstagsliste.php') && !StringUtils::strContains($gNavigation->getUrl(), 'message_send.php'))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$getUserId     = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0));
$getConfigText = admFuncVariableIsValid($_GET, 'configtext', 'string');
$getConfig     = admFuncVariableIsValid($_GET, 'config', 'numeric', array('defaultValue' => 0));

$getSubject = '';

// Konfiguration einlesen          
$pPreferences = new ConfigTablePGL();
$pPreferences->read();

// check if the call of the page was allowed by settings
if ($gSettingsManager->getInt('enable_mail_module') != 1 )
{
    // message if the sending of PM is not allowed
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}

// check if user has email address for sending a email
if ($gValidLogin && strlen($gCurrentUser->getValue('EMAIL')) == 0)
{
    $gMessage->show($gL10n->get('SYS_CURRENT_USER_NO_EMAIL', '<a href="'. ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php">', '</a>'));
}

//usr_id wurde uebergeben, dann Kontaktdaten des Users aus der DB fischen
$user = new User($gDb, $gProfileFields, $getUserId);

// if an User ID is given, we need to check if the actual user is alowed to contact this user  
if (($gCurrentUser->editUsers() == false && isMember($user->getValue('usr_id')) == false)
   || strlen($user->getValue('usr_id')) == 0 )
{
    $gMessage->show($gL10n->get('SYS_USER_ID_NOT_FOUND'));
}

// Subject und Body erzeugen
$text = new TableText($gDb);

$text->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$getConfig, 'txt_org_id' => ORG_ID));

$mailSrcText = $text->getValue('txt_text');

// now replace all parameters in email text
$mailSrcText = preg_replace ('/#user_first_name#/', $user->getValue('FIRST_NAME'),  $mailSrcText);
$mailSrcText = preg_replace ('/#user_last_name#/',  $user->getValue('LAST_NAME'), $mailSrcText);
$mailSrcText = preg_replace ('/#organization_long_name#/', $gCurrentOrganization->getValue('org_longname'), $mailSrcText);
$mailSrcText = preg_replace ('/#config#/', $getConfigText,  $mailSrcText);
     
// Betreff und Inhalt anhand von Kennzeichnungen splitten oder ggf. Default-Inhalte nehmen
if (strpos($mailSrcText, '#subject#') !== false)
{
	$getSubject = trim(substr($mailSrcText, strpos($mailSrcText, '#subject#') + 9, strpos($mailSrcText, '#content#') - 9));
}
else
{
	$getSubject = 'Nachricht von '. $gCurrentOrganization->getValue('org_longname');
}
        
if (strpos($mailSrcText, '#content#') !== false)
{
	$getBody = trim(substr($mailSrcText, strpos($mailSrcText, '#content#') + 9));
}
else
{
	$getBody = $mailSrcText;
}  

$getBody = preg_replace ('/\r\n/', '<BR>', $getBody);

if (strlen($getSubject) > 0)
{
    $headline = $gL10n->get('MAI_SUBJECT').': '.$getSubject;
}
else
{
    $headline = $gL10n->get('MAI_SEND_EMAIL');
}

// create html page object
$page = new HtmlPage($headline);

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);
$page->setUrlPreviousPage($gNavigation->getPreviousUrl());

 //Datensatz für E-Mail-Adresse zusammensetzen
if (strlen($user->getValue('EMAIL')) > 0)
{
	$userEmail = $user->getValue('EMAIL');				
}  

// besitzt der User eine gueltige E-Mail-Adresse
if (!StringUtils::strValidCharacters($user->getValue('EMAIL'), 'email'))
{
	$gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME')));
}

$userEmail = $user->getValue('EMAIL');

// Wenn die letzte URL in der Zuruecknavigation die des Scriptes message_send.php ist,
// dann soll das Formular gefuellt werden mit den Werten aus der Session
if (strpos($gNavigation->getUrl(),'message_send.php') > 0 && isset($_SESSION['message_request']))
{
    // Das Formular wurde also schon einmal ausgefuellt,
    // da der User hier wieder gelandet ist nach der Mailversand-Seite
    $form_values = strStripSlashesDeep($_SESSION['message_request']);
    unset($_SESSION['message_request']);
    $gNavigation->deleteLastUrl();
}
else
{
    $form_values['name']                  = '';
    $form_values['mailfrom']              = '';
    $form_values['subject']               = $getSubject;
    $form_values['msg_body']              = $getBody;
    $form_values['msg_to']                = 0;
    $form_values['carbon_copy']           = 1;
    $form_values['delivery_confirmation'] = 0;
}

$formParams = array('usr_id' => $getUserId);

// if subject was set as param then send this subject to next script
if (strlen($getSubject) > 0)
{
    $formParams['subject'] = $getSubject;
}
    
// show form
$form = new HtmlForm('mail_send_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_send.php', $formParams), $page);
$form->openGroupBox('gb_mail_contact_details', $gL10n->get('SYS_CONTACT_DETAILS'));
    
if ($getUserId > 0)
{
    // usr_id wurde uebergeben, dann E-Mail direkt an den User schreiben
    $preload_data = '{ id: "' .$getUserId. '", text: "' .$userEmail. '", locked: true}';
}
 
$form->addInput('msg_to', $gL10n->get('SYS_TO'), $userEmail, array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED)); 
$form->addLine();
$form->addInput('name', $gL10n->get('MAI_YOUR_NAME'), $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME'), array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED));
$form->addInput('mailfrom', $gL10n->get('MAI_YOUR_EMAIL'), $gCurrentUser->getValue('EMAIL'), array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED));
$form->addCheckbox('carbon_copy', $gL10n->get('MAI_SEND_COPY'), $form_values['carbon_copy']);
 
if (($gCurrentUser->getValue('usr_id') > 0 && $gSettingsManager->getInt('mail_delivery_confirmation') == 2) || $gSettingsManager->getInt('mail_delivery_confirmation') == 1)
{
    $form->addCheckbox('delivery_confirmation', $gL10n->get('MAI_DELIVERY_CONFIRMATION'), $form_values['delivery_confirmation']);
}

$form->closeGroupBox();

$form->openGroupBox('gb_mail_message', $gL10n->get('SYS_MESSAGE'));
$form->addInput('subject', $gL10n->get('MAI_SUBJECT'), $form_values['subject'], array('maxLength' => 77, 'property' => HtmlForm::FIELD_REQUIRED));

if (($gSettingsManager->getInt('max_email_attachment_size') > 0) && PhpIniUtils::isFileUploadEnabled())
{
    $form->addFileUpload(
        'btn_add_attachment', $gL10n->get('MAI_ATTACHEMENT'),
        array(
            'enableMultiUploads' => true,
            'maxUploadSize'      => Email::getMaxAttachmentSize(),
            'multiUploadLabel'   => $gL10n->get('MAI_ADD_ATTACHEMENT'),
            'hideUploadField'    => true,
            'helpTextIdLabel'    => $gL10n->get('MAI_MAX_ATTACHMENT_SIZE', array(Email::getMaxAttachmentSize(Email::SIZE_UNIT_MEBIBYTE))),
            
            'icon'               => 'fa-paperclip'
        )
    );
}

$templates = admFuncGetDirectoryEntries(ADMIDIO_PATH . FOLDER_DATA . '/mail_templates');
$selectBoxEntries = array();
if (is_array($templates))
{
    foreach($templates as $templateName)
    {
        $selectBoxEntries[$templateName] = str_replace('.html', '', $templateName);
    }
    unset($templateName);
    $form->addSelectBox('msg_template', $gL10n->get('PLG_GEBURTSTAGSLISTE_TEMPLATE'), $selectBoxEntries,
        array('defaultValue' => 'template.html', 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_TEMPLATE_DESC')
    );
}

// add textfield or ckeditor to form
if ($gValidLogin == true && $gSettingsManager->getInt('mail_html_registered_users') == 1)
{
    $form->addEditor('msg_body', null, $form_values['msg_body']);
}
else
{
    $form->addMultilineTextInput('msg_body', $gL10n->get('SYS_TEXT'), null, 10);
}

$form->closeGroupBox();
$form->addSubmitButton('btn_send', $gL10n->get('SYS_SEND'), array('icon' => 'fa-envelope'));

// add form to html page and show page
$page->addHtml($form->show(false));

// show page
$page->show();
