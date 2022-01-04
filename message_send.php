<?php
/**
 ***********************************************************************************************
 * Check message information and save it
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * usr_uuid  : Send email to this user
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');

// Initialize and check the parameters
$getUserUuid   = admFuncVariableIsValid($_GET, 'user_uuid', 'string', array('defaultValue' => $gCurrentUser->getValue('usr_uuid')));

// Check form values
$postFrom                  = admFuncVariableIsValid($_POST, 'mailfrom', 'string', array('defaultValue' => $gCurrentUser->getValue('EMAIL')));
$postName                  = admFuncVariableIsValid($_POST, 'namefrom', 'string', array('defaultValue' => $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME')));
$postSubject               = StringUtils::strStripTags($_POST['msg_subject']); 
$postBody                  = admFuncVariableIsValid($_POST, 'msg_body', 'html');
$postDeliveryConfirmation  = admFuncVariableIsValid($_POST, 'delivery_confirmation', 'bool');
$postCarbonCopy            = admFuncVariableIsValid($_POST, 'carbon_copy', 'boolean', array('defaultValue' => 0));
$postTemplate              = admFuncVariableIsValid($_POST, 'msg_template', 'string', array('defaultValue' => ''));

// save form data in session for back navigation
$_SESSION['message_request'] = $_POST;

// Stop if mail should be send and mail module is disabled
if (!$gSettingsManager->getBool('enable_mail_module'))
{
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

// if Attachmentsize is higher than max_post_size from php.ini, then $_POST is empty.
if (empty($_POST))
{
    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    // => EXIT
}

$sendResult = false;

// if no User is set, he is not able to ask for delivery confirmation 
if (!($gCurrentUserId > 0 && $gSettingsManager->getInt('mail_delivery_confirmation') == 2) && $gSettingsManager->getInt('mail_delivery_confirmation') != 1)
{
    $postDeliveryConfirmation = false;
}
      
// object to handle the current message in the database
$message = new TableMessage($gDb);
$message->setValue('msg_type', TableMessage::MESSAGE_TYPE_EMAIL);
$message->setValue('msg_subject', $postSubject);
$message->setValue('msg_usr_id_sender', $gCurrentUserId);
$message->addContent($postBody);        

$receiver = array();

// Create new Email Object
$email = new Email();

$user = new User($gDb, $gProfileFields);
$user->readDataByUuid($getUserUuid);
                
// error if no valid Email for given user ID
if (!StringUtils::strValidCharacters($user->getValue('EMAIL'), 'email'))
{
	$gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', array($user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'))));
}
                
// save page in navigation - to have a check for a navigation back.
$gNavigation->addUrl(CURRENT_URL);

// check if name is given
if (strlen($postName) == 0)
{
    $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_NAME'))));
}

// set sending address
if ($email->setSender($postFrom,$postName))
{
    // set subject
    if ($email->setSubject($postSubject))
    {
        // check for attachment
        if (isset($_FILES['userfile']))
        {
            // final check if user is logged in
            if (!$gValidLogin)
            {
                $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
            }
            $attachmentSize = 0;
            // add now every attachment
            for ($currentAttachmentNo = 0; isset($_FILES['userfile']['name'][$currentAttachmentNo]); ++$currentAttachmentNo)
            {
                // check if Upload was OK
                if (($_FILES['userfile']['error'][$currentAttachmentNo] !== UPLOAD_ERR_OK) 
                &&  ($_FILES['userfile']['error'][$currentAttachmentNo] !== UPLOAD_ERR_NO_FILE))
                {
                    $gMessage->show($gL10n->get('SYS_ATTACHMENT_TO_LARGE'));
                    // => EXIT
                }
                
                // check if a file was really uploaded
                if(!file_exists($_FILES['userfile']['tmp_name'][$currentAttachmentNo]) || !is_uploaded_file($_FILES['userfile']['tmp_name'][$currentAttachmentNo]))
                {
                    $gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
                    // => EXIT
                }
                    
                if ($_FILES['userfile']['error'][$currentAttachmentNo] === UPLOAD_ERR_OK)
                {
                    // check the size of the attachment
                    $attachmentSize += $_FILES['userfile']['size'][$currentAttachmentNo];
                    if ($attachmentSize > Email::getMaxAttachmentSize())
                    {
                        $gMessage->show($gL10n->get('SYS_ATTACHMENT_TO_LARGE'));
                        // => EXIT
                    }

                    // set filetyp to standart if not given
                    if (strlen($_FILES['userfile']['type'][$currentAttachmentNo]) <= 0)
                    {
                        $_FILES['userfile']['type'][$currentAttachmentNo] = 'application/octet-stream';                        
                    }

                    // add the attachment to the mail
                    try
                    {
                        $email->AddAttachment($_FILES['userfile']['tmp_name'][$currentAttachmentNo], $_FILES['userfile']['name'][$currentAttachmentNo], $encoding = 'base64', $_FILES['userfile']['type'][$currentAttachmentNo]);
                        $message->addAttachment($_FILES['userfile']['tmp_name'][$currentAttachmentNo], $_FILES['userfile']['name'][$currentAttachmentNo]);
                    }
                    catch (phpmailerException $e)
                    {
                        $gMessage->show($e->errorMessage());
                    }             
                }
            }
        }
    }
    else
    {
        $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_SUBJECT'))));
        // => EXIT
    }
}
else
{
    $gMessage->show($gL10n->get('SYS_EMAIL_INVALID', array($gL10n->get('SYS_EMAIL'))));
    // => EXIT
}

// if possible send html mail
if ($gValidLogin && $gSettingsManager->getBool('mail_html_registered_users'))
{
    $email->setHtmlMail();
}

// set flag if copy should be send to sender
if (isset($postCarbonCopy) && $postCarbonCopy)
{
    $email->setCopyToSenderFlag();
}

$email->addRecipient($user->getValue('EMAIL'), $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'));

// add user to the message object
$message->addUser((int) $user->getValue('usr_id'), $user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME'));
                             
// add confirmation mail to the sender
if ($postDeliveryConfirmation)
{
    $email->ConfirmReadingTo = $gCurrentUser->getValue('EMAIL');
}

// in der Originaldatei messages_send.php wird setTemplateText() verwendet um das Mail-Template einzulesen und die Platzhalter zu ersetzen
// diese Methode kann nicht verwendet werden, da das Mail-Template hard-coded ist,
// das Plugin Geburstagsliste jedoch mit variablen Templates (=$postTemplate) arbeitet

// load the template and set the new email body with template
try
{
    $emailTemplate = FileSystemUtils::readFile(ADMIDIO_PATH . FOLDER_DATA . '/mail_templates/'.$postTemplate);
}
catch (\RuntimeException $exception)
{
    $emailTemplate = '#message#';
}

// replace all line feeds within the mailtext into simple breaks because only those are valid within mails
$postBody = str_replace("\r\n", "\n", $postBody);

// replace parameters in email template
$replaces = array(
    '#sender#'       => $postName,
    '#sender_name#'  => $postName,
    '#sender_email#' => $gCurrentUser->getValue('EMAIL'),
    '#message#'      => $postBody,
    '#receiver#'     => $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'),
    '#recipients#'   => $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'),
    '#organization_name#'      => $gCurrentOrganization->getValue('org_longname'),
    '#organization_shortname#' => $gCurrentOrganization->getValue('org_shortname'),
    '#organization_website#'   => $gCurrentOrganization->getValue('org_homepage')
);

$emailHtmlText = StringUtils::strMultiReplace($emailTemplate, $replaces);

// set Text
$email->setText($emailHtmlText);

// finally send the mail
$sendResult = $email->sendEmail();

// message if send/save is OK
if ($sendResult === TRUE)
{
    // save mail to database
    $message->save();
	
    // after sending remove the actual Page from the NaviObject and remove also the send-page
    $gNavigation->deleteLastUrl();
    $gNavigation->deleteLastUrl();
    
    // message if sending was OK
    if ($gNavigation->count() > 0)
    {
		$gMessage->setForwardUrl($gNavigation->getUrl(), 2000);
    }
    else
    {
        $gMessage->setForwardUrl($gHomepage, 2000);
    }

    $gMessage->show($gL10n->get('SYS_EMAIL_SEND'));
}
else
{
    $gMessage->show($sendResult . '<br />' . $gL10n->get('SYS_EMAIL_NOT_SEND', array($gL10n->get('SYS_RECIPIENT'), $sendResult)));
}
