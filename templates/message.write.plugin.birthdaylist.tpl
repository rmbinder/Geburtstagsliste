<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>



    <div class="card admidio-field-group">
        <div class="card-header">{$l10n->get('SYS_CONTACT_DETAILS')}</div>
        <div class="card-body">


            {include 'sys-template-parts/form.input.tpl' data=$elements['msg_to']}
            <hr />
            {include 'sys-template-parts/form.input.tpl' data=$elements['namefrom']}
            {if $possibleEmails > 1}
                {include 'sys-template-parts/form.select.tpl' data=$elements['mailfrom']}
            {else}
                {include 'sys-template-parts/form.input.tpl' data=$elements['mailfrom']}
            {/if}
            {if {array_key_exists array=$elements key='carbon_copy'}}
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements['carbon_copy']}
            {/if}
            {if {array_key_exists array=$elements key='delivery_confirmation'}}
                {include 'sys-template-parts/form.checkbox.tpl' data=$elements['delivery_confirmation']}
            {/if}
        </div>
    </div>
    <div class="card admidio-field-group">
        <div class="card-header">{$l10n->get('SYS_MESSAGE')}</div>
        <div class="card-body">
            {include 'sys-template-parts/form.input.tpl' data=$elements['msg_subject']}
 
             {if {array_key_exists array=$elements key='btn_add_attachment'}}
              
                  {include '../templates/form.file.popover.plugin.birthdaylist.tpl' data=$elements['btn_add_attachment'] popover="{$helpTextAttachment}"}
            {/if}
                       
          
                             {include '../templates/form.select.popover.plugin.birthdaylist.tpl' data=$elements['msg_template'] popover="{$l10n->get('PLG_BIRTHDAYLIST_TEMPLATE_DESC')}"}
                             
                             
            {if $validLogin && $settings->getBool('mail_html_registered_users')}
                {include 'sys-template-parts/form.editor.tpl' data=$elements['msg_body']}
            {else}
                {include 'sys-template-parts/form.multiline.tpl' data=$elements['msg_body']}
            {/if}

        </div>
    </div>

    <div class="form-alert" style="display: none;">&nbsp;</div>
    {include 'sys-template-parts/form.button.tpl' data=$elements['btn_send']}
</form>
