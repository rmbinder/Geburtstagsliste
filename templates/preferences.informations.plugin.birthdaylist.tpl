<div class="admidio-form-group admidio-form-custom-content row mb-3">
    <label for="plg_name" class="col-sm-3 col-form-label">
        {$l10n->get('PLG_BIRTHDAYLIST_PLUGIN_NAME')}
    </label>
    <div class="col-sm-9">
        <div id="plg_name">{$plg_name}</div>
    </div>
</div>
<div class="admidio-form-group admidio-form-custom-content row mb-3">
    <label for="plg_version" class="col-sm-3 col-form-label">
        {$l10n->get('PLG_BIRTHDAYLIST_PLUGIN_VERSION')}
    </label>
    <div class="col-sm-9">
        <div id="plg_version">{$plg_version}</div>
    </div>
</div>
<div class="admidio-form-group admidio-form-custom-content row mb-3">
    <label for="plg_date" class="col-sm-3 col-form-label">
        {$l10n->get('PLG_BIRTHDAYLIST_PLUGIN_DATE')}
    </label>
    <div class="col-sm-9">
        <div id="plg_date">{$plg_date}</div>
    </div>
</div>
<div class="admidio-form-group admidio-form-custom-content row mb-3">
    <label class="col-sm-3 col-form-label">
        {$l10n->get('PLG_BIRTHDAYLIST_DOCUMENTATION')}
    </label>
    <div class="col-sm-9">
        <div>
            <a class="btn btn-secondary" id="open_documentation" href="{$open_doc}">
                <i class="bi bi-file-pdf"></i>{$l10n->get('PLG_BIRTHDAYLIST_DOCUMENTATION_OPEN')}</a>
            <div class="form-text">{$l10n->get('PLG_BIRTHDAYLIST_DOCUMENTATION_OPEN_DESC')}</div>
        </div>
    </div>
</div>