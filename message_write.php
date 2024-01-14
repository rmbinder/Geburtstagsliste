<?php
/**
 ***********************************************************************************************
 * E-Mails versenden aus dem Plugin Geburtstagsliste
 *
 * @copyright 2004-2024 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * usr_uuid     : E-Mail an den entsprechenden Benutzer schreiben
 * configtext   : Text in der letzten Spalte (Konfigurationsspalte)
 * config       : die gewaehlte Konfiguration
 *
 *****************************************************************************/

//require_once(__DIR__ . '/../../system/common.php');
require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/classes/TableText.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

if (!StringUtils::strContains($gNavigation->getUrl(), 'birthday_list.php') && !StringUtils::strContains($gNavigation->getUrl(), 'message_send.php'))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getUserUuid   = admFuncVariableIsValid($_GET, 'user_uuid',   'string');
$getConfigText = admFuncVariableIsValid($_GET, 'configtext', 'string');
$getConfig     = admFuncVariableIsValid($_GET, 'config', 'numeric', array('defaultValue' => 0));

// check if the call of the page was allowed by settings
if (!$gSettingsManager->getBool('enable_mail_module'))
{
    // message if the sending is not allowed
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}

// check if the current user has email address for sending an email
if (!$gCurrentUser->hasEmail())
{
    $gMessage->show($gL10n->get('SYS_CURRENT_USER_NO_EMAIL', array('<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php">', '</a>')));
    // => EXIT
}

$mailSubject = '';
$mailBody    = '';

// Konfiguration einlesen
$pPreferences = new ConfigTablePGL();
$pPreferences->read();

$user = new User($gDb, $gProfileFields);
$user->readDataByUuid($getUserUuid);

// we need to check if the actual user is allowed to contact this user
if (!$gCurrentUser->editUsers() && !isMember((int) $user->getValue('usr_id')))
{
    $gMessage->show($gL10n->get('SYS_USER_ID_NOT_FOUND'));
    // => EXIT
}

// check if the user has email address for receiving an email
if (!$user->hasEmail())
{
    $gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', array($user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'))));
    // => EXIT
}

// Subject und Body erzeugen
$text = new TableText($gDb);

$text->readDataByColumns(array('txt_name' => 'PGLMAIL_NOTIFICATION'.$getConfig, 'txt_org_id' => $gCurrentOrgId));

$mailSrcText = $text->getValue('txt_text');

// now replace all parameters in email text
$mailSrcText = preg_replace ('/#user_first_name#/', $user->getValue('FIRST_NAME'),  $mailSrcText);
$mailSrcText = preg_replace ('/#user_last_name#/',  $user->getValue('LAST_NAME'), $mailSrcText);
$mailSrcText = preg_replace ('/#organization_long_name#/', $gCurrentOrganization->getValue('org_longname'), $mailSrcText);
$mailSrcText = preg_replace ('/#config#/', $getConfigText,  $mailSrcText);
     
// Betreff und Inhalt anhand von Kennzeichnungen splitten oder ggf. Default-Inhalte nehmen
if (strpos($mailSrcText, '#subject#') !== false)
{
    $mailSubject = trim(substr($mailSrcText, strpos($mailSrcText, '#subject#') + 9, strpos($mailSrcText, '#content#') - 9));
}
else
{
    $mailSubject = 'Nachricht von '. $gCurrentOrganization->getValue('org_longname');
}
        
if (strpos($mailSrcText, '#content#') !== false)
{
    $mailBody = trim(substr($mailSrcText, strpos($mailSrcText, '#content#') + 9));
}
else
{
    $mailBody = $mailSrcText;
}  

$mailBody = preg_replace ('/\r\n/', '<BR>', $mailBody);

if ($mailSubject !== '')
{ 
    $headline = $mailSubject;
}
else
{
    $headline = $gL10n->get('SYS_SEND_EMAIL');
}

// If the last URL in the back navigation is the one of the script message_send.php,
// then the form should be filled with the values from the session
if (str_contains($gNavigation->getUrl(), 'message_send.php') && isset($_SESSION['message_request']))
{
    $formValues = $_SESSION['message_request'];
    unset($_SESSION['message_request']);

    if(!isset($formValues['carbon_copy']))
    {
        $formValues['carbon_copy'] = false;
    }
    if(!isset($formValues['delivery_confirmation']))
    {
        $formValues['delivery_confirmation'] = false;
    }
    if(!isset($formValues['mailfrom']))
    {
        $formValues['mailfrom'] = $user->getValue('EMAIL');
    }
}
else
{
    $formValues['msg_subject']  = $mailSubject;
    $formValues['msg_body']     = $mailBody;
    $formValues['namefrom']     = '';
    $formValues['mailfrom']     = $gCurrentUser->getValue('EMAIL');
    $formValues['carbon_copy']  = false;
    $formValues['delivery_confirmation'] = false;
    $formValues['msg_template'] = 'template.html';
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage('plg-birthday_list-message-write', $headline);

// show form
$form = new HtmlForm('mail_send_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_send.php', array('user_uuid' => $getUserUuid)), $page, array('enableFileUpload' => true));    
 
$form->openGroupBox('gb_mail_contact_details', $gL10n->get('SYS_CONTACT_DETAILS'));
$form->addInput('msg_to', $gL10n->get('SYS_TO'), $user->getValue('FIRST_NAME'). ' '. $user->getValue('LAST_NAME').' <'.$user->getValue('EMAIL').'>', array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED)); 
$form->addLine();

$form->addInput(
      'namefrom', $gL10n->get('SYS_YOUR_NAME'), $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME'),
      array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED)
    );
  
$sql = 'SELECT COUNT(*) AS count
          FROM '.TBL_USER_FIELDS.'
    INNER JOIN '. TBL_USER_DATA .'
            ON usd_usf_id = usf_id
         WHERE usf_type = \'EMAIL\'
           AND usd_usr_id = ? -- $gCurrentUserId
           AND usd_value IS NOT NULL';

$pdoStatement = $gDb->queryPrepared($sql, array($gCurrentUserId));
$possibleEmails = $pdoStatement->fetchColumn();

if($possibleEmails > 1)
{
    $sqlData = array();
    $sqlData['query'] = 'SELECT email.usd_value AS ID, email.usd_value AS email
                           FROM '.TBL_USERS.'
                     INNER JOIN '.TBL_USER_DATA.' AS email
                             ON email.usd_usr_id = usr_id
                            AND LENGTH(email.usd_value) > 0
                     INNER JOIN '.TBL_USER_FIELDS.' AS field
                             ON field.usf_id = email.usd_usf_id
                            AND field.usf_type = \'EMAIL\'
                          WHERE usr_id = ? -- $gCurrentUserId
                            AND usr_valid = 1
                       GROUP BY email.usd_value, email.usd_value';
    $sqlData['params'] = array($gCurrentUserId);

    $form->addSelectBoxFromSql(
        'mailfrom', $gL10n->get('SYS_YOUR_EMAIL'), $gDb, $sqlData,
        array('maxLength' => 50, 'defaultValue' => $formValues['mailfrom'], 'showContextDependentFirstEntry' => false)
    );
}
else
{
    $form->addInput(
        'mailfrom', $gL10n->get('SYS_YOUR_EMAIL'), $formValues['mailfrom'],
        array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED)
    );
}

$form->addCheckbox('carbon_copy', $gL10n->get('SYS_SEND_COPY'), $formValues['carbon_copy']);

// if preference is set then show a checkbox where the user can request a delivery confirmation for the email
if (( (int) $gSettingsManager->get('mail_delivery_confirmation') === 2) || (int) $gSettingsManager->get('mail_delivery_confirmation') === 1)
{
    $form->addCheckbox('delivery_confirmation', $gL10n->get('SYS_DELIVERY_CONFIRMATION'), $formValues['delivery_confirmation']);
}

$form->closeGroupBox();

$form->openGroupBox('gb_mail_message', $gL10n->get('SYS_MESSAGE'));
$form->addInput(
    'msg_subject', $gL10n->get('SYS_SUBJECT'), $formValues['msg_subject'],
    array('maxLength' => 77, 'property' => HtmlForm::FIELD_REQUIRED)
);

if (($gSettingsManager->getInt('max_email_attachment_size') > 0) && PhpIniUtils::isFileUploadEnabled())
{
    $form->addFileUpload(
        'btn_add_attachment', $gL10n->get('SYS_ATTACHMENT'),
        array(
            'enableMultiUploads' => true,
            'maxUploadSize'      => Email::getMaxAttachmentSize(),
            'multiUploadLabel'   => $gL10n->get('SYS_ADD_ATTACHMENT'),
            'hideUploadField'    => true,
            'helpTextIdLabel'    => $gL10n->get('SYS_MAX_ATTACHMENT_SIZE', array(Email::getMaxAttachmentSize(Email::SIZE_UNIT_MEBIBYTE))),
            'icon'               => 'fa-paperclip'
        )
    );
}

$templates = array_keys(FileSystemUtils::getDirectoryContent(ADMIDIO_PATH . FOLDER_DATA . '/mail_templates', false, false, array(FileSystemUtils::CONTENT_TYPE_FILE)));
$selectBoxEntries = array();
if (is_array($templates))
{
    foreach($templates as $templateName)
    {
        $selectBoxEntries[$templateName] = str_replace('.html', '', $templateName);
    }
    unset($templateName);
    $form->addSelectBox('msg_template', $gL10n->get('PLG_GEBURTSTAGSLISTE_TEMPLATE'), $selectBoxEntries,
        array('defaultValue' => $formValues['msg_template'], 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_GEBURTSTAGSLISTE_TEMPLATE_DESC')
    );
}

$form->addEditor('msg_body', '', $formValues['msg_body'], array('property' => HtmlForm::FIELD_REQUIRED));

$form->closeGroupBox();

$form->addSubmitButton('btn_send', $gL10n->get('SYS_SEND'), array('icon' => 'fa-envelope'));

// add form to html page and show page
$page->addHtml($form->show());

// show page
$page->show();
