<p>{$l10n->get('PLG_BIRTHDAYLIST_CONFIGURATIONS_HEADER')}                          
    <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-class="modal-lg" data-href="{$urlPopupText}">
        <i class="bi bi-info-circle-fill admidio-info-icon"></i>
    </a>
</p>
<hr />
 <div style="width:100%; height:1000px; overflow:auto; border:20px;">
<form {foreach $attributes as $attribute}
        {$attribute@key}="{$attribute}"
    {/foreach}>
    <div class="admidio-form-required-notice"><span>{$l10n->get('SYS_REQUIRED_INPUT')}</span></div>
    {include 'sys-template-parts/form.input.tpl' data=$elements['adm_csrf_token']}

    {foreach $configurations as $configuration}
        <div class="card admidio-field-group">
            <div class="card-header">{$configuration.key+1}. {$l10n->get('SYS_CONFIGURATION')}</div>
            <div class="card-body">
                {include '../templates/form.input.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.col_desc] popover="{$l10n->get('PLG_BIRTHDAYLIST_COL_DESC_DESC')}"}
       
                <div class="admidio-form-group admidio-form-custom-content row mb-3">
                    <label class="col-sm-3 col-form-label">
                        {$l10n->get('PLG_BIRTHDAYLIST_COLUMN_SELECTION')}  
                        <i class="bi bi-info-circle-fill admidio-info-icon" data-bs-toggle="popover" 
                            data-bs-html="true" data-bs-trigger="hover click" data-bs-placement="auto"
                            data-bs-content="{$l10n->get('PLG_BIRTHDAYLIST_COLUMN_SELECTION_DESC')}">
                        </i>                 
                    </label>
                    <div class="col-sm-9">
                        <div class="table-responsive">
                            <table class="table table-condensed" id="mylist_fields_table">
                                <thead>
                                <tr>
                                    <th style="width: 30%;">{$l10n->get('SYS_ABR_NO')}</th>
                                    <th style="width: 60%;">{$l10n->get('SYS_CONTENT')}</th>
                                </tr>
                                </thead>
                                <tbody id="mylist_fields_tbody{$configuration.key}">
                                <tr id="table_row_button">
                                    <td colspan="2">
                                        <a class="icon-text-link" href="javascript:addColumn{$configuration.key}()"><i class="bi bi-plus-circle-fill"></i> {$l10n->get('SYS_ADD_COLUMN')}</a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
                {include '../templates/form.select.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.col_sel] popover="{$l10n->get('PLG_BIRTHDAYLIST_COL_SEL_DESC')}"}
                {include '../templates/form.input.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.col_values] popover="{$l10n->get('PLG_BIRTHDAYLIST_COL_VALUES_DESC')}"}
                {include '../templates/form.input.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.col_suffix] popover="{$l10n->get('PLG_BIRTHDAYLIST_COL_SUFFIX_DESC')}"}
                {include '../templates/form.checkbox.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.suppress_age] popover="{$l10n->get('PLG_BIRTHDAYLIST_AGE_OR_ANNIVERSARY_NOT_SHOW_DESC')}"}           
                {include '../templates/form.select.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.selection_role] popover="{$l10n->get('SYS_ROLE_SELECTION_CONF_DESC')}"}
                {include '../templates/form.select.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.selection_cat] popover="{$l10n->get('SYS_CAT_SELECTION_CONF_DESC')}"}
                {include '../templates/form.multiline.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.col_mail] popover="{$l10n->get('PLG_BIRTHDAYLIST_NOTIFICATION_MAIL_TEXT_DESC')}"}
                {include '../templates/form.checkbox.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.calendar_year] popover="{$l10n->get('PLG_BIRTHDAYLIST_SHOW_CALENDAR_YEAR_DESC')}"}      
                {include '../templates/form.input.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.years_offset] popover="{$l10n->get('PLG_BIRTHDAYLIST_YEARS_OFFSET_DESC')}"}          

                {if {$relations_enabled}}
                     {include '../templates/form.select.popover.plugin.birthdaylist.tpl' data=$elements[$configuration.relationtype_id] popover="{$l10n->get('PLG_BIRTHDAYLIST_RELATION_DESC')}"}
                {/if}               
    
                {if isset($configuration.urlConfigCopy)}
                    <a id="copy_config" class="icon-text-lin offset-sm-3" href="{$configuration.urlConfigCopy}">
                        <i class="bi bi-copy"></i> {$l10n->get('SYS_COPY_CONFIGURATION')}</a>
                {/if}
                {if isset($configuration.urlConfigDelete)}
                    &nbsp;&nbsp;&nbsp;&nbsp;<a id="delete_config" class="icon-text-link offset-sm-3" href="{$configuration.urlConfigDelete}">
                    <i class="bi bi-trash"></i> {$l10n->get('SYS_DELETE_CONFIGURATION')}</a>
                {/if}
            </div>
        </div>
    {/foreach}
 </div>
    <hr />
    <a id="add_config" class="icon-text-link" href="{$urlConfigNew}">
        <i class="bi bi-plus-circle-fill"></i> {$l10n->get('SYS_ADD_ANOTHER_CONFIG')}
    </a>
    <div class="alert alert-warning alert-small" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>{$l10n->get('ORG_NOT_SAVED_SETTINGS_LOST')}
    </div>

    <div class="form-alert" style="display: none;">&nbsp;</div>
    {include 'sys-template-parts/form.button.tpl' data=$elements['adm_button_save_configurations']}
</form>
{$javascript}