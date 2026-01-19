<?php
/**
 ***********************************************************************************************
 * E-Mails versenden aus dem Plugin Geburtstagsliste
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * ****************************************************************************
 * Parameters:
 *
 * usr_uuid : E-Mail an den entsprechenden Benutzer schreiben
 * configtext : Text in der letzten Spalte (Konfigurationsspalte)
 * config : die gewaehlte Konfiguration
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Email;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Entity\Text;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\PhpIniUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;
use Plugins\BirthdayList\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    if (! StringUtils::strContains($gNavigation->getUrl(), 'birthday_list.php') && ! StringUtils::strContains($gNavigation->getUrl(), 'message_send.php')) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // Initialize and check the parameters
    $getUserUuid = admFuncVariableIsValid($_GET, 'user_uuid', 'string');
    $getConfigText = admFuncVariableIsValid($_GET, 'configtext', 'string');
    $getConfig = admFuncVariableIsValid($_GET, 'config', 'numeric', array(
        'defaultValue' => 0
    ));

    // check if the call of the page was allowed by settings
    if (! $gSettingsManager->getBool('mail_module_enabled')) {
        // message if the sending is not allowed
        throw new Exception('SYS_MODULE_DISABLED');
    }

    // check if the current user has email address for sending an email
    if (! $gCurrentUser->hasEmail()) {
        $gMessage->show($gL10n->get('SYS_CURRENT_USER_NO_EMAIL', array(
            '<a href="' . ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php">',
            '</a>'
        )));
        // => EXIT
    }

    $mailSubject = '';
    $mailBody = '';

    // Konfiguration einlesen
    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $user = new User($gDb, $gProfileFields);
    $user->readDataByUuid($getUserUuid);

    // we need to check if the actual user is allowed to contact this user
    if (! $gCurrentUser->isAdministratorUsers() && ! isMember((int) $user->getValue('usr_id'))) {
        throw new Exception('SYS_USER_ID_NOT_FOUND');
        // => EXIT
    }

    // check if the user has email address for receiving an email
    if (! $user->hasEmail()) {
        $gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', array(
            $user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME')
        )));
        // => EXIT
    }

    // Subject und Body erzeugen
    $text = new Text($gDb);

    $text->readDataByColumns(array(
        'txt_name' => 'PGLMAIL_NOTIFICATION' . $getConfig,
        'txt_org_id' => $gCurrentOrgId
    ));

    $mailSrcText = $text->getValue('txt_text');

    // now replace all parameters in email text
    $mailSrcText = preg_replace('/#user_first_name#/', $user->getValue('FIRST_NAME'), $mailSrcText);
    $mailSrcText = preg_replace('/#user_last_name#/', $user->getValue('LAST_NAME'), $mailSrcText);
    $mailSrcText = preg_replace('/#organization_long_name#/', $gCurrentOrganization->getValue('org_longname'), $mailSrcText);
    $mailSrcText = preg_replace('/#config#/', $getConfigText, $mailSrcText);

    // Betreff und Inhalt anhand von Kennzeichnungen splitten oder ggf. Default-Inhalte nehmen
    if (strpos($mailSrcText, '#subject#') !== false) {
        $mailSubject = trim(substr($mailSrcText, strpos($mailSrcText, '#subject#') + 9, strpos($mailSrcText, '#content#') - 9));
    } else {
        $mailSubject = 'Nachricht von ' . $gCurrentOrganization->getValue('org_longname');
    }

    if (strpos($mailSrcText, '#content#') !== false) {
        $mailBody = trim(substr($mailSrcText, strpos($mailSrcText, '#content#') + 9));
    } else {
        $mailBody = $mailSrcText;
    }

    $mailBody = preg_replace('/\r\n/', '<BR>', $mailBody);

    if ($mailSubject !== '') {
        $headline = $mailSubject;
    } else {
        $headline = $gL10n->get('SYS_SEND_EMAIL');
    }

    // If the last URL in the back navigation is the one of the script message_send.php,
    // then the form should be filled with the values from the session
    if (str_contains($gNavigation->getUrl(), 'message_send.php') && isset($_SESSION['message_request'])) {
        $formValues = $_SESSION['message_request'];
        unset($_SESSION['message_request']);

        if (! isset($formValues['carbon_copy'])) {
            $formValues['carbon_copy'] = false;
        }
        if (! isset($formValues['delivery_confirmation'])) {
            $formValues['delivery_confirmation'] = false;
        }
        if (! isset($formValues['mailfrom'])) {
            $formValues['mailfrom'] = $user->getValue('EMAIL');
        }
    } else {
        $formValues['msg_subject'] = $mailSubject;
        $formValues['msg_body'] = $mailBody;
        $formValues['namefrom'] = '';
        $formValues['mailfrom'] = $gCurrentUser->getValue('EMAIL');
        $formValues['carbon_copy'] = false;
        $formValues['delivery_confirmation'] = false;
        $formValues['msg_template'] = 'template.html';
    }

    // add current url to navigation stack
    $gNavigation->addUrl(CURRENT_URL, $headline);

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-birthday_list-message-write', $headline);

    // show form
    $form = new FormPresenter('mail_send_form', '../templates/message.write.plugin.birthdaylist.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/message_send.php', array(
        'user_uuid' => $getUserUuid
    )), $page, array(
        'enableFileUpload' => true
    ));

    $form->addInput('msg_to', $gL10n->get('SYS_TO'), $user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME') . ' <' . $user->getValue('EMAIL') . '>', array(
        'maxLength' => 50,
        'property' => FormPresenter::FIELD_DISABLED
    ));

    $form->addInput('namefrom', $gL10n->get('SYS_YOUR_NAME'), $gCurrentUser->getValue('FIRST_NAME') . ' ' . $gCurrentUser->getValue('LAST_NAME'), array(
        'maxLength' => 50,
        'property' => FormPresenter::FIELD_DISABLED
    ));

    $sql = 'SELECT COUNT(*) AS count
          FROM ' . TBL_USER_FIELDS . '
    INNER JOIN ' . TBL_USER_DATA . '
            ON usd_usf_id = usf_id
         WHERE usf_type = \'EMAIL\'
           AND usd_usr_id = ? -- $gCurrentUserId
           AND usd_value IS NOT NULL';

    $pdoStatement = $gDb->queryPrepared($sql, array(
        $gCurrentUserId
    ));
    $possibleEmails = $pdoStatement->fetchColumn();

    if ($possibleEmails > 1) {
        $sqlData = array();
        $sqlData['query'] = 'SELECT email.usd_value AS ID, email.usd_value AS email
                           FROM ' . TBL_USERS . '
                     INNER JOIN ' . TBL_USER_DATA . ' AS email
                             ON email.usd_usr_id = usr_id
                            AND LENGTH(email.usd_value) > 0
                     INNER JOIN ' . TBL_USER_FIELDS . ' AS field
                             ON field.usf_id = email.usd_usf_id
                            AND field.usf_type = \'EMAIL\'
                          WHERE usr_id = ? -- $gCurrentUserId
                            AND usr_valid = true
                       GROUP BY email.usd_value, email.usd_value';
        $sqlData['params'] = array(
            $gCurrentUserId
        );

        $form->addSelectBoxFromSql('mailfrom', $gL10n->get('SYS_YOUR_EMAIL'), $gDb, $sqlData, array(
            'maxLength' => 50,
            'defaultValue' => $formValues['mailfrom'],
            'showContextDependentFirstEntry' => false
        ));
    } else {
        $form->addInput('mailfrom', $gL10n->get('SYS_YOUR_EMAIL'), $formValues['mailfrom'], array(
            'maxLength' => 50,
            'property' => FormPresenter::FIELD_DISABLED
        ));
    }

    $form->addCheckbox('carbon_copy', $gL10n->get('SYS_SEND_COPY'), $formValues['carbon_copy']);

    // if preference is set then show a checkbox where the user can request a delivery confirmation for the email
    if (((int) $gSettingsManager->get('mail_delivery_confirmation') === 2) || (int) $gSettingsManager->get('mail_delivery_confirmation') === 1) {
        $form->addCheckbox('delivery_confirmation', $gL10n->get('SYS_DELIVERY_CONFIRMATION'), $formValues['delivery_confirmation']);
    }

    $form->addInput('msg_subject', $gL10n->get('SYS_SUBJECT'), $formValues['msg_subject'], array(
        'maxLength' => 77,
        'property' => FormPresenter::FIELD_REQUIRED
    ));

    if (($gSettingsManager->getInt('max_email_attachment_size') > 0) && PhpIniUtils::isFileUploadEnabled()) {
        $form->addFileUpload('btn_add_attachment', $gL10n->get('SYS_ATTACHMENT'), array(
            'enableMultiUploads' => true,
            'maxUploadSize' => Email::getMaxAttachmentSize(),
            'multiUploadLabel' => $gL10n->get('SYS_ADD_ATTACHMENT'),
            'hideUploadField' => true
        ));
    }

    $templates = array_keys(FileSystemUtils::getDirectoryContent(ADMIDIO_PATH . FOLDER_DATA . '/mail_templates', false, false, array(
        FileSystemUtils::CONTENT_TYPE_FILE
    )));
    $selectBoxEntries = array();
    if (is_array($templates)) {
        foreach ($templates as $templateName) {
            $selectBoxEntries[$templateName] = str_replace('.html', '', $templateName);
        }
        unset($templateName);
        $form->addSelectBox('msg_template', $gL10n->get('PLG_BIRTHDAYLIST_TEMPLATE'), $selectBoxEntries, array(
            'defaultValue' => $formValues['msg_template'],
            'showContextDependentFirstEntry' => true
        ));
    }

    $form->addEditor('msg_body', '', $formValues['msg_body'], array(
        'property' => FormPresenter::FIELD_REQUIRED
    ));
    $form->addSubmitButton('btn_send', $gL10n->get('SYS_SEND'), array(
        'icon' => 'bi-envelope-fill'
    ));

    // add form to html page and show page
    $page->assignSmartyVariable('possibleEmails', $possibleEmails);
    $page->assignSmartyVariable('helpTextAttachment', $gL10n->get('SYS_MAX_ATTACHMENT_SIZE', array(
        Email::getMaxAttachmentSize(Email::SIZE_UNIT_MEBIBYTE)
    )));

    $form->addToHtmlPage();

    // show page
    $page->show();
} catch (Throwable $e) {
    handleException($e);
}
